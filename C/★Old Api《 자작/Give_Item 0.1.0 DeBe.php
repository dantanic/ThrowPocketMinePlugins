<?php
 
/*
__PocketMine Plugin__
name=GiveItem
version=0.1.0
apiversion=8,9,10,11,12
author=DeBe
class=Give_Item
*/

class Give_Item implements Plugin{
  private $api;

 public function __construct(ServerAPI $api,$server=false){
   $this->api  = $api;
   $this->GI = array();
 }

 public function init(){
  	$this->api->addhandler("player.interact", array($this, "MainHandler"));
  	$this->api->addhandler("player.block.touch", array($this, "SubHandler"));
  	$this->api->console->register("giveitem", "Give Item", array($this, "Commander"));
  	  $this->api->console->alias("gi", "giveitme");
  	  $this->api->console->alias("기브", "giveitem");
  	  $this->api->console->alias("교환", "giveitme");
  	  $this->api->console->alias("선물", "giveitem");
 }

 public function MainHandler($data,$event){
     $U = $data["entity"]->player->username;
     $P = $data["entity"]->player;
     $E = $this->api->entity;
   if(isset($this->GI[$U])){
     $GI = $this->GI[$U];
       $T = $data["targetentity"]->player;
       $tt = $T->username;
       $I = $data["entity"]->player->getSlot($data["entity"]->player->slot);
       $IID = $I->getID();
       $IMT = $I->getMetadata();
       $ICT = $I->count;
			 if(($T->gamemode & 0x01) === 0x01){
			   $P->sendChat("[GI] $tt 님은 크리에이티브입니다..");
		  	  return false;
		  	}elseif($ICT > $GI[0]){
		  	  if($GI[0] <= 0){
		  	    $C = 1;
		  	  }else{
		  	    $C = $GI[0];
		  	  }
          $T->addItem($IID,$IMT,$GI[0]);
          $P->removeItem($IID,$IMT,$GI[0]);
          $P->sendChat("[GI] ".$tt."님에게 $IID:$IMT ".$GI[0]."개를 줬습니다.");
        return false;	
		  	}else{
		  	  $P->sendChat("[GI] $IID:$IMT ".$GI[0]."개보다 적어서 줄수가 없습니다.");
		  	  return false;
		  	}
   }
 }
 
 public function Commander($cmd,$params,$issuer,$alias){
     $U = $issuer->username;
   if($issuer === "console"){
		  return "[GI] 게임내에서만 사용해주세요.";
   }else{
		  $Pr0 = strtolower($params[0]);
		  if(!isset($Pr0)){
       $count = 1;
     }else{
       $count = (int) $Pr0;
     }
		  $this->GI[$U] = array($count);
		  $issuer->sendChat("[GI] 기브아이템이 활성화되었습니다." );
		return "[GI] 취소하려면 아무블럭이나 터치하세요.  설정된 갯수 : ".$count."";
		}
 }

 public function SubHandler($data,$event){
     $U = $data["player"]->username;
     $P = $data["player"];
   if(isset($this->GI[$U])){
     $P->sendChat("[GI] 기브아이템이 취소되었습니다.");
     unset($this->GI[$U]);
     return false;
   }
 }
 
 public function __destruct(){
 }
}

























