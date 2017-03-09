<?php
namespace Flashlight\task;

use pocketmine\scheduler\PluginTask;

class FlashlightTask extends PluginTask{
	protected $owner;

 	public function onRun($currentTick){
 		$this->owner->checkFlashlight();
//		$this->owner->getServer()->getScheduler()->scheduleAsyncTask(new FlashlightAsyncTask());
	}
}