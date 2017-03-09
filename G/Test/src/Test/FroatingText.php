<?php
namespace Test;

use pocketmine\Server;
use pocketmine\Player;
use pocketmine\math\Vector3;
use pocketmine\block\Block;
use pocketmine\item\Item;
use pocketmine\entity\Effect;

/* 
 * Particles
 * Level->addParticle(new (*Name*)Particle(Vector3 [, **]));
*/

//Vector3 $vec
use pocketmine\level\particle\BubbleParticle;
//Vector3 $vec, Int $scale = 0
use pocketmine\level\particle\CriticalParticle;
//Vector3 $vec Block $b
use pocketmine\level\particle\DestroyBlockParticle;
//Vector3 $vec, Int $r, Int $g, Int $b, Int $a = 255
use pocketmine\level\particle\DustParticle;
//Vector3 $vec
use pocketmine\level\particle\EnchantParticle;
//Vector3 $vec
use pocketmine\level\particle\EntityFlameParticle;
//Vector3 $vec
use pocketmine\level\particle\ExplodeParticle;
//Vector3 $vec
use pocketmine\level\particle\FlameParticle;
//Vector3 $vec, Int $scale = 0
use pocketmine\level\particle\HeartParticle;
//Vector3 $vec, Int $scale = 0
use pocketmine\level\particle\InkParticle;
//Vector3 $vec, Item $item
use pocketmine\level\particle\ItemBreakParticle;
//Vector3 $vec
use pocketmine\level\particle\LavaDripParticle;
//Vector3 $vec
use pocketmine\level\particle\LavaParticle;
//Vector3 $vec, Int $width = 0, Int $height = 0
use pocketmine\level\particle\MobSpawnParticle;
//Vector3 $vec
use pocketmine\level\particle\PortalParticle;
//Vector3 $vec, Int $lifetime = 1
use pocketmine\level\particle\RedstoneParticle;
//Vector3 $vec, Int $scale = 0
use pocketmine\level\particle\SmokeParticle;
//Vector3 $vec
use pocketmine\level\particle\SplashParticle;
//Vector3 $vec
use pocketmine\level\particle\SporeParticle;
//Vector3 $vec, Block $b
use pocketmine\level\particle\TerrainParticle;
//Vector3 $vec
use pocketmine\level\particle\WaterDripParticle;
//Vector3 $vec
use pocketmine\level\particle\WaterParticle;

/*
 * Sound
 * Level->addSound(new (*Name*)Sound(Vector3 [, Int]));
*/
//Vector3 $vec, Int $pitch = 0
use pocketmine\level\sound\BatSound;
//Vector3 $vec, Int $pitch = 0
use pocketmine\level\sound\ClickSound;
//Vector3 $vec, Int $pitch = 0
use pocketmine\level\sound\DoorSound;
//Vector3 $vec, Int $pitch = 0
use pocketmine\level\sound\FizzSound;
//Vector3 $vec, Int $pitch = 0
use pocketmine\level\sound\LaunchSound;
//Vector3 $vec, Int $pitch = 0
use pocketmine\level\sound\PopSound;

class FroatingText{
	public $closed = false;
	private $server;
	private $target;
	private $schedule;
	public $r = 0;
	public $g = 0;
	public $b = 0;
	public $a = 0;

	public function __construct($plugin, Player $target){
		$this->server = Server::getInstance();
		$this->target = $target;
		$this->server->getScheduler()->scheduleRepeatingTask(new Task($plugin, [$this,"onTick"]), 5);
	}

	public function onTick(){
		$t = $this->target;
 		if(!$t->loggedIn){
 			$this->closed = true;
 		}elseif($t->spawned){
//			$vec = new Vector3($t->x - (sin($t->getyaw() / 180 * M_PI) * cos($t->getPitch() / 180 * M_PI) * 2), $t->y + 1.5 - (sin($t->getPitch() / 180 * M_PI) * 2), $t->z + (cos($t->getyaw() / 180 * M_PI) * cos($t->getPitch() / 180 * M_PI) * 2));
			for($y = 0; $y < 360; $y += 10){
				$vec = new Vector3(
					$t->x - (sin($y / 180 * M_PI) * 0.5),
					$t->y + 1.8,
					$t->z + (cos($y / 180 * M_PI) * 0.5));
				$t->getLevel()->addParticle(new DustParticle($vec, 160, 220, 255));
			}
		}
	}
}