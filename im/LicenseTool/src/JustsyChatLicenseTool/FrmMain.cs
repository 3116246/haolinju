using System;
using System.Collections.Generic;
using System.ComponentModel;
using System.Data;
using System.Drawing;
using System.Text;
using System.Windows.Forms;
using System.Security.Cryptography.X509Certificates;
using System.Security.Cryptography;

namespace JustsyChatLicenseTool
{
    public partial class FrmMain : Form
    {
        const string DM_ENTERPRISELIST = "EnterpriseList";
        const string DM_NODELIST = "NodeList";

        DataSet dsEnterpriseList;
        DataSet dsNodeList;

        public FrmMain()
        {
            InitializeComponent();

            SqliteOper.CheckDBVersion();
        }

        private void FrmMain_Load(object sender, EventArgs e)
        {
            GetEnterpriseList();
        }

        private void GetEnterpriseList()
        {
            //载入已授权企业
            dsEnterpriseList = SqliteOper.GetEnterpriseList(DM_ENTERPRISELIST, tbFilteEnterprise.Text);
            dgvEnterprise.DataSource = dsEnterpriseList;
            dgvEnterprise.DataMember = DM_ENTERPRISELIST;
        }

        private void dgvEnterprise_SelectionChanged(object sender, EventArgs e)
        {
            string ename = "";

            DataView dvA = dsEnterpriseList.Tables[DM_ENTERPRISELIST].DefaultView;
            if (dvA.Count > 0)
            {
                ename = Convert.ToString(dvA[dgvEnterprise.BindingContext[dgvEnterprise.DataSource, dgvEnterprise.DataMember].Position]["enterprise"]);
            }

            //载入已授权节点
            dsNodeList = SqliteOper.GetNodeList(DM_NODELIST, ename);
            if (dgvNode.DataSource != null && dgvNode.BindingContext[dgvNode.DataSource, dgvNode.DataMember].Position >= dsNodeList.Tables[DM_NODELIST].Rows.Count)
                dgvNode.BindingContext[dgvNode.DataSource, dgvNode.DataMember].Position = dsNodeList.Tables[DM_NODELIST].Rows.Count - 1;
            dgvNode.DataSource = dsNodeList;
            dgvNode.DataMember = DM_NODELIST;
        }

        private void dgvNode_SelectionChanged(object sender, EventArgs e)
        {
            DataView dvA = dsNodeList.Tables[DM_NODELIST].DefaultView;

            if (dvA.Count == 0)
            {
                DisableInput();
            }
            else
            {
                EnableInput();

                DataRowView drvA = dvA[dgvNode.BindingContext[dgvNode.DataSource, dgvNode.DataMember].Position];
                tbRegistrationCode.Text = Convert.ToString(drvA["registration_code"]);
                tbSerial.Text = Convert.ToString(drvA["serial"]);
                tbEnterprise.Text = Convert.ToString(drvA["enterprise"]);
                tbClusterNodeNum.Text = Convert.ToString(drvA["cluster_node_num"]);
                cbbType.Text = Convert.ToString(drvA["type"]);
                dtpExpiration.Value = Convert.ToDateTime(drvA["expiration"]);
                tbSignature.Text = Convert.ToString(drvA["signature"]);
            }
        }

        private void EnableInput()
        {
            tbRegistrationCode.Enabled = true;
            tbSerial.Enabled = true;
            btnGenSerial.Enabled = true;
            tbEnterprise.Enabled = true;
            tbClusterNodeNum.Enabled = true;
            cbbType.Enabled = true;
            dtpExpiration.Enabled = true;
            btnSave.Enabled = true;
            btnExport.Enabled = true;
        }

        private void DisableInput()
        {
            tbRegistrationCode.Enabled = false;
            tbSerial.Enabled = false;
            btnGenSerial.Enabled = false;
            tbEnterprise.Enabled = false;
            tbClusterNodeNum.Enabled = false;
            cbbType.Enabled = false;
            dtpExpiration.Enabled = false;
            btnSave.Enabled = false;
            btnExport.Enabled = false;
        }

        private void btnNew_Click(object sender, EventArgs e)
        {
            EnableInput();

            tbRegistrationCode.Text = "";
            tbSerial.Text = "";
            tbEnterprise.Text = "";
            tbClusterNodeNum.Text = "999999";
            cbbType.Text = "PROD";
            dtpExpiration.Value = Convert.ToDateTime("2099-12-31");
            tbSignature.Text = "";

            btnGenSerial.PerformClick();
        }

