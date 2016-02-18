﻿using System;
using System.Data;

/// <summary>
/// DynamicUpdateOracle 的摘要说明
/// 
/// 非线程安全类
/// </summary>
namespace DynamicUpdate
{
    public class DynamicUpdateOracle : DynamicUpdate
    {
        protected System.Data.OleDb.OleDbConnection odc;
        protected System.Data.OleDb.OleDbDataAdapter oda;
        protected System.Data.OleDb.OleDbCommand odCommSelect, odCommInsert, odCommDelete, odCommUpdate, odCommExec;
        protected static System.Collections.Hashtable FhtDataType;
        protected static System.Collections.Hashtable htDataType
        {
            get
            {
                if (FhtDataType == null)
                {
                    FhtDataType = new System.Collections.Hashtable();
                    FhtDataType.Add(System.Type.GetType("System.Boolean"), System.Data.OleDb.OleDbType.Boolean);
                    FhtDataType.Add(System.Type.GetType("System.Byte"), System.Data.OleDb.OleDbType.Decimal);
                    FhtDataType.Add(System.Type.GetType("System.Byte[]"), System.Data.OleDb.OleDbType.LongVarBinary);
                    FhtDataType.Add(System.Type.GetType("System.DateTime"), System.Data.OleDb.OleDbType.DBTimeStamp);
                    FhtDataType.Add(System.Type.GetType("System.Decimal"), System.Data.OleDb.OleDbType.Decimal);
                    FhtDataType.Add(System.Type.GetType("System.Double"), System.Data.OleDb.OleDbType.Decimal);
                    FhtDataType.Add(System.Type.GetType("System.Int16"), System.Data.OleDb.OleDbType.Decimal);
                    FhtDataType.Add(System.Type.GetType("System.Int32"), System.Data.OleDb.OleDbType.Decimal);
                    FhtDataType.Add(System.Type.GetType("System.Int64"), System.Data.OleDb.OleDbType.Decimal);
                    FhtDataType.Add(System.Type.GetType("System.Single"), System.Data.OleDb.OleDbType.Decimal);
                    FhtDataType.Add(System.Type.GetType("System.String"), System.Data.OleDb.OleDbType.VarChar);
                }
                return FhtDataType;
            }
        }

        public DynamicUpdateOracle()
            : this(DynamicUpdate.ConnStr)
        {
        }

        public DynamicUpdateOracle(string ConnString)
        {
            odc = new System.Data.OleDb.OleDbConnection(ConnString);
            oda = new System.Data.OleDb.OleDbDataAdapter();
            oda.ContinueUpdateOnError = true;
        }

        override public System.Data.DataSet GetData(string MappingTable, string SQL)
        {
            return GetData(MappingTable, SQL, null, null);
        }

        override public System.Data.DataSet GetData(string MappingTable, string SQL, object[] ParaValue)
        {
            System.Data.OleDb.OleDbType[] ParaType = new System.Data.OleDb.OleDbType[ParaValue.Length];
            for (int i = 0; i < ParaValue.Length; i++)
            {
                ParaType[i] = (System.Data.OleDb.OleDbType)DynamicUpdateOracle.htDataType[ParaValue[i] == null ? typeof(string) : ParaValue[i].GetType()];
            }
            return GetData(MappingTable, SQL, ParaType, ParaValue);
        }

        override public System.Data.DataSet GetData(string MappingTable, string SQL, System.Data.OleDb.OleDbType[] ParaType, object[] ParaValue)
        {
            System.Data.DataSet ds = new System.Data.DataSet();

            try
            {
                odCommSelect = new System.Data.OleDb.OleDbCommand(SQL, odc);
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
                    oda.Fill(ds, PageIndex * MaxRecordes, MaxRecordes, MappingTable);
                else
                    oda.Fill(ds, MappingTable);
            }
            catch (Exception ex)
            {
                CCLog.WriteLog(ex);
                throw ex;
            }

            return ds;
        }

        // 取得多个数据集,MappingTables和SQLs必须一一对应
        override public System.Data.DataSet GetDatas(string[] MappingTables, string[] SQLs)
        {
            return GetDatas(MappingTables, SQLs, null, null);
        }

