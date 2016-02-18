#!/bin/sh
if [ "`whoami`" != "root" ]; then
  echo "must use root run this script!!!"
  exit
fi
PWD=`pwd`
cd ~/fafaim_update
svn update
cp -R bson/ebin /opt/fafa/lib/bson/
cp -R bson/include /opt/fafa/lib/bson/
cp -R ejabberd-2.1.11/ebin /opt/fafa/lib/ejabberd-2.1.11/
cp -R ejabberd-2.1.11/include /opt/fafa/lib/ejabberd-2.1.11/
cp -R ejabberd_ex/ebin /opt/fafa/lib/ejabberd_ex/
cp -R ejabberd_ex/include /opt/fafa/lib/ejabberd_ex/
cp -R mongodb/ebin /opt/fafa/lib/mongodb/
cp -R mongodb/include /opt/fafa/lib/mongodb/
cp -R mysql-2011.0825/ebin /opt/fafa/lib/mysql-2011.0825/
cp -R Yaws-1.89 /opt/fafa/lib/
cp -R public_key-0.13 /opt/fafa/lib/
cp -R apns4erl/ebin /opt/fafa/lib/apns4erl/
cp -R apns4erl/include /opt/fafa/lib/apns4erl/
cp -R apns4erl/priv /opt/fafa/lib/apns4erl/

cd /opt/fafa/bin
./ejabberdctl update all

cd $PWD

