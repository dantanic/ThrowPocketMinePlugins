<?
/*
__DeBe_Plugins__
name=Poseidon
version=0.1.2
author=DeBe
apiversion=1,2,3,4,5,6,7,8,9,10,11,12,13,14,15
class=Poseidon
*/

class Poseidon implements Plugin{
	private $api;
	public function __construct(ServerAPI $api, $server =false){
		$this->api =$api;
		$this->Poseidon = array();
	}
	public function init() {
	 	$this->api->addhandler("player.block.touch", array($this, "MainHandler"));
		$this->api->console->register("poseidon", "", array($this, "Commander"));
		$this->api->console->alias("포세이돈", "poseidon");
	}

 public function MainHandler($data,$event) {
     $U = $data["player"]->username;
     $P = $data["player"];
		if(isset($this->Poseidon[$U])){
	    $PoseidonWall =$this->PoseidonWall();
	    $T = $data["target"];
	    $TX = $T->x;
	    $TY = $T->y-1;
	    $TZ = $T->z;
  		foreach ($PoseidonWall as $data) {
	  	  $X =$TX +$data[0];
		    $Y =$TY +$data[1];
		    $Z =$TZ +$data[2];
		    $Vector = new Vector3($X, $Y, $Z);
		  $P->level->setBlockRaw($Vector, BlockAPI::get($data[3], $data[4]), false);
		  }
		  $P->sendChat("[Poseidon] 능력-어항 소환!");
		}
 }
 public function Commander($cmd,$args,$issuer, $alias){
		$m ="[Poseidon] ";
		if($issuer === "console"){
			$m.= "게임내에서만 사용해주세요..";
		}elseif(isset($this->Poseidon[$issuer->username])){
			unset($this->Poseidon[$issuer->username]);
			$m.= "포세이돈을 해제합니다.";
		}else{
			$this->Poseidon[$issuer->username] = array();
			$m.= "포세이돈을 발동합니다. 그만두려면 명령어를 다시 입력해주세요.";
		}
		return $m;
	}
 private function PoseidonWall(){
   $PoseidonWall= array(array(-2,0,-2,20,0),array(-2,0,-1,20,0),array(-2,0,0,20,0),array(-2,0,1,20,0),array(-2,0,2,20,0),array(-2,1,-2,20,0),array(-2,1,-1,20,0),array(-2,1,0,20,0),array(-2,1,1,20,0),array(-2,1,2,20,0),array(-2,2,-2,20,0),
   array(-2,2,-1,20,0),array(-2,2,0,20,0),array(-2,2,1,20,0),array(-2,2,2,20,0),array(-2,3,-2,20,0),array(-2,3,-1,20,0),array(-2,3,0,20,0),array(-2,3,1,20,0),array(-2,3,2,20,0),
   array(-2,4,-2,20,0),array(-2,4,-1,20,0),array(-2,4,0,20,0),array(-2,4,1,20,0),array(-2,4,2,20,0),array(-1,0,-2,20,0),array(-1,0,-1,20,0),array(-1,0,0,20,0),array(-1,0,1,20,0),
   array(-1,0,2,20,0),array(-1,1,-2,20,0),array(-1,1,-1,9,0),array(-1,1,0,9,0),array(-1,1,1,9,0),array(-1,1,2,20,0),array(-1,2,-2,20,0),array(-1,2,-1,9,0),array(-1,2,0,9,0),array(-1,2,1,9,0),array(-1,2,2,20,0),array(-1,3,-2,20,0),
   array(-1,3,-1,9,0),array(-1,3,0,9,0),array(-1,3,1,9,0),array(-1,3,2,20,0),array(-1,4,-2,20,0),array(-1,4,-1,20,0),array(-1,4,0,20,0),array(-1,4,1,20,0),array(-1,4,2,20,0),
   array(0,0,-2,20,0),array(0,0,-1,20,0),array(0,0,0,20,0),array(0,0,1,20,0),array(0,0,2,20,0),array(0,1,-2,20,0),array(0,1,-1,9,0),array(0,1,0,9,0),array(0,1,1,9,0),array(0,1,2,20,0),
   array(0,2,-2,20,0),array(0,2,-1,9,0),array(0,2,0,9,0),array(0,2,1,9,0),array(0,2,2,20,0),array(0,3,-2,20,0),array(0,3,-1,9,0),array(0,3,0,9,0),array(0,3,1,9,0),array(0,3,2,20,0),
   array(0,4,-2,20,0),array(0,4,-1,20,0),array(0,4,0,20,0),array(0,4,1,20,0),array(0,4,2,20,0),array(1,0,-2,20,0),array(1,0,-1,20,0),array(1,0,0,20,0),array(1,0,1,20,0),array(1,0,2,20,0),
   array(1,1,-2,20,0),array(1,1,-1,9,0),array(1,1,0,9,0),array(1,1,1,9,0),array(1,1,2,20,0),array(1,2,-2,20,0),array(1,2,-1,9,0),array(1,2,0,9,0),array(1,2,1,9,0),array(1,2,2,20,0),
   array(1,3,-2,20,0),array(1,3,-1,9,0),array(1,3,0,9,0),array(1,3,1,9,0),array(1,3,2,20,0),array(1,4,-2,20,0),array(1,4,-1,20,0),array(1,4,0,20,0),array(1,4,1,20,0),array(1,4,2,20,0),
   array(2,0,-2,20,0),array(2,0,-1,20,0),array(2,0,0,20,0),array(2,0,1,20,0),array(2,0,2,20,0),array(2,1,-2,20,0),array(2,1,-1,20,0),array(2,1,0,20,0),array(2,1,1,20,0),array(2,1,2,20,0),
   array(2,2,-2,20,0),array(2,2,-1,20,0),array(2,2,0,20,0),array(2,2,1,20,0),array(2,2,2,20,0),array(2,3,-2,20,0),array(2,3,-1,20,0),array(2,3,0,20,0),array(2,3,1,20,0),array(2,3,2,20,0),
   array(2,4,-2,20,0),array(2,4,-1,20,0),array(2,4,0,20,0),array(2,4,1,20,0),array(2,4,2,20,0),);
		return $PoseidonWall;	
 }
 
 public function __destruct(){
 }
}





















