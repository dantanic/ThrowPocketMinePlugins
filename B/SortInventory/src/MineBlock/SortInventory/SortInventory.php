<?php

namespace MineBlock\SortInventory;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\item\Item;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;

class SortInventory extends PluginBase{

	public function onCommand(CommandSender $sender, Command $cmd, $label, array $sub){ //명령어 입력시 인벤토리 정렬
		$mm = "[SortInventory] ";
		$ik = $this->isKorean();
		if($sender->getName() == "CONSOLE"){
			$sender->sendMessage($mm . ($ik ? "게임내에서만 사용가능합니다." : "Please run this command in-game"));
			return true;
		}
		$inv = $sender->getInventory();
		$save = [];
		foreach($inv->getContents() as $i) $save[] = $i->getID() . " " . $i->getDamage() . " " . $i->getCount();
		asort($save);
		$sort = [];
		foreach($save as $ii){
			$i = explode(" ", $ii);
			$sort[] = Item::get($i[0], $i[1], $i[2]);
		}
		$inv->setContents($sort);
		$sender->sendMessage($mm . ($ik ? "인벤토리를 정렬했습니다." : "Sort the inventory."));
		return true;
	}

	public function isKorean(){
		return strtolower($this->getServer()->getLanguage()->getName()) == "korean";
	}
}
