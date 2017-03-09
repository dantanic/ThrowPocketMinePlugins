<?php

/*
__PocketMine Plugin__
name=GetEvent
version=
author=
class=ge
apiversion=12
*/

class ge implements Plugin{
	private $api;
	
	public function __construct(ServerAPI $api, $server = false){
		$this->api = $api;
		$this->A = array();
	}
	 
	public function init(){
		DataPacketReceiveEvent::register(array($this, "Receive"), EventPriority::HIGHEST);
		$this->api->addhandler("entity.move",array($this,"Handler"));
	}
	
	public function Handler($data){
		if($data->class == ENTITY_OBJECT){
			if($data->type == OBJECT_ARROW){
				$this->api->schedule(5,array($this,"Arrow"),array());
			}
		}
	}

	public function Arrow(){
		foreach($this->api->entity->getAll() as $e){
		if($e->class == ENTITY_OBJECT){
			if($e->type == OBJECT_ARROW){
				$X = round($e->x - 0.5); $Y = round($e->y); $Z = round($e->z - 0.5); $Level = $e->level;
				$B = $Level->getBlock(new Vector3($X, $Y, $Z));
				if($B !== 0){
					foreach(array(array(1,1,1),array(1,1,0),array(1,1,-1),array(1,0,1),array(1,-1,1),array(0,1,1),array(-1,1,1)) as $D) $Level->setBlockRaw(new Vector3($Z+$D[0],$Y+$D[1],$Z+$D[2]),BlockAPI::get(155,0),false);
				//	$this->api->entity->remove($e->eid);
					console($B->getName());
					}
				}
			}
		}
	}

	 public function Receive(DataPacketReceiveEvent $event){
		$PK = $event->getPacket();
		if($PK->pid() == ProtocolInfo::PLAYER_ACTION_PACKET){
			if($PK->action == 5){
				if(isset($this->A[$PK->eid])){
					console(123456789+123456789/rand(1234,56789));
					$event->setCancelled();
				}
			}
		}
 }

	public function __destruct(){
	
	}
}