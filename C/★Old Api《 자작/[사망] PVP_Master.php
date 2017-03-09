<?php

/*
__PocketMine Plugin__
name=PVP Master
version=0.1.1
author=DeBe
apiversion=1,2,3,4,5,6,7,8,9,10,11,12,13,14,15
class=PVP_Master
*/

class PVP_Master implements Plugin{
private $api, $server;
 public function __construct(ServerAPI $api,$server = false){$this->api = $api;}

 public function init() {
  $this->api->console->register("pvp", " /PVP <On|Off> <Day|Night>", array($this, "DBcmd"));
  $this->api->addHandler("player.interact", array($this, "DBcmd"));
 }

 public function DBcmd($cmd, $params, $issuer, $alias){
 $m.="[PVP] "
 $u=$issuer->username;
 if(!$data['entity']->class === ENTITY_PLAYER){
  return true;
 }elseif(is_numeric($data['cause'])){
  switch($params[0]){
   case "on":
    if(!isset($params[1])){
     $m.= "PVP On - Always"; 
     return true;
    }
    if(strtolower($params[1])=="night"){
     if($this->api->time->getPhase()== "night"){
      $m.= "PVP On - Night"; 
      return true;
     }else{
      return false;
     }
    }
    if(strtolower($params[1])=="day"){
     if($this->api->time->getPhase()== "day"){
      $m.= "PVP On - Day"; 
      return true;
     }else{
      return false;
     }
    }
   case "on":
    if(!isset($params[1])){
     $m.= "PVP Off - Always"; 
     return false;
    }
    if(strtolower($params[1])=="night"){
     if($this->api->time->getPhase()== "night"){
      $m.= "PVP Off - Night";
      return false;
     }else{
      return true;
     }
    }
    if(strtolower($params[1])=="day"){
     if($this->api->time->getPhase()== "day"){
      $m.= "PVP Off - Day";
      return true;
     }else{
      return false;
     }
    }
   default:
    $m.= "Usage: /PVP <On|Off> <Day|Night> /n";
   }
   $this->api->chat->sendTo(false,$m,$u)
  }
 }
public function __destruct(){}}

























