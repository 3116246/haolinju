<?php
namespace Justsy\BaseBundle\DataAccess\DataTransfer;

class	ZtreeTransfer
{
	 protected $container;
	 protected $logger;
	 public function __construct($_container)
	 {
		  $this->container = $_container;
	  	$this->logger=$_container->get("logger");
	 }
	    
   public function dataToTree($data)
   {
   		//数据处理
    	$result = array();
    	$rootid = "root";
    	$assoc = false;
    	if (!$this->is_assoc($data))
    	{
    	  $data = $data[0];
    	  $assoc = true;
    	}
    	foreach($data as $key=>$val){
    	 	 if (is_array($val)){
    	 	 	  $id = rand(1000,9999);
    	 	 	  array_push($result,array("id"=>$id,"level"=>$rootid,"name"=>$key,"tag"=>$key,"children"=>true));
    	 	 	  $new_array = $this->arrayToTree($key,$id,$val);
    	 	 	  for($i=0;$i< count($new_array);$i++){
	 	 	      	array_push($result,$new_array[$i]);
	 	 	      }
    	 	 }
    	 	 else{
    	 	   if ( $assoc)
    	 	      array_push($result,array("id"=>$key,"level"=>$rootid,"name"=>$key,"tag"=>"->".$key,"children"=>false));
    	 	   else
    	 	      array_push($result,array("id"=>$key,"level"=>$rootid,"name"=>$key,"tag"=>$key,"children"=>false));
    	 	 }
    	}
    	return $result;
   }
   
   //数组转换到树结构的数据结构
	 private function arrayToTree($prevKey,$previd,$array)
   {
   	  $result = array();
   	  if ( count($array)==0){
   	  	return;
   	  }
   	  else{
   	  	if ($this->is_assoc($array))
   	  	{
   	  		$object_tree = $this->objectToTree($prevKey,$previd,$array);
 	 	      for($i=0;$i< count($object_tree);$i++){
 	 	      	array_push($result,$object_tree[$i]);
 	 	      }
   	  	}
   	  	else{
   	  	  $array = $array[0];
		      foreach($array as $key=>$val){
		      	$id = rand(1000,9999);
		      	$tag = $prevKey."->".$key;
		      	if ( is_array($val)){
		      		array_push($result,array("id"=>$id,"level"=>$previd,"name"=>$key,"tag"=>$prevKey."->".$key,"children"=>true));
		      		$array_tree = self::arrayToTree($tag,$id,$val);
		 	 	      for($i=0;$i< count($array_tree);$i++){
		 	 	      	array_push($result,$array_tree[$i]);
		 	 	      }
		      	}
		      	else{
		      		array_push($result,array("id"=>$id,"level"=>$previd,"name"=>$key,"tag"=>$prevKey."->".$key,"children"=>false));
		      	}
		      }
	      }
      }
      return $result;
   }
   
   //判断是否关联数组
   function is_assoc($arr) {
     return array_keys($arr) !== range(0, count($arr) - 1);  
   }
   
   //对象（关联数组）转换到树结构的数据结构
   private function objectToTree($prevKey,$previd,$object)
   {
  	$result = array();
    foreach($object as $key=>$val){
    	$id = rand(1000,9999);
    	$tag = $prevKey.".".$key;
    	if (is_array($val)){
    		array_push($result,array("id"=>$id,"level"=>$previd,"name"=>$key,"tag"=>$prevKey.".".$key,"children"=>true));
    		$array_tree = $this->arrayToTree($tag,$id,$val);
 	      for($i=0;$i<count($array_tree);$i++){
 	      	array_push($result,$array_tree[$i]);
 	      }
    	}
    	else{
    		array_push($result,array("id"=>$id,"level"=>$previd,"name"=>$key,"tag"=>$prevKey.".".$key,"children"=>false));
    	}
    }
    return $result;
   }  
}
?>