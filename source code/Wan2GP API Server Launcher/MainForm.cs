using System.Diagnostics;
using System.Net.NetworkInformation;
using System.Text;

namespace WanGP_Launcher
{
    public partial class MainForm : Form
    {
        private Process? _serverProcess;
        private readonly AppSettings _settings;
        private readonly System.Windows.Forms.Timer _statusTimer;

        public MainForm()
        {
            InitializeComponent();
            this.Icon = Icon.ExtractAssociatedIcon(Application.ExecutablePath);
            _settings = ConfigManager.Load();
            _statusTimer = new System.Windows.Forms.Timer { Interval = 1000 };
            _statusTimer.Tick += StatusTimer_Tick;

            AutoDetectConda();
            AutoDetectServerDir();
            LoadAuthFromFile();
            LoadSettingsToUI();
            UpdateServerStatus(false);

            if (!string.IsNullOrEmpty(_settings.CondaPath))
                AppendLog($"Conda detected: {_settings.CondaPath}");
            else
                AppendLog("Conda not found. Please specify the path manually.");

            string authFile = Path.Combine(AppDomain.CurrentDomain.BaseDirectory, ".api_auth.json");
            if (File.Exists(authFile))
                AppendLog("Auth loaded from .api_auth.json");

            if (!string.IsNullOrEmpty(_settings.ServerDirectory))
                AppendLog($"Server dir: {_settings.ServerDirectory}");
        }

        private void AutoDetectServerDir()
        {
            if (!string.IsNullOrEmpty(_settings.ServerDirectory) && Directory.Exists(_settings.ServerDirectory))
                return;

            string dir = AppDomain.CurrentDomain.BaseDirectory;
            for (int i = 0; i < 10; i++)
            {
                if (File.Exists(Path.Combine(dir, "api_server.py")))
                {
                    _settings.ServerDirectory = dir;
                    ConfigManager.Save(_settings);
                    return;
                }

                string? parent = Directory.GetParent(dir)?.FullName;
                if (string.IsNullOrEmpty(parent) || parent == dir)
                    break;
                dir = parent;
            }
        }

        private void LoadAuthFromFile()
        {
            string authFile = Path.Combine(AppDomain.CurrentDomain.BaseDirectory, ".api_auth.json");
            if (!File.Exists(authFile))
                return;

            try
            {
                string json = File.ReadAllText(authFile);
                using var doc = System.Text.Json.JsonDocument.Parse(json);
                var root = doc.RootElement;

                if (root.TryGetProperty("username", out var user))
                    _settings.ApiUsername = user.GetString() ?? "";
                if (root.TryGetProperty("password", out var pass))
                    _settings.ApiPassword = pass.GetString() ?? "";
            }
            catch { }
        }

        private void AutoDetectConda()
        {
            if (!string.IsNullOrEmpty(_settings.CondaPath) && File.Exists(_settings.CondaPath))
                return;

            string found = FindConda();
            if (!string.IsNullOrEmpty(found))
            {
                _settings.CondaPath = found;
                ConfigManager.Save(_settings);
            }
        }

        private void LoadSettingsToUI()
        {
            txtHost.Text = _settings.ApiHost;
            nudPort.Value = _settings.ApiPort;
            txtCondaEnv.Text = _settings.CondaEnvironment;
            txtCondaPath.Text = _settings.CondaPath;
            txtUsername.Text = _settings.ApiUsername;
            txtPassword.Text = _settings.ApiPassword;
            txtServerDir.Text = _settings.ServerDirectory;
            UpdateAuthUI();
        }

        private bool IsLocalhost()
        {
            string host = txtHost.Text.Trim().ToLowerInvariant();
            return host == "127.0.0.1" || host == "localhost" || host == "::1" || host == "[::1]";
        }

        private void UpdateAuthUI()
        {
            bool isLocalhost = IsLocalhost();
            bool authEnabled = !isLocalhost;

            txtUsername.Enabled = authEnabled;
            txtPassword.Enabled = authEnabled;
            btnTogglePass.Enabled = authEnabled;
            lblUsername.ForeColor = authEnabled ? Color.FromArgb(140, 140, 170) : Color.FromArgb(80, 80, 100);
            lblPassword.ForeColor = authEnabled ? Color.FromArgb(140, 140, 170) : Color.FromArgb(80, 80, 100);

            if (isLocalhost)
            {
                txtUsername.Text = "";
                txtPassword.Text = "";
            }
        }

