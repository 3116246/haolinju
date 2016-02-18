<?php

namespace Justsy\BaseBundle\Common;

class MIME
{
  public static $MIMEImgTable = array(
    ".bmp" => "image/bmp" ,
    ".gif" => "image/gif" ,
    ".jpeg" => "image/jpeg" ,
    ".jpg" => "image/jpeg" ,
    ".png" => "image/png" ,
  );
  public static $MIMEMediaTable = array(
    ".3gp" => "video/3gpp" ,
    ".avi" => "video/x-msvideo" ,
    ".mp3" => "audio/x-mpeg" ,
    ".mp4" => "video/mp4" ,
    ".rmvb" => "audio/x-pn-realaudio" ,
    ".wav" => "audio/x-wav" ,
    ".wma" => "audio/x-ms-wma" ,
    ".wmv" => "audio/x-ms-wmv" ,
  );
  public static $MIMEOtherTable = array(
    ".apk" => "application/vnd.android.package-archive" ,
    ".asf" => "video/x-ms-asf" ,
    ".bin" => "application/octet-stream" ,
    ".c" => "text/plain" ,
    ".class" => "application/octet-stream" ,
    ".conf" => "text/plain" ,
    ".config" => "text/plain" ,
    ".cpp" => "text/plain" ,
    ".doc" => "application/msword" ,
    ".docx" => "application/vnd.openxmlformats-officedocument.wordprocessingml.document" ,
    ".xls" => "application/vnd.ms-excel" ,
    ".xlsx" => "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" ,
    ".exe" => "application/octet-stream" ,
    ".gtar" => "application/x-gtar" ,
    ".gz" => "application/x-gzip" ,
    ".h" => "text/plain" ,
    ".htm" => "text/html" ,
    ".html" => "text/html" ,
    ".jar" => "application/java-archive" ,
    ".java" => "text/plain" ,
    ".js" => "application/x-javascript" ,
    ".log" => "text/plain" ,
    ".m3u" => "audio/x-mpegurl" ,
    ".m4a" => "audio/mp4a-latm" ,
    ".m4b" => "audio/mp4a-latm" ,
    ".m4p" => "audio/mp4a-latm" ,
    ".m4u" => "video/vnd.mpegurl" ,
    ".m4v" => "video/x-m4v" ,
    ".mov" => "video/quicktime" ,
    ".mp2" => "audio/x-mpeg" ,
    ".mpc" => "application/vnd.mpohun.certificate" ,
    ".mpe" => "video/mpeg" ,
    ".mpeg" => "video/mpeg" ,
    ".mpg" => "video/mpeg" ,
    ".mpg4" => "video/mp4" ,
    ".mpga" => "audio/mpeg" ,
    ".msg" => "application/vnd.ms-outlook" ,
    ".ogg" => "audio/ogg" ,
    ".pdf" => "application/pdf" ,
    ".pps" => "application/vnd.ms-powerpoint" ,
    ".ppt" => "application/vnd.ms-powerpoint" ,
    ".pptx" => "application/vnd.openxmlformats-officedocument.presentationml.presentation" ,
    ".prop" => "text/plain" ,
    ".rc" => "text/plain" ,
    ".rtf" => "application/rtf" ,
    ".sh" => "text/plain" ,
    ".tar" => "application/x-tar" ,
    ".tgz" => "application/x-compressed" ,
    ".txt" => "text/plain" ,
    ".wps" => "application/vnd.ms-works" ,
    ".xml" => "text/plain" ,
    ".ini" => "text/plain" ,
    ".z" => "application/x-compress" ,
    ".zip" => "application/x-zip-compressed" ,
    ".rar" => "application/octet-stream" ,
    ".7z" => "application/x-7z-compressed",
    "" => "*/*" 
  );
  
  private static $MIMEImgReg = "";
  public static function getMIMEImgReg() 
  {
    if (MIME::$MIMEImgReg == "") 
    {
      MIME::$MIMEImgReg = "/".join("|", array_map(function ($item) 
        {
          return preg_replace("/\./", "\\.", $item)."$";
        }, array_keys(MIME::$MIMEImgTable)))."/";
    }
    return MIME::$MIMEImgReg;
  }
  
  private static $MIMEMediaReg = "";
  public static function getMIMEMediaReg() 
  {
    if (MIME::$MIMEMediaReg == "") 
    {
      MIME::$MIMEMediaReg = "/".join("|", array_map(function ($item) 
        {
          return preg_replace("/\./", "\\.", $item)."$";
        }, array_keys(MIME::$MIMEMediaTable)))."/";
    }
    return MIME::$MIMEMediaReg;
  }
  
  public static function getFileIcon($filename) 
  {
    $s = strtolower($filename);
    
    if (preg_match("/\.doc|\.docx$/", $s)) return "doc.png";
    else if (preg_match("/\.xls|\.xlsx$/", $s)) return "xls.png";
    else if (preg_match("/\.ppt|\.pptx$/", $s)) return "ppt.png";
    else if (preg_match("/\.pdf$/", $s)) return "pdf.png";
    else if (preg_match("/\.7z|\.gtar|\.gz|\.rar|\.tar|\.tgz|\.z|\.zip$/", $s)) return "zip.png";
    
    return "otherfileicon.png";
  }
}
