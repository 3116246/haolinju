<?php

//生成关键词数组，以多维数组形式存储
//关键字源文本：keyword.txt，每行一词，UTF-8格式
//数组格式：[关键字节1][关键字节2][关键字节3]...[关键字节N]
//形如：中国＝》[中/1][中/2][国/1][国/2]=1
//      中级＝》            [级/1][级/2]=1

function genarray(&$arrayX, $index, &$str, $len) 
{
  if (!is_array($arrayX)) return;
  if ($index >= $len) return;
  if ($index == $len - 1) 
  {
    $arrayX[$str[$index]] = 1;
    return;
  }
  
  $x = null;
  if (!array_key_exists($str[$index], $arrayX))
  {
    $x = array();
    $arrayX[$str[$index]] = &$x;
  }
  else $x = &$arrayX[$str[$index]];
  
  genarray($x, $index+1, $str, $len);
}

$fin = fopen("keyword.txt", "rb");
$fout = fopen("KeywordArray.php", "wb");

fwrite($fout, '<?php

namespace Justsy\BaseBundle\Common;

class KeywordArray
{
  public static $Keywords = ');

$Keywords = array();
while(!feof($fin))
{
  $str = trim(fgets($fin));
  if ($str && $str != "")
  {
    $len = strlen($str);
    genarray($Keywords, 0, $str, $len);
  }
}

fwrite($fout, var_export($Keywords, 1));
fwrite($fout, ";
}");

fclose($fout);
fclose($fin);