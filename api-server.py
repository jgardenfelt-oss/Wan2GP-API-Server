from __future__ import annotations
import json
import os
import re
import secrets
import sys
import threading
import time
import uuid
from pathlib import Path
from typing import Any

PROJECT_ROOT = Path(__file__).resolve().parent
if str(PROJECT_ROOT) not in sys.path: sys.path.insert(0, str(PROJECT_ROOT))
from fastapi import FastAPI, HTTPException
from fastapi.middleware.cors import CORSMiddleware
from fastapi.responses import FileResponse, JSONResponse
from pydantic import BaseModel, Field
import uvicorn

_AUTH_USERNAME: str = ""
_AUTH_PASSWORD: str = ""
_REQUIRE_AUTH: bool = False


CONFIG_PATH = PROJECT_ROOT / "wgp_config.json"

def load_config() -> dict[str, Any]:
    if not CONFIG_PATH.exists():
        return {}
    with open(CONFIG_PATH, "r", encoding="utf-8") as f:
        return json.load(f)    
def save_config(cfg: dict[str, Any]) -> None:
    with open(CONFIG_PATH, "w", encoding="utf-8") as f:
        json.dump(cfg, f, indent=4)
class TaskRecord:
    def __init__(self, task_id: str, settings: dict[str, Any]) -> None:
        self.task_id = task_id
        self.settings = settings
        self.status: str = "queued"
        self.progress: float = 0.0
        self.phase: str = ""
        self.output_path: str = ""
        self.output_files: list[str] = []
        self.error: str = ""
        self.created_at: float = time.time()
        self.completed_at: float = 0.0
        self.job: Any = None
        self.released: bool = False
        self.log_path: str = ""

_tasks: dict[str, TaskRecord] = {}
_session: Any = None
_session_lock = threading.Lock()

def _get_session():
    global _session
    with _session_lock:
        if _session is None:
            from shared.api import WanGPSession
            _session = WanGPSession(root=PROJECT_ROOT, console_output=True)
            _session.ensure_ready()
        return _session

def _get_default_model_type() -> str:
    try:
        config_path = PROJECT_ROOT / "wgp_config.json"
        with open(config_path, "r", encoding="utf-8") as f:
            config = json.load(f)
        transformer_types = config.get("transformer_types", [])
        if transformer_types:
            return transformer_types[0]
    except Exception:
        pass
    return ""

def _build_settings(body: "GenerateRequest", task_id: str) -> dict[str, Any]:
    settings: dict[str, Any] = {
        "prompt": body.prompt,
        "client_id": task_id,
    } 

    model_type = body.model_type or _get_default_model_type()
    if model_type:
        settings["model_type"] = model_type
    if body.alt_prompt:
        settings["alt_prompt"] = body.alt_prompt
    settings["model_mode"] = body.model_mode
    if body.negative_prompt:
        settings["negative_prompt"] = body.negative_prompt
    if body.num_inference_steps > 0:
        settings["num_inference_steps"] = body.num_inference_steps
    if body.guidance_scale > 0:
        settings["guidance_scale"] = body.guidance_scale
    if body.alt_guidance_scale > 0:
        settings["alt_guidance_scale"] = body.alt_guidance_scale
    if body.temperature > 0:
        settings["temperature"] = body.temperature
    if body.top_p > 0:
        settings["top_p"] = body.top_p
    if body.top_k >= 0:
        settings["top_k"] = body.top_k
    if body.seed >= 0:
        settings["seed"] = body.seed
    if body.repeat_generation > 1:
        settings["repeat_generation"] = body.repeat_generation
    if body.model_mode == 0:
        if body.bpm > 0:
            settings["custom_settings"] = settings.get("custom_settings", {})
            settings["custom_settings"]["bpm"] = body.bpm
        if body.keyscale:
            settings["custom_settings"] = settings.get("custom_settings", {})
            settings["custom_settings"]["keyscale"] = body.keyscale
        if body.time_signature > 0:
            settings["custom_settings"] = settings.get("custom_settings", {})
            settings["custom_settings"]["timesignature"] = body.time_signature
        if body.language:
            settings["custom_settings"] = settings.get("custom_settings", {})
            settings["custom_settings"]["language"] = body.language
    else:
        custom = {}
        if body.bpm > 0:
            custom["bpm"] = body.bpm
        if body.keyscale:
            custom["keyscale"] = body.keyscale
        if body.time_signature > 0:
            custom["timesignature"] = body.time_signature
        if body.language:
            custom["language"] = body.language
        if custom:
            settings["custom_settings"] = custom
    if body.audio_scale > 0:
        settings["audio_scale"] = body.audio_scale
    if body.audio_guide:
        settings["audio_guide"] = body.audio_guide
    if body.audio_guide2:
        settings["audio_guide2"] = body.audio_guide2
    if body.audio_prompt_type:
        settings["audio_prompt_type"] = body.audio_prompt_type
    if body.video_length > 0:
        settings["video_length"] = body.video_length
    if body.height > 0:
        settings["height"] = body.height
    if body.width > 0:
        settings["width"] = body.width
    if body.extra_settings:
        settings.update(body.extra_settings)
    return settings
    
