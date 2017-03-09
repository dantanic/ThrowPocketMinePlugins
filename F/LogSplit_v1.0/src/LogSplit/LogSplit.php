<?php

namespace LogSplit;

class LogSplit extends \pocketmine\plugin\PluginBase{
	public function onEnable(){
		$this->getServer()->getScheduler()->scheduleRepeatingTask(new Task($this, [$this, "onSchedule"]), 10);
	}

	public function onSchedule(){
		@mkdir($this->getDataFolder());
		if(file_exists($logPath = $this->getServer()->getDataPath() . "server.log")){
	 		if(count(explode("\n", $log = file_get_contents($logPath))) > 1000){
	 			file_put_contents($this->getDataFolder() . date("Y-m-d H:i:s", time(true)) . ".log", str_replace("[Server thread", "[", $log));
	 			file_put_contents($logPath, "");
	 		}
	 	}else{
	 		file_put_contents($logPath, "");
	 	}
	}
}