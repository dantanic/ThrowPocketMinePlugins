<?php

/*
__PocketMine Plugin__
name=Explosion
version=0.1.0
author=DeBe
class=Explosion
apiversion=11,12
*/

class Explosion implements Plugin{
	private $api;
	
	public function __construct(ServerAPI $api, $server = false){
		$this->api = $api;
	}

	 public function init(){
		$this->api->addHandler("player.block.touch",array($this,"MainHandle"));
	}
	
	 
	public function MainHandle($data){
		if($data["item"]->getID() == 265){ 
			$P = $data["player"];
			$T = $data["target"];
			$X = $T->x; $Y = $T->y; $Z = $T->z;
			$pk = new ExplodePacket;
			$pk->x = $X;
			$pk->y = $Y;
			$pk->z = $Z;
			$pk->radius = 1;
			$pk->records = array();
			$this->api->player->broadcastPacket($T->level->players,$pk);
			foreach($T->level->players as $Lp){
				$e = $Lp->entity;
				$Bl = new Vector3($X, $Y, $Z);
				$Pl = new Vector3($e->x, $e->y, $e->z);
				$ar = round($Bl->distance($Pl));
				if ($ar <= 5) {
					$e->harm(10,"explosion");
				}
			}
			$P->removeitem(265,0,1);
			return false;
		}
	}

	public function __destruct(){
	}
}
