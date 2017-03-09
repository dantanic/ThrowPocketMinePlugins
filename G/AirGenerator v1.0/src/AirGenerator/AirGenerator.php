<?php

namespace AirGenerator;

class AirGenerator extends \pocketmine\level\generator\Generator{
	private $level;

	public function __construct(array $option = []){}

	public function init(\pocketmine\level\ChunkManager $level, \pocketmine\utils\Random $random){
		$this->level = $level;
	}

	public function generateChunk($chunkX, $chunkZ){
		$chunk = clone $this->level->getChunk($chunkX, $chunkZ);
		$chunk->setGenerated();
		$this->level->setChunk($chunkX, $chunkZ, $chunk);
	}

	public function populateChunk($chunkX, $chunkZ){}

	public function getSettings(){
		return [];
	}

	public function getName(){
		return "air";
	}

	public function getSpawn(){
		return new \pocketmine\math\Vector3(0, 0, 0);
	}
}