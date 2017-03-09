<?php

namespace MineBlock\TpsLog;

use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;

class TpsLog extends PluginBase{
	public function onEnable(){
		$this->loadYml();
		$this->getServer()->getScheduler()->scheduleRepeatingTask(new Task($this, [$this,"onTick"]), 1200);
	}
	
	public function onDisable(){
		$this->getServer()->getScheduler()->cancelTasks($this);
	}

	public function onTick(){
		$log = "TPS:" . $this->getServer()->getTicksPerSecond() . " || Load:" . $this->getServer()->getTickUsage() . "% || Ram:" . round((memory_get_usage() / 1024) / 1024, 2) . "/" . round((memory_get_usage(true) / 1024) / 1024, 2) . "MB";
		$this->getServer()->getLogger()->info("TpsLog\n" . str_replace("||", "\n[" . date("Y.m.d.H.i.s", time()) . "][TPS] ", $log));
		$this->tl[date("Y.m.d.H.i.s", time())] = $log;
		$this->saveYml();
	}

	public function loadYml(){
		@mkdir($this->getDataFolder());
		$this->tl = (new Config($this->getDataFolder() . "TpsLog.yml", Config::YAML))->getAll();
	}

	public function saveYml(){
		$tl = new Config($this->getDataFolder() . "TpsLog.yml", Config::YAML);
		$tl->setAll($this->tl);
		$tl->save();
	}
}