_GENRE_KEYWORDS = {
    "pop", "rock", "hip hop", "rap", "r&b", "soul", "funk", "jazz", "blues",
    "country", "folk", "classical", "electronic", "edm", "techno", "house",
    "trance", "dubstep", "reggae", "ska", "punk", "metal", "indie", "alternative",
    "lo-fi", "lofi", "synth", "synthwave", "disco", "ambient", "chill", "lounge",
    "orchestral", "cinematic", "soundtrack", "experimental", "industrial",
    "latin", "salsa", "bachata", "reggaeton", "bossa nova", "samba",
    "k-pop", "j-pop", "c-pop", "bollywood",
}

_MOOD_KEYWORDS = {
    "happy", "sad", "energetic", "chill", "relaxing", "upbeat", "mellow",
    "dark", "light", "dreamy", "ethereal", "melancholic", "nostalgic",
    "romantic", "aggressive", "peaceful", "euphoric", "groovy", "vibrant",
    "somber", "triumphant", "bittersweet", "hopeful", "anxious", "serene",
}

_INSTRUMENT_KEYWORDS = {
    "guitar", "piano", "drums", "bass", "synth", "keyboard", "violin",
    "strings", "brass", "saxophone", "trumpet", "flute", "harp",
    "acoustic guitar", "electric guitar", "piano", "organ", "pad",
    "vocals", "male vocals", "female vocals", "choir", "harmony",
}

_LYRIC_SECTION_TAGS = re.compile(r"\[(verse|chorus|bridge|intro|outro|pre-chorus|hook|instrumental|inst|interlude|tag)\]", re.IGNORECASE)

_BPM_PATTERN = re.compile(r"(\d{2,3})\s*bpm", re.IGNORECASE)


def parse_raw_tags(raw_text: str) -> dict[str, str]:
    text = raw_text.strip()
    if not text:
        return {"prompt": "", "alt_prompt": "", "bpm": 0, "tags_found": [], "sections_found": []}
    has_sections = bool(_LYRIC_SECTION_TAGS.search(text))
    bpm = 0
    bpm_match = _BPM_PATTERN.search(text)
    if bpm_match:
        bpm = int(bpm_match.group(1))
    tags_found = []
    sections_found = []
    lyrics_lines = []
    tag_lines = []
    for line in text.split("\n"):
        line_stripped = line.strip()
        if not line_stripped:
            continue
        section_match = _LYRIC_SECTION_TAGS.match(line_stripped)
        if section_match:
            sections_found.append(section_match.group(1).lower())
            lyrics_lines.append(line_stripped)
            continue
        if has_sections:
            lyrics_lines.append(line_stripped)
            continue
        tag_lines.append(line_stripped)
    alt_prompt = ", ".join(tag_lines) if tag_lines else ""
    if has_sections and lyrics_lines:
        prompt = "\n".join(lyrics_lines)
    elif lyrics_lines:
        prompt = "\n".join(lyrics_lines)
    else:
        prompt = ""
        if not alt_prompt:
            alt_prompt = text
    all_words = text.lower()
    for kw in _GENRE_KEYWORDS:
        if kw in all_words:
            tags_found.append(kw)
    for kw in _MOOD_KEYWORDS:
        if kw in all_words:
            tags_found.append(kw)
    for kw in _INSTRUMENT_KEYWORDS:
        if kw in all_words:
            tags_found.append(kw)

    return {
        "prompt": prompt,
        "alt_prompt": alt_prompt,
        "bpm": bpm,
        "tags_found": list(set(tags_found)),
        "sections_found": list(set(sections_found)),
    }