        private void TxtHost_TextChanged(object? sender, EventArgs e)
        {
            UpdateAuthUI();
        }

        private void SaveSettingsFromUI()
        {
            _settings.ApiHost = txtHost.Text.Trim();
            _settings.ApiPort = (int)nudPort.Value;
            _settings.CondaEnvironment = txtCondaEnv.Text.Trim();
            _settings.CondaPath = txtCondaPath.Text.Trim();
            _settings.ApiUsername = txtUsername.Text.Trim();
            _settings.ApiPassword = txtPassword.Text.Trim();
            _settings.ServerDirectory = txtServerDir.Text.Trim();
            ConfigManager.Save(_settings);
        }

        private void BtnStart_Click(object? sender, EventArgs e)
        {
            if (_serverProcess != null && !_serverProcess.HasExited)
            {
                MessageBox.Show("Server is already running.", "Info", MessageBoxButtons.OK, MessageBoxIcon.Information);
                return;
            }

            SaveSettingsFromUI();

            string condaPath = FindConda();
            if (string.IsNullOrEmpty(condaPath))
            {
                AppendLog("ERROR: Conda not found. Set the path in settings.");
                MessageBox.Show("Conda not found. Please specify the path in settings.", "Error", MessageBoxButtons.OK, MessageBoxIcon.Error);
                return;
            }

            AppendLog($"Using conda: {condaPath}");

            string pythonPath = FindPythonInEnv(condaPath);
            if (string.IsNullOrEmpty(pythonPath) || !File.Exists(pythonPath))
            {
                AppendLog($"ERROR: Python not found in conda env '{_settings.CondaEnvironment}'.");
                AppendLog($"Looked in: {pythonPath}");
                MessageBox.Show($"Python not found in conda env '{_settings.CondaEnvironment}'.", "Error", MessageBoxButtons.OK, MessageBoxIcon.Error);
                return;
            }

            AppendLog($"Using python: {pythonPath}");

            if (string.IsNullOrEmpty(_settings.ServerDirectory))
            {
                _settings.ServerDirectory = AppDomain.CurrentDomain.BaseDirectory;
            }

            if (!Directory.Exists(_settings.ServerDirectory))
            {
                AppendLog($"ERROR: Server directory not found: {_settings.ServerDirectory}");
                MessageBox.Show($"Server directory not found: {_settings.ServerDirectory}", "Error", MessageBoxButtons.OK, MessageBoxIcon.Error);
                return;
            }

            string apiScript = Path.Combine(_settings.ServerDirectory, "api_server.py");
            if (!File.Exists(apiScript))
            {
                AppendLog($"ERROR: api_server.py not found in {_settings.ServerDirectory}");
                MessageBox.Show($"api_server.py not found in {_settings.ServerDirectory}", "Error", MessageBoxButtons.OK, MessageBoxIcon.Error);
                return;
            }

            try
            {
                var args = new StringBuilder();
                args.Append($"api_server.py");
                args.Append($" --host {_settings.ApiHost}");
                args.Append($" --port {_settings.ApiPort}");

                if (!IsLocalhost() && !string.IsNullOrEmpty(_settings.ApiUsername) && !string.IsNullOrEmpty(_settings.ApiPassword))
                {
                    args.Append($" --username {_settings.ApiUsername} --password {_settings.ApiPassword}");
                }

                if (IsLocalhost())
                    AppendLog("Auth skipped: host is localhost (127.0.0.1)");
                else if (!string.IsNullOrEmpty(_settings.ApiUsername) && !string.IsNullOrEmpty(_settings.ApiPassword))
                    AppendLog($"Auth enabled for {_settings.ApiHost}");

                AppendLog($"Starting server...");
                AppendLog($"Cmd: {pythonPath} {args}");
                AppendLog($"Dir: {_settings.ServerDirectory}");

                _serverProcess = new Process();
                _serverProcess.StartInfo.FileName = pythonPath;
                _serverProcess.StartInfo.Arguments = args.ToString();
                _serverProcess.StartInfo.UseShellExecute = false;
                _serverProcess.StartInfo.CreateNoWindow = true;
                _serverProcess.StartInfo.RedirectStandardOutput = true;
                _serverProcess.StartInfo.RedirectStandardError = true;
                _serverProcess.StartInfo.WorkingDirectory = _settings.ServerDirectory;
                _serverProcess.EnableRaisingEvents = true;
                _serverProcess.OutputDataReceived += Process_OutputDataReceived;
                _serverProcess.ErrorDataReceived += Process_ErrorDataReceived;
                _serverProcess.Exited += Process_Exited;
                _serverProcess.Start();
                _serverProcess.BeginOutputReadLine();
                _serverProcess.BeginErrorReadLine();

                UpdateServerStatus(true);
                _statusTimer.Start();
                AppendLog("Server launched in new window.");
            }
            catch (Exception ex)
            {
                AppendLog($"ERROR: {ex.Message}");
                MessageBox.Show($"Failed to start server: {ex.Message}", "Error", MessageBoxButtons.OK, MessageBoxIcon.Error);
            }
        }

