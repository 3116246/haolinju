using System;
using System.Data;

/// <summary>
/// DynamicUpdateSqlite 的摘要说明
/// </summary>

namespace DynamicUpdate
{
    public class DynamicUpdateSqlite : MarshalByRefObject
    {
        public static string ConnStr = "";

        // 分页信息
        public int MaxRecordes = -1;
        public int PageIndex = 0;
        public int RecordCount = 0; // 当分页信息有用时，该值为总记录数，仅单个SQL GetData时有效

        protected System.Data.SQLite.SQLiteConnection odc;
        protected System.Data.SQLite.SQLiteDataAdapter oda;
        protected System.Data.SQLite.SQLiteCommand odCommSelect, odCommInsert, odCommDelete, odCommUpdate, odCommExec;
        protected static System.Collections.Hashtable FhtDataType;
        protected static System.Collections.Hashtable htDataType
        {
            get
            {
                if (FhtDataType == null)
                {
                    FhtDataType = new System.Collections.Hashtable();
                    FhtDataType.Add(System.Type.GetType("System.Boolean"), System.Data.DbType.Boolean);
                    FhtDataType.Add(System.Type.GetType("System.Byte"), System.Data.DbType.Decimal);
                    FhtDataType.Add(System.Type.GetType("System.Byte[]"), System.Data.DbType.Binary);
                    FhtDataType.Add(System.Type.GetType("System.DateTime"), System.Data.DbType.DateTime);
                    FhtDataType.Add(System.Type.GetType("System.Decimal"), System.Data.DbType.Decimal);
                    FhtDataType.Add(System.Type.GetType("System.Double"), System.Data.DbType.Decimal);
                    FhtDataType.Add(System.Type.GetType("System.Int16"), System.Data.DbType.Decimal);
                    FhtDataType.Add(System.Type.GetType("System.Int32"), System.Data.DbType.Decimal);
                    FhtDataType.Add(System.Type.GetType("System.Int64"), System.Data.DbType.Decimal);
                    FhtDataType.Add(System.Type.GetType("System.Single"), System.Data.DbType.Decimal);
                    FhtDataType.Add(System.Type.GetType("System.String"), System.Data.DbType.String);
                }
                return FhtDataType;
            }
        }

        public DynamicUpdateSqlite()
            : this(DynamicUpdateSqlite.ConnStr)
        {
        }

        public DynamicUpdateSqlite(string ConnString)
        {
            odc = new System.Data.SQLite.SQLiteConnection(ConnString);
            oda = new System.Data.SQLite.SQLiteDataAdapter();
            oda.ContinueUpdateOnError = true;
        }

        public System.Data.DataSet GetData(string MappingTable, string SQL)
        {
            return GetData(MappingTable, SQL, null, null);
        }

        public System.Data.DataSet GetData(string MappingTable, string SQL, object[] ParaValue)
        {
            System.Data.DbType[] ParaType = new System.Data.DbType[ParaValue.Length];
            for (int i = 0; i < ParaValue.Length; i++)
            {
                ParaType[i] = (System.Data.DbType)DynamicUpdateSqlite.htDataType[ParaValue[i] == null ? typeof(string) : ParaValue[i].GetType()];
            }
            return GetData(MappingTable, SQL, ParaType, ParaValue);
        }

        public System.Data.DataSet GetData(string MappingTable, string SQL, System.Data.DbType[] ParaType, object[] ParaValue)
        {
            System.Data.DataSet ds = new System.Data.DataSet();

            try
            {
                odCommSelect = new System.Data.SQLite.SQLiteCommand(SQL, odc);
                odCommSelect.CommandTimeout = 60 * 60 * 24;
                if (ParaType != null)
                {
                    for (int i = 0; i < ParaType.Length; i++)
                    {
                        odCommSelect.Parameters.Add("Para" + i, ParaType[i]).Value = ParaValue[i];
                    }
                }
                oda.SelectCommand = odCommSelect;
                
                if (MaxRecordes > -1)
                {
                    oda.Fill(ds, PageIndex * MaxRecordes, MaxRecordes, MappingTable);

                    // 统计分页时总记录数
                    DataSet dsRecordCount = new DataSet();
                    odCommSelect.CommandText = GenCountSQL(odCommSelect.CommandText);
                    oda.Fill(dsRecordCount, "RecordCount");
                    RecordCount = Convert.ToInt32(dsRecordCount.Tables[0].Rows[0][0]);
                }
                else
                {
                    oda.Fill(ds, MappingTable);
                    RecordCount = ds.Tables[MappingTable].Rows.Count;
                }
            }
            catch (Exception ex)
            {
                CCLog.WriteLog(ex);
                throw ex;
            }

            return ds;
        }

