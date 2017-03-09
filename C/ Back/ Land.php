<?php

/*
__Ability Plugins__
name=Land
version=0.1.0
author=DeBe
apiversion=12
class=Land
*/

class Land implements Plugin{
	private $api;

	public function __construct(ServerAPI $api, $server =false){
		$this->api = $api;
		$this->Land = array();
	}

	public function init(){
		$this->api->console->register("l","", array($this, "Commander"));
		$this->api->addHandler("player.block.touch", array($this,"MainHandler"));
	}

	public function Commander($cmd,$params,$issuer){
		if(!isset($this->Land[$issuer->username])){
	 		$this->Land[$issuer->username] = true;
			return " [Land] On";		
		}else{
			unset($this->Land[$issuer->username]);
			return " [Land] Off";
		}
	}

	public function MainHandler($data,$event){
		if(isset($this->Land[$data["player"]->username])){
			$I = $data["item"];
			if($I->getID() == 351){
				$mt = $I->getMetadata() == 12 ? 3 : 1;
				$T = $data["target"]; $x=$T->x; $y=$T->y; $z=$T->z;
				$Block = array(array(-3,1,-3,35,$mt),array(-3,1,-2,35,$mt),array(-3,1,-1,35,$mt),array(-3,1,0,35,$mt),array(-3,1,1,35,$mt),array(-3,1,2,35,$mt),array(-3,1,3,35,$mt),array(-2,1,-3,35,$mt),array(-2,1,-2,2,0),array(-2,1,-1,2,0),array(-2,1,0,2,0),array(-2,1,1,2,0),array(-2,1,2,2,0),array(-2,1,3,35,$mt),array(-1,1,-3,35,$mt),array(-1,1,-2,2,0),array(-1,1,-1,2,0),array(-1,1,0,2,0),array(-1,1,1,2,0),array(-1,1,2,2,0),array(-1,1,3,35,$mt),array(0,1,-3,35,$mt),array(0,1,-2,2,0),array(0,1,-1,2,0),array(0,1,0,2,0),array(0,1,1,2,0),array(0,1,2,2,0),array(0,1,3,35,$mt),array(1,1,-3,35,$mt),array(1,1,-2,2,0),array(1,1,-1,2,0),array(1,1,0,2,0),array(1,1,1,2,0),array(1,1,2,2,0),array(1,1,3,35,$mt),array(2,1,-3,35,$mt),array(2,1,-2,2,0),array(2,1,-1,2,0),array(2,1,0,2,0),array(2,1,1,2,0),array(2,1,2,2,0),array(2,1,3,35,$mt),array(3,1,-3,35,$mt),array(3,1,-2,35,$mt),array(3,1,-1,35,$mt),array(3,1,0,35,$mt),array(3,1,1,35,$mt),array(3,1,2,35,$mt),array(3,1,3,35,$mt));
 				foreach($Block as $D){
 					$Vec = new Vector3($x+$D[0],$y+$D[1],$z+$D[2]);
 					$T->level->setBlockRaw($Vec,BlockAPI::get($D[3],$D[4]),false);
 				}
 			}
		}
	}

	public function __destruct(){
	}
}