def _build_settings_from_raw(raw_text: str, task_id: str, model_type: str = "", extra: dict[str, Any] | None = None) -> dict[str, Any]:
    parsed = parse_raw_tags(raw_text)
    settings: dict[str, Any] = {
        "client_id": task_id,
        "model_mode": 4,
    }
    if parsed["prompt"]:
        settings["prompt"] = parsed["prompt"]
    else:
        settings["prompt"] = raw_text
    if parsed["alt_prompt"]:
        settings["alt_prompt"] = parsed["alt_prompt"]
    if parsed["bpm"] > 0:
        settings["custom_settings"] = {"bpm": parsed["bpm"]}
    model_type_resolved = model_type or _get_default_model_type()
    if model_type_resolved:
        settings["model_type"] = model_type_resolved
    if extra:
        settings.update(extra)
    return settings


def _summarize_settings(settings: dict[str, Any]) -> str:
    parts = []
    mode = settings.get("model_mode", 0)
    parts.append(f"mode={mode}")
    prompt = settings.get("prompt", "")
    if prompt:
        preview = prompt[:60].replace("\n", " ")
        if len(prompt) > 60:
            preview += "..."
        parts.append(f"prompt=\"{preview}\"")
    alt = settings.get("alt_prompt", "")
    if alt:
        parts.append(f"caption=\"{alt[:40]}\"")
    seed = settings.get("seed", -1)
    if seed >= 0:
        parts.append(f"seed={seed}")
    cs = settings.get("custom_settings", {})
    if cs:
        bits = [f"{k}={v}" for k, v in cs.items()]
        parts.append("custom={" + ", ".join(bits) + "}")
    return " | ".join(parts)


def _start_generation(record: TaskRecord) -> None:
    try:
        session = _get_session()
        settings_summary = _summarize_settings(record.settings)
        print(f"[api] task {record.task_id} starting: {settings_summary}")
        log_dir = PROJECT_ROOT / "api_logs"
        log_dir.mkdir(exist_ok=True)
        log_path = log_dir / f"task_{record.task_id}.log"
        log_file = open(log_path, "w", encoding="utf-8")
        original_stdout = sys.stdout
        original_stderr = sys.stderr
        sys.stdout = log_file
        sys.stderr = log_file
        try:
            job = session.submit_task(record.settings)
        finally:
            sys.stdout = original_stdout
            sys.stderr = original_stderr
            log_file.close()
        record.job = job
        record.status = "running"
        record.log_path = str(log_path)
        print(f"[api] task {record.task_id} submitted to pipeline")

        def _monitor():
            while not job.done:
                time.sleep(0.5)
            try:
                result = job.result(timeout=0.1)
            except Exception:
                result = None
            if result is not None and result.success and result.generated_files:
                record.output_files = list(result.generated_files)
                record.output_path = result.generated_files[0]
                record.status = "completed"
                record.progress = 100.0
                print(f"[api] task {record.task_id} completed: {record.output_path}")
            elif result is not None and result.errors:
                record.status = "failed"
                record.error = str(result.errors[0])
                print(f"[api] task {record.task_id} failed: {record.error}")
            else:
                record.status = "completed" if (result and not result.errors) else "failed"
                if record.status == "failed" and not record.error:
                    record.error = "Generation did not produce output"
                print(f"[api] task {record.task_id} finished (status={record.status})")
            record.completed_at = time.time()

        threading.Thread(target=_monitor, daemon=True, name=f"api-monitor-{record.task_id}").start()

    except Exception as e:
        record.status = "failed"
        record.error = str(e)
        record.completed_at = time.time()
        print(f"[api] task {record.task_id} error: {e}")

