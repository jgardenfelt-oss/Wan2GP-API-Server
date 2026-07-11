#!/usr/bin/env bash
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$SCRIPT_DIR"

HOST="${API_HOST:-You IP_ADDRESS}"
PORT="${API_PORT:-8001}"
CONDA_ENV="${CONDA_ENV:-wan2gp}"
USERNAME="${API_USERNAME:-}"
PASSWORD="${API_PASSWORD:-}"

echo "========================================"
echo "  WanGP API Server"
echo "========================================"

# Find conda
find_conda() {
    if command -v conda &>/dev/null; then
        command -v conda
        return
    fi

    for dir in \
        "$HOME/miniconda3" \
        "$HOME/Miniconda3" \
        "$HOME/anaconda3" \
        "$HOME/Anaconda3" \
        "/opt/miniconda3" \
        "/opt/anaconda3" \
        "/usr/local/miniconda3" \
        "/usr/local/anaconda3"; do
        if [ -x "$dir/bin/conda" ]; then
            echo "$dir/bin/conda"
            return
        fi
    done

    return 1
}

CONDA_EXE=""
if ! CONDA_EXE=$(find_conda); then
    echo "[ERROR] Conda not found."
    echo "Install miniconda: https://docs.conda.io/en/latest/miniconda.html"
    exit 1
fi

echo "Conda: $CONDA_EXE"

# Check if conda env exists
if ! "$CONDA_EXE" env list | grep -q "^${CONDA_ENV} "; then
    echo "[ERROR] Conda environment '${CONDA_ENV}' not found."
    echo "Create it: conda create -n $CONDA_ENV python=3.11"
    exit 1
fi

echo "Environment: $CONDA_ENV"
echo "Host: $HOST"
echo "Port: $PORT"
echo ""

# Build command
CMD=("$CONDA_EXE" "run" "-n" "$CONDA_ENV" "--no-capture-output" "python" "api_server.py" "--host" "$HOST" "--port" "$PORT")

if [ -n "$USERNAME" ] && [ -n "$PASSWORD" ]; then
    CMD+=("--username" "$USERNAME" "--password" "$PASSWORD")
    echo "Auth: using credentials from environment"
elif [ -f ".api_auth.json" ]; then
    echo "Auth: using saved credentials from .api_auth.json"
fi

echo "Starting..."
echo ""
exec "${CMD[@]}"
