<?php

namespace MineBlock\HealBed;

use pocketmine\Player;

class HealBed extends \pocketmine\plugin\PluginBase implements \pocketmine\event\Listener{
	protected $bedTable = [
		[3, 1], [5, -1], [3, -1], [5, 1]
	];

	public function onLoad(){
		$this->players = [];
		$this->sleepingProperty = (new \ReflectionClass("\\pocketmine\\Player"))->getProperty("sleeping");
		$this->sleepingProperty->setAccessible(true);
	}

	public function onEnable(){
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
	}

	/**
	 * @priority HIGHEST
	 */
	public function onPlayerInteract(\pocketmine\event\player\PlayerInteractEvent $event){
		if(!$event->isCancelled()){
			$block = $event->getBlock();
			if($block->getID() == 26){
				$player = $event->getPlayer();
				if(isset($this->bedTable[$damage = $block->getDamage()])){
					$block = $block->getSide(...$this->bedTable[$damage][0]);
				}
				$this->getServer()->getPluginManager()->callEvent($ev = new PlayerBedEnterEvent($player, $block));
				if(!$ev->isCancelled()){
					foreach($player->getLevel()->getNearbyEntities($player->getBoundingBox()->grow(2, 1, 2), $player) as $nearEntity){
						if($nearEntity instanceof Player && $nearEntity->isSleeping() && $block->distance($this->sleepingProperty->getValue($nearEntity)) <= 0.1){
							$player->sendMessage("This bed is occupied");
							return;
						}
					}
					$player->teleport($block->add(0.5, -0.5, 0.5));
 					$this->sleepingProperty->setValue($player, $block);
					$player->setDataProperty(Player::DATA_PLAYER_BED_POSITION, Player::DATA_TYPE_POS, [$block->x, $block->y, $block->z]);
					$player->setDataFlag(Player::DATA_PLAYER_FLAGS, Player::DATA_PLAYER_FLAG_SLEEP, true);
				}
			}
		}
	}

	public function onPlayerBedEnter(\pocketmine\event\player\PlayerBedEnterEvent $event){
		$player = $event->getPlayer();
		$this->players[$player->getName()] = $player;
	}

	public function onPlayerBedLeave(\pocketmine\event\player\PlayerBedLeaveEvent $event){
		$player = $event->getPlayer();
		unset($this->players[$player->getName()]);
	}

	public function onPlayerQuit(\pocketmine\event\player\PlayerQuitEvent $event){
		$player = $event->getPlayer();
		unset($this->players[$player->getName()]);
	}
}