        // 取得多个数据集,MappingTables和SQLs必须一一对应
        public System.Data.DataSet GetDatas(string[] MappingTables, string[] SQLs)
        {
            return GetDatas(MappingTables, SQLs, null, null);
        }

        public System.Data.DataSet GetDatas(string[] MappingTables, string[] SQLs, object[][] ParaValues)
        {
            System.Data.DbType[][] ParaTypes = new System.Data.DbType[ParaValues.Length][];
            for (int i = 0; i < ParaValues.Length; i++)
            {
                ParaTypes[i] = new System.Data.DbType[ParaValues[i].Length];
                for (int j = 0; j < ParaValues[i].Length; j++)
                {
                    ParaTypes[i][j] = (System.Data.DbType)DynamicUpdateSqlite.htDataType[ParaValues[i][j] == null ? typeof(string) : ParaValues[i][j].GetType()];
                }
            }
            return GetDatas(MappingTables, SQLs, ParaTypes, ParaValues);
        }

        public System.Data.DataSet GetDatas(string[] MappingTables, string[] SQLs, System.Data.DbType[][] ParaTypes, object[][] ParaValues)
        {
            System.Data.DataSet ds = new System.Data.DataSet();

            try
            {
                if (MappingTables.Length != SQLs.Length) throw new Exception("表名和SQL必须一一对应!!");
                odCommSelect = new System.Data.SQLite.SQLiteCommand(SQLs[0], odc);
                odCommSelect.CommandTimeout = 60 * 60 * 24;
                oda.SelectCommand = odCommSelect;

                for (int i = 0; i < MappingTables.Length; i++)
                {
                    odCommSelect.CommandText = SQLs[i];
                    odCommSelect.Parameters.Clear();
                    if (ParaTypes != null)
                    {
                        if (ParaTypes[i] != null)
                        {
                            for (int j = 0; j < ParaTypes[i].Length; j++)
                            {
                                odCommSelect.Parameters.Add("Para" + j, ParaTypes[i][j]).Value = ParaValues[i][j];
                            }
                        }
                    }
                    if (MaxRecordes > -1)
                        oda.Fill(ds, PageIndex * MaxRecordes, MaxRecordes, MappingTables[i]);
                    else
                        oda.Fill(ds, MappingTables[i]);
                }
            }
            catch (Exception ex)
            {
                CCLog.WriteLog(ex);
                throw ex;
            }

            return ds;
        }

        // 执行SQL语句
        public int ExecSQL(string SQL)
        {
            return ExecSQL(SQL, (System.Data.SQLite.SQLiteTransaction)null);
        }

        protected int ExecSQL(string SQL, System.Data.SQLite.SQLiteTransaction odTrans)
        {
            return ExecSQL(SQL, odTrans, null, null);
        }

        public int ExecSQL(string SQL, object[] ParaValue)
        {
            System.Data.DbType[] ParaType = new System.Data.DbType[ParaValue.Length];
            for (int i = 0; i < ParaValue.Length; i++)
            {
                ParaType[i] = (System.Data.DbType)DynamicUpdateSqlite.htDataType[ParaValue[i] == null ? typeof(string) : ParaValue[i].GetType()];
            }
            return ExecSQL(SQL, ParaType, ParaValue);
        }

        public int ExecSQL(string SQL, System.Data.DbType[] ParaType, object[] ParaValue)
        {
            return ExecSQL(SQL, null, ParaType, ParaValue);
        }

        protected int ExecSQL(string SQL, System.Data.SQLite.SQLiteTransaction odTrans, System.Data.DbType[] ParaType, object[] ParaValue)
        {
            int re = -1;

            ConnectionState OldConnState = odc.State;
            try
            {
                if (odc.State == ConnectionState.Closed) odc.Open();
                odCommExec = new System.Data.SQLite.SQLiteCommand(SQL, odc);
                odCommExec.CommandTimeout = 60 * 60 * 24;
                odCommExec.Transaction = odTrans;
                if (ParaType != null)
                {
                    for (int i = 0; i < ParaType.Length; i++)
                    {
                        odCommExec.Parameters.Add("Para" + i, ParaType[i]).Value = ParaValue[i];
                    }
                }
                re = odCommExec.ExecuteNonQuery();
            }
            catch (Exception ex)
            {
                CCLog.WriteLog(ex);
                throw ex;
            }
            finally
            {
                if (OldConnState == ConnectionState.Closed) odc.Close();
            }

            return re;
        }

