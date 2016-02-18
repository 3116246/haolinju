<?php
namespace Justsy\BaseBundle\Common;

class DES
{
  public static $key = "_sddb74+";
  private static $iv = "_sddb74+"; //Æ«ÒÆÁ¿

  public static function encrypt($str) 
  {
    //¼ÓÃÜ£¬·µ»Ø´óÐ´Ê®Áù½øÖÆ×Ö·û´®
    $size = mcrypt_get_block_size(MCRYPT_DES, MCRYPT_MODE_CBC);
    return strtoupper(bin2hex(mcrypt_cbc(MCRYPT_DES, DES::$key, $str, MCRYPT_ENCRYPT, DES::$iv)));
  }
  
  public static function encrypt2($str,$key) 
  {
    //¼ÓÃÜ£¬·µ»Ø´óÐ´Ê®Áù½øÖÆ×Ö·û´®
    $size = mcrypt_get_block_size(MCRYPT_DES, MCRYPT_MODE_CBC);
    return strtoupper(bin2hex(mcrypt_cbc(MCRYPT_DES, $key, $str, MCRYPT_ENCRYPT, $key)));
  }  

  public static function decrypt($str) 
  {
    //½âÃÜ
    $strBin = hex2bin(strtolower($str));
    $str = mcrypt_cbc(MCRYPT_DES, DES::$key, $strBin, MCRYPT_DECRYPT, DES::$iv);
    return trim($str);
  }
  public static function decrypt2($str,$key) 
  {
    //½âÃÜ
    $strBin = hex2bin(strtolower($str));
    $str = mcrypt_cbc(MCRYPT_DES, $key, $strBin, MCRYPT_DECRYPT, $key);
    return trim($str);
  }  

  //AES 128 位加密方式。加密的算法模式是 ECB,密钥长度是 128 位，补码方式是 PKCS5Padding，加密结果编码方式是 base64
  public static function decrypt_crv_fortoken($str,$key)
  {
		//$str="c0DM/Zlbb796FIbuzAv5DACxbXpE0zwgmf5UDVfiWVHF4sz2g+6srYxIUuzukdOS";
		//$str = urlDecode($str);
		//echo $str."\n";
		//$key = "crv-km";
		$keystr = $key;
		//echo $keystr."\n";
		$str = base64_decode($str);
		//echo $str."\n";
		$strBin =$str;// hex2bin(strtolower($str));
		//    $str = mcrypt_cbc(MCRYPT_DES, $keystr, $strBin, MCRYPT_DECRYPT, $keystr);
		$td = mcrypt_module_open(MCRYPT_RIJNDAEL_128,'','ecb','');
		//使用MCRYPT_DES算法,cbc模式                
		$iv = @mcrypt_create_iv(mcrypt_enc_get_iv_size($td), MCRYPT_RAND);
		$ks = mcrypt_enc_get_key_size($td);
		@mcrypt_generic_init($td, $keystr, $iv);
		//初始处理                
		$data = mdecrypt_generic($td, $strBin);
		//解密              
		mcrypt_generic_deinit($td);
		//结束            
		mcrypt_module_close($td);
		//echo $data."\n";
		$text = $data;
		$pad = ord($text{strlen($text)-1});
		if ($pad > strlen($text))
		    return false;
		if (strspn($text, chr($pad), strlen($text) - $pad) != $pad)
		    return false;
		$data = substr($text, 0, -1 * $pad);
		//echo $data."\n";
		return $data;
  }
 
  //华创专用解密
  //T1TBB%2BZDMFIEIJyv1h2v9Jydh%2FV37iawPOksIXXDF5V9jmxWAOOjW1Yd%2Be8XzNxIrX0UCtksWHM7F10Jqw9PUkdIKZ9Kyqvn4wrIqdf3l9ruOtdzSXlX2w%3D%3D
  public static function decrypt_other_crv($str)
  {
		$str = urlDecode($str);
        $key = [ -36, -63, 49, 37, -56, -32, 103, -85 ];
 		$keystr = '';
        foreach($key as $ch) {
            $keystr .= chr($ch);
        }
		$str = base64_decode($str);
		$strBin =$str;// hex2bin(strtolower($str));
		$td = mcrypt_module_open('des','','ecb','');
        //使用MCRYPT_DES算法,cbc模式                
        $iv = @mcrypt_create_iv(mcrypt_enc_get_iv_size($td), MCRYPT_RAND);            
        $ks = mcrypt_enc_get_key_size($td);               
        @mcrypt_generic_init($td, $keystr, $iv);         
        //初始处理                
        $text = mdecrypt_generic($td, $strBin);         
        //解密              
        mcrypt_generic_deinit($td);         
        //结束            
        mcrypt_module_close($td);
        $pad = ord($text{strlen($text)-1});       
        if ($pad > strlen($text))              
            return false;         
        if (strspn($text, chr($pad), strlen($text) - $pad) != $pad)               
            return false;         
        return substr($text, 0, -1 * $pad);
  }
}
