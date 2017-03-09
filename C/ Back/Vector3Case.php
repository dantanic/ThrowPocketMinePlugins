<?php
/*
__PocketMine Plugin__
name=OpLand
version=1.0.0
author=DeBe
apiversion=12
class=OpLand
*/

class OpLand implements Plugin{
	private $api;
	
	public function __construct(ServerAPI $api, $server = false){
		$this->api = $api;
 = array();
		$thia->tap = array();
	}
	
	public function init(){
		$this->api->console->register("opland","Op Land", array($this, "Commander"));
		$this->api->addHandler("player.block.touch", array($this, "MainHandler"));
		$this->setList();
	}
	
	public function Commander($cmd,$params,$issuer){
		if(!$issuer instanceof Player) return "Please run this command in-game.\n";
		$n = $issuer->username;
		if($isset($this->tap[$n])) $this->tap[$n] = 0
		if($this->tap[$n] !== 0){
			$this->tap[$n] = 0
		}else{
			$this->tap[$n] = 1;
		} 
	}

	public function MainHandler($data){
		$t = $data["target"];
		$v = newVector3($t->x,$t->y,$t->z);
		foreach($this->land as $land){
			if($land->getIn($v->x,$t->v,r->z) !== false){
				$data["player"]->sendChat(" [땅] 보호된 땅입니다.")
				break;
			}
		}
	}

	public function __destruct(){
	}
}

class VectorCase{
	public $vec1,$vec2,$x1,$x2,$y1,$y2,$z1,$z2,$size,$some;

	function __construct($x1 = 0,$y1 = 0,$z1 = 0,$x2 = 0,$y2 = 0,$z2 = 0){
		if($x1 instanceof Vector3 !== false){
			$y1 = $x1->y;
			$z1 = $x1->z;
			$x1 = $x1->x;
			if($y1 instanceof Vector3 !== false){
				$x2 = $y1->x;
				$y2 = $y1->y;
				$z2 = $y1->z;
			}
		}elseif($x2 instanceof Vector3 !== false){
			$y2 = $x2->y;
			$z2 = $x2->z;
			$x2 = $x2->x;
		}
		$x = $this->getCompare($x1,$x2);
		$y = $this->getCompare($y1,$y2);
		$z = $this->getCompare($z1,$z2);
		$this->x1 = round($x[0]);
		$this->x2 = round($x[1]);
		$this->y1 = round($y[0]);
		$this->y2 = round($y[1]);
		$this->z1 = round($z[0]);
		$this->z2 = round($z[1]);
		$this->vec1 = new Vector3($this->x1,$this->y1,$this->z1);
		$this->vec1 = new Vector3($this->x2,$this->y2,$this->z2);
		$this->size = $this->getSize();
		$this->some = $this->getSome();
	}

	public function getIn($x = 0,$y = 0,$z = 0){
		if($x instanceof Vector3 !== false){
			$x = $x->x;
			$y = $x->y;
			$z = $x->z;
		}
		if($x >= $this->x1 and $x <= $this->x2 and $y >= $this->y1 and $y <= $this->y2 and $z >= $this->z1 and $z <= $this->z2) return true;
		return false;
	}

	public function getSize(){
		$Diff = $this->getDifference();
		return $Diff[0]+$Diff[1]+$Diff[2];
	}

	public function getSome(){
		$Diff = $this->getDifference();
		return $Diff[0]*$Diff[1]*$Diff[2];
	}

	public function getCompare($x1 = 0,$x2 = 0){
		if($x1 < $x2) return array($x1,$x2);
		return array($x2,$x1);
	}

	public function getDifference(){
		$Vec1 = $this->vec1;
		$Vec2 = $this->vec2;
		$x = $this->x1 - $this->x2;
		$y = $this->y1 - $this->y2;
		$z = $this->z1 - $this->z2;
		return array($x,$y,$z);
	}
}