<?php
namespace AsyncItemEntity\task;

use pocketmine\scheduler\Task;
use pocketmine\entity\Entity;

class EntityTask extends Task{
	protected $entity, $args;

	public function __construct(Entity $entity, array $args){
		$this->entity = $entity;
		$this->args = $args;
	}

 	public function onRun($currentTick){
 		$this->entity->onTaskRun(...$this->args);
	}
}