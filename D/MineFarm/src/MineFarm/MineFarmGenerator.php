<?php

namespace MoreGenerator;

use pocketmine\math\Vector3;
use pocketmine\utils\Random;
use pocketmine\block\Block;
use pocketmine\utils\Config;

class MineFarmGenerator extends \pocketmine\level\generator\Generator{
	private $mf, $level, $chunk, $random, $populators = [], $structure, $chunks, $options, $floorLevel, $preset;

	public function getSettings(){
		return [];
	}

	public function getName(){
		return "minefarm";
	}

	public function __construct(array $option = []){}

	public function init(\pocketmine\level\ChunkManager $level, Random $random){
		$this->mf = (new Config(\pocketmine\PLUGIN_PATH . "MineFarm/Farm.yml", Config::YAML, ["Auto" => false, "Sell" => true, "Price" => 100000, "Distance" => 5, "Size" => 1, "Air" => 3, "Item" => true, "Items" => [["269:0", 1], ["270:0", 1], ["271:0", 1], ["290:0", 1]], "Farm" => [], "Invite" => []]))->getAll();
		$this->level = $level;
		$this->random = $random;
	}

	public function generateChunk($chunkX, $chunkZ){
		$chunk = clone $this->level->getChunk($chunkX, $chunkZ);
		$chunk->setGenerated();
		$this->level->setChunk($chunkX, $chunkZ, $chunk);
	}

	public function populateChunk($chunkX, $chunkZ){}

	public function getSpawn(){
		return new Vector3(128, 120, 128);
	}
}