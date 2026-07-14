using System.Diagnostics;
using System.IO.Compression;
using System.Net;
using System.Text.Json;
using Wan2GP_WebUI_Launcher;

namespace Wan2GP_WebUI_Launcher;

static class Program
{
    [STAThread]
    static void Main()
    {
        ApplicationConfiguration.Initialize();
        Application.Run(new MainForm());
    }
}

class MainForm : Form
{
    private readonly RichTextBox _log;
    private readonly Label _statusLabel;
    private Process? _process;
    private readonly System.Windows.Forms.Timer _pollTimer;
    private DateTime _startTime;
    private int _lastPollMessage;
    private const int TimeoutSeconds = 1800;
    private const string Url = "http://localhost:7860";

    public MainForm()
    {
        Text = "Wan2GP WebUI - Starting...";
        Size = new Size(720, 500);
        MinimumSize = new Size(520, 350);
        StartPosition = FormStartPosition.CenterScreen;
        BackColor = Color.FromArgb(30, 30, 30);
        Font = new Font("Segoe UI", 10f);
        Icon = CreateMusicNoteIcon();

        var titleLabel = new Label
        {
            Text = "Wan2GP WebUI Launcher",
            Font = new Font("Segoe UI", 16f, FontStyle.Bold),
            ForeColor = Color.FromArgb(0, 200, 80),
            Dock = DockStyle.Top,
            Height = 50,
            TextAlign = ContentAlignment.MiddleCenter,
        };

        _statusLabel = new Label
        {
            Text = "Initializing...",
            ForeColor = Color.FromArgb(200, 200, 200),
            Dock = DockStyle.Bottom,
            Height = 30,
            TextAlign = ContentAlignment.MiddleLeft,
            Padding = new Padding(8, 0, 0, 0),
        };

        _log = new RichTextBox
        {
            Dock = DockStyle.Fill,
            ReadOnly = true,
            BackColor = Color.FromArgb(20, 20, 20),
            ForeColor = Color.FromArgb(0, 220, 80),
            Font = new Font("Consolas", 9.5f),
            BorderStyle = BorderStyle.None,
            ScrollBars = RichTextBoxScrollBars.Vertical,
            WordWrap = false,
        };

        Controls.Add(_log);
        Controls.Add(titleLabel);
        Controls.Add(_statusLabel);

        _pollTimer = new System.Windows.Forms.Timer { Interval = 1000 };
        _pollTimer.Tick += PollTimer_Tick;

        Shown += MainForm_Shown;
        FormClosing += MainForm_FormClosing;
    }

    private void MainForm_Shown(object? sender, EventArgs e)
    {
        Task.Run(() => Start());
        Task.Run(() => CheckForUpdates());
    }

    private void Start()
    {
        string? conda = FindConda();
        if (conda == null)
        {
            Log("[!!] ERROR: Anaconda / Miniconda not found.", Color.Red);
            Log("Please install Anaconda or Miniconda first:", Color.Gray);
            Log("  https://docs.anaconda.com/miniconda/", Color.Gray);
            Invoke(() => _statusLabel.Text = "Conda not found");
            return;
        }

        Log($"Conda: {conda}", Color.White);

        Log("Finding Python in wan2gp environment...", Color.Gray);
        string? pythonExe = GetPythonFromConda(conda);
        if (pythonExe == null || !File.Exists(pythonExe))
        {
            Log($"[!!] Failed to locate python.exe at: {pythonExe}", Color.Red);
            Log("Trying to list conda environments...", Color.Gray);
            ListCondaEnvs(conda);
            Invoke(() => _statusLabel.Text = "Python not found");
            return;
        }

        Log($"Python: {pythonExe}", Color.White);

        Log("Checking dependencies...", Color.Gray);
        EnsurePackage(conda, "hf_xet");

        LaunchProcess(pythonExe);
    }

