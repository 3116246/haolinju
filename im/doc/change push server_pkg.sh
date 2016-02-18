#批量更换代码

mv /var/www/justsychat2/src/FaFaTime /var/www/justsychat2/src/Justsy
mv /var/www/justsychat2/src/FaFaWebIM /var/www/justsychat2/src/WebIM
rm -rf /var/www/justsychat2/src/Justsy/WeAppTopicBundle
rm -rf /var/www/justsychat2/web/bundles/fafatimeweapptopic
rm -rf /var/www/justsychat2/src/Justsy/WeAppOrganBundle
rm -rf /var/www/justsychat2/web/bundles/fafatimeweapporgan

cd /var/www/justsychat2/src/WebIM
rename FaFaWebIM WebIM */*
rename FaFaTimeWeInterface WebIM */*/*


cd /var/www/justsychat2/src/Justsy
#change bundle dir name
mv WeBaseBundle BaseBundle
mv WeMongoDocBundle MongoDocBundle
mv WeInterfaceBundle InterfaceBundle
mv WeOpenAPIBundle OpenAPIBundle
mv MBAppBundle AdminAppBundle

#change bundle php file name
rename FaFaTime Justsy */*
rename WeBaseBundle BaseBundle */*
rename WeMongoDocBundle MongoDocBundle */*
rename WeInterfaceBundle InterfaceBundle */*
rename WeOpenAPIBundle OpenAPIBundle */*
rename MBAppBundle AdminAppBundle */*
cd /var/www/justsychat2
#change php content

sed -i "s/FaFaTime/Justsy/g" `grep FaFaTime -rl ./*`
sed -i "s/FaFaWebIM/WebIM/g" `grep FaFaWebIM -rl ./*`
sed -i "s/WeBaseBundle/BaseBundle/g" `grep WeBaseBundle -rl ./*`

sed -i "s/WeMongoDocBundle/MongoDocBundle/g" `grep WeMongoDocBundle -rl ./*`

sed -i "s/WeInterfaceBundle/InterfaceBundle/g" `grep WeInterfaceBundle -rl ./*`

sed -i "s/WeOpenAPIBundle/OpenAPIBundle/g" `grep WeOpenAPIBundle -rl ./*`
sed -i "s/MBAppBundle/AdminAppBundle/g" `grep MBAppBundle -rl ./*`

mv web/bundles/fafatimeweopenapi web/bundles/justsyopenapi

mv web/bundles/fafawebimimchat web/bundles/justsywebimimchat
mv web/bundles/fafawebimimmain web/bundles/justsywebimimmain

mv web/bundles/fafatimembapp web/bundles/justsyadminapp
mv web/bundles/fafatimewebase web/bundles/justsybase

sed -i "s/fafatimeweopenapi/justsyopenapi/g" `grep fafatimeweopenapi -rl ./*`
sed -i "s/fafawebimimchat/justsywebimimchat/g" `grep fafawebimimchat -rl ./*`
sed -i "s/fafawebimimmain/justsywebimimmain/g" `grep fafawebimimmain -rl ./*`
sed -i "s/fafatimembapp/justsyadminapp/g" `grep fafatimembapp -rl ./*`
sed -i "s/fafatimewebase/justsybase/g" `grep fafatimewebase -rl ./*`

sed -i "/JustsyWeAppTopicBundle/"d /var/www/justsychat2/app/AppKernel.php
sed -i "/JustsyWeAppOrganBundle/"d /var/www/justsychat2/app/AppKernel.php

sed -i "/JustsyWeAppTopicBundle/,+2d" /var/www/justsychat2/app/config/routing.yml
sed -i "/JustsyWeAppOrganBundle/,+2d" /var/www/justsychat2/app/config/routing.yml

echo "批处理已完成，请完成以下步骤:"
echo "1、手动更改每个Bundle下的DependencyInjection目录文件(Configuration.php和XXXExtension.php)"
echo "2、重新生成路由"
echo "3、发布服务器"
