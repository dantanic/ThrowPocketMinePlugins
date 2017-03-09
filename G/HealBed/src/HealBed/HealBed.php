<?php

namespace HealBed;

use pocketmine\Player;

class HealBed extends \pocketmine\plugin\PluginBase implements \pocketmine\event\Listener{
	protected $bedTable = [
		[3, 1], [5, -1], [3, -1], [5, 1]
	];

	public function onLoad(){
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
			if($block->getID() == \pocketmine\block\Block::BED_BLOCK){
				$player = $event->getPlayer();
				$event->setCancelled();
				if(isset($this->bedTable[$damage = $block->getDamage()])){
					$block = $block->getSide(...$this->bedTable[$damage]);
				}
				$this->getServer()->getPluginManager()->callEvent($ev = new \pocketmine\event\player\PlayerBedEnterEvent($player, $block));
				if(!$ev->isCancelled()){
					foreach($player->getLevel()->getNearbyEntities($player->getBoundingBox()->grow(2, 1, 2), $player) as $nearEntity){
						if($nearEntity instanceof Player && $nearEntity->isSleeping() && $block->distance($this->sleepingProperty->getValue($nearEntity)) <= 0.1){
							$player->sendMessage("This bed is occupied");
							return;
						}
					}
					//$player->teleport($block->add(0.5, -0.8, 0.5));
 					$this->sleepingProperty->setValue($player, $block);
					$player->setDataProperty(Player::DATA_PLAYER_BED_POSITION, Player::DATA_TYPE_POS, [$block->x, $block->y, $block->z]);
					$player->setDataFlag(Player::DATA_PLAYER_FLAGS, Player::DATA_PLAYER_FLAG_SLEEP, true);
				}
			}
		}
	}

	/**
	 * @priority HIGHEST
	 */
	public function onPlayerBedEnter(\pocketmine\event\player\PlayerBedEnterEvent $event){
		if(!$event->isCancelled()){
			$event->getPlayer()->addEffect(\pocketmine\entity\Effect::getEffect(10)->setAmplifier(0)->setDuration(PHP_INT_MAX));
			$event->getPlayer()->addEffect(\pocketmine\entity\Effect::getEffect(9)->setAmplifier(3)->setDuration(PHP_INT_MAX));
		}
	}

	public function onPlayerBedLeave(\pocketmine\event\player\PlayerBedLeaveEvent $event){
		$event->getPlayer()->removeEffect(9);
		$event->getPlayer()->removeEffect(10);
	}

	public function onPlayerQuit(\pocketmine\event\player\PlayerQuitEvent $event){
		$event->getPlayer()->removeEffect(9);
		$event->getPlayer()->removeEffect(10);
	}
} 