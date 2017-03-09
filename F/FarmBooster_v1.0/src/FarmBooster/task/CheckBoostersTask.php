<?php
namespace FarmBooster\task;

use pocketmine\scheduler\PluginTask;

class CheckBoostersTask extends PluginTask{
	protected $owner;

 	public function onRun($currentTick){
		$this->owner->checkBoosters();
	}
}