<?php

namespace MineBlock\HealBed;

use pocketmine\Player;
use pocketmine\event\Listener;
use pocketmine\event\entity\EntityRegainHealthEvent;
use pocketmine\event\player\PlayerBedEnterEvent;
use pocketmine\event\player\PlayerBedLeaveEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\plugin\PluginBase;

class HealBed extends PluginBase implements Listener{

	public function onEnable(){
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
		$this->player = [];
	}

	/**
	 * @priority HIGHEST
	 */
	public function onPlayerInteract(PlayerInteractEvent $event){
		if($event->isCancelled()) return;
		$p = $event->getPlayer();
		$b = $event->getBlock();
		if($b->getID() !== 26) return;
		$event->setCancelled();
		$xTabel = [3 => 1, 1 => -1];
		$b = $b->getSide(5, isset($xTabel[$dmg = $b->getDamage()]) ? $xTabel[$dmg] : 0);
		$zTabel = [0 => 1, 2 => -1];
		$b = $b->getSide(3, isset($zTabel[$dmg]) ? $zTabel[$dmg] : 0);
		$this->getServer()->getPluginManager()->callEvent($ev = new PlayerBedEnterEvent($p, $b));
		if($ev->isCancelled()) return;
		$property = (new \ReflectionClass("\\pocketmine\\Player"))->getProperty("sleeping");
		$property->setAccessible(true);
		foreach($p->getLevel()->getNearbyEntities($p->getBoundingBox()->grow(2, 1, 2), $p) as $pl){
			if($pl instanceof Player && $pl->isSleeping()){
				if($b->distance($property->getValue($pl)) <= 0.1){
					$p->sendMessage("This bed is occupied");
					return;
				}
			}
		}
		$property->setValue($p, $b);
		$p->setDataProperty(Player::DATA_PLAYER_BED_POSITION, Player::DATA_TYPE_POS, [$b->x + 0.5, $b->y + 0.5, $b->z + 0.5]);
		$p->setDataFlag(Player::DATA_PLAYER_FLAGS, Player::DATA_PLAYER_FLAG_SLEEP, true);
	}

	public function onPlayerBedEnter(PlayerBedEnterEvent $event){
		$p = $event->getPlayer();
		if(!isset($this->player[$n = $p->getName()]) || $this->player[$n]->closed){
			$this->player[$n] = new FroatingText($p);
		}
	}

	public function onPlayerBedLeave(PlayerBedLeaveEvent $event){
		$p = $event->getPlayer();
		if(isset($this->player[$n = $p->getName()])){
			if(!$this->player[$n]->closed) $this->player[$n]->closed = true;
			unset($this->player[$n]);
		}
	}
}
