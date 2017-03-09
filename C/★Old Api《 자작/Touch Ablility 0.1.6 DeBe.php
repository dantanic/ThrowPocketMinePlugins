<?php
 
/*
__PocketMine Plugin__
name=Touch Ability
version=0.1.5 (Ability:5)
apiversion=8,9,10,11,12
author=DeBe
class=Touch_Ability
*/

class Touch_Ability implements Plugin{
  private $api;

 public function __construct(ServerAPI $api,$server=false){
   $this->api  = $api;
   $this->TA = array();
 }

 public function init(){
  	$this->api->event("player.block.touch", array($this, "MainHandler"), 100);
  	$this->api->console->register("/ta", "Touch Ability", array($this, "Commander"));
  	 		$this->api->console->alias("itemcopy", "/ta itemcopy");
  	 		$this->api->console->alias("아이템카피", "/ta itemcopy");
  	 		$this->api->console->alias("spiderman", "/ta spiderman");
  	 		$this->api->console->alias("스파이더맨", "/ta spiderman");
  	 		$this->api->console->alias("explosion", "/ta explosiontouch");
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
   if(isset($this->TA[$U])){
     $T = $data["target"];  $X = $T->x;  
     $Y = $T->y;  $Z = $T->z;  $L = $T->level;
     $TA = $this->TA[$U];
       switch($TA[0]){
         case "itemcopy":
         $I = $data["item"];
           if($I->getID()!==AIR or $I->count >0){
             $D = array(
               "x" => $X +rand(-10,10) /50,
               "y" => $Y +1.5,
               "z" => $Z +rand(-10,10) /50,
               "level" => $L,
               "speed_Y" => rand(5,8),
               "item" => $I,
             );
             $this->api->entity->spawnToAll($this->api->entity->add($L, ENTITY_ITEM,$I->getID(),$D));
             $P->sendChat("[TA] 아이템이 복사되었습니다.");
           }else{
             $P->sendChat("[TA] 아이템을 들고있어야합니다.");
           }
           return false;
		      break;
		      case "spiderman":
		        $P->teleport(new Vector3($X,($Y+1.5),$Z));
		        return false;
		      break;
		      case "explosiontouch":
		        $R = rand(1,3);
           $P->sendChat("[TA] 3초후 폭발합니다.");
           $EXP = new Explosion(new Position($X,$Y,$Z,$L),$R);
           $EXP->explode();
           $P->sendChat("    》 폭발 × $R !!!");
           return false;
		      break;
		      case "arrowspawner":
		        $D = array(
             "x" => $X,
             "y" => $Y + 1 + (rand(3,5) /5),
             "z" => $Z,
           );
           $Arrow = $this->api->entity->add($L,ENTITY_OBJECT,OBJECT_ARROW,$D);
             $Arrow->speedX = rand(-3,3) / 5;
             $Arrow->speedY = rand(-5,-3) / 5;
             $Arrow->speedZ = rand(-3,3) / 5;
           $this->api->entity->spawnToAll($Arrow);
           $P->sendChat("[TA] 화살 투하 !!!");
           return false;
		      break;
		      case "tntspawner":
		        $PR = rand(1,10);
           $TR = rand(0,5);
           $D = array(
             "x" => $X,
             "y" => $Y + 1 + (rand(3,5) /5),
             "z" => $Z,
             "power" => $PR,
             "fuse" => $TR * 20,
           );
           $TNT = $this->api->entity->add($L,ENTITY_OBJECT,OBJECT_PRIMEDTNT,$D);
             $TNT->speedX = rand(-3,3) / 5;
             $TNT->speedY = rand(-5,-3) / 5;
             $TNT->speedZ = rand(-3,3) / 5;
           $this->api->entity->spawnToAll($TNT);
           $P->sendChat("[TA] 폭탄 투하 !!!",$U);
           $P->sendChat("  》강도:$PR,타이머:$TR");
           return false;
		      break;
		      default:
		        return true;
		      break;
       }
     }
 }
 
 public function Commander($cmd,$params,$issuer, $alias){
     $U = $issuer->username;
   if($issuer === "console"){
		  return "[TA] 게임내에서만 사용해주세요.";
   }elseif(isset($this->TA[$U])){
     unset($this->TA[$U]);
     return "[TA] 능력을 해제합니다.";
		}else{
		  $Pr1 = strtolower($params[0]);
		  switch($Pr1){
		    case "itemcopy":
		      $this->TA[$U] = array($Pr1);
		      $N = "Item Copy";
		    break;
		    break;
		    case "spiderman":
		      $this->TA[$U] = array($Pr1);
		      $N = "Spider Man";
		    break;
		    case "explosiontouch":
		      $this->TA[$U] = array($Pr1);
		      $N = "Explosion Touch";
		    break;
		    case "arrowspawner":
		      $this->TA[$U] = array($Pr1);
		      $N = "Arrow Spawner";
		    break;
		    case "tntspawner":
		      $this->TA[$U] = array($Pr1);
		      $N = "TNT Spawner";
		    break;
		    default:
		      $N = "None Ability (?!)";
		    break;
		  }
		}
		$issuer->sendChat("[TA] $N 능력 발동합니다.");
		return "[TA] 해제하려면 /TA를 입력하세요..";
	}
 public function __destruct(){
 }
}

























