<?php

namespace MineBlock\DamageDisplay;

use pocketmine\entity\Entity;
use pocketmine\event\Listener;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityRegainHealthEvent;
use pocketmine\network\protocol\AddPlayerPacket;
use pocketmine\network\protocol\RemovePlayerPacket;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\TextFormat;

class DamageDisplay extends PluginBase implements Listener{

	public function onEnable(){
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
	}

	/**
	 * @priority HIGHTEST
	 */
 	public function onEntityDamage(EntityDamageEvent $event){
 		if(!$event->isCancelled()) $this->displayDamage($event->getEntity(), -$event->getFinalDamage());
	}

	/**
	 * @priority HIGHTEST
	 */
 	public function onEntityRegainHealth(EntityRegainHealthEvent $event){
 		if(!$event->isCancelled()) $this->displayDamage($event->getEntity(), $event->getAmount());
	}

	public function displayDamage(Entity $e, $damage = 0){
		$pk = new AddPlayerPacket();
		$pk->username = TextFormat::BOLD.TextFormat::ITALIC.($damage == 0 ? [TextFormat::WHITE, TextFormat::DARK_GRAY][rand(0,1)]."0" : ($damage < 0 ? [TextFormat::RED, TextFormat::DARK_RED][rand(0,1)].(-$damage) : [TextFormat::GREEN, TextFormat::DARK_GREEN][rand(0,1)].$damage));
		$pk->eid = $pk->clientID = $id = bcadd("1095216660480", mt_rand(0, 0x7fffffff));
		$pk->yaw = $pk->pitch = $pk->item = $pk->meta = $pk->slim = false;
		$pk->skin = str_repeat("\x00", 64 * 32 * 4);
		$pk->metadata = [
			Entity::DATA_FLAGS => [Entity::DATA_TYPE_BYTE, 1 << Entity::DATA_FLAG_INVISIBLE],
			Entity::DATA_AIR => [Entity::DATA_TYPE_SHORT, 300],
			Entity::DATA_SHOW_NAMETAG => [Entity::DATA_TYPE_BYTE, 1],
			Entity::DATA_NO_AI => [Entity::DATA_TYPE_BYTE, 1]
		];
		$pk->x = $e->x + (rand(-500, 500) * 0.001);
		$pk->y = $e->y + (rand(-2500, -880) * 0.001);
		$pk->z = $e->z + (rand(-500, 500) * 0.001);
		$this->getServer()->broadcastPacket($players = $e->getLevel()->getUsingChunk($e->x >> 4, $e->z >> 4), $pk);
		$pk2 = new RemovePlayerPacket();
		$pk2->eid = $pk2->clientID = $id;
		$this->getServer()->getScheduler()->scheduleDelayedTask(new Task($this, [$this->getServer(),"broadcastPacket"], [$players, $pk2]), 15);
	}
}