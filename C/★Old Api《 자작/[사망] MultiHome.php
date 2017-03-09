<?php
/*
__PocketMine Plugin__
name=TouchHome
version=0.1.3
author=DeBe
class=TouchHome
apiversion=1,2,3,4,5,6,7,8,9,10,11,12,13,14,15
*/

class TouchHome implements Plugin{
  private $api;
  
  
 public function __construct(ServerAPI $api, $server = false){
    $this->api = $api;
    $this->H = array();
  }

 public function init(){
    $this->Home= new Config($this->api->plugin->configPath($this)."Home.yml", CONFIG_YAML);
    	$this->api->addHandler("player.block.touch", array($this, "MainHandler"));
    $this->api->console->register("sh","SetHome", array($this,"Commander"));
    $this->api->ban->cmdWhitelist("hh");
    $this->api->console->alias("sethome","sh 1");
    	$this->api->console->alias("home","sh 2");
 }

 public function MainHandler($data,$event){
     $U = $data["player"]->username;
   if(isset($this->H[$U])){
     $T = $data["target"];
     $this->Home->set($U,array(
       "X" => $T->z,
       "Y" => ($T->y + 1),
       "Z" => $T->z,
       "L" => $T->level,
       ));
     $this->Home->save();
     unset($this->H[$U]);
     $data["player"]->sendChat("[Home] 홈 지점 설정되었습니다.");
     return false;
   }
 }

 public function Commander($cmd,$params,$issuer,$alias){
 		if($issuer === "console"){
			return "[Home] 게임내에서만 사용해주세요..";
		}else{
     switch($params[0]){
       case "1":
         $this->H[$U] = array();
         return "[Home] 블럭을 터치해 홈지점을 지정해주세요.";
       break;
       case "2":
         if($this->Home->exists($U)){
           $HU= $this->Home->get($U);
            $X = $HU["X"];
            $Y = $HU["Y"];
            $Z = $HU["Z"];
            $L = $HU["L"];
           $HP = new Position($X,$Y,$Z,$L);
           $issuer->teleport($HP);
           return "[Home] 홈으로 워프되었습니다.";
         }else{
           return "[Home] /SetHome으로 먼저 홈을 설정해주세요.";
         }
       break;
       default:
         return "[Home] Usage : <Sethome | Home>";
       break;
     }
   }
 }
 
 public function __destruct(){
  $this->config->save();
 }
}









