class GenerateRequest(BaseModel):
    # --- Core ---
    prompt: str = Field(..., description="Lyrics / prompt text. Use [Verse], [Chorus], [Bridge] tags. Use [Instrumental] for instrumental-only.")
    alt_prompt: str = Field(default="", description="Music caption describing style, genre, instruments, mood (e.g. 'dreamy synth-pop, shimmering pads, soft vocals')")
    model_type: str = Field(default="", description="Model type (leave empty for default from wgp_config.json)")
    negative_prompt: str = Field(default="", description="Negative prompt")

    # --- Duration ---
    model_mode: int = Field(
        default=4,
        description=(
            "LM Chain Of Thought Preprocessing mode for ACE-Step 1.5.\n"
            "The LM analyzes your lyrics + caption to auto-determine the best settings.\n\n"
            "  0 = Default: Use provided BPM/key, fill defaults for missing\n"
            "  1 = Infer Missing: Auto-compute BPM, keyscale, time signature, language\n"
            "  2 = Refine Caption: Infer missing + refine your music caption\n"
            "  3 = Refine + Duration: Infer missing + refine caption + auto-determine duration\n"
            "  4 = Infer + Duration: Infer missing fields + auto-determine duration (RECOMMENDED)\n\n"
            "Mode 4 is recommended: the LM reads your lyrics and caption, then picks\n"
            "the best BPM, key, time signature, language, and song duration automatically."
        ),
    )
    num_inference_steps: int = Field(default=0, description="Denoising steps (0 = default, ACE-Step 1.5 turbo uses 8)")
    guidance_scale: float = Field(default=0.0, description="CFG scale (0 = default)")
    alt_guidance_scale: float = Field(default=0.0, description="LM Guidance scale for ACE-Step 1.5 (0 = default 2.5)")
    temperature: float = Field(default=0.0, description="Sampling temperature (0 = default 0.85)")
    top_p: float = Field(default=0.0, description="Top-p sampling (0 = default 0.9)")
    top_k: int = Field(default=-1, description="Top-k sampling (-1 = default 0 = disabled)")
    seed: int = Field(default=-1, description="Random seed (-1 = random)")
    repeat_generation: int = Field(default=1, description="Number of generations to produce")
    bpm: int = Field(default=0, description="BPM (0 = auto/unused)")
    keyscale: str = Field(default="", description="Key scale, e.g. 'C major', 'F# minor' (empty = auto)")
    time_signature: int = Field(default=0, description="Time signature: 2, 3, 4, or 6 (0 = auto)")
    language: str = Field(default="", description="ISO language code (empty = auto/unknown)")
    audio_scale: float = Field(default=0.0, description="Source audio strength for audio2audio (0 = unused)")
    audio_guide: str = Field(default="", description="Path to source audio for audio2audio mode")
    audio_guide2: str = Field(default="", description="Path to reference timbre audio")
    audio_prompt_type: str = Field(default="", description="Audio task type: '' (text2audio), 'A' (cover), 'B' (timbre transfer), 'AB' (cover + timbre)")
    video_length: int = Field(default=0, description="Number of frames for video models (0 = default)")
    height: int = Field(default=0, description="Output height (0 = default)")
    width: int = Field(default=0, description="Output width (0 = default)")
    extra_settings: dict[str, Any] = Field(default_factory=dict, description="Any additional settings passed directly to WanGP")


class TaskCreatedResponse(BaseModel):
    task_id: str
    status: str


class TaskStatusResponse(BaseModel):
    task_id: str
    status: str
    progress: float
    phase: str
    output_path: str
    output_files: list[str]
    error: str
    created_at: float
    completed_at: float


