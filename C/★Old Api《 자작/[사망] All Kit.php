<?php

/*
__PocketMine Plugin__
name=All Kit
version=0.1.0
author=DeBe
apiversion=1,2,3,4,5,6,7,8,9,10,11,12,13,14,15
class=All_Kit
*/

class All_Kit implements Plugin{
  private $api, $server;
  
 public function __construct(ServerAPI $api,$server = false){$this->api = $api;
 }

 public function init(){
  $this->api->console->register("kit", "All Kit", array($this, "Commander"));
  $this->api->console->alias("가죽","kit 가죽");
 }

 public function Commander($cmd, $params, $issuer,$alias){
   if(!isset($params[1])){
     $T = $issuer;
   }else{
     $t = $this->api->player->get($params[1]);
     $T = $t;
   }
   switch(strtolower($params[0])){
     case "1":
     case "가죽":
      	$A = array(298,299,300,301);
      	break;
     case "2":
     case "사슬":
     case "체인":
     case "chain":
      	$A = array(302,303,304,305);
      	break;
     case "3":
     case "철":
     case "은":
     case "강철":
     case "실버":
     case "아이언":
     case "iron":
     case "silber":
      	$A = array(306,307,308,309);
       break;
     case "4":
     case "금":
     case "황금":
     case "골드":
     case "gold":
      	$A = array(314,315,316,317);
      	break;
     case "5":
     case "다야":
     case "다이아":
     case "다이아몬드":
     case "daimomd":
      	$A = array(310,311,312,313);
      	break;
      	
     default:
       $air = BlockAPI::getItem(0,0,0);
       	$A = array($air,$air,$air,$air);
      
  }
  $T->armor = $A;
  $T->sendArmor($T);
  return "뭐꼬";
}
public function __destruct(){}}




















