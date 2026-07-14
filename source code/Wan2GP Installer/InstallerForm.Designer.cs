namespace Wan2GP_Installer;

partial class InstallerForm
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
        System.ComponentModel.ComponentResourceManager resources = new System.ComponentModel.ComponentResourceManager(typeof(InstallerForm));
        mainPanel = new Panel();
        headerPanel = new Panel();
        logoBox = new PictureBox();
        titleLabel = new Label();
        subtitleLabel = new Label();
        pathLabel = new Label();
        pathTextBox = new TextBox();
        browseButton = new Button();
        prereqPanel = new Panel();
        prereqTitle = new Label();
        gitStatus = new Label();
        gitLink = new Label();
        condaStatus = new Label();
        condaLink = new Label();
        refreshPrereqBtn = new Button();
        outputBox = new RichTextBox();
        progressBar = new ProgressBar();
        statusLabel = new Label();
        installButton = new Button();
        launchButton = new Button();
        mainPanel.SuspendLayout();
        headerPanel.SuspendLayout();
        ((System.ComponentModel.ISupportInitialize)logoBox).BeginInit();
        prereqPanel.SuspendLayout();
        SuspendLayout();
        // 
        // mainPanel
        // 
        mainPanel.BackColor = Color.FromArgb(18, 18, 24);
        mainPanel.Controls.Add(headerPanel);
        mainPanel.Controls.Add(pathLabel);
        mainPanel.Controls.Add(pathTextBox);
        mainPanel.Controls.Add(browseButton);
        mainPanel.Controls.Add(prereqPanel);
        mainPanel.Controls.Add(outputBox);
        mainPanel.Controls.Add(progressBar);
        mainPanel.Controls.Add(statusLabel);
        mainPanel.Controls.Add(installButton);
        mainPanel.Controls.Add(launchButton);
        mainPanel.Dock = DockStyle.Fill;
        mainPanel.Location = new Point(0, 0);
        mainPanel.Name = "mainPanel";
        mainPanel.Size = new Size(884, 641);
        mainPanel.TabIndex = 0;
        // 
        // headerPanel
        // 
        headerPanel.BackColor = Color.FromArgb(24, 24, 36);
        headerPanel.Controls.Add(logoBox);
        headerPanel.Controls.Add(titleLabel);
        headerPanel.Controls.Add(subtitleLabel);
        headerPanel.Dock = DockStyle.Top;
        headerPanel.Location = new Point(0, 0);
        headerPanel.Name = "headerPanel";
        headerPanel.Size = new Size(884, 140);
        headerPanel.TabIndex = 1;
        // 
        // logoBox
        // 
        logoBox.BackColor = Color.FromArgb(99, 102, 241);
        logoBox.Location = new Point(30, 46);
        logoBox.Name = "logoBox";
        logoBox.Size = new Size(48, 48);
        logoBox.SizeMode = PictureBoxSizeMode.CenterImage;
        logoBox.TabIndex = 0;
        logoBox.TabStop = false;
        // 
        // titleLabel
        // 
        titleLabel.AutoSize = true;
        titleLabel.Font = new Font("Segoe UI", 24F, FontStyle.Bold);
        titleLabel.ForeColor = Color.White;
        titleLabel.Location = new Point(90, 32);
        titleLabel.Name = "titleLabel";
        titleLabel.Size = new Size(277, 45);
        titleLabel.TabIndex = 1;
        titleLabel.Text = "Wan2GP Installer";
        // 
        // subtitleLabel
        // 
        subtitleLabel.AutoSize = true;
        subtitleLabel.Font = new Font("Segoe UI", 10F);
        subtitleLabel.ForeColor = Color.FromArgb(148, 149, 163);
        subtitleLabel.Location = new Point(92, 72);
        subtitleLabel.Name = "subtitleLabel";
        subtitleLabel.Size = new Size(313, 19);
        subtitleLabel.TabIndex = 2;
        subtitleLabel.Text = "AI Video And Music Generation Framework Setup";
        // 
        // pathLabel
        // 
        pathLabel.AutoSize = true;
        pathLabel.Font = new Font("Segoe UI", 9.5F);
        pathLabel.ForeColor = Color.FromArgb(180, 181, 196);
        pathLabel.Location = new Point(30, 160);
        pathLabel.Name = "pathLabel";
        pathLabel.Size = new Size(130, 17);
        pathLabel.TabIndex = 2;
        pathLabel.Text = "Installation Directory:";
        // 
        // pathTextBox
        // 
        pathTextBox.BackColor = Color.FromArgb(30, 30, 42);
        pathTextBox.BorderStyle = BorderStyle.FixedSingle;
        pathTextBox.Font = new Font("Segoe UI", 10F);
        pathTextBox.ForeColor = Color.FromArgb(220, 221, 236);
        pathTextBox.Location = new Point(30, 185);
        pathTextBox.Name = "pathTextBox";
        pathTextBox.Size = new Size(730, 25);
        pathTextBox.TabIndex = 3;
        // 
        // browseButton
        // 
        browseButton.BackColor = Color.FromArgb(40, 40, 56);
        browseButton.Cursor = Cursors.Hand;
        browseButton.FlatAppearance.BorderColor = Color.FromArgb(60, 60, 80);
        browseButton.FlatAppearance.MouseOverBackColor = Color.FromArgb(55, 55, 75);
        browseButton.FlatStyle = FlatStyle.Flat;
        browseButton.Font = new Font("Segoe UI", 9.5F, FontStyle.Bold);
        browseButton.ForeColor = Color.FromArgb(180, 181, 196);
        browseButton.Location = new Point(778, 185);
        browseButton.Name = "browseButton";
        browseButton.Size = new Size(80, 30);
        browseButton.TabIndex = 4;
        browseButton.Text = "Browse";
        browseButton.UseVisualStyleBackColor = false;
        browseButton.Click += BrowseButton_Click;
        // 
        // prereqPanel
        // 
        prereqPanel.BackColor = Color.FromArgb(22, 22, 32);
        prereqPanel.Controls.Add(prereqTitle);
        prereqPanel.Controls.Add(gitStatus);
        prereqPanel.Controls.Add(gitLink);
        prereqPanel.Controls.Add(condaStatus);
        prereqPanel.Controls.Add(condaLink);
        prereqPanel.Controls.Add(refreshPrereqBtn);
        prereqPanel.Location = new Point(30, 230);
        prereqPanel.Name = "prereqPanel";
        prereqPanel.Size = new Size(828, 110);
        prereqPanel.TabIndex = 5;
        // 
        // prereqTitle
        // 
        prereqTitle.AutoSize = true;
        prereqTitle.Font = new Font("Segoe UI", 10F, FontStyle.Bold);
        prereqTitle.ForeColor = Color.FromArgb(200, 201, 216);
        prereqTitle.Location = new Point(12, 8);
        prereqTitle.Name = "prereqTitle";
        prereqTitle.Size = new Size(96, 19);
        prereqTitle.TabIndex = 0;
        prereqTitle.Text = "Prerequisites";
        // 
        // gitStatus
        // 
        gitStatus.AutoSize = true;
        gitStatus.Font = new Font("Segoe UI", 9.5F);
        gitStatus.ForeColor = Color.FromArgb(148, 149, 163);
        gitStatus.Location = new Point(12, 38);
        gitStatus.Name = "gitStatus";
        gitStatus.Size = new Size(89, 17);
        gitStatus.TabIndex = 1;
        gitStatus.Text = "Checking Git...";
        // 
        // gitLink
        // 
        gitLink.AutoSize = true;
        gitLink.Cursor = Cursors.Hand;
        gitLink.Font = new Font("Segoe UI", 9F, FontStyle.Underline);
        gitLink.ForeColor = Color.FromArgb(99, 102, 241);
        gitLink.Location = new Point(250, 38);
        gitLink.Name = "gitLink";
        gitLink.Size = new Size(0, 15);
        gitLink.TabIndex = 2;
        gitLink.Visible = false;
        gitLink.Click += GitLink_Click;
        // 
        // condaStatus
        // 
        condaStatus.AutoSize = true;
        condaStatus.Font = new Font("Segoe UI", 9.5F);
        condaStatus.ForeColor = Color.FromArgb(148, 149, 163);
        condaStatus.Location = new Point(12, 65);
        condaStatus.Name = "condaStatus";
        condaStatus.Size = new Size(111, 17);
        condaStatus.TabIndex = 3;
        condaStatus.Text = "Checking Conda...";
        // 
        // condaLink
        // 
        condaLink.AutoSize = true;
        condaLink.Cursor = Cursors.Hand;
        condaLink.Font = new Font("Segoe UI", 9F, FontStyle.Underline);
        condaLink.ForeColor = Color.FromArgb(99, 102, 241);
        condaLink.Location = new Point(250, 65);
        condaLink.Name = "condaLink";
        condaLink.Size = new Size(0, 15);
        condaLink.TabIndex = 4;
        condaLink.Visible = false;
        condaLink.Click += CondaLink_Click;
        // 
        // refreshPrereqBtn
        // 
        refreshPrereqBtn.BackColor = Color.FromArgb(40, 40, 56);
        refreshPrereqBtn.Cursor = Cursors.Hand;
        refreshPrereqBtn.FlatAppearance.BorderColor = Color.FromArgb(60, 60, 80);
        refreshPrereqBtn.FlatAppearance.MouseOverBackColor = Color.FromArgb(55, 55, 75);
        refreshPrereqBtn.FlatStyle = FlatStyle.Flat;
        refreshPrereqBtn.Font = new Font("Segoe UI", 8.5F, FontStyle.Bold);
        refreshPrereqBtn.ForeColor = Color.FromArgb(148, 149, 163);
        refreshPrereqBtn.Location = new Point(748, 8);
        refreshPrereqBtn.Name = "refreshPrereqBtn";
        refreshPrereqBtn.Size = new Size(65, 26);
        refreshPrereqBtn.TabIndex = 5;
        refreshPrereqBtn.Text = "Refresh";
        refreshPrereqBtn.UseVisualStyleBackColor = false;
        refreshPrereqBtn.Click += RefreshPrereqBtn_Click;
        // 
        // outputBox
        // 
        outputBox.BackColor = Color.FromArgb(14, 14, 20);
        outputBox.BorderStyle = BorderStyle.None;
        outputBox.Font = new Font("Cascadia Mono", 9.5F);
        outputBox.ForeColor = Color.FromArgb(0, 200, 120);
        outputBox.Location = new Point(30, 355);
        outputBox.Name = "outputBox";
        outputBox.ReadOnly = true;
        outputBox.Size = new Size(828, 178);
        outputBox.TabIndex = 8;
        outputBox.Text = "";
        // 
        // progressBar
        // 
        progressBar.BackColor = Color.FromArgb(30, 30, 42);
        progressBar.ForeColor = Color.FromArgb(99, 102, 241);
        progressBar.Location = new Point(30, 545);
        progressBar.Name = "progressBar";
        progressBar.Size = new Size(775, 6);
        progressBar.Style = ProgressBarStyle.Continuous;
        progressBar.TabIndex = 9;
        progressBar.Visible = false;
        // 
        // statusLabel
        // 
        statusLabel.AutoSize = true;
        statusLabel.Font = new Font("Segoe UI", 9F);
        statusLabel.ForeColor = Color.FromArgb(120, 121, 140);
        statusLabel.Location = new Point(30, 555);
        statusLabel.Name = "statusLabel";
        statusLabel.Size = new Size(136, 15);
        statusLabel.TabIndex = 10;
        statusLabel.Text = "Checking prerequisites...";
        // 
        // installButton
        // 
        installButton.BackColor = Color.FromArgb(99, 102, 241);
        installButton.Cursor = Cursors.Hand;
        installButton.Enabled = false;
        installButton.FlatAppearance.BorderSize = 0;
        installButton.FlatAppearance.MouseDownBackColor = Color.FromArgb(79, 82, 221);
        installButton.FlatAppearance.MouseOverBackColor = Color.FromArgb(119, 122, 251);
        installButton.FlatStyle = FlatStyle.Flat;
        installButton.Font = new Font("Segoe UI", 13F, FontStyle.Bold);
        installButton.ForeColor = Color.White;
        installButton.Location = new Point(329, 561);
        installButton.Name = "installButton";
        installButton.Size = new Size(200, 48);
        installButton.TabIndex = 6;
        installButton.Text = "Install";
        installButton.UseVisualStyleBackColor = false;
        installButton.Click += InstallButton_Click;
        // 
        // launchButton
        // 
        launchButton.BackColor = Color.FromArgb(0, 180, 100);
        launchButton.Cursor = Cursors.Hand;
        launchButton.Enabled = false;
        launchButton.FlatAppearance.BorderSize = 0;
        launchButton.FlatAppearance.MouseDownBackColor = Color.FromArgb(0, 150, 90);
        launchButton.FlatAppearance.MouseOverBackColor = Color.FromArgb(0, 200, 120);
        launchButton.FlatStyle = FlatStyle.Flat;
        launchButton.Font = new Font("Segoe UI", 11F, FontStyle.Bold);
        launchButton.ForeColor = Color.White;
        launchButton.Location = new Point(643, 561);
        launchButton.Name = "launchButton";
        launchButton.Size = new Size(200, 48);
        launchButton.TabIndex = 7;
        launchButton.Text = "Launch Wan2GP";
        launchButton.UseVisualStyleBackColor = false;
        launchButton.Visible = false;
        launchButton.Click += LaunchButton_Click;
        // 
        // InstallerForm
        // 
        AutoScaleDimensions = new SizeF(96F, 96F);
        AutoScaleMode = AutoScaleMode.Dpi;
        BackColor = Color.FromArgb(18, 18, 24);
        ClientSize = new Size(884, 641);
        Controls.Add(mainPanel);
        FormBorderStyle = FormBorderStyle.FixedSingle;
        Icon = (Icon)resources.GetObject("$this.Icon");
        MaximizeBox = false;
        MaximumSize = new Size(900, 680);
        MinimumSize = new Size(900, 680);
        Name = "InstallerForm";
        StartPosition = FormStartPosition.CenterScreen;
        Text = "Wan2GP Installer";
        mainPanel.ResumeLayout(false);
        mainPanel.PerformLayout();
        headerPanel.ResumeLayout(false);
        headerPanel.PerformLayout();
        ((System.ComponentModel.ISupportInitialize)logoBox).EndInit();
        prereqPanel.ResumeLayout(false);
        prereqPanel.PerformLayout();
        ResumeLayout(false);
    }

    private System.Windows.Forms.Panel mainPanel;
    private System.Windows.Forms.Panel headerPanel;
    private System.Windows.Forms.PictureBox logoBox;
    private System.Windows.Forms.Label titleLabel;
    private System.Windows.Forms.Label subtitleLabel;
    private System.Windows.Forms.Label pathLabel;
    private System.Windows.Forms.TextBox pathTextBox;
    private System.Windows.Forms.Button browseButton;
    private System.Windows.Forms.Panel prereqPanel;
    private System.Windows.Forms.Label prereqTitle;
    private System.Windows.Forms.Label gitStatus;
    private System.Windows.Forms.Label gitLink;
    private System.Windows.Forms.Label condaStatus;
    private System.Windows.Forms.Label condaLink;
    private System.Windows.Forms.Button refreshPrereqBtn;
    private System.Windows.Forms.Button installButton;
    private System.Windows.Forms.Button launchButton;
    private System.Windows.Forms.RichTextBox outputBox;
    private System.Windows.Forms.ProgressBar progressBar;
    private System.Windows.Forms.Label statusLabel;
}
