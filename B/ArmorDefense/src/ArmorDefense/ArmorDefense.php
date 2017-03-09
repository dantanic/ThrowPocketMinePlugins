<?php

namespace ArmorDefense;

use pocketmine\item\Item;
use pocketmine\event\entity\EntityDamageEvent;

class ArmorDefense extends \pocketmine\plugin\PluginBase implements \pocketmine\event\Listener{
	public function onEnable(){
		@mkdir($this->getDataFolder());
		$this->armorTable = (new \pocketmine\utils\Config($this->getDataFolder() . "ArmorDefense.yml", Config::YAML, [Item::LEATHER_CAP => 1, Item::LEATHER_TUNIC => 3, Item::LEATHER_PANTS => 2, Item::LEATHER_BOOTS => 1, Item::CHAIN_HELMET => 1, Item::CHAIN_CHESTPLATE => 5, Item::CHAIN_LEGGINGS => 4, Item::CHAIN_BOOTS => 1, Item::GOLD_HELMET => 1, Item::GOLD_CHESTPLATE => 5, Item::GOLD_LEGGINGS => 3, Item::GOLD_BOOTS => 1, Item::IRON_HELMET => 2, Item::IRON_CHESTPLATE => 6, Item::IRON_LEGGINGS => 5, Item::IRON_BOOTS => 2, Item::DIAMOND_HELMET => 3, Item::DIAMOND_CHESTPLATE => 8, Item::DIAMOND_LEGGINGS => 6, Item::DIAMOND_BOOTS => 3]))->getAll();
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
	}

	/**
	 * @priority HIGHTEST
	 */
 	public function onEntityDamage(EntityDamageEvent $event){
		if(($player = $event->getEntity()) instanceof \pocketmine\Player && $event instanceof \pocketmine\event\entity\EntityDamageByEntityEvent){
			$defense = 0;
			foreach($player->getInventory()->getArmorContents() as $index => $armor){
				if(isset($this->armorTable[$id = $armor->getID()])){
					$defense += $this->armorTable[$id];
				}
			}
			$event->setDamage(max(-floor($event->getDamage(EntityDamageEvent::MODIFIER_BASE) * $defense * 0.04), -$event->getDamage(EntityDamageEvent::MODIFIER_BASE)), EntityDamageEvent::MODIFIER_ARMOR);
		}
	}
}