class RawTagRequest(BaseModel):
    raw_text: str = Field(..., description="Raw tags/lyrics text copied from external site")
    model_type: str = Field(default="", description="Model type (leave empty for default)")
    extra_settings: dict[str, Any] = Field(default_factory=dict, description="Additional settings override")


class ParseTagsRequest(BaseModel):
    raw_text: str = Field(..., description="Raw tags/lyrics text to parse")


class ParseTagsResponse(BaseModel):
    prompt: str
    alt_prompt: str
    bpm: int
    tags_found: list[str]
    sections_found: list[str]

app = FastAPI(
    title="WanGP API",
    description="REST API for WanGP media generation. Reads settings from wgp_config.json.",
    version="1.0.0",
)

app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)


@app.middleware("http")
async def auth_middleware(request, call_next):
    if _REQUIRE_AUTH:
        auth_header = request.headers.get("Authorization")
        if not auth_header or not auth_header.startswith("Basic "):
            return JSONResponse(
                status_code=401,
                content={"detail": "Authentication required"},
                headers={"WWW-Authenticate": "Basic"},
            )
        try:
            import base64
            encoded = auth_header[len("Basic "):]
            decoded = base64.b64decode(encoded).decode("utf-8")
            username, password = decoded.split(":", 1)
            if not (secrets.compare_digest(username, _AUTH_USERNAME) and secrets.compare_digest(password, _AUTH_PASSWORD)):
                return JSONResponse(
                    status_code=401,
                    content={"detail": "Invalid username or password"},
                    headers={"WWW-Authenticate": "Basic"},
                )
        except Exception:
            return JSONResponse(
                status_code=401,
                content={"detail": "Invalid authentication header"},
                headers={"WWW-Authenticate": "Basic"},
            )
    return await call_next(request)

@app.get("/")
def root():
    return {
        "name": "WanGP API",
        "version": "1.0.0",
        "status": "running",
        "authentication": "required" if _REQUIRE_AUTH else "disabled",
        "endpoints": {
            "create_task": "POST /create_task",
            "create_task_raw": "POST /create_task_raw (raw tags from external sites)",
            "parse_tags": "POST /parse_tags (parse raw tags without generating)",
            "task_status": "GET /task_status/{task_id}",
            "release_task": "POST /release_task/{task_id}",
            "get_result": "GET /get_result/{task_id}",
            "cancel_task": "POST /cancel_task/{task_id}",
            "list_tasks": "GET /list_tasks",
            "models": "GET /models",
            "config": "GET /config",
        },
    }

@app.get("/health")
def health():
    return {"status": "ok"}

@app.post("/create_task", response_model=TaskCreatedResponse)
@app.post("/generate", response_model=TaskCreatedResponse)
def create_task(body: GenerateRequest):
    task_id = f"api_{uuid.uuid4().hex[:12]}"
    settings = _build_settings(body, task_id)
    record = TaskRecord(task_id, settings)
    _tasks[task_id] = record
    _start_generation(record)
    return TaskCreatedResponse(task_id=task_id, status=record.status)

@app.get("/task_status/{task_id}", response_model=TaskStatusResponse)
def task_status(task_id: str):
    record = _tasks.get(task_id)
    if record is None:
        raise HTTPException(status_code=404, detail=f"Task '{task_id}' not found")
    return TaskStatusResponse(
        task_id=record.task_id,
        status=record.status,
        progress=record.progress,
        phase=record.phase,
        output_path=record.output_path,
        output_files=record.output_files,
        error=record.error,
        created_at=record.created_at,
        completed_at=record.completed_at,
    )

@app.post("/release_task/{task_id}")
def release_task(task_id: str):
    record = _tasks.get(task_id)
    if record is None:
        raise HTTPException(status_code=404, detail=f"Task '{task_id}' not found")
    record.released = True
    record.job = None
    return {"task_id": task_id, "status": "released"}

