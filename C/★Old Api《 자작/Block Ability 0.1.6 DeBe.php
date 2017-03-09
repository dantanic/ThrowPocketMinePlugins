<?php
 
/*
__PocketMine Plugin__
name=Block Ability
version=0.1.5 (Ability:5)
apiversion=8,9,10,11,12
author=DeBe
class=Block_Ability
*/

class Block_Ability implements Plugin{
  private $api;

 public function __construct(ServerAPI $api,$server=false){
   $this->api  = $api;
   $this->BA = array();
 }

 public function init(){
  	$this->api->event("player.block.touch", array($this, "MainHandler"), 100);
  	$this->api->console->register("/va", "Touch Ability", array($this, "Commander"));
  	 		$this->api->console->alias("포세이돈", "/va 30");
  	 		$this->api->console->alias("아카이누", "/va 31");
  	 		$this->api->console->alias("목둔", "/va 33");
  	 		$this->api->console->alias("아마테라스", "/va 41");
  	 		$this->api->console->alias("아폴론", "/va 22");
  	 		$this->api->console->alias("폭발", "/ta explosiontouch");
  	 		$this->api->console->alias("arrow", "/ta arrowspawner");
  	 		$this->api->console->alias("화살", "/ta arrowspawner");
  	 		 $this->api->console->alias("tnt", "/ta tntspawner");
  	 		$this->api->console->alias("티엔티", "/ta tntspawner");
  	 		$this->api->console->alias("none", "/ta");
  	 		$this->api->console->alias("무능력", "/ta");
 }

