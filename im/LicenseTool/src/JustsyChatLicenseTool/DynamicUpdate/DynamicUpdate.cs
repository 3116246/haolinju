using System;
using System.Data;

/// <summary>
/// DynamicUpdate 的摘要说明
/// 
/// 非线程安全类
/// </summary>
namespace DynamicUpdate
{
    public abstract class DynamicUpdate : MarshalByRefObject
    {
        public static string ConnStr = "";

        public DynamicUpdate()
        {
        }

        public enum DataBaseType { Sybase, SQLServer, Access, Oralce };
        public static DynamicUpdate CreateInstance(DataBaseType dbt)
        {
            DynamicUpdate du = null;
            switch (dbt)
            {
                case DataBaseType.Sybase:
                    du = new DynamicUpdateSybase();
                    break;
                case DataBaseType.SQLServer:
                    du = new DynamicUpdateSQLServer();
                    break;
                case DataBaseType.Access:
                    du = new DynamicUpdateAccess();
                    break;
                case DataBaseType.Oralce:
                    du = new DynamicUpdateOracle();
                    break;
            }
            return du;
        }
        public static DynamicUpdate CreateInstance(DataBaseType dbt, string connstr)
        {
            DynamicUpdate du = null;
            switch (dbt)
            {
                case DataBaseType.Sybase:
                    du = new DynamicUpdateSybase(connstr);
                    break;
                case DataBaseType.SQLServer:
                    du = new DynamicUpdateSQLServer(connstr);
                    break;
                case DataBaseType.Access:
                    du = new DynamicUpdateAccess(connstr);
                    break;
                case DataBaseType.Oralce:
                    du = new DynamicUpdateOracle(connstr);
                    break;
            }
            return du;
        }
        public static DynamicUpdate CreateInstance(string connstr)
        {
            DynamicUpdate du = null;
            if (connstr.IndexOf("Oracle",StringComparison.CurrentCultureIgnoreCase) >= 0)
            {
                du = CreateInstance(DataBaseType.Oralce, connstr);
            }
            else if (connstr.IndexOf("Sybase", StringComparison.CurrentCultureIgnoreCase) >= 0)
            {
                du = CreateInstance(DataBaseType.Sybase, connstr);
            }
            else if (connstr.IndexOf("SQLOLEDB", StringComparison.CurrentCultureIgnoreCase) >= 0)
            {
                du = CreateInstance(DataBaseType.SQLServer, connstr);
            }
            else if (connstr.IndexOf("Microsoft.Jet.OLEDB", StringComparison.CurrentCultureIgnoreCase) >= 0)
            {
                du = CreateInstance(DataBaseType.Access, connstr);
            }
            return du;
        }

        // 分页信息
        public int MaxRecordes = -1;
        public int PageIndex = 0;
        public int RecordCount = 0; // 当分页信息有用时，该值为总记录数，仅单个SQL GetData时有效

        abstract public System.Data.DataSet GetData(string MappingTable, string SQL);
        abstract public System.Data.DataSet GetData(string MappingTable, string SQL, object[] ParaValue);
        abstract public System.Data.DataSet GetData(string MappingTable, string SQL, System.Data.OleDb.OleDbType[] ParaType, object[] ParaValue);

        // 取得多个数据集,MappingTables和SQLs必须一一对应
        abstract public System.Data.DataSet GetDatas(string[] MappingTables, string[] SQLs);
        abstract public System.Data.DataSet GetDatas(string[] MappingTables, string[] SQLs, object[][] ParaValues);
        abstract public System.Data.DataSet GetDatas(string[] MappingTables, string[] SQLs, System.Data.OleDb.OleDbType[][] ParaTypes, object[][] ParaValues);

        // 执行SQL语句
        abstract public int ExecSQL(string SQL);
        abstract public int ExecSQL(string SQL, object[] ParaValue);
        abstract public int ExecSQL(string SQL, System.Data.OleDb.OleDbType[] ParaType, object[] ParaValue);
        abstract public int[] ExecSQL(string[] SQL);
        abstract public int[] ExecSQL(string[] SQL, object[][] ParaValues);
        abstract public int[] ExecSQL(string[] SQL, System.Data.OleDb.OleDbType[][] ParaTypes, object[][] ParaValues);

        // 动态更新函数, 根据DataTable内的字段动态构造更新语句, 返回更新后的DataTable
        abstract public System.Data.DataTable Update(System.Data.DataTable xdt, string SourceTable);

        abstract public System.Data.DataSet Update(System.Data.DataSet xds, string MappingTable, string SourceTable);

        // 同时更新多个数据集
        abstract public System.Data.DataSet Updates(System.Data.DataSet xds, string[] MappingTables, string[] SourceTables);

        // 生成取总记录数SQL
        virtual public string GenCountSQL(string sql)
        {
            return "select count(*) as RecordCount from ("+sql+") as tmp_table";
        }

        /// <summary>
        /// 返回一个OleDbDataReader，该系列函数仅能用在服务端，使用参数CommandBehavior.CloseConnection打开，记得使用后一定要关闭OleDbDataReader
        /// </summary>
        /// <param name="SQL"></param>
        /// <returns></returns>
        abstract public System.Data.OleDb.OleDbDataReader ExecuteReader(string SQL);
        abstract public System.Data.OleDb.OleDbDataReader ExecuteReader(string SQL, object[] ParaValue);
        abstract public System.Data.OleDb.OleDbDataReader ExecuteReader(string SQL, System.Data.OleDb.OleDbType[] ParaType, object[] ParaValue);

        /// <summary>
        /// 返回当前Connection，该系列函数仅能用在服务端，记得使用后一定要关闭Connection
        /// </summary>
        /// <returns></returns>
        abstract public System.Data.OleDb.OleDbConnection GetConnection();

        /// <summary>
        /// 返回指定AType对应的OleDbType
        /// </summary>
        /// <param name="AType"></param>
        /// <returns></returns>
        abstract public System.Data.OleDb.OleDbType GetOleDbType(Type AType);
    }
}