@app.get("/get_result/{task_id}")
def get_result(task_id: str):
    record = _tasks.get(task_id)
    if record is None:
        raise HTTPException(status_code=404, detail=f"Task '{task_id}' not found")
    if record.status != "completed" or not record.output_path:
        raise HTTPException(status_code=400, detail=f"Task is not completed (status={record.status})")
    path = Path(record.output_path)
    if not path.exists():
        raise HTTPException(status_code=404, detail="Output file not found on disk")
    safe_name = path.name.encode("ascii", errors="replace").decode("ascii").replace("?", "_")
    return FileResponse(
        path=str(path),
        filename=safe_name,
        media_type="application/octet-stream",
    )

@app.post("/cancel_task/{task_id}")
def cancel_task(task_id: str):
    record = _tasks.get(task_id)
    if record is None:
        raise HTTPException(status_code=404, detail=f"Task '{task_id}' not found")
    if record.job is not None and not record.job.done:
        record.job.cancel()
        record.status = "cancelled"
        record.completed_at = time.time()
        return {"task_id": task_id, "status": "cancelled"}
    return {"task_id": task_id, "status": record.status}

@app.get("/list_tasks")
def list_tasks():
    tasks = []
    for tid, rec in _tasks.items():
        tasks.append({
            "task_id": tid,
            "status": rec.status,
            "progress": rec.progress,
            "created_at": rec.created_at,
            "completed_at": rec.completed_at,
        })
    return {"tasks": tasks, "total": len(tasks)}

@app.post("/parse_tags", response_model=ParseTagsResponse)
def parse_tags(body: ParseTagsRequest):
    parsed = parse_raw_tags(body.raw_text)
    return ParseTagsResponse(**parsed)

@app.post("/create_task_raw", response_model=TaskCreatedResponse)
def create_task_raw(body: RawTagRequest):
    task_id = f"api_{uuid.uuid4().hex[:12]}"
    settings = _build_settings_from_raw(body.raw_text, task_id, model_type=body.model_type, extra=body.extra_settings)
    record = TaskRecord(task_id, settings)
    _tasks[task_id] = record
    _start_generation(record)
    return TaskCreatedResponse(task_id=task_id, status=record.status)

@app.get("/config")
def get_config():
    return load_config()

@app.put("/config")
def update_config(body: dict[str, Any]):
    cfg = load_config()
    cfg.update(body)
    save_config(cfg)
    return {"status": "updated", "config": cfg}

@app.get("/config/{key}")
def get_config_key(key: str):
    cfg = load_config()
    if key not in cfg:
        raise HTTPException(status_code=404, detail=f"Key '{key}' not found in config")
    return {key: cfg[key]}

@app.put("/config/{key}")
def set_config_key(key: str, body: dict[str, Any]):
    cfg = load_config()
    cfg[key] = body.get("value", body)
    save_config(cfg)
    return {"status": "updated", key: cfg[key]}

@app.get("/models")
def list_models():
    try:
        session = _get_session()
        models = session.list_model_metadata(include_availability=True)
        return {"models": models}
    except Exception as e:
        return JSONResponse(status_code=500, content={"error": str(e)})

@app.get("/models/{model_type}")
def get_model(model_type: str):
    try:
        session = _get_session()
        meta = session.get_model_metadata(model_type, include_availability=True)
        if meta is None:
            raise HTTPException(status_code=404, detail=f"Model '{model_type}' not found")
        defaults = session.get_default_settings(model_type)
        return {"model": meta, "default_settings": defaults}
    except HTTPException:
        raise
    except Exception as e:
        return JSONResponse(status_code=500, content={"error": str(e)})

@app.post("/run")
def run_direct(body: GenerateRequest):
    task_id = f"api_{uuid.uuid4().hex[:12]}"
    settings = _build_settings(body, task_id)
    try:
        session = _get_session()
        result = session.run_task(settings)
        return {
            "success": result.success,
            "task_id": task_id,
            "generated_files": result.generated_files,
            "errors": [str(e) for e in result.errors],
        }
    except Exception as e:
        return JSONResponse(status_code=500, content={"success": False, "error": str(e)})

