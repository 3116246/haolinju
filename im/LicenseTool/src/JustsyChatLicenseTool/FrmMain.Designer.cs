namespace JustsyChatLicenseTool
{
    partial class FrmMain
    {
        /// <summary>
        /// 必需的设计器变量。
        /// </summary>
        private System.ComponentModel.IContainer components = null;

        /// <summary>
        /// 清理所有正在使用的资源。
        /// </summary>
        /// <param name="disposing">如果应释放托管资源，为 true；否则为 false。</param>
        protected override void Dispose(bool disposing)
        {
            if (disposing && (components != null))
            {
                components.Dispose();
            }
            base.Dispose(disposing);
        }

        #region Windows 窗体设计器生成的代码

        /// <summary>
        /// 设计器支持所需的方法 - 不要
        /// 使用代码编辑器修改此方法的内容。
        /// </summary>
        private void InitializeComponent()
        {
            this.components = new System.ComponentModel.Container();
            this.panel1 = new System.Windows.Forms.Panel();
            this.label15 = new System.Windows.Forms.Label();
            this.label6 = new System.Windows.Forms.Label();
            this.label1 = new System.Windows.Forms.Label();
            this.splitContainer1 = new System.Windows.Forms.SplitContainer();
            this.splitContainer2 = new System.Windows.Forms.SplitContainer();
            this.dgvEnterprise = new System.Windows.Forms.DataGridView();
            this.Column1 = new System.Windows.Forms.DataGridViewTextBoxColumn();
            this.panel2 = new System.Windows.Forms.Panel();
            this.tbFilteEnterprise = new System.Windows.Forms.TextBox();
            this.label12 = new System.Windows.Forms.Label();
            this.label10 = new System.Windows.Forms.Label();
            this.dgvNode = new System.Windows.Forms.DataGridView();
            this.dataGridViewTextBoxColumn1 = new System.Windows.Forms.DataGridViewTextBoxColumn();
            this.panel3 = new System.Windows.Forms.Panel();
            this.label11 = new System.Windows.Forms.Label();
            this.label17 = new System.Windows.Forms.Label();
            this.btnExport = new System.Windows.Forms.Button();
            this.dtpExpiration = new System.Windows.Forms.DateTimePicker();
            this.cbbType = new System.Windows.Forms.ComboBox();
            this.btnGenSerial = new System.Windows.Forms.Button();
            this.tbSignature = new System.Windows.Forms.TextBox();
            this.label24 = new System.Windows.Forms.Label();
            this.label18 = new System.Windows.Forms.Label();
            this.label20 = new System.Windows.Forms.Label();
            this.label21 = new System.Windows.Forms.Label();
            this.tbClusterNodeNum = new System.Windows.Forms.TextBox();
            this.label22 = new System.Windows.Forms.Label();
            this.tbEnterprise = new System.Windows.Forms.TextBox();
            this.label16 = new System.Windows.Forms.Label();
            this.label13 = new System.Windows.Forms.Label();
            this.tbSerial = new System.Windows.Forms.TextBox();
            this.label14 = new System.Windows.Forms.Label();
            this.btnCopy = new System.Windows.Forms.Button();
            this.btnNew = new System.Windows.Forms.Button();
            this.label9 = new System.Windows.Forms.Label();
            this.btnSave = new System.Windows.Forms.Button();
            this.label7 = new System.Windows.Forms.Label();
            this.tbCertPwd = new System.Windows.Forms.TextBox();
            this.label8 = new System.Windows.Forms.Label();
            this.label5 = new System.Windows.Forms.Label();
            this.tbCert = new System.Windows.Forms.TextBox();
            this.label4 = new System.Windows.Forms.Label();
            this.label3 = new System.Windows.Forms.Label();
            this.tbRegistrationCode = new System.Windows.Forms.TextBox();
            this.label2 = new System.Windows.Forms.Label();
            this.errorProvider1 = new System.Windows.Forms.ErrorProvider(this.components);
            this.panel1.SuspendLayout();
            this.splitContainer1.Panel1.SuspendLayout();
            this.splitContainer1.Panel2.SuspendLayout();
            this.splitContainer1.SuspendLayout();
            this.splitContainer2.Panel1.SuspendLayout();
            this.splitContainer2.Panel2.SuspendLayout();
            this.splitContainer2.SuspendLayout();
            ((System.ComponentModel.ISupportInitialize)(this.dgvEnterprise)).BeginInit();
            this.panel2.SuspendLayout();
            ((System.ComponentModel.ISupportInitialize)(this.dgvNode)).BeginInit();
            this.panel3.SuspendLayout();
            ((System.ComponentModel.ISupportInitialize)(this.errorProvider1)).BeginInit();
            this.SuspendLayout();
            // 
            // panel1
            // 
            this.panel1.BackColor = System.Drawing.SystemColors.GradientActiveCaption;
            this.panel1.Controls.Add(this.label15);
            this.panel1.Controls.Add(this.label6);
            this.panel1.Controls.Add(this.label1);
            this.panel1.Dock = System.Windows.Forms.DockStyle.Top;
            this.panel1.Location = new System.Drawing.Point(0, 0);
            this.panel1.Name = "panel1";
            this.panel1.Size = new System.Drawing.Size(836, 57);
            this.panel1.TabIndex = 7;
            // 
            // label15
            // 
            this.label15.BackColor = System.Drawing.SystemColors.Desktop;
            this.label15.Dock = System.Windows.Forms.DockStyle.Bottom;
            this.label15.Font = new System.Drawing.Font("宋体", 12F, System.Drawing.FontStyle.Bold, System.Drawing.GraphicsUnit.Point, ((byte)(134)));
            this.label15.ForeColor = System.Drawing.Color.BlueViolet;
            this.label15.Location = new System.Drawing.Point(0, 53);
            this.label15.Name = "label15";
            this.label15.Size = new System.Drawing.Size(836, 4);
            this.label15.TabIndex = 26;
            // 
            // label6
            // 
            this.label6.AutoSize = true;
            this.label6.Font = new System.Drawing.Font("宋体", 15.75F, System.Drawing.FontStyle.Bold, System.Drawing.GraphicsUnit.Point, ((byte)(134)));
            this.label6.ForeColor = System.Drawing.SystemColors.ActiveCaption;
            this.label6.Location = new System.Drawing.Point(4, 6);
            this.label6.Name = "label6";
            this.label6.Size = new System.Drawing.Size(144, 21);
            this.label6.TabIndex = 2;
            this.label6.Text = "IM服务器授权";
            // 
            // label1
            // 
            this.label1.AutoSize = true;
            this.label1.ForeColor = System.Drawing.Color.MidnightBlue;
            this.label1.Location = new System.Drawing.Point(7, 35);
            this.label1.Name = "label1";
            this.label1.Size = new System.Drawing.Size(443, 12);
            this.label1.TabIndex = 1;
            this.label1.Text = "说明：根据用户提交的注册码(RegistrationCode)生成其对应的授权文件(lic.txt)";
            // 
            // splitContainer1
            // 
            this.splitContainer1.Dock = System.Windows.Forms.DockStyle.Fill;
            this.splitContainer1.FixedPanel = System.Windows.Forms.FixedPanel.Panel1;
            this.splitContainer1.Location = new System.Drawing.Point(0, 57);
            this.splitContainer1.Name = "splitContainer1";
            // 
            // splitContainer1.Panel1
            // 
            this.splitContainer1.Panel1.Controls.Add(this.splitContainer2);
            // 
            // splitContainer1.Panel2
            // 
            this.splitContainer1.Panel2.Controls.Add(this.label17);
            this.splitContainer1.Panel2.Controls.Add(this.btnExport);
            this.splitContainer1.Panel2.Controls.Add(this.dtpExpiration);
            this.splitContainer1.Panel2.Controls.Add(this.cbbType);
            this.splitContainer1.Panel2.Controls.Add(this.btnGenSerial);
            this.splitContainer1.Panel2.Controls.Add(this.tbSignature);
            this.splitContainer1.Panel2.Controls.Add(this.label24);
            this.splitContainer1.Panel2.Controls.Add(this.label18);
            this.splitContainer1.Panel2.Controls.Add(this.label20);
            this.splitContainer1.Panel2.Controls.Add(this.label21);
            this.splitContainer1.Panel2.Controls.Add(this.tbClusterNodeNum);
            this.splitContainer1.Panel2.Controls.Add(this.label22);
            this.splitContainer1.Panel2.Controls.Add(this.tbEnterprise);
            this.splitContainer1.Panel2.Controls.Add(this.label16);
            this.splitContainer1.Panel2.Controls.Add(this.label13);
            this.splitContainer1.Panel2.Controls.Add(this.tbSerial);
            this.splitContainer1.Panel2.Controls.Add(this.label14);
            this.splitContainer1.Panel2.Controls.Add(this.btnCopy);
            this.splitContainer1.Panel2.Controls.Add(this.btnNew);
            this.splitContainer1.Panel2.Controls.Add(this.label9);
            this.splitContainer1.Panel2.Controls.Add(this.btnSave);
            this.splitContainer1.Panel2.Controls.Add(this.label7);
            this.splitContainer1.Panel2.Controls.Add(this.tbCertPwd);
            this.splitContainer1.Panel2.Controls.Add(this.label8);
            this.splitContainer1.Panel2.Controls.Add(this.label5);
            this.splitContainer1.Panel2.Controls.Add(this.tbCert);
            this.splitContainer1.Panel2.Controls.Add(this.label4);
            this.splitContainer1.Panel2.Controls.Add(this.label3);
            this.splitContainer1.Panel2.Controls.Add(this.tbRegistrationCode);
            this.splitContainer1.Panel2.Controls.Add(this.label2);
            this.splitContainer1.Size = new System.Drawing.Size(836, 485);
            this.splitContainer1.SplitterDistance = 250;
            this.splitContainer1.TabIndex = 14;
            // 
            // splitContainer2
            // 
            this.splitContainer2.Dock = System.Windows.Forms.DockStyle.Fill;
            this.splitContainer2.Location = new System.Drawing.Point(0, 0);
            this.splitContainer2.Name = "splitContainer2";
            this.splitContainer2.Orientation = System.Windows.Forms.Orientation.Horizontal;
            // 
            // splitContainer2.Panel1
            // 
            this.splitContainer2.Panel1.Controls.Add(this.dgvEnterprise);
            this.splitContainer2.Panel1.Controls.Add(this.panel2);
            // 
            // splitContainer2.Panel2
            // 
            this.splitContainer2.Panel2.Controls.Add(this.dgvNode);
            this.splitContainer2.Panel2.Controls.Add(this.panel3);
            this.splitContainer2.Size = new System.Drawing.Size(250, 485);
            this.splitContainer2.SplitterDistance = 272;
            this.splitContainer2.TabIndex = 0;
            // 
            // dgvEnterprise
            // 
            this.dgvEnterprise.AllowUserToAddRows = false;
            this.dgvEnterprise.AllowUserToDeleteRows = false;
            this.dgvEnterprise.ColumnHeadersHeightSizeMode = System.Windows.Forms.DataGridViewColumnHeadersHeightSizeMode.AutoSize;
            this.dgvEnterprise.Columns.AddRange(new System.Windows.Forms.DataGridViewColumn[] {
            this.Column1});
            this.dgvEnterprise.Dock = System.Windows.Forms.DockStyle.Fill;
            this.dgvEnterprise.Location = new System.Drawing.Point(0, 34);
            this.dgvEnterprise.MultiSelect = false;
            this.dgvEnterprise.Name = "dgvEnterprise";
            this.dgvEnterprise.ReadOnly = true;
            this.dgvEnterprise.RowTemplate.Height = 23;
            this.dgvEnterprise.SelectionMode = System.Windows.Forms.DataGridViewSelectionMode.FullRowSelect;
            this.dgvEnterprise.Size = new System.Drawing.Size(250, 238);
            this.dgvEnterprise.TabIndex = 0;
            this.dgvEnterprise.SelectionChanged += new System.EventHandler(this.dgvEnterprise_SelectionChanged);
            // 
            // Column1
            // 
            this.Column1.DataPropertyName = "enterprise";
            this.Column1.HeaderText = "企业名称";
            this.Column1.Name = "Column1";
            this.Column1.ReadOnly = true;
            this.Column1.Width = 200;
            // 
            // panel2
            // 
            this.panel2.Controls.Add(this.tbFilteEnterprise);
            this.panel2.Controls.Add(this.label12);
            this.panel2.Controls.Add(this.label10);
            this.panel2.Dock = System.Windows.Forms.DockStyle.Top;
            this.panel2.Location = new System.Drawing.Point(0, 0);
            this.panel2.Name = "panel2";
            this.panel2.Size = new System.Drawing.Size(250, 34);
            this.panel2.TabIndex = 1;
            // 
            // tbFilteEnterprise
            // 
            this.tbFilteEnterprise.Location = new System.Drawing.Point(171, 7);
            this.tbFilteEnterprise.Name = "tbFilteEnterprise";
            this.tbFilteEnterprise.Size = new System.Drawing.Size(73, 21);
            this.tbFilteEnterprise.TabIndex = 16;
            this.tbFilteEnterprise.TextChanged += new System.EventHandler(this.tbFilteEnterprise_TextChanged);
            // 
            // label12
            // 
            this.label12.AutoSize = true;
            this.label12.Location = new System.Drawing.Point(142, 11);
            this.label12.Name = "label12";
            this.label12.Size = new System.Drawing.Size(29, 12);
            this.label12.TabIndex = 15;
            this.label12.Text = "搜索";
            // 
            // label10
            // 
            this.label10.AutoSize = true;
            this.label10.Font = new System.Drawing.Font("宋体", 12F, System.Drawing.FontStyle.Bold, System.Drawing.GraphicsUnit.Point, ((byte)(134)));
            this.label10.ForeColor = System.Drawing.SystemColors.ActiveCaption;
            this.label10.Location = new System.Drawing.Point(7, 9);
            this.label10.Name = "label10";
            this.label10.Size = new System.Drawing.Size(93, 16);
            this.label10.TabIndex = 3;
            this.label10.Text = "已授权企业";
            // 
            // dgvNode
            // 
            this.dgvNode.AllowUserToAddRows = false;
            this.dgvNode.AllowUserToDeleteRows = false;
            this.dgvNode.ColumnHeadersHeightSizeMode = System.Windows.Forms.DataGridViewColumnHeadersHeightSizeMode.AutoSize;
            this.dgvNode.Columns.AddRange(new System.Windows.Forms.DataGridViewColumn[] {
            this.dataGridViewTextBoxColumn1});
            this.dgvNode.Dock = System.Windows.Forms.DockStyle.Fill;
            this.dgvNode.Location = new System.Drawing.Point(0, 34);
            this.dgvNode.MultiSelect = false;
            this.dgvNode.Name = "dgvNode";
            this.dgvNode.ReadOnly = true;
            this.dgvNode.RowTemplate.Height = 23;
            this.dgvNode.SelectionMode = System.Windows.Forms.DataGridViewSelectionMode.FullRowSelect;
            this.dgvNode.Size = new System.Drawing.Size(250, 175);
            this.dgvNode.TabIndex = 2;
            this.dgvNode.SelectionChanged += new System.EventHandler(this.dgvNode_SelectionChanged);
            // 
            // dataGridViewTextBoxColumn1
            // 
            this.dataGridViewTextBoxColumn1.DataPropertyName = "registration_code";
            this.dataGridViewTextBoxColumn1.HeaderText = "注册码";
            this.dataGridViewTextBoxColumn1.Name = "dataGridViewTextBoxColumn1";
            this.dataGridViewTextBoxColumn1.ReadOnly = true;
            this.dataGridViewTextBoxColumn1.Width = 200;
            // 
            // panel3
            // 
            this.panel3.Controls.Add(this.label11);
            this.panel3.Dock = System.Windows.Forms.DockStyle.Top;
            this.panel3.Location = new System.Drawing.Point(0, 0);
            this.panel3.Name = "panel3";
            this.panel3.Size = new System.Drawing.Size(250, 34);
            this.panel3.TabIndex = 3;
            // 
            // label11
            // 
            this.label11.AutoSize = true;
            this.label11.Font = new System.Drawing.Font("宋体", 12F, System.Drawing.FontStyle.Bold, System.Drawing.GraphicsUnit.Point, ((byte)(134)));
            this.label11.ForeColor = System.Drawing.SystemColors.ActiveCaption;
            this.label11.Location = new System.Drawing.Point(7, 9);
            this.label11.Name = "label11";
            this.label11.Size = new System.Drawing.Size(93, 16);
            this.label11.TabIndex = 3;
            this.label11.Text = "已授权节点";
            // 
            // label17
            // 
            this.label17.AutoSize = true;
            this.label17.ForeColor = System.Drawing.Color.Blue;
            this.label17.Location = new System.Drawing.Point(250, 267);
            this.label17.Name = "label17";
            this.label17.Size = new System.Drawing.Size(149, 12);
            this.label17.TabIndex = 50;
            this.label17.Text = "保存后根据输入的信息生成";
            // 
            // btnExport
            // 
            this.btnExport.Font = new System.Drawing.Font("宋体", 21.75F, System.Drawing.FontStyle.Bold, System.Drawing.GraphicsUnit.Point, ((byte)(134)));
            this.btnExport.Location = new System.Drawing.Point(164, 291);
            this.btnExport.Name = "btnExport";
            this.btnExport.Size = new System.Drawing.Size(210, 45);
            this.btnExport.TabIndex = 12;
            this.btnExport.Text = "导出授权文件";
            this.btnExport.UseVisualStyleBackColor = true;
            this.btnExport.Click += new System.EventHandler(this.btnExport_Click);
            // 
            // dtpExpiration
            // 
            this.dtpExpiration.Location = new System.Drawing.Point(68, 237);
            this.dtpExpiration.Name = "dtpExpiration";
            this.dtpExpiration.Size = new System.Drawing.Size(176, 21);
            this.dtpExpiration.TabIndex = 9;
            this.dtpExpiration.Value = new System.DateTime(2099, 12, 31, 0, 0, 0, 0);
            // 
            // cbbType
            // 
            this.cbbType.DropDownStyle = System.Windows.Forms.ComboBoxStyle.DropDownList;
            this.cbbType.FormattingEnabled = true;
            this.cbbType.Items.AddRange(new object[] {
            "PROD",
            "TEST"});
            this.cbbType.Location = new System.Drawing.Point(68, 211);
            this.cbbType.Name = "cbbType";
            this.cbbType.Size = new System.Drawing.Size(175, 20);
            this.cbbType.TabIndex = 8;
            this.cbbType.SelectionChangeCommitted += new System.EventHandler(this.cbbType_SelectionChangeCommitted);
            // 
            // btnGenSerial
            // 
            this.btnGenSerial.Location = new System.Drawing.Point(245, 128);
            this.btnGenSerial.Name = "btnGenSerial";
            this.btnGenSerial.Size = new System.Drawing.Size(37, 22);
            this.btnGenSerial.TabIndex = 6;
            this.btnGenSerial.Text = "生成";
            this.btnGenSerial.UseVisualStyleBackColor = true;
            this.btnGenSerial.Click += new System.EventHandler(this.btnGenSerial_Click);
            // 
            // tbSignature
            // 
            this.tbSignature.BackColor = System.Drawing.Color.LightGray;
            this.tbSignature.Location = new System.Drawing.Point(68, 264);
            this.tbSignature.Name = "tbSignature";
            this.tbSignature.ReadOnly = true;
            this.tbSignature.Size = new System.Drawing.Size(176, 21);
            this.tbSignature.TabIndex = 10;
            // 
            // label24
            // 
            this.label24.AutoSize = true;
            this.label24.Location = new System.Drawing.Point(9, 268);
            this.label24.Name = "label24";
            this.label24.Size = new System.Drawing.Size(29, 12);
            this.label24.TabIndex = 43;
            this.label24.Text = "签名";
            // 
            // label18
            // 
            this.label18.AutoSize = true;
            this.label18.Location = new System.Drawing.Point(9, 241);
            this.label18.Name = "label18";
            this.label18.Size = new System.Drawing.Size(41, 12);
            this.label18.TabIndex = 40;
            this.label18.Text = "有效期";
            // 
            // label20
            // 
            this.label20.AutoSize = true;
            this.label20.Location = new System.Drawing.Point(9, 214);
            this.label20.Name = "label20";
            this.label20.Size = new System.Drawing.Size(53, 12);
            this.label20.TabIndex = 37;
            this.label20.Text = "授权类型";
            // 
            // label21
            // 
            this.label21.AutoSize = true;
            this.label21.ForeColor = System.Drawing.Color.Blue;
            this.label21.Location = new System.Drawing.Point(248, 187);
            this.label21.Name = "label21";
            this.label21.Size = new System.Drawing.Size(113, 12);
            this.label21.TabIndex = 36;
            this.label21.Text = "授权最大集群节点数";
            // 
            // tbClusterNodeNum
            // 
            this.errorProvider1.SetIconAlignment(this.tbClusterNodeNum, System.Windows.Forms.ErrorIconAlignment.MiddleLeft);
            this.tbClusterNodeNum.Location = new System.Drawing.Point(68, 183);
            this.tbClusterNodeNum.Name = "tbClusterNodeNum";
            this.tbClusterNodeNum.Size = new System.Drawing.Size(176, 21);
            this.tbClusterNodeNum.TabIndex = 7;
            this.tbClusterNodeNum.Text = "999999";
            this.tbClusterNodeNum.Validating += new System.ComponentModel.CancelEventHandler(this.tbClusterNodeNum_Validating);
            // 
            // label22
            // 
            this.label22.AutoSize = true;
            this.label22.Location = new System.Drawing.Point(9, 187);
            this.label22.Name = "label22";
            this.label22.Size = new System.Drawing.Size(41, 12);
            this.label22.TabIndex = 34;
            this.label22.Text = "节点数";
            // 
            // tbEnterprise
            // 
            this.tbEnterprise.Location = new System.Drawing.Point(68, 156);
            this.tbEnterprise.Name = "tbEnterprise";
            this.tbEnterprise.Size = new System.Drawing.Size(176, 21);
            this.tbEnterprise.TabIndex = 6;
            // 
            // label16
            // 
            this.label16.AutoSize = true;
            this.label16.Location = new System.Drawing.Point(9, 160);
            this.label16.Name = "label16";
            this.label16.Size = new System.Drawing.Size(53, 12);
            this.label16.TabIndex = 31;
            this.label16.Text = "企业名称";
            // 
            // label13
            // 
            this.label13.AutoSize = true;
            this.label13.ForeColor = System.Drawing.Color.Blue;
            this.label13.Location = new System.Drawing.Point(285, 133);
            this.label13.Name = "label13";
            this.label13.Size = new System.Drawing.Size(101, 12);
            this.label13.TabIndex = 30;
            this.label13.Text = "唯一标识授权企业";
            // 
            // tbSerial
            // 
            this.tbSerial.Location = new System.Drawing.Point(68, 129);
            this.tbSerial.Name = "tbSerial";
            this.tbSerial.Size = new System.Drawing.Size(176, 21);
            this.tbSerial.TabIndex = 5;
            // 
            // label14
            // 
            this.label14.AutoSize = true;
            this.label14.Location = new System.Drawing.Point(9, 133);
            this.label14.Name = "label14";
            this.label14.Size = new System.Drawing.Size(41, 12);
            this.label14.TabIndex = 28;
            this.label14.Text = "序列号";
            // 
            // btnCopy
            // 
            this.btnCopy.Font = new System.Drawing.Font("宋体", 10.5F, System.Drawing.FontStyle.Bold, System.Drawing.GraphicsUnit.Point, ((byte)(134)));
            this.btnCopy.Location = new System.Drawing.Point(71, 71);
            this.btnCopy.Name = "btnCopy";
            this.btnCopy.Size = new System.Drawing.Size(54, 24);
            this.btnCopy.TabIndex = 3;
            this.btnCopy.Text = "复制";
            this.btnCopy.UseVisualStyleBackColor = true;
            this.btnCopy.Click += new System.EventHandler(this.btnCopy_Click);
            // 
            // btnNew
            // 
            this.btnNew.Font = new System.Drawing.Font("宋体", 10.5F, System.Drawing.FontStyle.Bold, System.Drawing.GraphicsUnit.Point, ((byte)(134)));
            this.btnNew.Location = new System.Drawing.Point(11, 71);
            this.btnNew.Name = "btnNew";
            this.btnNew.Size = new System.Drawing.Size(54, 24);
            this.btnNew.TabIndex = 2;
            this.btnNew.Text = "新增";
            this.btnNew.UseVisualStyleBackColor = true;
            this.btnNew.Click += new System.EventHandler(this.btnNew_Click);
            // 
            // label9
            // 
            this.label9.Anchor = ((System.Windows.Forms.AnchorStyles)(((System.Windows.Forms.AnchorStyles.Top | System.Windows.Forms.AnchorStyles.Left)
                        | System.Windows.Forms.AnchorStyles.Right)));
            this.label9.BackColor = System.Drawing.SystemColors.Desktop;
            this.label9.Font = new System.Drawing.Font("宋体", 12F, System.Drawing.FontStyle.Bold, System.Drawing.GraphicsUnit.Point, ((byte)(134)));
            this.label9.ForeColor = System.Drawing.Color.BlueViolet;
            this.label9.Location = new System.Drawing.Point(2, 58);
            this.label9.Name = "label9";
            this.label9.Size = new System.Drawing.Size(574, 4);
            this.label9.TabIndex = 25;
            // 
            // btnSave
            // 
            this.btnSave.Font = new System.Drawing.Font("宋体", 21.75F, System.Drawing.FontStyle.Bold, System.Drawing.GraphicsUnit.Point, ((byte)(134)));
            this.btnSave.Location = new System.Drawing.Point(11, 291);
            this.btnSave.Name = "btnSave";
            this.btnSave.Size = new System.Drawing.Size(147, 45);
            this.btnSave.TabIndex = 11;
            this.btnSave.Text = "保  存";
            this.btnSave.UseVisualStyleBackColor = true;
            this.btnSave.Click += new System.EventHandler(this.btnSave_Click);
            // 
            // label7
            // 
            this.label7.AutoSize = true;
            this.label7.ForeColor = System.Drawing.Color.Blue;
            this.label7.Location = new System.Drawing.Point(248, 36);
            this.label7.Name = "label7";
            this.label7.Size = new System.Drawing.Size(269, 12);
            this.label7.TabIndex = 22;
            this.label7.Text = "输入该密码后才可访问证书中的公钥及私钥等信息";
            // 
            // tbCertPwd
            // 
            this.tbCertPwd.Location = new System.Drawing.Point(68, 32);
            this.tbCertPwd.Name = "tbCertPwd";
            this.tbCertPwd.PasswordChar = '*';
            this.tbCertPwd.Size = new System.Drawing.Size(176, 21);
            this.tbCertPwd.TabIndex = 1;
            // 
            // label8
            // 
            this.label8.AutoSize = true;
            this.label8.Location = new System.Drawing.Point(9, 36);
            this.label8.Name = "label8";
            this.label8.Size = new System.Drawing.Size(53, 12);
            this.label8.TabIndex = 20;
            this.label8.Text = "证书密码";
            // 
            // label5
            // 
            this.label5.AutoSize = true;
            this.label5.ForeColor = System.Drawing.Color.Blue;
            this.label5.Location = new System.Drawing.Point(248, 106);
            this.label5.Name = "label5";
            this.label5.Size = new System.Drawing.Size(329, 12);
            this.label5.TabIndex = 19;
            this.label5.Text = "用户在服务器程序安装配置完成后，执行注册码生成程序获得";
            // 
            // tbCert
            // 
            this.tbCert.BackColor = System.Drawing.Color.LightGray;
            this.tbCert.Location = new System.Drawing.Point(68, 5);
            this.tbCert.Name = "tbCert";
            this.tbCert.ReadOnly = true;
            this.tbCert.Size = new System.Drawing.Size(176, 21);
            this.tbCert.TabIndex = 0;
            this.tbCert.Text = "cer.pfx";
            // 
            // label4
            // 
            this.label4.AutoSize = true;
            this.label4.Location = new System.Drawing.Point(9, 9);
            this.label4.Name = "label4";
            this.label4.Size = new System.Drawing.Size(53, 12);
            this.label4.TabIndex = 17;
            this.label4.Text = "签名证书";
            // 
            // label3
            // 
            this.label3.AutoSize = true;
            this.label3.ForeColor = System.Drawing.Color.Blue;
            this.label3.Location = new System.Drawing.Point(248, 9);
            this.label3.Name = "label3";
            this.label3.Size = new System.Drawing.Size(227, 12);
            this.label3.TabIndex = 16;
            this.label3.Text = "该证书用于对授权文件(lic.txt)进行签名";
            // 
            // tbRegistrationCode
            // 
            this.tbRegistrationCode.Location = new System.Drawing.Point(68, 102);
            this.tbRegistrationCode.Name = "tbRegistrationCode";
            this.tbRegistrationCode.Size = new System.Drawing.Size(176, 21);
            this.tbRegistrationCode.TabIndex = 4;
            // 
            // label2
            // 
            this.label2.AutoSize = true;
            this.label2.Location = new System.Drawing.Point(9, 106);
            this.label2.Name = "label2";
            this.label2.Size = new System.Drawing.Size(41, 12);
            this.label2.TabIndex = 14;
            this.label2.Text = "注册码";
            // 
            // errorProvider1
            // 
            this.errorProvider1.ContainerControl = this;
            // 
            // FrmMain
            // 
            this.AutoScaleDimensions = new System.Drawing.SizeF(6F, 12F);
            this.AutoScaleMode = System.Windows.Forms.AutoScaleMode.Font;
            this.ClientSize = new System.Drawing.Size(836, 542);
            this.Controls.Add(this.splitContainer1);
            this.Controls.Add(this.panel1);
            this.Name = "FrmMain";
            this.StartPosition = System.Windows.Forms.FormStartPosition.CenterScreen;
            this.Text = "IM Server License Tool";
            this.Load += new System.EventHandler(this.FrmMain_Load);
            this.panel1.ResumeLayout(false);
            this.panel1.PerformLayout();
            this.splitContainer1.Panel1.ResumeLayout(false);
            this.splitContainer1.Panel2.ResumeLayout(false);
            this.splitContainer1.Panel2.PerformLayout();
            this.splitContainer1.ResumeLayout(false);
            this.splitContainer2.Panel1.ResumeLayout(false);
            this.splitContainer2.Panel2.ResumeLayout(false);
            this.splitContainer2.ResumeLayout(false);
            ((System.ComponentModel.ISupportInitialize)(this.dgvEnterprise)).EndInit();
            this.panel2.ResumeLayout(false);
            this.panel2.PerformLayout();
            ((System.ComponentModel.ISupportInitialize)(this.dgvNode)).EndInit();
            this.panel3.ResumeLayout(false);
            this.panel3.PerformLayout();
            ((System.ComponentModel.ISupportInitialize)(this.errorProvider1)).EndInit();
            this.ResumeLayout(false);

        }

        #endregion

        private System.Windows.Forms.Panel panel1;
        private System.Windows.Forms.Label label6;
        private System.Windows.Forms.Label label1;
        private System.Windows.Forms.SplitContainer splitContainer1;
        private System.Windows.Forms.Label label9;
        private System.Windows.Forms.Button btnSave;
        private System.Windows.Forms.Label label7;
        private System.Windows.Forms.TextBox tbCertPwd;
        private System.Windows.Forms.Label label8;
        private System.Windows.Forms.Label label5;
        private System.Windows.Forms.TextBox tbCert;
        private System.Windows.Forms.Label label4;
        private System.Windows.Forms.Label label3;
        private System.Windows.Forms.TextBox tbRegistrationCode;
        private System.Windows.Forms.Label label2;
        private System.Windows.Forms.SplitContainer splitContainer2;
        private System.Windows.Forms.DataGridView dgvEnterprise;
        private System.Windows.Forms.Panel panel2;
        private System.Windows.Forms.Label label10;
        private System.Windows.Forms.DataGridView dgvNode;
        private System.Windows.Forms.Panel panel3;
        private System.Windows.Forms.Label label11;
        private System.Windows.Forms.TextBox tbFilteEnterprise;
        private System.Windows.Forms.Label label12;
        private System.Windows.Forms.Button btnCopy;
        private System.Windows.Forms.Button btnNew;
        private System.Windows.Forms.TextBox tbSignature;
        private System.Windows.Forms.Label label24;
        private System.Windows.Forms.Label label18;
        private System.Windows.Forms.Label label20;
        private System.Windows.Forms.Label label21;
        private System.Windows.Forms.TextBox tbClusterNodeNum;
        private System.Windows.Forms.Label label22;
        private System.Windows.Forms.TextBox tbEnterprise;
        private System.Windows.Forms.Label label16;
        private System.Windows.Forms.Label label13;
        private System.Windows.Forms.TextBox tbSerial;
        private System.Windows.Forms.Label label14;
        private System.Windows.Forms.Button btnGenSerial;
        private System.Windows.Forms.ComboBox cbbType;
        private System.Windows.Forms.DateTimePicker dtpExpiration;
        private System.Windows.Forms.Button btnExport;
        private System.Windows.Forms.DataGridViewTextBoxColumn Column1;
        private System.Windows.Forms.DataGridViewTextBoxColumn dataGridViewTextBoxColumn1;
        private System.Windows.Forms.Label label15;
        private System.Windows.Forms.Label label17;
        private System.Windows.Forms.ErrorProvider errorProvider1;
    }
}

