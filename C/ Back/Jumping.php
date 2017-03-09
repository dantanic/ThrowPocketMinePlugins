<?php

/*
__Plugins__
name=Arrow
version=0.1.0
author=DeBe
apiversion=12
class=Arrow
*/

class Arrow implements Plugin{
	private $api;

	public function __construct(ServerAPI $api, $server =false){
		$this->api = $api;
	}

	public function init(){
		$this->api->addHandler("player.action", array($this,"MainHandler"));
		DataPacketReceiveEvent::register(array($this, "PacketHandler"), EventPriority::HIGHEST);
	}

	public function MainHandler($data){
		$P = $data["player"];
		$I = $P->getSlot($P->slot);
		if($I->getID() == BOW){
			$TF = false;
 	 		foreach($P->inventory as $slot => $Ii){
				if($Ii->getID() == 262){
					$TF = true;
					break;
				}
			}
			if($TF == true){
				$P->removeItem(262,0,1);
				$V = $this->View($P);
				if($V !== false){
					$P->teleport(new Vector3($V->x,$V->y+1,$V->z));	
				}
			}
		}
 	}

	public function PacketHandler(DataPacketReceiveEvent $event){
		$PK = $event->getPacket();
	/*	if($PK instanceof ProtocolInfo::PLAYER_ACTION_PACKET){
			$event->setCancelled();
		} */
	}

	public function View($P){
		$e = $P->entity;
		$eY = $e->yaw;
		$eP = $e->pitch;
		$Vs = -sin($eY/180 *M_PI);
		$Vc = cos($eY/180*M_PI);
		$Vt = -sin($eP/180*M_PI);
		$Vp = cos($eP/180*M_PI);
		$X = round($e->x); 
		$Y = round($e->y)+1; 
		$Z = round($e->z);
		$L = $e->level;
		for($f=0; $f<50; ++$f){
			$X += $Vs * $Vp;
			$Y += $Vt;
			$Z += $Vc * $Vp;
			if($f > 30 or $X < 0 or $X > 256 or $Y < 0 or $X > 128 or $Z < 0 or $X > 256){
				return false;
			}else{
				$B = $L->getBlock(new Vector3(round($X),round($Y),round($Z)));
				if($B->isSolid == false){
					return $B;
				}
			}
		}
	}

	public function __destruct(){
	}
}