<?php

namespace X;

use pocketmine\utils\TextFormat as Color;
use pocketmine\item\Item;

class X extends \pocketmine\plugin\PluginBase implements \pocketmine\event\Listener{
	public function onLoad(){
		if(!Item::isCreativeItem($item = new Item(288))) Item::addCreativeItem($item);
	}

	public function onEnable(){
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
	}

	public function onCommand(\pocketmine\command\CommandSender $sender, \pocketmine\command\Command $cmd, $label, array $sub){
		$ik = $this->getServer()->getLanguage()->getName() == "\"한국어\"";
		if(!$sender instanceof \pocketmine\Player){
			$sender->sendMessage(Color::YELLOW . "[X] " . ($ik ? "게임내에서만 사용가능합니다. " : "Please run this command in-game"));
		}else{
			if(isset($sub[0]) && strtolower($sub[0]) == "all"){
				$sender->getInventory()->clearAll();
				$sender->sendMessage(Color::YELLOW . "[X] " . ($ik ? "모든 아이템을 제거합니다." : "Remove all item"));
			}else{
				$sender->getInventory()->setItemInHand(new Item(0), $sender);
				$sender->sendMessage(Color::YELLOW . "[X] " . ($ik ? "들고있는 아이템을 제거합니다." : "Remove item in your hand"));
			}
		}
		return true;
	}

	public function onPlayerInteract(\pocketmine\event\player\PlayerInteractEvent $event){
		if($event->getItem()->getID() == 288 && $event->getFace() !== 0xff){
			$player = $event->getPlayer();
			if($player->isCreative()){
				$player->teleport($event->getBlock()->add(0.5, 1, 0.5));
			}
		}
	}
}