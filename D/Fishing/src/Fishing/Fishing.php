<?php

namespace Fishing;

use pocketmine\item\Item;
use pocketmine\nbt\tag\Compound;
use pocketmine\nbt\tag\Enum;
use pocketmine\nbt\tag\Double;
use pocketmine\nbt\tag\Float;
use pocketmine\entity\Entity;

class Fishing extends \pocketmine\plugin\PluginBase implements \pocketmine\event\Listener{
	public function onLoad(){
		$this->players = [];
		if(!Item::isCreativeItem($item = new Item(346))) Item::addCreativeItem($item);
		Entity::registerEntity(FishingHook::class);
 		Item::$list[346] = FishingRod::class;
	}

	public function onEnable(){
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
	}

 	public function onPlayerInteract(\pocketmine\event\player\PlayerInteractEvent $event){
		if($event->getFace() == 255 && $event->getItem()->getID() === 346){
			$player = $event->getPlayer();
			if(!$player->getInventory() instanceof \pocketmine\inventory\PlayerInventory) return;
			$aimPos = $event->getTouchVector();
			$event->setCancelled();
			if(isset($this->players[$name = $player->getName()]) && $this->players[$name]->getHealth() !== 0){
				$this->players[$name]->goBack();
				if($this->players[$name]->closed) unset($this->players[$name]);
 			}else{
 				if(!$player->isCreative()){
					$item = $event->getItem();
 					$item->setDamage($item->getDamage() + 5);	
  				if($item->getDamage() >= 360){
						$player->getInventory()->setItemInHand(Item::get(Item::AIR, 0, 1), $player);
						return;
					}else{
						$player->getInventory()->setItemInHand($item, $player);
					}
				}
				$fishingHook = new FishingHook(
					$player->getLevel()->getChunk($player->x >> 4, $player->z >> 4, true),
					new Compound("", [
						"Pos" => new Enum("Pos", [
							new Double("", $player->x),
							new Double("", $player->y + $player->getEyeHeight()),
							new Double("", $player->z)
							]),
						"Motion" => new Enum("Motion", [
							new Double("", $aimPos->x * 0.7),
							new Double("", $aimPos->y * 0.7),
							new Double("", $aimPos->z * 0.7)
						]),
						"Rotation" => new Enum("Rotation", [
							new Float("", 0),
							new Float("", 0)
					])
					]),
					$player
				);
				$fishingHook->spawnToAll();
				$this->players[$name] = $fishingHook;
			}
 		}
	}

	public function onPlayerQuit(\pocketmine\event\player\PlayerQuitEvent $event){
		if(isset($this->players[$name = $event->getPlayer()->getName()])){
			$this->players[$name]->kill();
			unset($this->players[$name]);
 		}
	}

	public function onBlockBreak(\pocketmine\event\block\BlockBreakEvent $event){
		if($event->getItem()->getID() == 346) $event->setCancelled();
	}
}