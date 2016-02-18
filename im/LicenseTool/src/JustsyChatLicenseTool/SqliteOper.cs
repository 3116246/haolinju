using System;
using System.Collections.Generic;
using System.Text;
using System.Data;

namespace JustsyChatLicenseTool
{
    /// <summary>
    /// SqliteOper
    /// 为了线程安全，须在每个方法内声明 DynamicUpdateSqlite du;
    /// </summary>
    class SqliteOper
    {
        private static string connstr = "pooling=True;data source=" + AppDomain.CurrentDomain.BaseDirectory + "ImServerLicense.db";

        public static void CheckDBVersion()
        {
            DynamicUpdate.DynamicUpdateSqlite du = new DynamicUpdate.DynamicUpdateSqlite(connstr);

            int dbversion = 0;

            DataSet dsX = du.GetData("sqlite_master", "select [sql] from sqlite_master where [type] = 'table' and lower(name) = ?", new object[] { "dbversion" });
            bool has_dbversion = (dsX.Tables["sqlite_master"].Rows.Count > 0);
            if (has_dbversion)
            {
                dsX = du.GetData("dbversion", "select version from dbversion");
                if (dsX.Tables["dbversion"].Rows.Count > 0)
                {
                    dbversion = Convert.ToInt32(dsX.Tables["dbversion"].Rows[0]["version"]);
                }
            }
            int lastversioin = 0;
            for (int i = 0; i < DBInitSQL.InitSql.Length; )
            {
                lastversioin = (int)DBInitSQL.InitSql[i];
                if (dbversion < lastversioin)
                {
                    i++;
                    string[] sqls = (string[])DBInitSQL.InitSql[i];
                    for (int j = 0; j < sqls.Length; j++)
                    {
                        du.ExecSQL(sqls[j]);
                    }
                }
                else
                {
                    i++;
                }

                i++;
            }
            if (dbversion < lastversioin)
            {
                du.ExecSQL("delete from dbversion");
                du.ExecSQL("insert into dbversion(version, note) values(?, ?)", new object[] { lastversioin, "" });
            }
        }

        public static DataSet GetEnterpriseList(string logictablename, string Aenterprise)
        {
            DataSet re = null;

            DynamicUpdate.DynamicUpdateSqlite du = new DynamicUpdate.DynamicUpdateSqlite(connstr);

            if (Aenterprise == null || Aenterprise.Length == 0)
            {
                string sql = @"select distinct enterprise from wlt_license";
                re = du.GetData(logictablename, sql);
            }
            else
            {
                string sql = @"select distinct enterprise from wlt_license where enterprise like ('%' || ? || '%')";
                re = du.GetData(logictablename, sql, new object[] { Aenterprise });
            }

            return re;
        }

        public static DataSet GetNodeList(string logictablename, string ename)
        {
            DataSet re = null;

            DynamicUpdate.DynamicUpdateSqlite du = new DynamicUpdate.DynamicUpdateSqlite(connstr);

            string sql = @"select serial, enterprise, cluster_node_num, type, expiration, registration_code, signature from wlt_license where enterprise=?";
            re = du.GetData(logictablename, sql, new object[] { ename });

            return re;
        }

        public static DataRow GetLicense(string AEnterprise, string ARegistrationCode)
        {
            DataRow re = null;

            DynamicUpdate.DynamicUpdateSqlite du = new DynamicUpdate.DynamicUpdateSqlite(connstr);

            string sql = @"select serial, enterprise, cluster_node_num, type, expiration, registration_code, signature from wlt_license where enterprise=? and registration_code=?";
            DataSet dsA = du.GetData("wlt_license", sql, new object[] { AEnterprise, ARegistrationCode });
            if (dsA != null && dsA.Tables["wlt_license"].Rows.Count > 0)
                re = dsA.Tables["wlt_license"].Rows[0];

            return re;            
        }

        public static bool SaveLicense(string Aserial, string Aenterprise, decimal Acluster_node_num, string Atype, DateTime Aexpiration, string Aregistration_code, string Asignature)
        {
            bool re = false;

            DynamicUpdate.DynamicUpdateSqlite du = new DynamicUpdate.DynamicUpdateSqlite(connstr);

            string sqldel = @"delete from wlt_license where enterprise=? and registration_code=?";
            string sqlins = @"insert into wlt_license(serial, enterprise, cluster_node_num, type, expiration, registration_code, signature) values(?, ?, ?, ?, ?, ?, ?)";
            du.ExecSQL(new string[] { sqldel, sqlins },
                new object[][]{
                    new object[] { Aenterprise, Aregistration_code },
                    new object[] { Aserial, Aenterprise, Acluster_node_num, Atype, Aexpiration, Aregistration_code, Asignature }});
            re = true;

            return re;
        }
    }
} 