        private void BtnStop_Click(object? sender, EventArgs e)
        {
            StopServer();
        }

        private void BtnClearLog_Click(object? sender, EventArgs e)
        {
            txtLog.Clear();
        }

        private void BtnBrowseConda_Click(object? sender, EventArgs e)
        {
            using var dialog = new OpenFileDialog
            {
                Filter = "Conda|conda.exe|All Files|*.*",
                Title = "Select conda.exe"
            };

            if (dialog.ShowDialog() == DialogResult.OK)
            {
                txtCondaPath.Text = dialog.FileName;
            }
        }

        private void BtnBrowseDir_Click(object? sender, EventArgs e)
        {
            using var dialog = new FolderBrowserDialog
            {
                Description = "Select API Server Directory"
            };

            if (dialog.ShowDialog() == DialogResult.OK)
            {
                txtServerDir.Text = dialog.SelectedPath;
            }
        }

        private void BtnTogglePass_Click(object? sender, EventArgs e)
        {
            txtPassword.UseSystemPasswordChar = !txtPassword.UseSystemPasswordChar;
            btnTogglePass.Text = txtPassword.UseSystemPasswordChar ? "Show" : "Hide";
        }

        private string FindConda()
        {
            if (!string.IsNullOrEmpty(_settings.CondaPath) && File.Exists(_settings.CondaPath))
                return _settings.CondaPath;

            string[] commonPaths = new[]
            {
                Path.Combine(Environment.GetFolderPath(Environment.SpecialFolder.UserProfile), "miniconda3", "Scripts", "conda.exe"),
                Path.Combine(Environment.GetFolderPath(Environment.SpecialFolder.UserProfile), "Miniconda3", "Scripts", "conda.exe"),
                Path.Combine(Environment.GetFolderPath(Environment.SpecialFolder.UserProfile), "anaconda3", "Scripts", "conda.exe"),
                Path.Combine(Environment.GetFolderPath(Environment.SpecialFolder.UserProfile), "Anaconda3", "Scripts", "conda.exe"),
                Path.Combine(Environment.GetFolderPath(Environment.SpecialFolder.LocalApplicationData), "miniconda3", "Scripts", "conda.exe"),
                Path.Combine(Environment.GetFolderPath(Environment.SpecialFolder.LocalApplicationData), "Miniconda3", "Scripts", "conda.exe"),
                @"C:\ProgramData\miniconda3\Scripts\conda.exe",
                @"C:\ProgramData\Miniconda3\Scripts\conda.exe",
                @"C:\ProgramData\anaconda3\Scripts\conda.exe",
                @"C:\ProgramData\Anaconda3\Scripts\conda.exe",
            };

            foreach (string path in commonPaths)
            {
                if (File.Exists(path))
                    return path;
            }

            string? pathEnv = Environment.GetEnvironmentVariable("PATH");
            if (!string.IsNullOrEmpty(pathEnv))
            {
                foreach (string dir in pathEnv.Split(Path.PathSeparator))
                {
                    string candidate = Path.Combine(dir.Trim(), "conda.exe");
                    if (File.Exists(candidate))
                        return candidate;
                }
            }

            try
            {
                var psi = new ProcessStartInfo("where", "conda.exe")
                {
                    UseShellExecute = false,
                    RedirectStandardOutput = true,
                    CreateNoWindow = true
                };
                using var proc = Process.Start(psi);
                if (proc != null)
                {
                    string output = proc.StandardOutput.ReadToEnd().Trim();
                    proc.WaitForExit();
                    if (proc.ExitCode == 0 && !string.IsNullOrEmpty(output))
                    {
                        return output.Split(new[] { '\r', '\n' }, StringSplitOptions.RemoveEmptyEntries)[0];
                    }
                }
            }
            catch { }

            return string.Empty;
        }