    private void CheckForUpdates()
    {
        try
        {
            using var client = new WebClient();
            client.Headers.Add("User-Agent", "Wan2GP-Launcher");

            string shaFile = Path.Combine(AppDomain.CurrentDomain.BaseDirectory, "wan2gp_version.txt");
            string localSha = "";
            if (File.Exists(shaFile))
                localSha = File.ReadAllText(shaFile).Trim();

            string json = client.DownloadString("https://api.github.com/repos/deepbeepmeep/Wan2GP/commits?sha=main&per_page=1");
            using var doc = JsonDocument.Parse(json);
            if (doc.RootElement.ValueKind != JsonValueKind.Array || doc.RootElement.GetArrayLength() == 0)
                return;

            var commit = doc.RootElement[0];
            string sha = commit.GetProperty("sha").GetString() ?? "";
            string shortSha = sha.Length >= 7 ? sha[..7] : sha;

            if (localSha == sha)
            {
                Log($"[i] Wan2GP is up to date ({shortSha})", Color.Gray);
                return;
            }

            Log($"[i] New version available: {shortSha}", Color.Cyan);
            Log("    Downloading source code...", Color.Gray);

            string zipUrl = "https://github.com/deepbeepmeep/Wan2GP/archive/refs/heads/main.zip";
            string tempZip = Path.Combine(Path.GetTempPath(), "wan2gp_update.zip");
            client.DownloadFile(zipUrl, tempZip);

            string tempExtract = Path.Combine(Path.GetTempPath(), "wan2gp_update_extract");
            if (Directory.Exists(tempExtract))
                Directory.Delete(tempExtract, true);
            ZipFile.ExtractToDirectory(tempZip, tempExtract);

            string extractedDir = Path.Combine(tempExtract, "Wan2GP-main");
            if (!Directory.Exists(extractedDir))
                extractedDir = Directory.GetDirectories(tempExtract).FirstOrDefault() ?? "";

            if (string.IsNullOrEmpty(extractedDir))
            {
                Log("    [!] Failed to extract update.", Color.Red);
                File.Delete(tempZip);
                return;
            }

            string appDir = AppDomain.CurrentDomain.BaseDirectory;
            string[] excludeFiles = ["start.exe", "start.dll", "start.pdb", "start.csproj", "music_note.ico"];
            CopyFiles(extractedDir, appDir, excludeFiles);

            File.WriteAllText(shaFile, sha);

            File.Delete(tempZip);
            Directory.Delete(tempExtract, true);

            Log($"    Updated to {shortSha}. Please restart to use new version.", Color.LimeGreen);

            string requirementsNew = Path.Combine(extractedDir, "requirements.txt");
            if (File.Exists(requirementsNew))
            {
                Log("    Checking Python packages...", Color.Gray);
                string? conda = FindConda();
                if (conda != null)
                {
                    RunCondaCommand(conda, "run -n wan2gp pip install -r requirements.txt");
                    Log("    Python packages updated.", Color.LimeGreen);
                }
                else
                {
                    Log("    [!] Conda not found - run pip install manually.", Color.Yellow);
                }
            }
        }
        catch (Exception ex)
        {
            Log($"[!] Update check failed: {ex.Message}", Color.Yellow);
        }
    }

    private static void CopyFiles(string source, string destination, string[] excludeFiles)
    {
        Directory.CreateDirectory(destination);

        foreach (string file in Directory.GetFiles(source))
        {
            string fileName = Path.GetFileName(file);
            if (excludeFiles.Contains(fileName, StringComparer.OrdinalIgnoreCase))
                continue;
            string destFile = Path.Combine(destination, fileName);
            File.Copy(file, destFile, true);
        }

        foreach (string dir in Directory.GetDirectories(source))
        {
            string dirName = Path.GetFileName(dir);
            string destDir = Path.Combine(destination, dirName);
            CopyFiles(dir, destDir, excludeFiles);
        }
    }

    private static Icon CreateMusicNoteIcon()
    {
        int size = 32;
        using var bmp = new Bitmap(size, size);
        using var g = Graphics.FromImage(bmp);
        g.SmoothingMode = System.Drawing.Drawing2D.SmoothingMode.AntiAlias;
        g.Clear(Color.Transparent);

        using var brush = new SolidBrush(Color.FromArgb(0, 200, 80));

        g.FillEllipse(brush, 2, 18, 10, 10);
        g.FillEllipse(brush, 18, 14, 10, 10);

        g.FillRectangle(brush, 10, 4, 3, 16);
        g.FillRectangle(brush, 26, 2, 3, 14);

        g.FillRectangle(brush, 10, 4, 19, 3);

        using var pen = new Pen(Color.FromArgb(0, 200, 80), 2f);
        g.DrawRectangle(pen, 10, 4, 19, 3);

        return Icon.FromHandle(bmp.GetHicon());
    }

    private void RunCondaCommand(string conda, string arguments)
    {
        try
        {
            var psi = new ProcessStartInfo
            {
                FileName = conda,
                Arguments = arguments,
                RedirectStandardOutput = true,
                RedirectStandardError = true,
                UseShellExecute = false,
                CreateNoWindow = true,
            };

            using Process proc = Process.Start(psi) ?? throw new InvalidOperationException("Process.Start returned null");
            string stdout = proc.StandardOutput.ReadToEnd();
            string stderr = proc.StandardError.ReadToEnd();
            proc.WaitForExit();

            if (proc.ExitCode != 0)
                Log($"    [!] Command failed: {stderr.Trim()}", Color.Yellow);
        }
        catch (Exception ex)
        {
            Log($"    [!] Command error: {ex.Message}", Color.Yellow);
        }
    }

