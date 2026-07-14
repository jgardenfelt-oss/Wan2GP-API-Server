using System.Diagnostics;

namespace Wan2GP_Installer;

public partial class InstallerForm : Form
{
    private bool isInstalling = false;
    private string installPath = "";
    private bool gitFound = false;
    private bool condaFound = false;
    private string? _gitPath = null;
    private string? _condaPath = null;

    public InstallerForm()
    {
        InitializeComponent();
        LoadIcon();
        Shown += InstallerForm_Shown;
    }

    private void LoadIcon()
    {
        try
        {
            string iconPath = Path.Combine(AppContext.BaseDirectory, "wan2gp.ico");
            if (File.Exists(iconPath))
                Icon = new Icon(iconPath);
        }
        catch { }
    }

    private async void InstallerForm_Shown(object? sender, EventArgs e)
    {
        await CheckPrerequisites();
    }

    private void GitLink_Click(object? sender, EventArgs e)
    {
        OpenUrl("https://git-scm.com/download/win");
    }

    private void CondaLink_Click(object? sender, EventArgs e)
    {
        OpenUrl("https://docs.conda.io/en/latest/miniconda.html");
    }

    private async void RefreshPrereqBtn_Click(object? sender, EventArgs e)
    {
        await CheckPrerequisites();
    }

    private void BrowseButton_Click(object? sender, EventArgs e)
    {
        using var dialog = new FolderBrowserDialog
        {
            Description = "Select installation directory",
            SelectedPath = pathTextBox.Text,
            ShowNewFolderButton = true
        };

        if (dialog.ShowDialog() == DialogResult.OK)
        {
            pathTextBox.Text = dialog.SelectedPath;
        }
    }

    private async void InstallButton_Click(object? sender, EventArgs e)
    {
        if (isInstalling) return;

        installPath = pathTextBox.Text;

        if (string.IsNullOrWhiteSpace(installPath))
        {
            MessageBox.Show("Please select an installation directory.", "Error",
                MessageBoxButtons.OK, MessageBoxIcon.Error);
            return;
        }

        isInstalling = true;
        installButton.Enabled = false;
        installButton.Text = "Installing...";
        installButton.BackColor = Color.FromArgb(60, 60, 80);
        browseButton.Enabled = false;
        progressBar.Visible = true;
        progressBar.Value = 0;
        progressBar.Maximum = 100;
        outputBox.Clear();

        try
        {
            await RunInstallation();
        }
        catch (Exception ex)
        {
            AppendOutput($"\n[ERROR] {ex.Message}", Color.FromArgb(255, 80, 80));
            UpdateStatus("Installation failed");
        }
        finally
        {
            isInstalling = false;
            installButton.Enabled = true;
            installButton.Text = "Install";
            installButton.BackColor = Color.FromArgb(99, 102, 241);
            browseButton.Enabled = true;
        }
    }

    private void LaunchButton_Click(object? sender, EventArgs e)
    {
        try
        {
            string envName = "wan2gp";
            string condaExe = _condaPath ?? "conda";
            string batContent = $"@echo off\r\ncall \"{condaExe.Trim('\"')}\" activate {envName}\r\ncd /d \"{installPath}\"\r\npython wgp.py\r\npause";
            string batPath = Path.Combine(installPath, "launch_wan2gp.bat");
            File.WriteAllText(batPath, batContent);
            Process.Start(new ProcessStartInfo
            {
                FileName = batPath,
                UseShellExecute = true,
                WorkingDirectory = installPath
            });
        }
        catch (Exception ex)
        {
            MessageBox.Show($"Failed to launch: {ex.Message}", "Error",
                MessageBoxButtons.OK, MessageBoxIcon.Error);
        }
    }