 public function MainHandler($data,$event){
     $U = $data["player"]->username;
     $P = $data["player"];
     $E = $this->api->entity;
   if(isset($this->BA[$U])){
     $T = $data["target"];
	     $TX = $T->x;
	     $TY = $T->y;
	     $TZ = $T->z;
	     $L = $T->level;
     $BA = $this->BA[$U];
       switch($BA[0]){
         case "22":
		        $BlockWall = array(array(-2,1,-1,87,0),array(-2,1,0,87,0),array(-2,1,1,87,0),array(-1,0,-1,87,0),array(-1,0,0,87,0),array(-1,0,1,87,0),array(-1,1,-2,87,0),array(-1,1,-1,11,0),array(-1,1,0,11,0),array(-1,1,1,11,0),array(-1,1,2,87,0),array(0,0,-1,87,0),array(0,0,0,87,0),array(0,0,1,87,0),array(0,1,-2,87,0),array(0,1,-1,11,0),array(0,1,0,11,0),array(0,1,1,11,0),array(0,1,2,87,0),array(1,0,-1,87,0),array(1,0,0,87,0),array(1,0,1,87,0),array(1,1,-2,87,0),array(1,1,-1,11,0),array(1,1,0,11,0),array(1,1,1,11,0),array(1,1,2,87,0),array(2,1,-1,87,0),array(2,1,0,87,0),array(2,1,1,87,0));
		        $P->sendChat("[BA] 아폴론 능력사용!");
		        foreach ($BlockWall as $data) {
	   	$X =$TX +$data[0];
	   	$Y =$TY +$data[1];
	   	$Z =$TZ +$data[2];
	   $L->setBlockRaw(new Vector3($X,$Y,$Z),BlockAPI::get($data[3], $data[4]),false);
		        return false;
		        break;
         case "30":
           $BlockWall = array(array(-2,0,-2,20,0),array(-2,0,-1,20,0),array(-2,0,0,20,0),array(-2,0,1,20,0),array(-2,0,2,20,0),array(-2,1,-2,20,0),array(-2,1,-1,20,0),array(-2,1,0,20,0),array(-2,1,1,20,0),array(-2,1,2,20,0),array(-2,2,-2,20,0),array(-2,2,-1,20,0),array(-2,2,0,20,0),array(-2,2,1,20,0),array(-2,2,2,20,0),array(-2,3,-2,20,0),array(-2,3,-1,20,0),array(-2,3,0,20,0),array(-2,3,1,20,0),array(-2,3,2,20,0),array(-2,4,-2,20,0),array(-2,4,-1,20,0),array(-2,4,0,20,0),array(-2,4,1,20,0),array(-2,4,2,20,0),array(-1,0,-2,20,0),array(-1,0,-1,20,0),array(-1,0,0,20,0),array(-1,0,1,20,0),array(-1,0,2,20,0),array(-1,1,-2,20,0),array(-1,1,-1,9,0),array(-1,1,0,9,0),array(-1,1,1,9,0),array(-1,1,2,20,0),array(-1,2,-2,20,0),array(-1,2,-1,9,0),array(-1,2,0,9,0),array(-1,2,1,9,0),array(-1,2,2,20,0),array(-1,3,-2,20,0),array(-1,3,-1,9,0),array(-1,3,0,9,0),array(-1,3,1,9,0),array(-1,3,2,20,0),array(-1,4,-2,20,0),array(-1,4,-1,20,0),array(-1,4,0,20,0),array(-1,4,1,20,0),array(-1,4,2,20,0),array(0,0,-2,20,0),array(0,0,-1,20,0),array(0,0,0,20,0),array(0,0,1,20,0),array(0,0,2,20,0),array(0,1,-2,20,0),array(0,1,-1,9,0),array(0,1,0,9,0),array(0,1,1,9,0),array(0,1,2,20,0),array(0,2,-2,20,0),array(0,2,-1,9,0),array(0,2,0,9,0),array(0,2,1,9,0),array(0,2,2,20,0),array(0,3,-2,20,0),array(0,3,-1,9,0),array(0,3,0,9,0),array(0,3,1,9,0),array(0,3,2,20,0),array(0,4,-2,20,0),array(0,4,-1,20,0),array(0,4,0,20,0),array(0,4,1,20,0),array(0,4,2,20,0),array(1,0,-2,20,0),array(1,0,-1,20,0),array(1,0,0,20,0),array(1,0,1,20,0),array(1,0,2,20,0),array(1,1,-2,20,0),array(1,1,-1,9,0),array(1,1,0,9,0),array(1,1,1,9,0),array(1,1,2,20,0),array(1,2,-2,20,0),array(1,2,-1,9,0),array(1,2,0,9,0),array(1,2,1,9,0),array(1,2,2,20,0),array(1,3,-2,20,0),array(1,3,-1,9,0),array(1,3,0,9,0),array(1,3,1,9,0),array(1,3,2,20,0),array(1,4,-2,20,0),array(1,4,-1,20,0),array(1,4,0,20,0),array(1,4,1,20,0),array(1,4,2,20,0),array(2,0,-2,20,0),array(2,0,-1,20,0),array(2,0,0,20,0),array(2,0,1,20,0),array(2,0,2,20,0),array(2,1,-2,20,0),array(2,1,-1,20,0),array(2,1,0,20,0),array(2,1,1,20,0),array(2,1,2,20,0),array(2,2,-2,20,0),array(2,2,-1,20,0),array(2,2,0,20,0),array(2,2,1,20,0),array(2,2,2,20,0),array(2,3,-2,20,0),array(2,3,-1,20,0),array(2,3,0,20,0),array(2,3,1,20,0),array(2,3,2,20,0),array(2,4,-2,20,0),array(2,4,-1,20,0),array(2,4,0,20,0),array(2,4,1,20,0),array(2,4,2,20,0));
           $P->sendChat("[BA] 포세이돈 능력사용!");
          foreach ($BlockWall as $data) {
	   	$X =$TX +$data[0];
	   	$Y =$TY +$data[1];
	   	$Z =$TZ +$data[2];
	   $L->setBlockRaw(new Vector3($X,$Y,$Z),BlockAPI::get($data[3], $data[4]),false);
           return false;
		      break;
		      case "31":
		        $BlockWall = array(array(-1,0,-1,87,0),array(-1,0,0,87,0),array(-1,0,1,87,0),array(-1,1,-1,51,15),array(-1,1,0,51,2),array(-1,1,1,51,15),array(0,0,-1,87,0),array(0,0,0,87,0),array(0,0,1,87,0),array(0,1,-1,51,15),array(0,1,0,51,1),array(0,1,1,51,15),array(1,0,-1,87,0),array(1,0,0,87,0),array(1,0,1,87,0),array(1,1,-1,51,15),array(1,1,0,51,15),array(1,1,1,51,1));
		        $P->sendChat("[BA] 아카이누 능력사용!"); foreach ($BlockWall as $data) {
	   	$X =$TX +$data[0];
	   	$Y =$TY +$data[1];
	   	$Z =$TZ +$data[2];
	   $L->setBlockRaw(new Vector3($X,$Y,$Z),BlockAPI::get($data[3], $data[4]),false);
		        return false;
		      break;
		      case "33":
		        $BlockWall = array(array(-2,1,0,5,3),array(-2,2,0,5,2),array(-2,3,0,5,1),array(-2,4,0,5,0),array(-1,1,0,5,2),array(-1,2,0,5,1),array(-1,3,0,5,0),array(-1,4,0,0,0),array(0,1,0,5,3),array(0,2,0,5,2),array(0,3,0,5,1),array(0,4,0,5,0),array(1,1,0,5,2),array(1,2,0,5,1),array(1,3,0,5,0),array(1,4,0,0,0),array(2,1,0,5,3),array(2,2,0,5,2),array(2,3,0,5,1),array(2,4,0,5,0));
		        $P->sendChat("[BA] 목둔 능력사용!"); foreach ($BlockWall as $data) {
	   	$X =$TX +$data[0];
	   	$Y =$TY +$data[1];
	   	$Z =$TZ +$data[2];
	   $L->setBlockRaw(new Vector3($X,$Y,$Z),BlockAPI::get($data[3], $data[4]),false);
		        return false;
		      break;
		      case "45":
		        $BlockWall = array(array(-1,0,-1,87,0),array(-1,0,0,87,0),array(-1,0,1,87,0),array(-1,1,-1,51,9),array(-1,1,0,51,15),array(-1,1,1,51,15),array(-1,2,-1,0,0),array(-1,2,0,0,0),array(-1,2,1,0,0),array(0,0,-1,87,0),array(0,0,0,87,0),array(0,0,1,87,0),
array(0,1,-1,51,14),array(0,1,0,51,0),array(0,1,1,51,15),array(0,2,-1,0,0),array(0,2,0,0,0),array(0,2,1,0,0),array(1,0,-1,87,0),array(1,0,0,87,0),array(1,0,1,87,0),array(1,1,-1,51,1),array(1,1,0,51,15),array(1,1,1,51,15),array(1,2,-1,0,0),array(1,2,0,0,0),array(1,2,1,0,0));
           $P->sendChat("[BA] 아마테라스 능력사용!"); foreach ($BlockWall as $data) {
	   	$X =$TX +$data[0];
	   	$Y =$TY +$data[1];
	   	$Z =$TZ +$data[2];
	   $L->setBlockRaw(new Vector3($X,$Y,$Z),BlockAPI::get($data[3], $data[4]),false);
           return false;
         break;
         case "":
		        $P->sendChat("[BA]  능력사용!");
		        return false;
		      break;
		      case "":
		        $P->sendChat("[BA]  능력사용!");
		        return false;
		      break;
		      case "":
		        $P->sendChat("[BA]  능력사용!");
		        return false;
		      break;
		      case "":
		        $P->sendChat("[BA]  능력사용!");
		        return false;
		      break;
		      default:
		        $BlockWall = array();
		        return true;
		      break;
       }
   foreach ($BlockWall as $data) {
	   	$X =$TX +$data[0];
	   	$Y =$TY +$data[1];
	   	$Z =$TZ +$data[2];
	   $L->setBlockRaw(new Vector3($X,$Y,$Z),BlockAPI::get($data[3], $data[4]),false);
     }
   }
 }
 
 public function Commander($cmd,$params,$issuer, $alias){
     $U = $issuer->username;
   if($issuer === "console"){
		  return "[BA] 게임내에서만 사용해주세요.";
   }elseif(isset($this->BA[$U])){
     unset($this->BA[$U]);
     return "[BA] 능력을 해제합니다.";
		}else{
		  $Pr1 = strtolower($params[0]);
		  switch($Pr1){
		    case "22":
		      $N = "아폴톤";
		    break;
		    case "30":
		      $N = "포세이돈";
		    break;
		    case "31":
		      $N = "아카이누";
		    break;
		    case "33":
		      $N = "목둔";
		    break;
		    case "45":
		      $N = "아마테라스";
		    break;
		    case "30":
		      $N = "포세이돈";
		    break;
		    default:
		      $N = "None Ability (?!)";
		    break;
		  }
		  $this->BA[$U] = array($Pr1);
		}
		$issuer->sendChat("[BA] $N 능력 발동합니다.");
		return "[BA] 해제하려면 /BA를 입력하세요..";
	}
 public function __destruct(){
 }
}

























