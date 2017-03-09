<?php
namespace AsyncItemEntity\task;

use pocketmine\scheduler\AsyncTask;
use pocketmine\entity\Entity;

class EntityAsyncTask extends AsyncTask{
	public $eid = null;

	public function __construct($eid){
		$this->eid = $eid;
	}

	public function onCompletion(\pocketmine\Server $server){
		$entity = null;
		foreach($server->getLevels() as $level){
			$entity = $level->getEntity($this->eid);
			if($entity instanceof Entity){
				break;
			}
		}
		if($entity instanceof Entity){
			$entity->onAsyncRun();
		}
	}

	public function onRun(){
	}
}