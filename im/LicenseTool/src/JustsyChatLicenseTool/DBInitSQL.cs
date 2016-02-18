using System;
using System.Collections.Generic;
using System.Text;

namespace JustsyChatLicenseTool
{
    class DBInitSQL
    {
        private static object[] _InitSql = null;
        public static object[] InitSql
        {
            get
            {
                if (_InitSql == null)
                {
                    _InitSql = new object[] {
                        0, new string[]{}, //占位
                        1, new string[]{
                                @"create table dbversion (
version              numeric(8)                     not null,	
note                 text,	
primary key (version)	
)",
                                @"create table wlt_license (
serial              varchar(100),
enterprise          varchar(100)                     not null,
cluster_node_num    decimal(8),
type                varchar(100),
expiration          datetime,
registration_code   varchar(100)                     not null,
signature           text,
primary key (enterprise, registration_code)
)"}
                    };
                }

                return _InitSql;
            }
        }
    }
}
