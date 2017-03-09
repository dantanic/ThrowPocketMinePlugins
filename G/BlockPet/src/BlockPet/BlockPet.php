<?php

namespace BlockPet;

use pocketmine\nbt\tag\Enum;
use pocketmine\nbt\tag\Double;
use pocketmine\entity\Entity;

class BlockPet extends \pocketmine\plugin\PluginBase{ //implements \pocketmine\event\Listener{
	public function onLoad(){
		Entity::registerEntity(BlockPetEntity::class);
		$this->motion = new Enum("Motion", [
			$double = new Double("", 0),
			clone $double,
			clone $double,
		]);
		$this->rotation = new Enum("Rotation", [
			$float = new \pocketmine\nbt\tag\Float("", 0),
			clone $float
		]);
		$this->players = [];
	}

	public function onEnable(){
		$this->getServer()->getScheduler()->scheduleRepeatingTask(new Task($this, [$this, "onTick"]), 10);
	}

	public function onCommand(\pocketmine\command\CommandSender $sender, \pocketmine\command\Command $cmd, $label, array $sub){
	}

	public function onTick(){
		foreach($this->getServer()->getOnlinePlayers() as $player){
			if($player->spawned && (!isset($this->players[$name = strtolower($player->getName())]) || $this->players[$name]->closed)){
				$blockPet = Entity::createEntity("BlockPetEntity", $player->getLevel()->getChunk($player->x >> 4, $player->z >> 4), new \pocketmine\nbt\tag\Compound("", [
					"Pos" => new Enum("Pos", [
						new Double("", $player->x + 0.5),
						new Double("", $player->y),
						new Double("", $player->z + 0.5)
					]),
					"Motion" => clone $this->motion,
					"Rotation" => clone $this->rotation
				]), $this, $player);
				$blockPet->spawnToAll();
				$this->players[$name] = $blockPet;
			}
		}
	}

	public function isKorean(){
		return $this->getServer()->getLanguage()->getName() == "\"한국어\"";
	}
}