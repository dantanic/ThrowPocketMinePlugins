<?php

/*
__PocketMine Plugin__
name=InventorySaved
version=
author=milk0417(우유맛비누)
apiversion=11,12,13
class=InventorySaved
*/

class InventorySaved implements Plugin{

	private $api;
	private $server;
	public function __construct(ServerAPI $api, $server = false){
		$this->api = $api;
		$this->server = ServerAPI::request();
	}
	
	public function init(){
		$this->api->addHandler("entity.health.change", array($this, "handle"));
	}
	
	public function __destruct(){}
	
	public function handle($data){
		$en = $data["entity"];
		if(!$en->player instanceof Player) return;
		if($data["health"] < 1){
			$pk = new SetHealthPacket;
			$pk->health = 0;
			$en->player->dataPacket($pk);
			
			$pk = new MoveEntityPacket_PosRot;
			$pk->eid = $en->eid;
			$pk->x = -256;
			$pk->y = 128;
			$pk->z = -256;
			$pk->yaw = 0;
			$pk->pitch = 0;
			$this->api->player->broadcastPacket($en->level->players, $pk);
			
			$en->air = 300;
			$en->fire = 0;
			$en->crouched = false;
			$en->fallY = false;
			$en->fallStart = false;
			$en->updateMetadata();
			$en->dead = true;
			$this->death[$en->eid] = true;
			if($this->api->getProperty("hardcore") == 1) $this->api->ban->ban($en->player->username);
			$this->api->dhandle("player.death", array("player" => $en->player, "cause" => $data["cause"]));
			return false;
		}
	}
}