    private async Task RunInstallation()
    {
        string parentDir = Path.GetDirectoryName(installPath) ?? Path.GetPathRoot(Environment.CurrentDirectory) ?? "C:\\";
        string envName = "wan2gp";
        string condaExe = _condaPath ?? "conda";
        string condaBase = Path.GetDirectoryName(Path.GetDirectoryName(condaExe.Trim('"'))) ?? "";
        string envPython = Path.Combine(condaBase, "envs", envName, "python.exe");
        string envPip = Path.Combine(condaBase, "envs", envName, "Scripts", "pip.exe");

        AppendOutput("========================================", Color.FromArgb(99, 102, 241));
        AppendOutput("       Wan2GP Installer", Color.FromArgb(99, 102, 241));
        AppendOutput("========================================", Color.FromArgb(99, 102, 241));
        AppendOutput("", Color.FromArgb(180, 181, 196));
        AppendOutput($"Target: {installPath}", Color.FromArgb(180, 181, 196));
        AppendOutput("", Color.FromArgb(180, 181, 196));

        int totalSteps = 6;
        int currentStep = 0;

        // Step 1: Remove old env
        AppendOutput("[Step 1/6] Removing old environment...", Color.FromArgb(99, 102, 241));
        if (File.Exists(envPython))
        {
            var (removeOk, removeOut) = await RunCondaCommand($"env remove -n {envName} -y", installPath);
            AppendOutput(removeOut, Color.FromArgb(180, 181, 196));
            if (removeOk)
                AppendOutput("[OK] Old environment removed", Color.FromArgb(0, 200, 120));
            else
                AppendOutput("[WARN] Could not remove old env, continuing anyway", Color.FromArgb(255, 200, 80));
        }
        else
        {
            AppendOutput("[SKIP] No existing environment to remove", Color.FromArgb(180, 181, 196));
        }
        currentStep++;
        UpdateProgress(currentStep, totalSteps);

        // Step 2: Clone repo
        AppendOutput("", Color.FromArgb(180, 181, 196));
        AppendOutput("[Step 2/6] Cloning Wan2GP repository...", Color.FromArgb(99, 102, 241));
        if (Directory.Exists(installPath))
        {
            try { Directory.Delete(installPath, true); }
            catch { }
        }
        string gitExe = _gitPath ?? "git";
        var (cloneSuccess, cloneOutput) = await RunProcessAsync(gitExe, $"clone https://github.com/deepbeepmeep/Wan2GP.git \"{installPath}\"", parentDir);
        AppendOutput(cloneOutput, Color.FromArgb(180, 181, 196));

        if (!cloneSuccess)
        {
            AppendOutput("[ERROR] Failed to clone repository!", Color.FromArgb(255, 80, 80));
            UpdateStatus("Clone failed");
            return;
        }
        AppendOutput("[OK] Repository cloned successfully", Color.FromArgb(0, 200, 120));
        currentStep++;
        UpdateProgress(currentStep, totalSteps);

        // Step 3: Create conda env
        AppendOutput("", Color.FromArgb(180, 181, 196));
        AppendOutput("[Step 3/6] Creating conda environment 'wan2gp' (Python 3.11.14)...", Color.FromArgb(99, 102, 241));
        var (envSuccess, envOutput) = await RunCondaCommand($"create -n {envName} python=3.11.14 -y", installPath);
        AppendOutput(envOutput, Color.FromArgb(180, 181, 196));

        if (!envSuccess || !File.Exists(envPython))
        {
            AppendOutput("[ERROR] Failed to create conda environment!", Color.FromArgb(255, 80, 80));
            UpdateStatus("Conda env creation failed");
            return;
        }
        AppendOutput("[OK] Conda environment ready", Color.FromArgb(0, 200, 120));
        currentStep++;
        UpdateProgress(currentStep, totalSteps);

        // Step 4: Install PyTorch
        AppendOutput("", Color.FromArgb(180, 181, 196));
        AppendOutput("[Step 4/6] Installing PyTorch with CUDA support...", Color.FromArgb(99, 102, 241));
        var (torchSuccess, torchOutput) = await RunProcessAsync(
            envPip, "install torch==2.10.0 torchvision==0.25.0 torchaudio==2.10.0 --index-url https://download.pytorch.org/whl/cu130",
            installPath);
        AppendOutput(torchOutput, Color.FromArgb(180, 181, 196));

        if (!torchSuccess)
        {
            AppendOutput("[ERROR] Failed to install PyTorch!", Color.FromArgb(255, 80, 80));
            UpdateStatus("PyTorch installation failed");
            return;
        }
        AppendOutput("[OK] PyTorch installed", Color.FromArgb(0, 200, 120));
        currentStep++;
        UpdateProgress(currentStep, totalSteps);

        // Step 5: Install requirements
        AppendOutput("", Color.FromArgb(180, 181, 196));
        AppendOutput("[Step 5/6] Installing requirements...", Color.FromArgb(99, 102, 241));
        string requirementsPath = Path.Combine(installPath, "requirements.txt");
        if (!File.Exists(requirementsPath))
        {
            AppendOutput("[WARN] requirements.txt not found, skipping...", Color.FromArgb(255, 200, 80));
        }
        else
        {
            AppendOutput("[Step 5a/6] Fixing conda package conflicts...", Color.FromArgb(99, 102, 241));
            await RunProcessAsync(envPip, "install --force-reinstall --no-deps Pillow numpy", installPath);

            AppendOutput("[Step 5b/6] Installing requirements...", Color.FromArgb(99, 102, 241));
            var (reqSuccess, reqOutput) = await RunProcessAsync(
                envPip, "install -r requirements.txt",
                installPath);
            AppendOutput(reqOutput, Color.FromArgb(180, 181, 196));

            if (!reqSuccess)
            {
                AppendOutput("[WARN] Some packages may have failed, retrying with --ignore-installed...", Color.FromArgb(255, 200, 80));
                var (reqSuccess2, reqOutput2) = await RunProcessAsync(
                    envPip, "install --ignore-installed -r requirements.txt",
                    installPath);
                AppendOutput(reqOutput2, Color.FromArgb(180, 181, 196));
            }
        }
        AppendOutput("[OK] Requirements installed", Color.FromArgb(0, 200, 120));
        currentStep++;
        UpdateProgress(currentStep, totalSteps);

        // Step 6: Verify
        AppendOutput("", Color.FromArgb(180, 181, 196));
        AppendOutput("[Step 6/6] Verifying installation...", Color.FromArgb(99, 102, 241));
        var (verifySuccess, verifyOutput) = await RunProcessAsync(envPython, "-c \"import torch; print('PyTorch ' + torch.__version__ + ' - CUDA available: ' + str(torch.cuda.is_available()))\"", installPath);
        AppendOutput(verifyOutput, Color.FromArgb(180, 181, 196));

        if (!verifySuccess)
        {
            AppendOutput("", Color.FromArgb(255, 200, 80));
            AppendOutput("[WARN] PyTorch import failed - this is usually a CUDA driver issue.", Color.FromArgb(255, 200, 80));
            AppendOutput("Your NVIDIA driver may be too old for CUDA 13.0 (PyTorch 2.10).", Color.FromArgb(255, 200, 80));
            AppendOutput("Update your NVIDIA driver from: https://www.nvidia.com/Download/index.aspx", Color.FromArgb(99, 102, 241));
            AppendOutput("", Color.FromArgb(255, 200, 80));
            AppendOutput("If that does not help, try installing an older PyTorch for your GPU:", Color.FromArgb(255, 200, 80));
            AppendOutput($"  {envPip} install torch==2.7.1 torchvision==0.22.1 torchaudio==2.7.1 --index-url https://download.pytorch.org/whl/test/cu128", Color.FromArgb(99, 102, 241));
        }

        AppendOutput("", Color.FromArgb(180, 181, 196));
        AppendOutput("========================================", Color.FromArgb(0, 200, 120));
        AppendOutput("  Installation Complete!", Color.FromArgb(0, 200, 120));
        AppendOutput("========================================", Color.FromArgb(0, 200, 120));
        AppendOutput("", Color.FromArgb(180, 181, 196));
        AppendOutput("To run Wan2GP:", Color.FromArgb(200, 200, 210));
        AppendOutput($"  cd {installPath}", Color.FromArgb(99, 102, 241));
        AppendOutput($"  conda activate {envName}", Color.FromArgb(99, 102, 241));
        AppendOutput("  python wgp.py", Color.FromArgb(99, 102, 241));
        AppendOutput("", Color.FromArgb(180, 181, 196));

        progressBar.Value = 100;
        UpdateStatus("Installation complete!");
        if (InvokeRequired)
            Invoke(() => launchButton.Visible = false);
        else
            launchButton.Visible = false;
    }

