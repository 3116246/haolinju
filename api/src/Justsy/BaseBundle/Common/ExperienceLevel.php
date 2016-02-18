<?php

namespace Justsy\BaseBundle\Common;

//经验值计算类
//级别的经验值＝（级别×级别×级别－上一级别×上一级别×上一级别）×2 ＋上一级别的经验值
class ExperienceLevel
{
  private static $ExperienceLevels = null;
  
  public static function getExperienceLevels() 
  {
    if (ExperienceLevel::$ExperienceLevels == null)
    {
      ExperienceLevel::$ExperienceLevels = array();
      ExperienceLevel::$ExperienceLevels[] = 0;
      for ($i=1; $i<=100; $i++)
      {
        ExperienceLevel::$ExperienceLevels[$i] = ($i * $i * $i - ($i - 1) * ($i - 1) * ($i - 1)) * 2 + ExperienceLevel::$ExperienceLevels[$i - 1];
      }
    }
    return ExperienceLevel::$ExperienceLevels;
  }
  
  //根据经验值计算级别
  public static function getLevel($Experience) 
  {
    $AExperienceLevels = ExperienceLevel::getExperienceLevels();
    $count = count($AExperienceLevels);
    
    for($i=1; $i < $count; $i++)
    {
      if ($Experience < $AExperienceLevels[$i]) return ($i - 1);
    }
    return $count - 1;
  }
}
