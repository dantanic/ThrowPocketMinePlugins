<?php
namespace AsyncItemEntity;

use pocketmine\plugin\PluginBase;
use pocketmine\entity\Entity;
use AsyncItemEntity\entity\Item as ItemEntity;

class AsyncItemEntity extends PluginBase{
	public function onLoad(){
 		Entity::registerEntity(ItemEntity::class);
 	}
}