    private async Task CheckPrerequisites()
    {
        gitFound = await CheckCommand("git", "--version");
        condaFound = await CheckCommand("conda", "--version");

        if (InvokeRequired)
        {
            Invoke(UpdatePrereqUI);
            return;
        }
        UpdatePrereqUI();
    }

    private void UpdatePrereqUI()
    {
        gitLink.Visible = false;
        condaLink.Visible = false;

        if (gitFound)
        {
            gitStatus.Text = "Git";
            gitStatus.ForeColor = Color.FromArgb(0, 200, 120);
        }
        else
        {
            gitStatus.Text = "Git";
            gitStatus.ForeColor = Color.FromArgb(255, 80, 80);
            gitLink.Text = "Download from git-scm.com";
            gitLink.Visible = true;
        }

        if (condaFound)
        {
            condaStatus.Text = "Conda (Anaconda / Miniconda)";
            condaStatus.ForeColor = Color.FromArgb(0, 200, 120);
        }
        else
        {
            condaStatus.Text = "Conda (Anaconda / Miniconda)";
            condaStatus.ForeColor = Color.FromArgb(255, 80, 80);
            condaLink.Text = "Download Miniconda";
            condaLink.Visible = true;
        }

        installButton.Enabled = gitFound && condaFound;

        if (gitFound && condaFound)
        {
            statusLabel.Text = "All prerequisites met - Ready to install";
            statusLabel.ForeColor = Color.FromArgb(0, 200, 120);
        }
        else
        {
            var missing = new List<string>();
            if (!gitFound) missing.Add("Git");
            if (!condaFound) missing.Add("Conda");
            statusLabel.Text = $"Missing: {string.Join(", ", missing)} - Please install to continue";
            statusLabel.ForeColor = Color.FromArgb(255, 180, 80);
        }
    }

