<?php

namespace BurnTime;

use pocketmine\inventory\Fuel;

class BurnTime extends \pocketmine\plugin\PluginBase{
	public function onEnable(){
		@mkdir($this->getDataFolder());
		Fuel::$duration = (new \pocketmine\utils\Config($this->getDataFolder() . "BurnTime.yml", Config::YAML, Fuel::$duration))->getAll();
	}
}