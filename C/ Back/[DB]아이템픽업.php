<?php

/*
__PocketMine Plugin__
name=DB_ItemPickUp
version=1
author=DB
apiversion=11
class=ItemPickUp
*/

class ItemPickUp implements Plugin{
	private $api;
	
	public function __construct(ServerAPI $api, $server = false){
		$this->api = $api;
	}
	
	public function init(){
		$this->api->addHandler("item.drop", array($this, "onDrop"));
		$this->api->addHandler("player.block.break", array($this, "onBreak"));
	}
	
	public function __destruct(){}
	
	public function onDrop(&$data){
		$firstX = $data["x"];
		$firstZ = $data["z"];
		$data["y"] = floor($data["y"]);
		$data["x"] = (ceil($firstX) - 0.5);
		$data["z"] = (ceil($firstZ) - 0.5);
	}
	
	public function onBreak($data){
		$blockY = $data["target"]->y + 1;
		$blockX = $data["target"]->x;
		$blockZ = $data["target"]->z;
		
		$targetX = (floor($data["target"]->x) + 0.5);
		$targetZ = (floor($data["target"]->z) + 0.5);
		
		$y = $data["target"]->y - 1;
		
		$entities = $this->api->entity->getRadius($data["target"], 2, ENTITY_ITEM);
		foreach($entities as $entity){
			$x = floor($entity->x);
			$y = round($entity->y);
			$z = floor($entity->z);
			if($y == $blockY and $data["target"]->x == $x and $data["target"]->z == $z){
				if($y != ($data["target"]->y - 1)){
					$entity->x = $targetX;
					$entity->y = $y;
					$entity->z = $targetZ;
					
					$entity->updatePosition();
					$entity->updateLast();
				}
				
				$down = $data["player"]->level->getBlock(new Vector3($x, $data["target"]->y - 1, $z));
				while($down instanceof AirBlock){
					--$y;
					$down = $down->getSide(0);
				}
				
				$entity->x = $targetX;
				$entity->y = $y;
				$entity->z = $targetZ;
				
				$entity->updatePosition();
				$entity->updateLast();
			}
		}
	}
	
	public function __toString(){
		return "ItemPickUp";
	}
}