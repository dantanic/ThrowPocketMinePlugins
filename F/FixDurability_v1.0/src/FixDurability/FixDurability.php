<?php

namespace FixDurability;

use pocketmine\item\Item;

class FixDurability extends \pocketmine\plugin\PluginBase implements \pocketmine\event\Listener{
	private $players = [];

	public function onEnable(){
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
	}

	/**
	 * @priority HIGHEST
	 */
	public function onBlockBreak(\pocketmine\event\block\BlockBreakEvent $event){
 		$item = $event->getItem();
		$player = $event->getPlayer();
		if($item->isTool() && $player->isSurvival()){
			if($item->getmaxDurability() <= ($damage = $item->getDamage() + 2)){
				$player->getInventory()->setItemInHand(Item::get(0, 0));
			}else{
				$player->getInventory()->setItemInHand(Item::get($item->getID(), $damage, 1));
			}
		}
	}
}