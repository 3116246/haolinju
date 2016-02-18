<?php

namespace Justsy\BaseBundle\Common;

//关键字过滤，以UTF－8编码存储及搜索
class KeywordFilter
{
  //过滤关键词，并以*替换
  public static function filterKeyword($content) 
  {
    $KeywordArray = &KeywordArray::$Keywords;
    
    $content_len = strlen($content);
    for ($i=0; $i<$content_len; $i++)
    {
      if (!array_key_exists($content[$i], $KeywordArray)) continue;
      
      $arrayX = &$KeywordArray[$content[$i]];
      $n = KeywordFilter::matchKeyword($arrayX, $content, $i+1, $content_len);
      
      if ($n <= 0) continue;
      
      for ($j = 0; $j < $n + 1; $j++)
      {
        $content[$i+$j] = '*';
      }
      $i += $n;
    }
    
    return $content;
  }
  
  public static function matchKeyword(&$wordarray, &$content, $start, $content_len) 
  {
    if ($start >= $content_len) return 0;
    
    if (!array_key_exists($content[$start], $wordarray)) return 0;
    
    $X = &$wordarray[$content[$start]];
    
    if ($X == 1) return 1;
    else 
    {
      $n = KeywordFilter::matchKeyword($X, $content, $start+1, $content_len);
      if ($n == 0) return 0;
      else return $n+1;
    }
  }
}
