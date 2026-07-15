namespace WanGP_Launcher
{
    partial class MainForm
    {
        private System.ComponentModel.IContainer components = null;

        protected override void Dispose(bool disposing)
        {
            if (disposing && (components != null))
            {
                components.Dispose();
            }
            base.Dispose(disposing);
        }

        private void InitializeComponent()
        {
            System.ComponentModel.ComponentResourceManager resources = new System.ComponentModel.ComponentResourceManager(typeof(MainForm));
            txtHost = new TextBox();
            nudPort = new NumericUpDown();
            txtCondaEnv = new TextBox();
            txtCondaPath = new TextBox();
            txtUsername = new TextBox();
            txtPassword = new TextBox();
            txtServerDir = new TextBox();
            txtLog = new TextBox();
            btnStart = new Button();
            btnStop = new Button();
            btnClearLog = new Button();
            btnBrowseConda = new Button();
            btnBrowseDir = new Button();
            btnTogglePass = new Button();
            lblStatus = new Label();
            lblStatusDot = new Label();
            headerPanel = new Panel();
            lblTitle = new Label();
            lblSubtitle = new Label();
            settingsPanel = new Panel();
            lblSettingsTitle = new Label();
            separator1 = new Label();
            lblHost = new Label();
            lblPort = new Label();
            lblCondaEnv = new Label();
            lblCondaPath = new Label();
            lblUsername = new Label();
            lblPassword = new Label();
            lblServerDir = new Label();
            actionsPanel = new Panel();
            lblStatusTitle = new Label();
            logPanel = new Panel();
            lblLogTitle = new Label();
            bottomPanel = new Panel();
            lblVersion = new Label();
            ((System.ComponentModel.ISupportInitialize)nudPort).BeginInit();
            headerPanel.SuspendLayout();
            settingsPanel.SuspendLayout();
            actionsPanel.SuspendLayout();
            logPanel.SuspendLayout();
            bottomPanel.SuspendLayout();
            SuspendLayout();
            // 
            // txtHost
            // 
            txtHost.BackColor = Color.FromArgb(38, 38, 58);
            txtHost.BorderStyle = BorderStyle.None;
            txtHost.Font = new Font("Segoe UI Semibold", 10F);
            txtHost.ForeColor = Color.FromArgb(230, 230, 245);
            txtHost.Location = new Point(120, 52);
            txtHost.Name = "txtHost";
            txtHost.Size = new Size(200, 18);
            txtHost.TabIndex = 3;
            txtHost.Text = "127.0.0.1";
            txtHost.TextChanged += TxtHost_TextChanged;
            // 
            // nudPort
            // 
            nudPort.BackColor = Color.FromArgb(38, 38, 58);
            nudPort.Font = new Font("Segoe UI Semibold", 10F);
            nudPort.ForeColor = Color.FromArgb(230, 230, 245);
            nudPort.Location = new Point(436, 52);
            nudPort.Maximum = new decimal(new int[] { 65535, 0, 0, 0 });
            nudPort.Minimum = new decimal(new int[] { 1, 0, 0, 0 });
            nudPort.Name = "nudPort";
            nudPort.Size = new Size(100, 25);
            nudPort.TabIndex = 5;
            nudPort.Value = new decimal(new int[] { 8001, 0, 0, 0 });
            // 
            // txtCondaEnv
            // 
            txtCondaEnv.BackColor = Color.FromArgb(38, 38, 58);
            txtCondaEnv.BorderStyle = BorderStyle.None;
            txtCondaEnv.Font = new Font("Segoe UI Semibold", 10F);
            txtCondaEnv.ForeColor = Color.FromArgb(230, 230, 245);
            txtCondaEnv.Location = new Point(120, 90);
            txtCondaEnv.Name = "txtCondaEnv";
            txtCondaEnv.Size = new Size(200, 18);
            txtCondaEnv.TabIndex = 7;
            txtCondaEnv.Text = "wan2gp";
            // 
            // txtCondaPath
            // 
            txtCondaPath.BackColor = Color.FromArgb(38, 38, 58);
            txtCondaPath.BorderStyle = BorderStyle.None;
            txtCondaPath.Font = new Font("Segoe UI Semibold", 10F);
            txtCondaPath.ForeColor = Color.FromArgb(230, 230, 245);
            txtCondaPath.Location = new Point(436, 90);
            txtCondaPath.Name = "txtCondaPath";
            txtCondaPath.Size = new Size(267, 18);
            txtCondaPath.TabIndex = 9;
            // 
            // txtUsername
            // 
            txtUsername.BackColor = Color.FromArgb(38, 38, 58);
            txtUsername.BorderStyle = BorderStyle.None;
            txtUsername.Font = new Font("Segoe UI Semibold", 10F);
            txtUsername.ForeColor = Color.FromArgb(230, 230, 245);
            txtUsername.Location = new Point(120, 128);
            txtUsername.Name = "txtUsername";
            txtUsername.Size = new Size(200, 18);
            txtUsername.TabIndex = 12;
            // 
            // txtPassword
            // 
            txtPassword.BackColor = Color.FromArgb(38, 38, 58);
            txtPassword.BorderStyle = BorderStyle.None;
            txtPassword.Font = new Font("Segoe UI Semibold", 10F);
            txtPassword.ForeColor = Color.FromArgb(230, 230, 245);
            txtPassword.Location = new Point(436, 128);
            txtPassword.Name = "txtPassword";
            txtPassword.Size = new Size(267, 18);
            txtPassword.TabIndex = 14;
            txtPassword.UseSystemPasswordChar = true;
            // 
            // txtServerDir
            // 
            txtServerDir.BackColor = Color.FromArgb(38, 38, 58);
            txtServerDir.BorderStyle = BorderStyle.None;
            txtServerDir.Font = new Font("Segoe UI Semibold", 10F);
            txtServerDir.ForeColor = Color.FromArgb(230, 230, 245);
            txtServerDir.Location = new Point(120, 166);
            txtServerDir.Name = "txtServerDir";
            txtServerDir.Size = new Size(583, 18);
            txtServerDir.TabIndex = 17;
            // 
            // txtLog
            // 
            txtLog.BackColor = Color.FromArgb(14, 14, 22);
            txtLog.BorderStyle = BorderStyle.None;
            txtLog.Dock = DockStyle.Fill;
            txtLog.Font = new Font("Cascadia Code", 9.5F);
            txtLog.ForeColor = Color.FromArgb(160, 210, 160);
            txtLog.Location = new Point(0, 0);
            txtLog.Multiline = true;
            txtLog.Name = "txtLog";
            txtLog.ReadOnly = true;
            txtLog.ScrollBars = ScrollBars.Both;
            txtLog.Size = new Size(1183, 280);
            txtLog.TabIndex = 0;
            txtLog.WordWrap = false;
            // 
            // btnStart
            // 
            btnStart.BackColor = Color.FromArgb(80, 220, 120);
            btnStart.Cursor = Cursors.Hand;
            btnStart.FlatAppearance.BorderSize = 0;
            btnStart.FlatAppearance.MouseOverBackColor = Color.FromArgb(60, 200, 100);
            btnStart.FlatStyle = FlatStyle.Flat;
            btnStart.Font = new Font("Segoe UI Semibold", 10F);
            btnStart.ForeColor = Color.White;
            btnStart.Location = new Point(24, 12);
            btnStart.Name = "btnStart";
            btnStart.Size = new Size(150, 38);
            btnStart.TabIndex = 0;
            btnStart.Text = "  Start Server";
            btnStart.UseVisualStyleBackColor = false;
            btnStart.Click += BtnStart_Click;
            // 
            // btnStop
            // 
            btnStop.BackColor = Color.FromArgb(255, 90, 90);
            btnStop.Cursor = Cursors.Hand;
            btnStop.FlatAppearance.BorderSize = 0;
            btnStop.FlatAppearance.MouseOverBackColor = Color.FromArgb(220, 70, 70);
            btnStop.FlatStyle = FlatStyle.Flat;
            btnStop.Font = new Font("Segoe UI Semibold", 10F);
            btnStop.ForeColor = Color.White;
            btnStop.Location = new Point(186, 12);
            btnStop.Name = "btnStop";
            btnStop.Size = new Size(150, 38);
            btnStop.TabIndex = 1;
            btnStop.Text = "  Stop Server";
            btnStop.UseVisualStyleBackColor = false;
            btnStop.Click += BtnStop_Click;
            // 
            // btnClearLog
            // 
            btnClearLog.BackColor = Color.FromArgb(38, 38, 58);
            btnClearLog.Cursor = Cursors.Hand;
            btnClearLog.FlatAppearance.BorderSize = 0;
            btnClearLog.FlatAppearance.MouseOverBackColor = Color.FromArgb(55, 55, 80);
            btnClearLog.FlatStyle = FlatStyle.Flat;
            btnClearLog.Font = new Font("Segoe UI Semibold", 10F);
            btnClearLog.ForeColor = Color.White;
            btnClearLog.Location = new Point(348, 12);
            btnClearLog.Name = "btnClearLog";
            btnClearLog.Size = new Size(130, 38);
            btnClearLog.TabIndex = 2;
            btnClearLog.Text = "  Clear Log";
            btnClearLog.UseVisualStyleBackColor = false;
            btnClearLog.Click += BtnClearLog_Click;
            // 
            // btnBrowseConda
            // 
            btnBrowseConda.BackColor = Color.FromArgb(38, 38, 58);
            btnBrowseConda.Cursor = Cursors.Hand;
            btnBrowseConda.FlatAppearance.BorderSize = 0;
            btnBrowseConda.FlatAppearance.MouseOverBackColor = Color.FromArgb(55, 55, 80);
            btnBrowseConda.FlatStyle = FlatStyle.Flat;
            btnBrowseConda.Font = new Font("Segoe UI", 10F, FontStyle.Bold);
            btnBrowseConda.ForeColor = Color.White;
            btnBrowseConda.Location = new Point(729, 88);
            btnBrowseConda.Name = "btnBrowseConda";
            btnBrowseConda.Size = new Size(30, 28);
            btnBrowseConda.TabIndex = 10;
            btnBrowseConda.Text = "...";
            btnBrowseConda.UseVisualStyleBackColor = false;
            btnBrowseConda.Click += BtnBrowseConda_Click;
            // 
            // btnBrowseDir
            // 
            btnBrowseDir.BackColor = Color.FromArgb(38, 38, 58);
            btnBrowseDir.Cursor = Cursors.Hand;
            btnBrowseDir.FlatAppearance.BorderSize = 0;
            btnBrowseDir.FlatAppearance.MouseOverBackColor = Color.FromArgb(55, 55, 80);
            btnBrowseDir.FlatStyle = FlatStyle.Flat;
            btnBrowseDir.Font = new Font("Segoe UI", 10F, FontStyle.Bold);
            btnBrowseDir.ForeColor = Color.White;
            btnBrowseDir.Location = new Point(729, 166);
            btnBrowseDir.Name = "btnBrowseDir";
            btnBrowseDir.Size = new Size(30, 28);
            btnBrowseDir.TabIndex = 18;
            btnBrowseDir.Text = "...";
            btnBrowseDir.UseVisualStyleBackColor = false;
            btnBrowseDir.Click += BtnBrowseDir_Click;
            // 
            // btnTogglePass
            // 
            btnTogglePass.BackColor = Color.FromArgb(38, 38, 58);
            btnTogglePass.Cursor = Cursors.Hand;
            btnTogglePass.FlatAppearance.BorderSize = 0;
            btnTogglePass.FlatAppearance.MouseOverBackColor = Color.FromArgb(55, 55, 80);
            btnTogglePass.FlatStyle = FlatStyle.Flat;
            btnTogglePass.Font = new Font("Segoe UI", 8.5F);
            btnTogglePass.ForeColor = Color.White;
            btnTogglePass.Location = new Point(729, 123);
            btnTogglePass.Name = "btnTogglePass";
            btnTogglePass.Size = new Size(50, 28);
            btnTogglePass.TabIndex = 15;
            btnTogglePass.Text = "Show";
            btnTogglePass.UseVisualStyleBackColor = false;
            btnTogglePass.Click += BtnTogglePass_Click;
            // 
            // lblStatus
            // 
            lblStatus.Anchor = AnchorStyles.Top | AnchorStyles.Right;
            lblStatus.AutoSize = true;
            lblStatus.Font = new Font("Segoe UI", 9F, FontStyle.Bold);
            lblStatus.ForeColor = Color.FromArgb(255, 90, 90);
            lblStatus.Location = new Point(1114, 25);
            lblStatus.Name = "lblStatus";
            lblStatus.Size = new Size(59, 15);
            lblStatus.TabIndex = 5;
            lblStatus.Text = "STOPPED";
            lblStatus.TextAlign = ContentAlignment.MiddleRight;
            // 
            // lblStatusDot
            // 
            lblStatusDot.Anchor = AnchorStyles.Top | AnchorStyles.Right;
            lblStatusDot.BackColor = Color.FromArgb(255, 90, 90);
            lblStatusDot.Location = new Point(1098, 26);
            lblStatusDot.Name = "lblStatusDot";
            lblStatusDot.Size = new Size(10, 10);
            lblStatusDot.TabIndex = 4;
            lblStatusDot.TextAlign = ContentAlignment.MiddleRight;
            // 
            // headerPanel
            // 
            headerPanel.BackColor = Color.FromArgb(28, 28, 45);
            headerPanel.Controls.Add(lblTitle);
            headerPanel.Controls.Add(lblSubtitle);
            headerPanel.Dock = DockStyle.Top;
            headerPanel.Location = new Point(0, 0);
            headerPanel.Name = "headerPanel";
            headerPanel.Size = new Size(1183, 80);
            headerPanel.TabIndex = 4;
            // 
            // lblTitle
            // 
            lblTitle.AutoSize = true;
            lblTitle.Font = new Font("Segoe UI", 18F, FontStyle.Bold);
            lblTitle.ForeColor = Color.FromArgb(230, 230, 245);
            lblTitle.Location = new Point(24, 12);
            lblTitle.Name = "lblTitle";
            lblTitle.Size = new Size(238, 32);
            lblTitle.TabIndex = 0;
            lblTitle.Text = "Wan2GP API Server";
            // 
            // lblSubtitle
            // 
            lblSubtitle.AutoSize = true;
            lblSubtitle.Font = new Font("Segoe UI", 9.5F);
            lblSubtitle.ForeColor = Color.FromArgb(140, 140, 170);
            lblSubtitle.Location = new Point(26, 48);
            lblSubtitle.Name = "lblSubtitle";
            lblSubtitle.Size = new Size(234, 17);
            lblSubtitle.TabIndex = 1;
            lblSubtitle.Text = "Configure and manage your API server";
            // 
            // settingsPanel
            // 
            settingsPanel.BackColor = Color.FromArgb(18, 18, 30);
            settingsPanel.Controls.Add(lblSettingsTitle);
            settingsPanel.Controls.Add(separator1);
            settingsPanel.Controls.Add(lblHost);
            settingsPanel.Controls.Add(txtHost);
            settingsPanel.Controls.Add(lblPort);
            settingsPanel.Controls.Add(nudPort);
            settingsPanel.Controls.Add(lblCondaEnv);
            settingsPanel.Controls.Add(txtCondaEnv);
            settingsPanel.Controls.Add(lblCondaPath);
            settingsPanel.Controls.Add(txtCondaPath);
            settingsPanel.Controls.Add(btnBrowseConda);
            settingsPanel.Controls.Add(lblUsername);
            settingsPanel.Controls.Add(txtUsername);
            settingsPanel.Controls.Add(lblPassword);
            settingsPanel.Controls.Add(txtPassword);
            settingsPanel.Controls.Add(btnTogglePass);
            settingsPanel.Controls.Add(lblServerDir);
            settingsPanel.Controls.Add(txtServerDir);
            settingsPanel.Controls.Add(btnBrowseDir);
            settingsPanel.Dock = DockStyle.Top;
            settingsPanel.Location = new Point(0, 80);
            settingsPanel.Name = "settingsPanel";
            settingsPanel.Size = new Size(1183, 202);
            settingsPanel.TabIndex = 3;
            // 
            // lblSettingsTitle
            // 
            lblSettingsTitle.AutoSize = true;
            lblSettingsTitle.Font = new Font("Segoe UI", 8.5F, FontStyle.Bold);
            lblSettingsTitle.ForeColor = Color.FromArgb(99, 140, 255);
            lblSettingsTitle.Location = new Point(24, 14);
            lblSettingsTitle.Name = "lblSettingsTitle";
            lblSettingsTitle.Size = new Size(150, 15);
            lblSettingsTitle.TabIndex = 0;
            lblSettingsTitle.Text = "SERVER CONFIGURATION";
            // 
            // separator1
            // 
            separator1.BorderStyle = BorderStyle.Fixed3D;
            separator1.Location = new Point(24, 38);
            separator1.Name = "separator1";
            separator1.Size = new Size(620, 2);
            separator1.TabIndex = 1;
            // 
            // lblHost
            // 
            lblHost.AutoSize = true;
            lblHost.Font = new Font("Segoe UI Semibold", 9.5F);
            lblHost.ForeColor = Color.FromArgb(140, 140, 170);
            lblHost.Location = new Point(24, 54);
            lblHost.Name = "lblHost";
            lblHost.Size = new Size(37, 17);
            lblHost.TabIndex = 2;
            lblHost.Text = "Host";
            // 
            // lblPort
            // 
            lblPort.AutoSize = true;
            lblPort.Font = new Font("Segoe UI Semibold", 9.5F);
            lblPort.ForeColor = Color.FromArgb(140, 140, 170);
            lblPort.Location = new Point(385, 54);
            lblPort.Name = "lblPort";
            lblPort.Size = new Size(34, 17);
            lblPort.TabIndex = 4;
            lblPort.Text = "Port";
            // 
            // lblCondaEnv
            // 
            lblCondaEnv.AutoSize = true;
            lblCondaEnv.Font = new Font("Segoe UI Semibold", 9.5F);
            lblCondaEnv.ForeColor = Color.FromArgb(140, 140, 170);
            lblCondaEnv.Location = new Point(24, 90);
            lblCondaEnv.Name = "lblCondaEnv";
            lblCondaEnv.Size = new Size(73, 17);
            lblCondaEnv.TabIndex = 6;
            lblCondaEnv.Text = "Conda Env";
            // 
            // lblCondaPath
            // 
            lblCondaPath.AutoSize = true;
            lblCondaPath.Font = new Font("Segoe UI Semibold", 9.5F);
            lblCondaPath.ForeColor = Color.FromArgb(140, 140, 170);
            lblCondaPath.Location = new Point(340, 88);
            lblCondaPath.Name = "lblCondaPath";
            lblCondaPath.Size = new Size(79, 17);
            lblCondaPath.TabIndex = 8;
            lblCondaPath.Text = "Conda Path";
            // 
            // lblUsername
            // 
            lblUsername.AutoSize = true;
            lblUsername.Font = new Font("Segoe UI Semibold", 9.5F);
            lblUsername.ForeColor = Color.FromArgb(140, 140, 170);
            lblUsername.Location = new Point(24, 128);
            lblUsername.Name = "lblUsername";
            lblUsername.Size = new Size(69, 17);
            lblUsername.TabIndex = 11;
            lblUsername.Text = "Username";
            // 
            // lblPassword
            // 
            lblPassword.AutoSize = true;
            lblPassword.Font = new Font("Segoe UI Semibold", 9.5F);
            lblPassword.ForeColor = Color.FromArgb(140, 140, 170);
            lblPassword.Location = new Point(348, 128);
            lblPassword.Name = "lblPassword";
            lblPassword.Size = new Size(66, 17);
            lblPassword.TabIndex = 13;
            lblPassword.Text = "Password";
            // 
            // lblServerDir
            // 
            lblServerDir.AutoSize = true;
            lblServerDir.Font = new Font("Segoe UI Semibold", 9.5F);
            lblServerDir.ForeColor = Color.FromArgb(140, 140, 170);
            lblServerDir.Location = new Point(24, 166);
            lblServerDir.Name = "lblServerDir";
            lblServerDir.Size = new Size(68, 17);
            lblServerDir.TabIndex = 16;
            lblServerDir.Text = "Server Dir";
            // 
            // actionsPanel
            // 
            actionsPanel.BackColor = Color.FromArgb(18, 18, 30);
            actionsPanel.Controls.Add(btnStart);
            actionsPanel.Controls.Add(btnStop);
            actionsPanel.Controls.Add(btnClearLog);
            actionsPanel.Controls.Add(lblStatusTitle);
            actionsPanel.Controls.Add(lblStatusDot);
            actionsPanel.Controls.Add(lblStatus);
            actionsPanel.Dock = DockStyle.Top;
            actionsPanel.Location = new Point(0, 282);
            actionsPanel.Name = "actionsPanel";
            actionsPanel.Size = new Size(1183, 60);
            actionsPanel.TabIndex = 2;
            // 
            // lblStatusTitle
            // 
            lblStatusTitle.Anchor = AnchorStyles.Top | AnchorStyles.Right;
            lblStatusTitle.AutoSize = true;
            lblStatusTitle.Font = new Font("Segoe UI", 8F, FontStyle.Bold);
            lblStatusTitle.ForeColor = Color.FromArgb(140, 140, 170);
            lblStatusTitle.Location = new Point(1047, 26);
            lblStatusTitle.Name = "lblStatusTitle";
            lblStatusTitle.Size = new Size(45, 13);
            lblStatusTitle.TabIndex = 3;
            lblStatusTitle.Text = "STATUS";
            lblStatusTitle.TextAlign = ContentAlignment.MiddleRight;
            // 
            // logPanel
            // 
            logPanel.BackColor = Color.FromArgb(18, 18, 30);
            logPanel.Controls.Add(txtLog);
            logPanel.Controls.Add(lblLogTitle);
            logPanel.Dock = DockStyle.Fill;
            logPanel.Location = new Point(0, 342);
            logPanel.Name = "logPanel";
            logPanel.Size = new Size(1183, 280);
            logPanel.TabIndex = 0;
            // 
            // lblLogTitle
            // 
            lblLogTitle.AutoSize = true;
            lblLogTitle.Font = new Font("Segoe UI", 8.5F, FontStyle.Bold);
            lblLogTitle.ForeColor = Color.FromArgb(140, 140, 170);
            lblLogTitle.Location = new Point(24, 6);
            lblLogTitle.Name = "lblLogTitle";
            lblLogTitle.Size = new Size(82, 15);
            lblLogTitle.TabIndex = 1;
            lblLogTitle.Text = "OUTPUT LOG";
            // 
            // bottomPanel
            // 
            bottomPanel.BackColor = Color.FromArgb(28, 28, 45);
            bottomPanel.Controls.Add(lblVersion);
            bottomPanel.Dock = DockStyle.Bottom;
            bottomPanel.Location = new Point(0, 622);
            bottomPanel.Name = "bottomPanel";
            bottomPanel.Size = new Size(1183, 30);
            bottomPanel.TabIndex = 1;
            // 
            // lblVersion
            // 
            lblVersion.AutoSize = true;
            lblVersion.Font = new Font("Segoe UI", 8F);
            lblVersion.ForeColor = Color.FromArgb(140, 140, 170);
            lblVersion.Location = new Point(24, 8);
            lblVersion.Name = "lblVersion";
            lblVersion.Size = new Size(177, 13);
            lblVersion.TabIndex = 0;
            lblVersion.Text = "Wan2GP API Server Launcher v1.0";
            // 
            // MainForm
            // 
            AutoScaleDimensions = new SizeF(7F, 15F);
            AutoScaleMode = AutoScaleMode.Font;
            BackColor = Color.FromArgb(18, 18, 30);
            ClientSize = new Size(1183, 652);
            Controls.Add(logPanel);
            Controls.Add(bottomPanel);
            Controls.Add(actionsPanel);
            Controls.Add(settingsPanel);
            Controls.Add(headerPanel);
            Font = new Font("Segoe UI", 9F);
            Icon = (Icon)resources.GetObject("$this.Icon");
            MinimumSize = new Size(680, 580);
            Name = "MainForm";
            StartPosition = FormStartPosition.CenterScreen;
            Text = "Wan2GP API Server Launcher";
            ((System.ComponentModel.ISupportInitialize)nudPort).EndInit();
            headerPanel.ResumeLayout(false);
            headerPanel.PerformLayout();
            settingsPanel.ResumeLayout(false);
            settingsPanel.PerformLayout();
            actionsPanel.ResumeLayout(false);
            actionsPanel.PerformLayout();
            logPanel.ResumeLayout(false);
            logPanel.PerformLayout();
            bottomPanel.ResumeLayout(false);
            bottomPanel.PerformLayout();
            ResumeLayout(false);
        }

        private TextBox txtHost;
        private NumericUpDown nudPort;
        private TextBox txtCondaEnv;
        private TextBox txtCondaPath;
        private TextBox txtUsername;
        private TextBox txtPassword;
        private TextBox txtServerDir;
        private TextBox txtLog;
        private Button btnStart;
        private Button btnStop;
        private Button btnClearLog;
        private Button btnBrowseConda;
        private Button btnBrowseDir;
        private Button btnTogglePass;
        private Label lblStatus;
        private Label lblStatusDot;
        private Panel headerPanel;
        private Label lblTitle;
        private Label lblSubtitle;
        private Panel settingsPanel;
        private Label lblSettingsTitle;
        private Label separator1;
        private Label lblHost;
        private Label lblPort;
        private Label lblCondaEnv;
        private Label lblCondaPath;
        private Label lblUsername;
        private Label lblPassword;
        private Label lblServerDir;
        private Panel actionsPanel;
        private Label lblStatusTitle;
        private Panel logPanel;
        private Label lblLogTitle;
        private Panel bottomPanel;
        private Label lblVersion;
    }
}
