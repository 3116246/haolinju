echo Options -Indexes > web\ss.txt
echo ^<IfModule mod_rewrite.c^> >> web\ss.txt
echo     RewriteEngine On >> web\ss.txt
echo     #RewriteCond %%{REQUEST_FILENAME} !-f >> web\ss.txt
echo     #RewriteRule ^^(.*)$ app.php [QSA,L] >> web\ss.txt

php app/console router:dump-apache -e=prod --no-debug >> web\ss.txt

echo ^</IfModule^> >> web\ss.txt

copy web\ss.txt web\.htaccess
del web\ss.txt

pause
