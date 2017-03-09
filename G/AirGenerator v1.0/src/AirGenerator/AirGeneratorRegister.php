<?php

namespace AirGenerator;

class AirGeneratorRegister extends \pocketmine\plugin\PluginBase{
	public function onLoad(){
		\pocketmine\level\generator\Generator::addGenerator(AirGenerator::class, "Air");
	}
}