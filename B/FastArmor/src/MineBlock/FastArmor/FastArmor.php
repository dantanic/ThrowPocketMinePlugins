<?php

namespace MineBlock\FastArmor;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\item\Item;
use pocketmine\plugin\PluginBase;

class FastArmor extends PluginBase implements Listener{

	public function onEnable(){
		$this->armorTable = [Item::LEATHER_CAP => 0, Item::LEATHER_TUNIC => 1, Item::LEATHER_PANTS => 2, Item::LEATHER_BOOTS => 3, Item::CHAIN_HELMET => 0, Item::CHAIN_CHESTPLATE => 1, Item::CHAIN_LEGGINGS => 2, Item::CHAIN_BOOTS => 3, Item::GOLD_HELMET => 0, Item::GOLD_CHESTPLATE => 1, Item::GOLD_LEGGINGS => 2, Item::GOLD_BOOTS => 3, Item::IRON_HELMET => 0, Item::IRON_CHESTPLATE => 1, Item::IRON_LEGGINGS => 2, Item::IRON_BOOTS => 3, Item::DIAMOND_HELMET => 0, Item::DIAMOND_CHESTPLATE => 1, Item::DIAMOND_LEGGINGS => 2, Item::DIAMOND_BOOTS => 3];
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
	}

	public function onPlayerInteract(PlayerInteractEvent $event){
		if($event->getFace() == 0xff){
			$p = $event->getPlayer();
			$inv = $p->getInventory();
			$i = $inv->getItemInHand();
			if(isset($this->armorTable[$id = $i->getID()])){
				$ai = $inv->getArmorItem($type = $this->armorTable[$id]);
				$inv->setArmorItem($type, $i, $p);
				$inv->setItem($inv->getHeldItemSlot(), $ai);
				$inv->sendContents($p);
				$inv->sendArmorContents($p);
			}
		}
	}
}