        private void btnCopy_Click(object sender, EventArgs e)
        {
            DataView dvA = dsNodeList.Tables[DM_NODELIST].DefaultView;

            if (dvA.Count == 0) 
            {
                MessageBox.Show("请先选择要复制的企业节点");
                return;
            }

            EnableInput();

            DataRowView drvA = dvA[dgvNode.CurrentRow.Index];
            tbRegistrationCode.Text = "";
            tbSerial.Text = Convert.ToString(drvA["serial"]);
            tbEnterprise.Text = Convert.ToString(drvA["enterprise"]);
            tbClusterNodeNum.Text = Convert.ToString(drvA["cluster_node_num"]);
            cbbType.Text = Convert.ToString(drvA["type"]);
            dtpExpiration.Value = Convert.ToDateTime(drvA["expiration"]);
            tbSignature.Text = "";
        }

        private void btnGenSerial_Click(object sender, EventArgs e)
        {
            tbSerial.Text = Guid.NewGuid().ToString();
        }

        private void btnSave_Click(object sender, EventArgs e)
        {
            try
            {
                string CertPwd = tbCertPwd.Text;
                if (CertPwd.Length == 0)
                {
                    MessageBox.Show("请输入证书密码！");
                    return;
                }

                string RegistrationCode = tbRegistrationCode.Text;
                if (RegistrationCode.Length == 0)
                {
                    MessageBox.Show("请输入注册码！");
                    return;
                }

                string Serial = tbSerial.Text;
                if (Serial.Length == 0)
                {
                    MessageBox.Show("请生成序列号！");
                    return;
                }

                string Enterprise = tbEnterprise.Text;
                if (Enterprise.Length == 0)
                {
                    MessageBox.Show("请输入企业名称！");
                    return;
                }

                string ClusterNodeNum = tbClusterNodeNum.Text;
                if (ClusterNodeNum.Length == 0)
                {
                    MessageBox.Show("请输入节点数！");
                    return;
                }

                //检测注册码
                DataRow drOldLic = SqliteOper.GetLicense(Enterprise, RegistrationCode);
                if (drOldLic != null)
                {
                    string msg = string.Format(@"该注册码【{0}】已被企业【{1}】注册，是否覆盖？", RegistrationCode, drOldLic["enterprise"]);
                    if (MessageBox.Show(msg, "提示") != System.Windows.Forms.DialogResult.OK)
                        return;
                }

                string LicType = cbbType.Text;
                DateTime Expiration = dtpExpiration.Value;

                //签名
                string willsigndata = Serial
                    + "!" + Enterprise
                    + "@" + ClusterNodeNum
                    + "#" + RegistrationCode
                    + "$" + LicType
                    + "%" + Expiration.ToString("yyyy-MM-dd");
                X509Certificate2 xcWefafa = new X509Certificate2(AppDomain.CurrentDomain.BaseDirectory + "\\" + tbCert.Text, CertPwd);
                byte[] signdata = (xcWefafa.PrivateKey as RSACryptoServiceProvider).SignData(Encoding.UTF8.GetBytes(willsigndata), "SHA1");
                string canwritesigndata = BitConverter.ToString(signdata).Replace("-", "");
                tbSignature.Text = canwritesigndata;

                //保存
                SqliteOper.SaveLicense(Serial, Enterprise, Convert.ToDecimal(ClusterNodeNum), LicType, Expiration, RegistrationCode, canwritesigndata);

                btnSave.Enabled = false;

                //加入左侧列表
                DataRow[] foundrows = dsEnterpriseList.Tables[DM_ENTERPRISELIST].Select("enterprise='"+Enterprise+"'");
                if (foundrows.Length == 0)
                {
                    DataRow drN = dsEnterpriseList.Tables[DM_ENTERPRISELIST].NewRow();
                    drN["enterprise"] = Enterprise;
                    dsEnterpriseList.Tables[DM_ENTERPRISELIST].Rows.Add(drN);
                    dsEnterpriseList.AcceptChanges(); 
                    dgvEnterprise.BindingContext[dgvEnterprise.DataSource, dgvEnterprise.DataMember].Position = dsEnterpriseList.Tables[DM_ENTERPRISELIST].Rows.Count - 1;
                }
                else
                {
                    if (Enterprise != Convert.ToString(dsEnterpriseList.Tables[DM_ENTERPRISELIST].DefaultView[dgvEnterprise.CurrentRow.Index]["enterprise"]))
                    {
                        int i = 0;
                        foreach (DataRow drX in dsEnterpriseList.Tables[DM_ENTERPRISELIST].Rows)
                        {
                            if (Enterprise == Convert.ToString(drX["enterprise"]))
                            {
                                dgvEnterprise.BindingContext[dgvEnterprise.DataSource, dgvEnterprise.DataMember].Position = i;
                                break;
                            }
                            i++;
                        }
                    }
                    else
                    {
                        DataRow[] foundrows2 = dsNodeList.Tables[DM_NODELIST].Select("registration_code='" + RegistrationCode + "'");
                        if (foundrows2.Length > 0)
                        {
                            foundrows2[0].Delete();
                            dsNodeList.AcceptChanges();
                        }
                        DataRow drN = dsNodeList.Tables[DM_NODELIST].NewRow();
                        drN["serial"] = Serial;
                        drN["enterprise"] = Enterprise;
                        drN["cluster_node_num"] = Convert.ToDecimal(ClusterNodeNum);
                        drN["type"] = LicType;
                        drN["expiration"] = Expiration;
                        drN["registration_code"] = RegistrationCode;
                        drN["signature"] = canwritesigndata;
                        dsNodeList.Tables[DM_NODELIST].Rows.Add(drN);
                        dsNodeList.AcceptChanges();
                        dgvNode.BindingContext[dgvNode.DataSource, dgvNode.DataMember].Position = dsNodeList.Tables[DM_NODELIST].Rows.Count - 1;
                    }
                }
            }
            catch (CryptographicException ce)
            {
                MessageBox.Show(string.Format(@"证书错误：" + ce.Message));
            }
            catch (Exception ex)
            {
                MessageBox.Show(ex.Message + "\n" + ex.StackTrace);
            }
        }

