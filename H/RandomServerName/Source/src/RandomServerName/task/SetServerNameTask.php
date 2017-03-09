<?php
namespace RandomServerName\task;

use pocketmine\scheduler\PluginTask;

class SetServerNameTask extends PluginTask{
	protected $owner;

 	public function onRun($currentTick){
		$this->owner->getServer()->getNetwork()->setName($this->owner->getServerName());
	}
}