    private void OpenUrl(string url)
    {
        try
        {
            Process.Start(new ProcessStartInfo
            {
                FileName = url,
                UseShellExecute = true
            });
        }
        catch { }
    }

    private async Task<bool> CheckCommand(string command, string args)
    {
        if (await TryCommand(command, args)) return true;
        if (await TryCommand("cmd.exe", $"/c {command} {args}")) return true;

        if (command.Equals("conda", StringComparison.OrdinalIgnoreCase))
        {
            string userProfile = Environment.GetFolderPath(Environment.SpecialFolder.UserProfile);
            string[] condaPaths = new[]
            {
                Path.Combine(userProfile, "miniconda3", "condabin", "conda.bat"),
                Path.Combine(userProfile, "anaconda3", "condabin", "conda.bat"),
                Path.Combine(userProfile, "miniconda3", "Scripts", "conda.exe"),
                Path.Combine(userProfile, "anaconda3", "Scripts", "conda.exe"),
                Path.Combine(userProfile, "Miniconda3", "condabin", "conda.bat"),
                Path.Combine(userProfile, "Anaconda3", "condabin", "conda.bat"),
                @"C:\ProgramData\miniconda3\condabin\conda.bat",
                @"C:\ProgramData\anaconda3\condabin\conda.bat",
                @"C:\ProgramData\Miniconda3\condabin\conda.bat",
                @"C:\ProgramData\Anaconda3\condabin\conda.bat",
            };

            foreach (string path in condaPaths)
            {
                if (File.Exists(path))
                {
                    _condaPath = path;
                    return true;
                }
            }
        }

        if (command.Equals("git", StringComparison.OrdinalIgnoreCase))
        {
            string[] gitPaths = new[]
            {
                @"C:\Program Files\Git\bin\git.exe",
                @"C:\Program Files (x86)\Git\bin\git.exe",
                Path.Combine(Environment.GetFolderPath(Environment.SpecialFolder.LocalApplicationData), "Programs", "Git", "bin", "git.exe"),
            };

            foreach (string path in gitPaths)
            {
                if (File.Exists(path))
                {
                    _gitPath = path;
                    return true;
                }
            }
        }

        return false;
    }

