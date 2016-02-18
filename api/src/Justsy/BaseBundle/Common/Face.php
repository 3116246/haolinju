<?php

namespace Justsy\BaseBundle\Common;

class Face
{
  public static $FaceEmotes = array(
//    "微笑"	 => 	"f_01_wx.png"   ,
//    "撇嘴"	 => 	"f_02_pz.png"   ,
//    "色"	 => 	"f_03_se.png"   ,
//    "昏倒"	 => 	"f_04_fd.png"   ,
//    "大笑"	 => 	"f_05_dx.png"   ,
//    "流泪"	 => 	"f_06_ll.png"   ,
//    "害羞"	 => 	"f_07_hx.png"   ,
//    "闭嘴"	 => 	"f_08_bz.png"   ,
//    "睡"	 => 	"f_09_shui.png" ,
//    "大哭"	 => 	"f_10_dk.png"   ,
//    "尴尬"	 => 	"f_11_gg.png"   ,
//    "发怒"	 => 	"f_12_fn.png"   ,
//    "调皮"	 => 	"f_13_tp.png"   ,
//    "哈哈"	 => 	"f_14_haha.png" ,
//    "惊讶"	 => 	"f_15_jy.png"   ,
//    "难过"	 => 	"f_16_ng.png"   ,
//    "强"	 => 	"f_17_qiang.png",
//    "寒冷"	 => 	"f_18_lengh.png",
//    "自信"	 => 	"f_19_zx.png"   ,
//    "汗"	 => 	"f_20_han.png"  ,
//    "偷笑"	 => 	"f_21_tx.png"   ,
//    "投降"	 => 	"f_22_tx.png"   ,
//    "流口水"	 => 	"f_23_lks.png"  ,
//    "傲慢"	 => 	"f_24_am.png"   ,
//    "饥饿"	 => 	"f_25_jie.png"  ,
//    "困"	 => 	"f_26_kun.png"  ,
//    "不错"	 => 	"f_27_buc.png"  ,
//    "流鼻涕"	 => 	"f_28_lbx.png"  ,
//    "必须的"	 => 	"f_29_bxd.png"  ,
//    "再见"	 => 	"f_30_zj.png"   ,
//    "委屈"	 => 	"f_31_wq.png"   ,
//    "哦"	 => 	"f_32_oh.png"   ,
//    "疑问"	 => 	"f_33_yw.png"   ,
//    "生气"	 => 	"f_34_yw.png"   ,
//    "晕"	 => 	"f_35_yun.png"  ,
//    "折磨"	 => 	"f_36_zhem.png" ,
//    "衰"	 => 	"f_37_shuai.png",
//    "亲吻"	 => 	"f_38_qw.png"   ,
//    "敲打"	 => 	"f_39_qd.png"   ,
//    "膏药"	 => 	"f_40_gy.png"   ,
//    "说唱"	 => 	"f_41_rap.png"  ,
//    "板砖"	 => 	"f_42_bz.png"   ,
//    "崇拜"	 => 	"f_43_cb.png"   ,
//    "坏笑"	 => 	"f_44_huaix.png",
//    "哈欠"	 => 	"f_45_hq.png"   ,
//    "瞌睡"	 => 	"f_46_kh.png"   ,
//    "美"	 => 	"f_47_mei.png"  ,
//    "烧香"	 => 	"f_48_shaox.png",
//    "困惑"	 => 	"f_49_kunh.png" ,
//    "阎王"	 => 	"f_50_yw.png"

    "你好"        =>        "hi.png",
    "再见"        =>        "bye.png",
    "微笑"        =>        "wx.png",
    "大笑"        =>        "dx.png",
    "偷笑"        =>        "tx.png",
    "憨笑"        =>        "hanx.png",
    "坏笑"        =>        "huaix.png",
    "抓狂"        =>        "zk.png",
    "晕"        =>        "yun.png",
    "冷汗"        =>        "lh.png",
    "嘘"        =>        "xu.png",
    "疑问"        =>        "yw.png",
    "委屈"        =>        "wq.png",
    "调皮"        =>        "tp.png",
    "色"        =>        "she.png",
    "糗大了"        =>        "qdl.png",
    "亲亲"        =>        "qq.png",
    "难过"        =>        "ng.png",
    "流泪"        =>        "ll.png",
    "困"        =>        "kun.png",
    "可爱"        =>        "keai.png",
    "可怜"        =>        "kl.png",
    "惊讶"        =>        "jy.png",
    "惊恐"        =>        "jk.png",
    "呵欠"        =>        "hq.png",
    "发呆"        =>        "fd.png",
    "害羞"        =>        "haix.png",
    "尴尬"        =>        "gg.png",
    "大哭"        =>        "dk.png",
    "出汗"        =>        "chuh.png",
    "得意"        =>        "deyi.png",
    "示爱"        =>        "sa.png",
    "发怒"        =>        "fn.png",
    "鄙视"        =>        "bs.png",
    "闭嘴"        =>        "bz.png",
    "酷"        =>        "cool.png",
    "傲慢"        =>        "am.png",
    "白眼"        =>        "by.png",
    "擦汗"        =>        "cah.png",
    "睡"        =>        "shui.png",
    "OK"        =>        "ok.png",
    "NO"        =>        "no.png",
    "强"        =>        "qiang.png",
    "弱"        =>        "ruo.png",
    "差劲"        =>        "cj.png",
    "鼓掌"        =>        "gz.png",
    "胜利"        =>        "sl.png",
    "握手"        =>        "ws.png",
    "拳头"        =>        "qt.png",
    "勾引"        =>        "gy.png",
    "拥抱"        =>        "yb.png",
    "抱拳"        =>        "bq.png",
    "拜托"        =>        "bt.png",
    "敬礼"        =>        "jl.png",
    "敲打"        =>        "qiaod.png",
    "猪头"        =>        "zt.png",
    "吐"        =>        "tu.png",
    "礼物"        =>        "lw.png",
    "饭"        =>        "fan.png",
    "饥饿"        =>        "je.png"
  );
  
