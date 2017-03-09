<?php
namespace BeautifulExplosion;

use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use pocketmine\event\entity\ExplosionPrimeEvent;
use pocketmine\level\Level;
use pocketmine\level\particle\Particle;
use pocketmine\network\protocol\LevelEventPacket;
use BeautifulExplosion\task\ShowParticleAsyncTask;

class BeautifulExplosion extends PluginBase implements Listener{
	public $particles = [];

	public function onLoad(){
		/* Dust Particle data :
			(0xff << 24) | (($r & 0xff) << 16) | (($g & 0xff) << 8) | ($b & 0xff);
		*/


		// Green : r = 50(0x32), g = 200(0xC8), b = 0(0x00) 
		$g = (0xff << 24) | ((0x32 & 0xff) << 16) | ((0xC8 & 0xff) << 8) | (0x00 & 0xff);
		// Black : r = 0(0x00), g = 0(0x00), b = 0(0x00)
		$b = (0xff << 24) | ((0x00 & 0xff) << 16) | ((0x00 & 0xff) << 8) | (0x00 & 0xff);
		// DarkGreen : r = 50(0x32), g = 100(0x64), b = 50(0x32),
		$d = (0xff << 24) | ((0x32 & 0xff) << 16) | ((0x64 & 0xff) << 8) | (0x32 & 0xff);
		
		// Creaper
 		$this->particles[] = [
 			$g, $g, $g, $g, $g, $g, $g, $g,
			$g, $g, $g, $g, $g, $g, $g, $g,
			$g, $d, $d, $g, $g, $d, $d, $g,
			$g, $d, $b, $g, $g, $b, $d, $g,
			$g, $g, $g, $d, $d, $g, $g, $g,
			$g, $g, $d, $b, $b, $d, $g, $g,
			$g, $g, $b, $b, $b, $b, $g, $g,
			$g, $g, $b, $g, $g, $b, $g, $g
		];
	}
	public function onEnable(){
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
	}

	/**
	 * @priority HIGHEST
	 */
	public function onExplosionPrime(ExplosionPrimeEvent $event){
		$event->setCancelled();
		$entity = $event->getEntity();
		$this->getServer()->getScheduler()->scheduleAsyncTask(new ShowParticleAsyncTask($entity->x, $entity->y + 5, $entity->z, $entity->level->getName()));
	}

	public function showParticle($x, $y, $z, Level $level){
		$packets = [];
		$particlePk = new LevelEventPacket;
		$particlePk->evid = LevelEventPacket::EVENT_ADD_PARTICLE_MASK | Particle::TYPE_DUST;
		$particlePk->x = $x;
		$particlePk->y = $y;
		$particlePk->z = $z;
		foreach($this->particles[array_rand($this->particles)] as $key => $data){
			$px = $key % 8;
			$py = ($key - $px) * (1 / 8);
	 		$pk = clone $particlePk;
			$pk->x += ($px - 4) * 0.2;
			$pk->y -= $py * 0.2;
			$pk->data = $data;
			$packets[] = $pk;
		}
		foreach($level->getChunkPlayers($x >> 4, $z >> 4) as $player){
			foreach($packets as $pk){
				for($i = 0; $i < 5; $i++){
					$player->directDataPacket($pk);
				}
			}
		}
	}
}