        private string BuildCommand()
        {
            var sb = new StringBuilder();
            sb.Append($"run -n {_settings.CondaEnvironment} --no-capture-output python api_server.py");
            sb.Append($" --host {_settings.ApiHost}");
            sb.Append($" --port {_settings.ApiPort}");

            if (!IsLocalhost() && !string.IsNullOrEmpty(_settings.ApiUsername) && !string.IsNullOrEmpty(_settings.ApiPassword))
            {
                sb.Append($" --username {_settings.ApiUsername} --password {_settings.ApiPassword}");
            }

            return sb.ToString();
        }

        private string FindPythonInEnv(string condaPath)
        {
            string condaBase = Path.GetDirectoryName(Path.GetDirectoryName(condaPath)) ?? "";
            if (string.IsNullOrEmpty(condaBase))
                return string.Empty;

            string pythonPath = Path.Combine(condaBase, "envs", _settings.CondaEnvironment, "python.exe");
            if (File.Exists(pythonPath))
                return pythonPath;

            pythonPath = Path.Combine(condaBase, "envs", _settings.CondaEnvironment, "python.bat");
            if (File.Exists(pythonPath))
                return pythonPath;

            string basePython = Path.Combine(condaBase, "python.exe");
            if (File.Exists(basePython))
                return basePython;

            return string.Empty;
        }

        private void StopServer()
        {
            _statusTimer.Stop();

            if (_serverProcess != null)
            {
                try
                {
                    if (!_serverProcess.HasExited)
                    {
                        int pid = _serverProcess.Id;
                        AppendLog($"Stopping server (PID: {pid})...");

                        TaskKill(pid);
                        _serverProcess.WaitForExit(5000);

                        if (!_serverProcess.HasExited)
                        {
                            AppendLog("Retrying kill with /F /T...");
                            RunCommand("taskkill", $"/F /PID {pid} /T");
                            _serverProcess.WaitForExit(5000);
                        }

                        if (!_serverProcess.HasExited)
                        {
                            AppendLog("Enumerating child processes...");
                            KillChildProcesses(pid);
                            _serverProcess.WaitForExit(3000);
                        }

                        if (!_serverProcess.HasExited)
                        {
                            AppendLog("Trying to kill by port...");
                            KillProcessByPort(_settings.ApiPort);
                            _serverProcess.WaitForExit(3000);
                        }

                        bool killed = _serverProcess.HasExited;
                        AppendLog(killed ? "Server stopped successfully." : "WARNING: Server may still be running. Check Task Manager.");
                    }
                    else
                    {
                        AppendLog("Server already exited.");
                    }
                }
                catch (Exception ex)
                {
                    AppendLog($"Error stopping server: {ex.Message}");
                }

                _serverProcess.Dispose();
                _serverProcess = null;
            }

            UpdateServerStatus(false);
        }

        private void TaskKill(int pid)
        {
            try
            {
                _serverProcess?.Kill(entireProcessTree: true);
            }
            catch
            {
                try { _serverProcess?.Kill(); } catch { }
                RunCommand("taskkill", $"/F /PID {pid} /T");
            }
        }

        private void RunCommand(string file, string args)
        {
            try
            {
                var psi = new ProcessStartInfo(file, args)
                {
                    UseShellExecute = false,
                    CreateNoWindow = true
                };
                using var p = Process.Start(psi);
                p?.WaitForExit(5000);
            }
            catch { }
        }