    private void LaunchProcess(string pythonExe)
    {
        string appDir = AppDomain.CurrentDomain.BaseDirectory;

        var psi = new ProcessStartInfo
        {
            FileName = pythonExe,
            Arguments = "-u wgp.py",
            WorkingDirectory = appDir,
            UseShellExecute = false,
            RedirectStandardOutput = true,
            RedirectStandardError = true,
            CreateNoWindow = true,
        };

        try
        {
            _process = Process.Start(psi);
        }
        catch (Exception ex)
        {
            Log($"[!!] Failed to start process: {ex.Message}", Color.Red);
            return;
        }

        if (_process == null)
        {
            Log("[!!] Failed to start process.", Color.Red);
            return;
        }

        _process.OutputDataReceived += (_, e) =>
        {
            if (e.Data != null) Log(e.Data, Color.White);
        };
        _process.ErrorDataReceived += (_, e) =>
        {
            if (e.Data != null) Log(e.Data, Color.OrangeRed);
        };
        _process.BeginOutputReadLine();
        _process.BeginErrorReadLine();

        Log("", Color.White);
        Log("Loading... polling " + Url + " for ready signal", Color.Yellow);
        Log("", Color.White);

        Invoke(() =>
        {
            Text = "Wan2GP - Loading...";
            _statusLabel.Text = "Loading...";
        });

        _startTime = DateTime.Now;
        _lastPollMessage = 0;
        _pollTimer.Start();
    }

    private void PollTimer_Tick(object? sender, EventArgs e)
    {
        if (_process == null || _process.HasExited)
        {
            _pollTimer.Stop();
            if (_process != null)
                Log($"[!!] Wan2GP exited unexpectedly (code {_process.ExitCode})", Color.Red);
            Invoke(() => _statusLabel.Text = "Process exited");
            return;
        }

        try
        {
            var request = (HttpWebRequest)WebRequest.Create(Url);
            request.Timeout = 2000;
            using var response = (HttpWebResponse)request.GetResponse();
            response.Close();

            _pollTimer.Stop();
            OnReady();
            return;
        }
        catch { }

        int elapsed = (int)(DateTime.Now - _startTime).TotalSeconds;
        if (elapsed >= TimeoutSeconds)
        {
            _pollTimer.Stop();
            Log("[!!] Timed out after 30 minutes.", Color.Red);
            Invoke(() => _statusLabel.Text = "Timed out");
            return;
        }

        if (elapsed > 0 && elapsed % 15 == 0 && elapsed != _lastPollMessage)
        {
            _lastPollMessage = elapsed;
            Log($"... still loading ({elapsed}s elapsed)", Color.Yellow);
        }
    }

    private void OnReady()
    {
        Text = "Wan2GP - Ready";
        Log("", Color.White);
        Log("============================================", Color.LimeGreen);
        Log($"  Wan2GP is ready at {Url}", Color.LimeGreen);
        Log("============================================", Color.LimeGreen);
        Log("  Close this window .", Color.Yellow);
        Log("============================================", Color.LimeGreen);
        Log("", Color.White);

        Invoke(() => _statusLabel.Text = $"Ready at {Url}");

        OpenBrowser(Url);
    }

    private void OpenBrowser(string url)
    {
        try
        {
            Log($"    Opening browser: {url}", Color.Gray);
            Process.Start(new ProcessStartInfo(url) { UseShellExecute = true });
        }
        catch (Exception ex1)
        {
            Log($"    [!] Default browser failed: {ex1.Message}", Color.Yellow);
            try
            {
                Process.Start(new ProcessStartInfo
                {
                    FileName = "cmd",
                    Arguments = $"/c start {url}",
                    UseShellExecute = false,
                    CreateNoWindow = true,
                });
            }
            catch (Exception ex2)
            {
                Log($"[!] Could not open browser: {ex2.Message}", Color.Red);
            }
        }
    }

    private void MainForm_FormClosing(object? sender, FormClosingEventArgs e)
    {
        _pollTimer.Stop();
        if (_process != null && !_process.HasExited)
        {
            try { _process.Kill(entireProcessTree: true); }
            catch { }
        }
    }

    private void Log(string text, Color color)
    {
        if (InvokeRequired)
        {
            Invoke(() => Log(text, color));
            return;
        }

        _log.SelectionStart = _log.TextLength;
        _log.SelectionLength = 0;
        _log.SelectionColor = color;
        _log.AppendText(text + Environment.NewLine);
        _log.ScrollToCaret();
    }

