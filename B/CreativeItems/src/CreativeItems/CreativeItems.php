<?php

namespace CreativeItems;

use pocketmine\item\Item;
use pocketmine\block\Block;

class CreativeItems extends \pocketmine\plugin\PluginBase implements \pocketmine\event\Listener{
	public function onLoad(){
		foreach(array_keys($this->armorTable = [Item::LEATHER_CAP => 0, Item::LEATHER_TUNIC => 1, Item::LEATHER_PANTS => 2, Item::LEATHER_BOOTS => 3, Item::CHAIN_HELMET => 0, Item::CHAIN_CHESTPLATE => 1, Item::CHAIN_LEGGINGS => 2, Item::CHAIN_BOOTS => 3, Item::GOLD_HELMET => 0, Item::GOLD_CHESTPLATE => 1, Item::GOLD_LEGGINGS => 2, Item::GOLD_BOOTS => 3, Item::IRON_HELMET => 0, Item::IRON_CHESTPLATE => 1, Item::IRON_LEGGINGS => 2, Item::IRON_BOOTS => 3, Item::DIAMOND_HELMET => 0, Item::DIAMOND_CHESTPLATE => 1, Item::DIAMOND_LEGGINGS => 2, Item::DIAMOND_BOOTS => 3]) + [8, 10, 27, [38,1], [38,2], [38,3], [38,4], [38,5], [38,6], [38,7], [38,8], 51, 60, 62, 63, 66, 71, 92, 99, 100, 111, 127, 175, [175,1], [175,2], [175,3], [175,4], [175,5], 246, 248, 249, 260, 262, 263, 264, 265, 266, 268, 269, 270, 271, 272, 273, 275, 275, 276, 277, 278, 279, 280, 281, 282, 283, 284, 285, 286, 287, 288, 289, 290, 291, 292, 293, 294, 297, 318, 319, 320, 321, 329, 330, 331, 332, 333, 334, 336, 337, 339, 340, 341, 344, 346, 348, 352, 353, 357, 360, 363, 364, 365, 366, 388, 393, 400, 405, 406, 457, 459] as $id){
			if(!Item::isCreativeItem($item = new Item(...(is_array($id) ? $id : [$id])))) Item::addCreativeItem($item);
		}
	}
	
	public function onEnable(){
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
	}

	public function onPlayerInteract(\pocketmine\event\player\PlayerInteractEvent $event){
		$item = $event->getItem();
		$player = $event->getPlayer();
		if(($face = $event->getFace()) == 255){
			if(isset($this->armorTable[$id = $item->getID()])){
				$inven = $event->getPlayer()->getInventory();
				if($inven->getArmorItem($this->armorTable[$id])->getID() == $id) $inven->setArmorItem($this->armorTable[$id], Item::get(0, 0), $player);
				else $inven->setArmorItem($this->armorTable[$id], $item, $player);
				$inven->sendArmorContents($player);
			}
		}else{
			$block = $event->getBlock();
			$sideBlock = $block->getSide($face);
			if($item->getID() == 248){
				if($sideBlock->getID() == 95){
					$event->setCancelled();
					$player->getLevel()->setBlock($sideBlock, Block::get(0,0));
				}
			}elseif($item->getID() == 249){
				$event->setCancelled();
				$player->getLevel()->setBlock($sideBlock, Block::get(95,0));
			}
		}
	}
}