        public int[] ExecSQL(string[] SQL)
        {
            return ExecSQL(SQL, null, null);
        }

        public int[] ExecSQL(string[] SQL, object[][] ParaValues)
        {
            System.Data.DbType[][] ParaTypes = new System.Data.DbType[ParaValues.Length][];
            for (int i = 0; i < ParaValues.Length; i++)
            {
                ParaTypes[i] = new System.Data.DbType[ParaValues[i].Length];
                for (int j = 0; j < ParaValues[i].Length; j++)
                {
                    ParaTypes[i][j] = (System.Data.DbType)DynamicUpdateSqlite.htDataType[ParaValues[i][j] == null ? typeof(string) : ParaValues[i][j].GetType()];
                }
            }
            return ExecSQL(SQL, ParaTypes, ParaValues);
        }

        public int[] ExecSQL(string[] SQL, System.Data.DbType[][] ParaTypes, object[][] ParaValues)
        {
            int[] re = new int[SQL.Length];
            System.Data.SQLite.SQLiteTransaction odTrans = null;

            try
            {
                odc.Open();
                odTrans = odc.BeginTransaction();
                for (int i = 0; i < SQL.Length; i++)
                {
                    if (ParaTypes != null)
                        re[i] = ExecSQL(SQL[i], odTrans, ParaTypes[i], ParaValues[i]);
                    else
                        re[i] = ExecSQL(SQL[i], odTrans);
                }
                odTrans.Commit();
            }
            catch (Exception ex)
            {
                odTrans.Rollback();
                CCLog.WriteLog(ex);
                throw ex;
            }
            finally
            {
                odc.Close();
            }

            return re;
        }


        // 动态更新函数, 根据DataTable内的字段动态构造更新语句, 返回更新后的DataTable
        public System.Data.DataTable Update(System.Data.DataTable xdt, string SourceTable)
        {
            return Update(xdt, SourceTable, null);
        }

