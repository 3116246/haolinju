cd src\Justsy\BaseBundle\Resources\public\js
call compressjs.bat
cd ..\..\..\..\..\..
php app/console assets:install web
pause