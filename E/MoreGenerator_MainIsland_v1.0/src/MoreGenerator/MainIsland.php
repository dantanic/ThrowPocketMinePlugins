<?php

namespace MoreGenerator;

use pocketmine\block\Block;

class MainIsland extends \pocketmine\plugin\PluginBase{

 	public function onLoad(){
 		\pocketmine\level\generator\Generator::addGenerator(MainIslandGenerator::class, "mainisland");
	}
}