        private void tbClusterNodeNum_Validating(object sender, CancelEventArgs e)
        {
            errorProvider1.SetError(tbClusterNodeNum, "");

            decimal d = 0;
            if (!decimal.TryParse(tbClusterNodeNum.Text, out d))
            {
                e.Cancel = true;
                errorProvider1.SetError(tbClusterNodeNum, "只能输入数字");
            }
        }

        private void tbFilteEnterprise_TextChanged(object sender, EventArgs e)
        {
            GetEnterpriseList();
        }

        private void btnExport_Click(object sender, EventArgs e)
        {
            SaveFileDialog sfd = new SaveFileDialog();
            sfd.Filter = "文本文件(*.txt)|*.txt|所有文件(*.*)|*.*";
            sfd.FileName = "lic.txt";
            if (sfd.ShowDialog() != System.Windows.Forms.DialogResult.OK) return;

            using (System.IO.Stream os = sfd.OpenFile())
            {
                using (System.IO.StreamWriter savestream = new System.IO.StreamWriter(os, new UTF8Encoding(false)))
                {
                    savestream.WriteLine("[IM Server]");
                    savestream.WriteLine(string.Format(@"Serial={0}", tbSerial.Text));
                    savestream.WriteLine(string.Format(@"Enterprise={0}", tbEnterprise.Text));
                    savestream.WriteLine(string.Format(@"ClusterNodeNum={0}", tbClusterNodeNum.Text));
                    savestream.WriteLine(string.Format(@"Type={0}", cbbType.Text));
                    savestream.WriteLine(string.Format(@"Expiration={0}", dtpExpiration.Value.ToString("yyyy-MM-dd")));
                    savestream.WriteLine(string.Format(@"RegistrationCode={0}", tbRegistrationCode.Text));
                    savestream.WriteLine(string.Format(@"Signature={0}", tbSignature.Text)); 
                }
            }
        }

        private void cbbType_SelectionChangeCommitted(object sender, EventArgs e)
        {
            dtpExpiration.Value = (cbbType.Text == "PROD" ? Convert.ToDateTime("2099-12-31") : DateTime.Today.AddMonths(3));
        }
    }
}