        protected System.Data.DataTable Update(System.Data.DataTable xdt, string SourceTable, System.Data.SQLite.SQLiteTransaction odTrans)
        {
            ConnectionState OldConnState = odc.State;
            try
            {
                System.Data.DataTable dtColumns = new System.Data.DataTable();
                System.Data.DataTable dt;
                if (odc.State == ConnectionState.Closed) odc.Open();

                odCommSelect = new System.Data.SQLite.SQLiteCommand("select * from " + SourceTable + " where 1 = 2", odc);
                odCommSelect.Transaction = odTrans;

                System.Data.SQLite.SQLiteDataReader read = odCommSelect.ExecuteReader();
                dt = read.GetSchemaTable();

                System.Data.DataColumn c = new System.Data.DataColumn();
                c.DataType = System.Type.GetType("System.Int16");
                c.ColumnName = "type";
                dtColumns.Columns.Add(c);

                System.Data.DataColumn c1 = new System.Data.DataColumn();
                c1.DataType = System.Type.GetType("System.String");
                c1.ColumnName = "name";
                dtColumns.Columns.Add(c1);

                System.Data.DataColumn c2 = new System.Data.DataColumn();
                c2.DataType = System.Type.GetType("System.Int32");
                c2.ColumnName = "length";
                dtColumns.Columns.Add(c2);

                System.Data.DataColumn c3 = new System.Data.DataColumn();
                c3.DataType = System.Type.GetType("System.Byte");
                c3.ColumnName = "prec";
                dtColumns.Columns.Add(c3);

                System.Data.DataColumn c4 = new System.Data.DataColumn();
                c4.DataType = System.Type.GetType("System.Byte");
                c4.ColumnName = "scale";
                dtColumns.Columns.Add(c4);

                DataRow dr;
                foreach (DataRow r in dt.Rows)
                {
                    dr = dtColumns.NewRow();
                    dr["type"] = 0;
                    dr["name"] = r[0].ToString();
                    dr["length"] = Convert.ToInt32(r[2]);
                    dr["prec"] = Convert.ToInt32(r[3]);
                    dr["scale"] = Convert.ToInt32(r[4]);
                    dtColumns.Rows.Add(dr);
                }

                read.Close();

                dtColumns.PrimaryKey = new System.Data.DataColumn[] { dtColumns.Columns["name"] };

                string Insert, Values, Where, Update, Delete;
                int length;
                byte prec, scale;
                System.Data.DataRow row;

                odCommInsert = new System.Data.SQLite.SQLiteCommand();
                odCommInsert.Connection = odc;
                odCommInsert.Transaction = odTrans;
                oda.InsertCommand = odCommInsert;

                odCommUpdate = new System.Data.SQLite.SQLiteCommand();
                odCommUpdate.Connection = odc;
                odCommUpdate.Transaction = odTrans;
                oda.UpdateCommand = odCommUpdate;

                odCommDelete = new System.Data.SQLite.SQLiteCommand();
                odCommDelete.Connection = odc;
                odCommDelete.Transaction = odTrans;
                oda.DeleteCommand = odCommDelete;

                Insert = "INSERT INTO " + SourceTable + " ( ";
                Values = " VALUES( ";
                Update = "UPDATE " + SourceTable + " SET ";
                Delete = "DELETE FROM " + SourceTable;
                foreach (System.Data.DataColumn dc in xdt.Columns)
                {
                    Insert += "[" + dc.ColumnName + "],";
                    Values += "?,";
                    Update += "[" + dc.ColumnName + "]=?,";
                    row = dtColumns.Rows.Find(dc.ColumnName);
                    if (row == null) throw new Exception("无效列名：" + dc.ColumnName);
                    prec = (byte)row["prec"];
                    scale = (byte)row["scale"];
                    length = (int)row["length"];
                    odCommInsert.Parameters.Add(new System.Data.SQLite.SQLiteParameter(dc.ColumnName, (System.Data.DbType)DynamicUpdateSqlite.htDataType[dc.DataType], length, System.Data.ParameterDirection.Input, false, prec, scale, dc.ColumnName, System.Data.DataRowVersion.Current, null));
                    odCommUpdate.Parameters.Add(new System.Data.SQLite.SQLiteParameter(dc.ColumnName, (System.Data.DbType)DynamicUpdateSqlite.htDataType[dc.DataType], length, System.Data.ParameterDirection.Input, false, prec, scale, dc.ColumnName, System.Data.DataRowVersion.Current, null));
                }

                Insert = Insert.Remove(Insert.Length - 1, 1);   // 消除最后的","号
                Values = Values.Remove(Values.Length - 1, 1);   // 消除最后的","号
                Update = Update.Remove(Update.Length - 1, 1);   // 消除最后的","号

                // 构造Where语句及其参数
                Where = " WHERE (1=1) ";
                if (xdt.PrimaryKey.Length == 0)
                {
                    foreach (System.Data.DataColumn dc in xdt.Columns)
                    {
                        row = dtColumns.Rows.Find(dc.ColumnName);
                        if (row == null) throw new Exception("无效列名：" + dc.ColumnName);
                        prec = (byte)row["prec"];
                        scale = (byte)row["scale"];
                        length = (int)row["length"];
                        if (length == 2147483647) continue;

                        Where += " AND ([" + dc.ColumnName + "]=?)";
                        odCommUpdate.Parameters.Add(new System.Data.SQLite.SQLiteParameter("Original_" + dc.ColumnName, (System.Data.DbType)DynamicUpdateSqlite.htDataType[dc.DataType], length, System.Data.ParameterDirection.Input, false, prec, scale, dc.ColumnName, System.Data.DataRowVersion.Original, null));
                        odCommDelete.Parameters.Add(new System.Data.SQLite.SQLiteParameter("Original_" + dc.ColumnName, (System.Data.DbType)DynamicUpdateSqlite.htDataType[dc.DataType], length, System.Data.ParameterDirection.Input, false, prec, scale, dc.ColumnName, System.Data.DataRowVersion.Original, null));
                    }
                }
                else
                {
                    foreach (System.Data.DataColumn dc in xdt.PrimaryKey)
                    {
                        row = dtColumns.Rows.Find(dc.ColumnName);
                        if (row == null) throw new Exception("无效列名：" + dc.ColumnName);
                        prec = (byte)row["prec"];
                        scale = (byte)row["scale"];
                        length = (int)row["length"];

                        Where += " AND ([" + dc.ColumnName + "]=?)";
                        odCommUpdate.Parameters.Add(new System.Data.SQLite.SQLiteParameter("Original_" + dc.ColumnName, (System.Data.DbType)DynamicUpdateSqlite.htDataType[dc.DataType], length, System.Data.ParameterDirection.Input, false, prec, scale, dc.ColumnName, System.Data.DataRowVersion.Original, null));
                        odCommDelete.Parameters.Add(new System.Data.SQLite.SQLiteParameter("Original_" + dc.ColumnName, (System.Data.DbType)DynamicUpdateSqlite.htDataType[dc.DataType], length, System.Data.ParameterDirection.Input, false, prec, scale, dc.ColumnName, System.Data.DataRowVersion.Original, null));
                    }
                }

                odCommInsert.CommandText = Insert + ") " + Values + ")";
                odCommUpdate.CommandText = Update + Where;
                odCommDelete.CommandText = Delete + Where;

                oda.Update(xdt);
            }
            catch (Exception ex)
            {
                CCLog.WriteLog(ex);
                throw ex;
            }
            finally
            {
                if (OldConnState == ConnectionState.Closed) odc.Close();
            }

            return xdt;
        }