    private async Task<(bool success, string output)> RunCondaCommand(string args, string workDir)
    {
        string condaExe = _condaPath ?? "conda";
        string batContent = $"@echo off\r\n\"{condaExe.Trim('"')}\" {args}";
        string batPath = Path.Combine(Path.GetTempPath(), "wan2gp_conda.bat");
        File.WriteAllText(batPath, batContent);
        try
        {
            return await RunProcessAsync(batPath, "", workDir);
        }
        finally
        {
            try { File.Delete(batPath); } catch { }
        }
    }

    private async Task<bool> TryCommand(string fileName, string args)
    {
        try
        {
            using var process = new Process();
            process.StartInfo = new ProcessStartInfo
            {
                FileName = fileName,
                Arguments = args,
                UseShellExecute = false,
                RedirectStandardOutput = true,
                RedirectStandardError = true,
                CreateNoWindow = true
            };
            process.Start();
            await process.WaitForExitAsync();
            return process.ExitCode == 0;
        }
        catch
        {
            return false;
        }
    }

    private async Task<(bool success, string output)> RunProcessAsync(string fileName, string arguments, string workDir)
    {
        var output = new System.Text.StringBuilder();

        try
        {
            using var process = new Process();
            process.StartInfo = new ProcessStartInfo
            {
                FileName = fileName,
                Arguments = arguments,
                WorkingDirectory = workDir,
                UseShellExecute = false,
                RedirectStandardOutput = true,
                RedirectStandardError = true,
                CreateNoWindow = true,
                StandardOutputEncoding = System.Text.Encoding.UTF8,
                StandardErrorEncoding = System.Text.Encoding.UTF8
            };

            string certPath = Path.Combine(
                Environment.GetFolderPath(Environment.SpecialFolder.UserProfile),
                "miniconda3", "Library", "ssl", "cacert.pem");
            if (!File.Exists(certPath))
                certPath = Path.Combine(
                    Environment.GetFolderPath(Environment.SpecialFolder.UserProfile),
                    "anaconda3", "Library", "ssl", "cacert.pem");
            if (File.Exists(certPath))
            {
                process.StartInfo.EnvironmentVariables["SSL_CERT_FILE"] = certPath;
                process.StartInfo.EnvironmentVariables["PIP_CERT"] = certPath;
            }

            process.OutputDataReceived += (s, e) =>
            {
                if (e.Data != null)
                {
                    AppendOutput(e.Data, Color.FromArgb(180, 181, 196));
                }
            };

            process.ErrorDataReceived += (s, e) =>
            {
                if (e.Data != null && !e.Data.Contains("WARNING") && !e.Data.Contains("warning"))
                {
                    AppendOutput(e.Data, Color.FromArgb(200, 180, 120));
                }
            };

            process.Start();
            process.BeginOutputReadLine();
            process.BeginErrorReadLine();
            await process.WaitForExitAsync();

            return (process.ExitCode == 0, output.ToString());
        }
        catch (Exception ex)
        {
            return (false, ex.Message);
        }
    }

    private void AppendOutput(string text, Color color)
    {
        if (InvokeRequired)
        {
            Invoke(() => AppendOutput(text, color));
            return;
        }

        outputBox.SelectionStart = outputBox.TextLength;
        outputBox.SelectionLength = 0;
        outputBox.SelectionColor = color;
        outputBox.AppendText(text + "\n");
        outputBox.SelectionStart = outputBox.TextLength;
        outputBox.ScrollToCaret();
    }

    private void UpdateProgress(int current, int total)
    {
        if (InvokeRequired)
        {
            Invoke(() => UpdateProgress(current, total));
            return;
        }
        progressBar.Value = (int)((current / (double)total) * 100);
    }

    private void UpdateStatus(string text)
    {
        if (InvokeRequired)
        {
            Invoke(() => UpdateStatus(text));
            return;
        }
        statusLabel.Text = text;
    }
}