        private void KillChildProcesses(int parentPid)
        {
            try
            {
                var psi = new ProcessStartInfo("wmic", $"process where \"ParentProcessId={parentPid}\" get ProcessId")
                {
                    UseShellExecute = false,
                    RedirectStandardOutput = true,
                    CreateNoWindow = true
                };
                using var p = Process.Start(psi);
                if (p == null) return;

                string output = p.StandardOutput.ReadToEnd();
                p.WaitForExit();

                foreach (string line in output.Split(new[] { '\r', '\n' }, StringSplitOptions.RemoveEmptyEntries))
                {
                    string trimmed = line.Trim();
                    if (int.TryParse(trimmed, out int childPid) && childPid != parentPid)
                    {
                        AppendLog($"Killing child process {childPid}...");
                        RunCommand("taskkill", $"/F /PID {childPid} /T");
                    }
                }
            }
            catch { }
        }

        private void KillProcessByPort(int port)
        {
            try
            {
                var psi = new ProcessStartInfo("netstat", "-ano")
                {
                    UseShellExecute = false,
                    RedirectStandardOutput = true,
                    CreateNoWindow = true
                };
                using var p = Process.Start(psi);
                if (p == null) return;

                string output = p.StandardOutput.ReadToEnd();
                p.WaitForExit();

                string portStr = $":{port}";
                HashSet<int> pids = new();

                foreach (string line in output.Split(new[] { '\r', '\n' }, StringSplitOptions.RemoveEmptyEntries))
                {
                    if (line.Contains(portStr) && line.Contains("LISTENING"))
                    {
                        string[] parts = line.Split(new[] { ' ' }, StringSplitOptions.RemoveEmptyEntries);
                        if (parts.Length > 0)
                        {
                            string last = parts[^1];
                            if (int.TryParse(last, out int foundPid))
                                pids.Add(foundPid);
                        }
                    }
                }

                foreach (int foundPid in pids)
                {
                    AppendLog($"Killing process on port {port} (PID: {foundPid})...");
                    RunCommand("taskkill", $"/F /PID {foundPid} /T");
                }
            }
            catch { }
        }

        private void UpdateServerStatus(bool running)
        {
            bool isRunning = running && _serverProcess != null && !_serverProcess.HasExited;
            lblStatus.Text = isRunning ? "RUNNING" : "STOPPED";
            lblStatus.ForeColor = isRunning ? Color.FromArgb(80, 220, 120) : Color.FromArgb(255, 90, 90);
            lblStatusDot.BackColor = isRunning ? Color.FromArgb(80, 220, 120) : Color.FromArgb(255, 90, 90);
            btnStart.Enabled = !isRunning;
            btnStop.Enabled = isRunning;
        }

        private void Process_OutputDataReceived(object sender, DataReceivedEventArgs e)
        {
            if (!string.IsNullOrEmpty(e.Data))
            {
                BeginInvoke(() => AppendLog(e.Data));
            }
        }

        private void Process_ErrorDataReceived(object sender, DataReceivedEventArgs e)
        {
            if (!string.IsNullOrEmpty(e.Data))
            {
                BeginInvoke(() => AppendLog(e.Data));
            }
        }

        private void Process_Exited(object? sender, EventArgs e)
        {
            BeginInvoke(() =>
            {
                AppendLog("Server process exited.");
                UpdateServerStatus(false);
                _statusTimer.Stop();
            });
        }

        private void StatusTimer_Tick(object? sender, EventArgs e)
        {
            if (_serverProcess != null && _serverProcess.HasExited)
            {
                AppendLog($"Server exited with code {_serverProcess.ExitCode}.");
                UpdateServerStatus(false);
                _statusTimer.Stop();
            }
        }

        private void AppendLog(string message)
        {
            txtLog.AppendText($"[{DateTime.Now:HH:mm:ss}] {message}{Environment.NewLine}");
        }

        protected override void OnFormClosing(FormClosingEventArgs e)
        {
            StopServer();
            base.OnFormClosing(e);
        }
    }
}