        public System.Data.DataSet Update(System.Data.DataSet xds, string MappingTable, string SourceTable)
        {
            Update(xds, MappingTable, SourceTable, null);

            return xds;
        }

        protected System.Data.DataSet Update(System.Data.DataSet xds, string MappingTable, string SourceTable, System.Data.SQLite.SQLiteTransaction odTrans)
        {
            Update(xds.Tables[MappingTable], SourceTable, odTrans);

            return xds;
        }

        // 同时更新多个数据集
        public System.Data.DataSet Updates(System.Data.DataSet xds, string[] MappingTables, string[] SourceTables)
        {
            if (MappingTables.Length != SourceTables.Length) throw new Exception("映射表名和源表名必须一一对应!!");

            System.Data.SQLite.SQLiteTransaction odTrans = null;
            try
            {
                odc.Open();
                odTrans = odc.BeginTransaction();
                for (int i = 0; i < MappingTables.Length; i++)
                {
                    Update(xds.Tables[MappingTables[i]], SourceTables[i], odTrans);
                }
                odTrans.Commit();
            }
            catch (Exception ex)
            {
                odTrans.Rollback();
                throw ex;
            }
            finally
            {
                odc.Close();
            }

            return xds;
        }

        virtual public string GenCountSQL(string sql)
        {
            return "select count(*) as RecordCount from (" + sql + ") as tmp_table";
        }

        public System.Data.SQLite.SQLiteDataReader ExecuteReader(string SQL)
        {
            return ExecuteReader(SQL, null, null);
        }

        public System.Data.SQLite.SQLiteDataReader ExecuteReader(string SQL, object[] ParaValue)
        {
            System.Data.DbType[] ParaType = new System.Data.DbType[ParaValue.Length];
            for (int i = 0; i < ParaValue.Length; i++)
            {
                ParaType[i] = (System.Data.DbType)DynamicUpdateSqlite.htDataType[ParaValue[i] == null ? typeof(string) : ParaValue[i].GetType()];
            }
            return ExecuteReader(SQL, ParaType, ParaValue);
        }

        public System.Data.SQLite.SQLiteDataReader ExecuteReader(string SQL, System.Data.DbType[] ParaType, object[] ParaValue)
        {
            try
            {
                odCommSelect = new System.Data.SQLite.SQLiteCommand(SQL, odc);
                odCommSelect.CommandTimeout = 60 * 60 * 24;
                if (ParaType != null)
                {
                    for (int i = 0; i < ParaType.Length; i++)
                    {
                        odCommSelect.Parameters.Add("Para" + i, ParaType[i]).Value = ParaValue[i];
                    }
                }

                odc.Open();
                return odCommSelect.ExecuteReader(CommandBehavior.CloseConnection);
            }
            catch (Exception ex)
            {
                CCLog.WriteLog(ex);
                throw ex;
            }
        }

        public System.Data.SQLite.SQLiteConnection GetConnection()
        {
            return odc;
        }

        public System.Data.DbType GetDbType(Type AType)
        {
            return (System.Data.DbType)DynamicUpdateSqlite.htDataType[AType];
        }
    }
}