if __name__ == "__main__":
    import argparse
    import string

    AUTH_CRED_PATH = PROJECT_ROOT / ".api_auth.json"

    def _load_saved_auth() -> tuple[str, str]:
        if AUTH_CRED_PATH.exists():
            try:
                with open(AUTH_CRED_PATH, "r", encoding="utf-8") as f:
                    data = json.load(f)
                return data.get("username", ""), data.get("password", "")
            except Exception:
                pass
        return "", ""

    def _save_auth(username: str, password: str) -> None:
        with open(AUTH_CRED_PATH, "w", encoding="utf-8") as f:
            json.dump({"username": username, "password": password}, f, indent=2)

    def _generate_credentials() -> tuple[str, str]:
        username = "admin"
        alphabet = string.ascii_letters + string.digits
        password = "".join(secrets.choice(alphabet) for _ in range(16))
        return username, password

    parser = argparse.ArgumentParser(description="WanGP API Server")
    parser.add_argument("--host", type=str, default="127.0.0.1", help="Bind host")
    parser.add_argument("--port", type=int, default=8001, help="Bind port")
    parser.add_argument("--config", type=str, default="", help="Path to config directory")
    parser.add_argument("--username", type=str, default="", help="Username for API authentication")
    parser.add_argument("--password", type=str, default="", help="Password for API authentication")
    parser.add_argument("--require-auth", action="store_true", default=False, help="Force authentication even for local connections")
    parser.add_argument("--reset-auth", action="store_true", default=False, help="Reset saved authentication credentials")
    args = parser.parse_args()
    if args.config:
        alt_config = Path(args.config) / "wgp_config.json"
        if alt_config.exists():
            CONFIG_PATH = alt_config

    if args.reset_auth:
        if AUTH_CRED_PATH.exists():
            AUTH_CRED_PATH.unlink()
            print("Saved authentication credentials have been deleted.")

    _is_external = args.host not in ("127.0.0.1", "localhost", "::1", "0.0.0.0")

    if _is_external or args.require_auth:
        saved_user, saved_pass = _load_saved_auth()

        if args.username and args.password:
            _AUTH_USERNAME = args.username
            _AUTH_PASSWORD = args.password
            _save_auth(_AUTH_USERNAME, _AUTH_PASSWORD)
            _REQUIRE_AUTH = True
            print(f"Authentication enabled with provided credentials for host: {args.host}")
        elif saved_user and saved_pass:
            _AUTH_USERNAME = saved_user
            _AUTH_PASSWORD = saved_pass
            _REQUIRE_AUTH = True
            print(f"Authentication enabled with saved credentials for host: {args.host}")
        else:
            _AUTH_USERNAME, _AUTH_PASSWORD = _generate_credentials()
            _save_auth(_AUTH_USERNAME, _AUTH_PASSWORD)
            _REQUIRE_AUTH = True
            print(f"Authentication enabled with auto-generated credentials for host: {args.host}")

        print()
        print("=" * 50)
        print("  API CREDENTIALS (saved to .api_auth.json)")
        print("=" * 50)
        print(f"  Username: {_AUTH_USERNAME}")
        print(f"  Password: {_AUTH_PASSWORD}")
        print("=" * 50)
        print()

    elif args.username and args.password:
        _AUTH_USERNAME = args.username
        _AUTH_PASSWORD = args.password
        _REQUIRE_AUTH = True
        _save_auth(_AUTH_USERNAME, _AUTH_PASSWORD)
        print("Authentication enabled (explicit --username/--password provided)")

    print(f"WanGP API Server - Config: {CONFIG_PATH}")
    print(f"Starting on http://{args.host}:{args.port}")
    if _REQUIRE_AUTH:
        print("Authentication: REQUIRED (HTTP Basic Auth)")
    else:
        print("Authentication: DISABLED (local connection)")
    print(f"Docs: http://{args.host}:{args.port}/docs")
    uvicorn.run(app, host=args.host, port=args.port, log_level="info")
