<?php

namespace MineBlock\WeaponDamage;

use pocketmine\Player;
use pocketmine\event\Listener;
use pocketmine\event\entity\EntityDamageByChildEntityEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\item\Item;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;

class WeaponDamage extends PluginBase implements Listener{
	public function onEnable(){
		@mkdir($this->getDataFolder());
		$this->damageTable = (new Config($this->getDataFolder() . "WeaponDamage.yml", Config::YAML, [Item::AIR => 1, Item::WOODEN_SWORD => 4, Item::GOLD_SWORD => 4, Item::STONE_SWORD => 5, Item::IRON_SWORD => 6, Item::DIAMOND_SWORD => 7, Item::WOODEN_AXE => 3, Item::GOLD_AXE => 3, Item::STONE_AXE => 3, Item::IRON_AXE => 5, Item::DIAMOND_AXE => 6, Item::WOODEN_PICKAXE => 2, Item::GOLD_PICKAXE => 2, Item::STONE_PICKAXE => 3, Item::IRON_PICKAXE => 4, Item::DIAMOND_PICKAXE => 5, Item::WOODEN_SHOVEL => 1, Item::GOLD_SHOVEL => 1, Item::STONE_SHOVEL => 2, Item::IRON_SHOVEL => 3, Item::DIAMOND_SHOVEL => 4]))->getAll();
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
	}

	/**
	 * @priority LOWEST
	 */
 	public function onEntityDamage(EntityDamageEvent $event){
		if($event instanceof EntityDamageByEntityEvent && ($d = $event->getDamager()) instanceof Player) $event->setDamage(isset($this->damageTable[$id = $event instanceof EntityDamageByChildEntityEvent ? Item::ARROW : $d->getInventory()->getItemInHand()->getID()]) ? $this->damageTable[$id] : $this->damageTable[0], EntityDamageEvent::MODIFIER_BASE);
	}
}