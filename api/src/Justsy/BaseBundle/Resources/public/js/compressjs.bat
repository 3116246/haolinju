for /f %%i in (compressjs.txt) do type %%i >> wefafa_all.js
java -jar ..\..\..\..\..\..\app\java\yuicompressor-2.4.7.jar --type js --charset utf-8 -o wefafa_all.min.js wefafa_all.js 
del wefafa_all.js
pause