    public static $FaceEmotes_GIF = array(

    "你好"        =>        "hi.gif",
    "再见"        =>        "bye.gif",
    "微笑"        =>        "wx.gif",
    "大笑"        =>        "dx.gif",
    "偷笑"        =>        "tx.gif",
    "憨笑"        =>        "hanx.gif",
    "坏笑"        =>        "huaix.gif",
    "抓狂"        =>        "zk.gif",
    "晕"        =>        "yun.gif",
    "冷汗"        =>        "lh.gif",
    "嘘"        =>        "xu.gif",
    "疑问"        =>        "yw.gif",
    "委屈"        =>        "wq.gif",
    "调皮"        =>        "tp.gif",
    "色"        =>        "she.gif",
    "糗大了"        =>        "qdl.gif",
    "亲亲"        =>        "qq.gif",
    "难过"        =>        "ng.gif",
    "流泪"        =>        "ll.gif",
    "困"        =>        "kun.gif",
    "可爱"        =>        "keai.gif",
    "可怜"        =>        "kl.gif",
    "惊讶"        =>        "jy.gif",
    "惊恐"        =>        "jk.gif",
    "呵欠"        =>        "hq.gif",
    "发呆"        =>        "fd.gif",
    "害羞"        =>        "haix.gif",
    "尴尬"        =>        "gg.gif",
    "大哭"        =>        "dk.gif",
    "出汗"        =>        "chuh.gif",
    "得意"        =>        "deyi.gif",
    "示爱"        =>        "sa.gif",
    "发怒"        =>        "fn.gif",
    "鄙视"        =>        "bs.gif",
    "闭嘴"        =>        "bz.gif",
    "酷"        =>        "cool.gif",
    "傲慢"        =>        "am.gif",
    "白眼"        =>        "by.gif",
    "擦汗"        =>        "cah.gif",
    "睡"        =>        "shui.gif",
    "OK"        =>        "ok.gif",
    "NO"        =>        "no.gif",
    "强"        =>        "qiang.gif",
    "弱"        =>        "ruo.gif",
    "差劲"        =>        "cj.gif",
    "鼓掌"        =>        "gz.gif",
    "胜利"        =>        "sl.gif",
    "握手"        =>        "ws.gif",
    "拳头"        =>        "qt.gif",
    "勾引"        =>        "gy.gif",
    "拥抱"        =>        "yb.gif",
    "抱拳"        =>        "bq.gif",
    "拜托"        =>        "bt.gif",
    "敬礼"        =>        "jl.gif",
    "敲打"        =>        "qiaod.gif",
    "猪头"        =>        "zt.gif",
    "吐"        =>        "tu.gif",
    "礼物"        =>        "lw.gif",
    "饭"        =>        "fan.gif",
    "饥饿"        =>        "je.gif"
  );
  
  private static $FaceEmoteReg = array();
  private static $FaceEmoteImg = array();
  public static $FaceEmoteImgReplaceStr = "";
  
  public static function getFaceEmoteReg() 
  {
    if (count(Face::$FaceEmoteReg) == 0) 
    {
      Face::$FaceEmoteReg = array_map(function ($item) 
        {
          return "/\[$item\]/";
        }, array_keys(Face::$FaceEmotes));
    }
    return Face::$FaceEmoteReg;
  }
  public static function getFaceEmoteReg_GIF() 
  {
    if (count(Face::$FaceEmoteReg) == 0) 
    {
      Face::$FaceEmoteReg = array_map(function ($item) 
        {
          return "/\[$item\]/";
        }, array_keys(Face::$FaceEmotes_GIF));
    }
    return Face::$FaceEmoteReg;
  }
  //$repacestr  形如：<img str="[IMGSRC]">，其中[IMGSRC]将被替换为真实的图片
  public static function getFaceEmoteImg($repacestr) 
  {
    if (count(Face::$FaceEmoteImg) == 0 || Face::$FaceEmoteImgReplaceStr != $repacestr)
    {
      Face::$FaceEmoteImgReplaceStr = $repacestr;
      Face::$FaceEmoteImg = array_map(function ($item) 
      {
        return preg_replace("/\[IMGSRC\]/", $item, Face::$FaceEmoteImgReplaceStr);
      }, array_values(Face::$FaceEmotes));
    }
    return Face::$FaceEmoteImg;
  }
  public static function getFaceEmoteImg_GIF($repacestr) 
  {
    if (count(Face::$FaceEmoteImg) == 0 || Face::$FaceEmoteImgReplaceStr != $repacestr)
    {
      Face::$FaceEmoteImgReplaceStr = $repacestr;
      Face::$FaceEmoteImg = array_map(function ($item) 
      {
        return preg_replace("/\[IMGSRC\]/", $item, Face::$FaceEmoteImgReplaceStr);
      }, array_values(Face::$FaceEmotes_GIF));
    }
    return Face::$FaceEmoteImg;
  }  
}