    private static string? FindConda()
    {
        string? pathEnv = Environment.GetEnvironmentVariable("PATH");
        if (pathEnv != null)
        {
            foreach (string dir in pathEnv.Split(Path.PathSeparator))
            {
                try
                {
                    string candidate = Path.Combine(dir, "conda.exe");
                    if (File.Exists(candidate)) return candidate;

                    candidate = Path.Combine(dir, "conda.bat");
                    if (File.Exists(candidate)) return candidate;

                    candidate = Path.Combine(dir, "conda");
                    if (File.Exists(candidate)) return candidate;
                }
                catch { }
            }
        }

        string userProfile = Environment.GetFolderPath(Environment.SpecialFolder.UserProfile);
        string programData = Environment.GetFolderPath(Environment.SpecialFolder.CommonApplicationData);
        string programFiles = Environment.GetFolderPath(Environment.SpecialFolder.ProgramFiles);
        string localAppData = Environment.GetFolderPath(Environment.SpecialFolder.LocalApplicationData);

        string[] baseDirs =
        [
            Path.Combine(userProfile, "miniconda3"),
            Path.Combine(userProfile, "Anaconda3"),
            Path.Combine(programData, "miniconda3"),
            Path.Combine(programData, "Anaconda3"),
            Path.Combine(programFiles, "miniconda3"),
            Path.Combine(programFiles, "Anaconda3"),
            Path.Combine(localAppData, "miniconda3"),
            Path.Combine(localAppData, "Anaconda3"),
        ];

        foreach (string baseDir in baseDirs)
        {
            string candidate = Path.Combine(baseDir, "condabin", "conda.bat");
            if (File.Exists(candidate)) return candidate;

            candidate = Path.Combine(baseDir, "Scripts", "conda.exe");
            if (File.Exists(candidate)) return candidate;
        }

        return null;
    }

    private static string? GetPythonFromConda(string conda)
    {
        try
        {
            var psi = new ProcessStartInfo
            {
                FileName = conda,
                Arguments = "run -n wan2gp python -c \"import sys; print(sys.executable)\"",
                RedirectStandardOutput = true,
                RedirectStandardError = true,
                UseShellExecute = false,
                CreateNoWindow = true,
            };

            using Process proc = Process.Start(psi) ?? throw new InvalidOperationException("Process.Start returned null");
            string output = proc.StandardOutput.ReadToEnd();
            string stderr = proc.StandardError.ReadToEnd();
            proc.WaitForExit();

            if (proc.ExitCode != 0)
            {
                Console.WriteLine($"[DEBUG] conda run failed (code {proc.ExitCode}): {stderr}");
                return null;
            }
            return output.Trim();
        }
        catch (Exception ex)
        {
            Console.WriteLine($"[DEBUG] GetPythonFromConda exception: {ex.Message}");
            return null;
        }
    }

    private void EnsurePackage(string conda, string package)
    {
        try
        {
            var psi = new ProcessStartInfo
            {
                FileName = conda,
                Arguments = $"run -n wan2gp pip install {package}",
                RedirectStandardOutput = true,
                RedirectStandardError = true,
                UseShellExecute = false,
                CreateNoWindow = true,
            };

            using Process proc = Process.Start(psi) ?? throw new InvalidOperationException("Process.Start returned null");
            string stdout = proc.StandardOutput.ReadToEnd();
            string stderr = proc.StandardError.ReadToEnd();
            proc.WaitForExit();

            if (proc.ExitCode == 0)
            {
                if (stdout.Contains("Successfully installed") || stdout.Contains("already satisfied"))
                    Log($"  {package}: OK", Color.Gray);
            }
            else
            {
                Log($"  [!] {package} install failed: {stderr.Trim()}", Color.Yellow);
            }
        }
        catch (Exception ex)
        {
            Log($"  [!] {package} install error: {ex.Message}", Color.Yellow);
        }
    }

    private void ListCondaEnvs(string conda)
    {
        try
        {
            var psi = new ProcessStartInfo
            {
                FileName = conda,
                Arguments = "env list",
                RedirectStandardOutput = true,
                RedirectStandardError = true,
                UseShellExecute = false,
                CreateNoWindow = true,
            };

            using Process proc = Process.Start(psi) ?? throw new InvalidOperationException("Process.Start returned null");
            string output = proc.StandardOutput.ReadToEnd();
            proc.WaitForExit();

            Log("Available conda environments:", Color.Gray);
            foreach (string line in output.Split(Environment.NewLine))
            {
                if (!string.IsNullOrWhiteSpace(line) && !line.StartsWith("#"))
                    Log($"  {line.Trim()}", Color.Gray);
            }
        }
        catch (Exception ex)
        {
            Log($"  [!] Could not list environments: {ex.Message}", Color.Yellow);
        }
    }
}
