<?php

namespace UsefulShears;

use pocketmine\block\Block;
use pocketmine\item\Item;

class UsefulShears extends \pocketmine\plugin\PluginBase implements \pocketmine\event\Listener{
	public function onEnable(){
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
	}

	/**
	 * @priority LOWEST
	 */
 	public function onBlockBreak(\pocketmine\event\block\BlockBreakEvent $event){
 		$item = $event->getItem();
 		$block = $event->getBlock();
 		if(!$event->isCancelled() && $item->getID() == 359 && $block->getDamage() >= 7){
			$player = $event->getPlayer();
			if($player->isSurvival()){
				$event->setCancelled();
				$drops = $block->getDrops($item);
				switch($block->getID()){
					case Block::BEETROOT_BLOCK:
					case Block::WHEAT_BLOCK:
						if($drops[1][2] > 0){
							$drops[1][2]--;
						}
					break;
					case Block::CARROT_BLOCK:
					case Block::POTATO_BLOCK:
						$drops[0][2]--;
					break;
					default:
						return;
					break;
				}
				$player->getInventory()->setItemInHand($item->getDamage() + 1 >= 239 ? new Item(0, 0, 0) : new Item($item->getID(), $item->getDamage() + 1, $item->getCount()));
				$block->getLevel()->setBlock($block, new Block($block->getID(), 0));
 				foreach($drops as $drop){
					$block->getLevel()->dropItem($block->add(0.5, 0.5, 0.5), Item::get(...$drop));
				}
				$block->getLevel()->addParticle(new \pocketmine\level\particle\DestroyBlockParticle($block->add(0.5), $block), $block->getLevel()->getChunkPlayers($block->x >> 4, $block->z >> 4));
			}
		}
	}
}