        override public System.Data.DataSet GetDatas(string[] MappingTables, string[] SQLs, object[][] ParaValues)
        {
            System.Data.OleDb.OleDbType[][] ParaTypes = new System.Data.OleDb.OleDbType[ParaValues.Length][];
            for (int i = 0; i < ParaValues.Length; i++)
            {
                ParaTypes[i] = new System.Data.OleDb.OleDbType[ParaValues[i].Length];
                for (int j = 0; j < ParaValues[i].Length; j++)
                {
                    ParaTypes[i][j] = (System.Data.OleDb.OleDbType)DynamicUpdateOracle.htDataType[ParaValues[i][j] == null ? typeof(string) : ParaValues[i][j].GetType()];
                }
            }
            return GetDatas(MappingTables, SQLs, ParaTypes, ParaValues);
        }

        override public System.Data.DataSet GetDatas(string[] MappingTables, string[] SQLs, System.Data.OleDb.OleDbType[][] ParaTypes, object[][] ParaValues)
        {
            System.Data.DataSet ds = new System.Data.DataSet();

            try
            {
                if (MappingTables.Length != SQLs.Length) throw new Exception("表名和SQL必须一一对应!!");
                odCommSelect = new System.Data.OleDb.OleDbCommand(SQLs[0], odc);
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
        override public int ExecSQL(string SQL)
        {
            return ExecSQL(SQL, null);
        }

        protected int ExecSQL(string SQL, System.Data.OleDb.OleDbTransaction odTrans)
        {
            return ExecSQL(SQL, odTrans, null, null);
        }

        override public int ExecSQL(string SQL, object[] ParaValue)
        {
            System.Data.OleDb.OleDbType[] ParaType = new System.Data.OleDb.OleDbType[ParaValue.Length];
            for (int i = 0; i < ParaValue.Length; i++)
            {
                ParaType[i] = (System.Data.OleDb.OleDbType)DynamicUpdateOracle.htDataType[ParaValue[i] == null ? typeof(string) : ParaValue[i].GetType()];
            }
            return ExecSQL(SQL, ParaType, ParaValue);
        }

        override public int ExecSQL(string SQL, System.Data.OleDb.OleDbType[] ParaType, object[] ParaValue)
        {
            return ExecSQL(SQL, null, ParaType, ParaValue);
        }

        protected int ExecSQL(string SQL, System.Data.OleDb.OleDbTransaction odTrans, System.Data.OleDb.OleDbType[] ParaType, object[] ParaValue)
        {
            int re = -1;

            ConnectionState OldConnState = odc.State;
            try
            {
                if (odc.State == ConnectionState.Closed) odc.Open();
                odCommExec = new System.Data.OleDb.OleDbCommand(SQL, odc);
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

        override public int[] ExecSQL(string[] SQL)
        {
            return ExecSQL(SQL, null, null);
        }

        override public int[] ExecSQL(string[] SQL, object[][] ParaValues)
        {
            System.Data.OleDb.OleDbType[][] ParaTypes = new System.Data.OleDb.OleDbType[ParaValues.Length][];
            for (int i = 0; i < ParaValues.Length; i++)
            {
                ParaTypes[i] = new System.Data.OleDb.OleDbType[ParaValues[i].Length];
                for (int j = 0; j < ParaValues[i].Length; j++)
                {
                    ParaTypes[i][j] = (System.Data.OleDb.OleDbType)DynamicUpdateOracle.htDataType[ParaValues[i][j] == null ? typeof(string) : ParaValues[i][j].GetType()];
                }
            }
            return ExecSQL(SQL, ParaTypes, ParaValues);
        }

        override public int[] ExecSQL(string[] SQL, System.Data.OleDb.OleDbType[][] ParaTypes, object[][] ParaValues)
        {
            int[] re = new int[SQL.Length];
            System.Data.OleDb.OleDbTransaction odTrans = null;

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
        override public System.Data.DataTable Update(System.Data.DataTable xdt, string SourceTable)
        {
            return Update(xdt, SourceTable, null);
        }

        protected System.Data.DataTable Update(System.Data.DataTable xdt, string SourceTable, System.Data.OleDb.OleDbTransaction odTrans)
        {
            try
            {
                System.Data.DataTable dtColumns = new System.Data.DataTable();

                string sqlSelCol = "SELECT COLUMN_NAME, DATA_TYPE, (CASE WHEN DATA_TYPE IN ('BLOB', 'CLOB', 'LONG RAW') THEN 2147483647 ELSE DATA_LENGTH END) DATA_LENGTH, NVL(DATA_PRECISION, 0) DATA_PRECISION, NVL(DATA_SCALE, 0) DATA_SCALE FROM USER_TAB_COLUMNS WHERE TABLE_NAME=UPPER('" + SourceTable + "')";
                int dotidx = SourceTable.IndexOf('.');
                if (dotidx>0)
                {
                    sqlSelCol = "SELECT COLUMN_NAME, DATA_TYPE, (CASE WHEN DATA_TYPE IN ('BLOB', 'CLOB', 'LONG RAW') THEN 2147483647 ELSE DATA_LENGTH END) DATA_LENGTH, NVL(DATA_PRECISION, 0) DATA_PRECISION, NVL(DATA_SCALE, 0) DATA_SCALE FROM ALL_TAB_COLUMNS WHERE OWNER=UPPER('"+SourceTable.Substring(0, dotidx)+"') AND TABLE_NAME=UPPER('" + SourceTable.Substring(dotidx+1) + "')";
                }
                odCommSelect = new System.Data.OleDb.OleDbCommand(sqlSelCol, odc);
                odCommSelect.Transaction = odTrans;
                oda.SelectCommand = odCommSelect;

                oda.Fill(dtColumns);
                dtColumns.PrimaryKey = new System.Data.DataColumn[] { dtColumns.Columns["COLUMN_NAME"] };

                string Insert, Values, Where, Update, Delete;
                int length;
                byte prec, scale;
                System.Data.DataRow row;

                odCommInsert = new System.Data.OleDb.OleDbCommand();
                odCommInsert.Connection = odc;
                odCommInsert.Transaction = odTrans;
                oda.InsertCommand = odCommInsert;

                odCommUpdate = new System.Data.OleDb.OleDbCommand();
                odCommUpdate.Connection = odc;
                odCommUpdate.Transaction = odTrans;
                oda.UpdateCommand = odCommUpdate;

                odCommDelete = new System.Data.OleDb.OleDbCommand();
                odCommDelete.Connection = odc;
                odCommDelete.Transaction = odTrans;
                oda.DeleteCommand = odCommDelete;

                Insert = "INSERT INTO " + SourceTable + " ( ";
                Values = " VALUES( ";
                Update = "UPDATE " + SourceTable + " SET ";
                Delete = "DELETE FROM " + SourceTable;
                foreach (System.Data.DataColumn dc in xdt.Columns)
                {
                    Insert += dc.ColumnName + ",";
                    Values += "?,";
                    Update += dc.ColumnName + "=?,";
                    row = dtColumns.Rows.Find(dc.ColumnName);
                    if (row == null) throw new Exception("无效列名：" + dc.ColumnName);
                    prec = Convert.ToByte(row["DATA_PRECISION"]);
                    scale = Convert.ToByte(row["DATA_SCALE"]);
                    length = Convert.ToInt32(row["DATA_LENGTH"]);
                    odCommInsert.Parameters.Add(new System.Data.OleDb.OleDbParameter(dc.ColumnName, (System.Data.OleDb.OleDbType)DynamicUpdateOracle.htDataType[dc.DataType], length, System.Data.ParameterDirection.Input, false, prec, scale, dc.ColumnName, System.Data.DataRowVersion.Current, null));
                    odCommUpdate.Parameters.Add(new System.Data.OleDb.OleDbParameter(dc.ColumnName, (System.Data.OleDb.OleDbType)DynamicUpdateOracle.htDataType[dc.DataType], length, System.Data.ParameterDirection.Input, false, prec, scale, dc.ColumnName, System.Data.DataRowVersion.Current, null));
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
                        prec = Convert.ToByte(row["DATA_PRECISION"]);
                        scale = Convert.ToByte(row["DATA_SCALE"]);
                        length = Convert.ToInt32(row["DATA_LENGTH"]);
                        if (length == 2147483647) continue;

                        Where += " AND (" + dc.ColumnName + "=?)";
                        odCommUpdate.Parameters.Add(new System.Data.OleDb.OleDbParameter("Original_" + dc.ColumnName, (System.Data.OleDb.OleDbType)DynamicUpdateOracle.htDataType[dc.DataType], length, System.Data.ParameterDirection.Input, false, prec, scale, dc.ColumnName, System.Data.DataRowVersion.Original, null));
                        odCommDelete.Parameters.Add(new System.Data.OleDb.OleDbParameter("Original_" + dc.ColumnName, (System.Data.OleDb.OleDbType)DynamicUpdateOracle.htDataType[dc.DataType], length, System.Data.ParameterDirection.Input, false, prec, scale, dc.ColumnName, System.Data.DataRowVersion.Original, null));
                    }
                }
                else
                {
                    foreach (System.Data.DataColumn dc in xdt.PrimaryKey)
                    {
                        row = dtColumns.Rows.Find(dc.ColumnName);
                        if (row == null) throw new Exception("无效列名：" + dc.ColumnName);
                        prec = Convert.ToByte(row["DATA_PRECISION"]);
                        scale = Convert.ToByte(row["DATA_SCALE"]);
                        length = Convert.ToInt32(row["DATA_LENGTH"]);

                        Where += " AND (" + dc.ColumnName + "=?)";
                        odCommUpdate.Parameters.Add(new System.Data.OleDb.OleDbParameter("Original_" + dc.ColumnName, (System.Data.OleDb.OleDbType)DynamicUpdateOracle.htDataType[dc.DataType], length, System.Data.ParameterDirection.Input, false, prec, scale, dc.ColumnName, System.Data.DataRowVersion.Original, null));
                        odCommDelete.Parameters.Add(new System.Data.OleDb.OleDbParameter("Original_" + dc.ColumnName, (System.Data.OleDb.OleDbType)DynamicUpdateOracle.htDataType[dc.DataType], length, System.Data.ParameterDirection.Input, false, prec, scale, dc.ColumnName, System.Data.DataRowVersion.Original, null));
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

            return xdt;
        }

        override public System.Data.DataSet Update(System.Data.DataSet xds, string MappingTable, string SourceTable)
        {
            Update(xds, MappingTable, SourceTable, null);

            return xds;
        }

        protected System.Data.DataSet Update(System.Data.DataSet xds, string MappingTable, string SourceTable, System.Data.OleDb.OleDbTransaction odTrans)
        {
            Update(xds.Tables[MappingTable], SourceTable, odTrans);

            return xds;
        }

        // 同时更新多个数据集
        override public System.Data.DataSet Updates(System.Data.DataSet xds, string[] MappingTables, string[] SourceTables)
        {
            if (MappingTables.Length != SourceTables.Length) throw new Exception("映射表名和源表名必须一一对应!!");

            System.Data.OleDb.OleDbTransaction odTrans = null;
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

        override public System.Data.OleDb.OleDbDataReader ExecuteReader(string SQL)
        {
            return ExecuteReader(SQL, null, null);
        }

        override public System.Data.OleDb.OleDbDataReader ExecuteReader(string SQL, object[] ParaValue)
        {
            System.Data.OleDb.OleDbType[] ParaType = new System.Data.OleDb.OleDbType[ParaValue.Length];
            for (int i = 0; i < ParaValue.Length; i++)
            {
                ParaType[i] = (System.Data.OleDb.OleDbType)DynamicUpdateOracle.htDataType[ParaValue[i] == null ? typeof(string) : ParaValue[i].GetType()];
            }
            return ExecuteReader(SQL, ParaType, ParaValue);
        }

        override public System.Data.OleDb.OleDbDataReader ExecuteReader(string SQL, System.Data.OleDb.OleDbType[] ParaType, object[] ParaValue)
        {
            try
            {
                odCommSelect = new System.Data.OleDb.OleDbCommand(SQL, odc);
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

        override public System.Data.OleDb.OleDbConnection GetConnection()
        {
            return odc;
        }

        override public System.Data.OleDb.OleDbType GetOleDbType(Type AType)
        {
            return (System.Data.OleDb.OleDbType)DynamicUpdateOracle.htDataType[AType];
        }
    }
}