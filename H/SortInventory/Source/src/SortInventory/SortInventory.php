<?php

namespace SortInventory;

use pocketmine\utils\TextFormat as Color;

class SortInventory extends \pocketmine\plugin\PluginBase{
	public function onCommand(\pocketmine\command\CommandSender $sender, \pocketmine\command\Command $cmd, $label, array $sub){
		$ik = $this->getServer()->getLanguage()->getName() == "\"한국어\"";
		if(!$sender instanceof \pocketmine\Player){
			$sender->sendMessage(Color::RED . "[SortInventory] " . ($ik ? "게임내에서만 사용가능합니다. " : "Please run this command in-game"));
		}elseif(!$sender->isSurvival()){
			$sender->sendMessage(Color::RED . "[SortInventory] " . ($ik ? "당신은 서바이벌 모드가 아닙니다." : "You are not survival mode"));
		}else{
			$armorInventory = [];
			foreach($sender->getInventory()->getArmorContents() as $item){
				if($item->getCount() > 0 && $item->getID() !== 0){
					$armorInventory[] = $item;
				}
			}
			$sender->getInventory()->setArmorContents([]);
			$inventory = [];
			foreach($sender->getInventory()->getContents() as $item){
				if($item->getCount() > 0 && $item->getID() !== 0){
					$inventory[] = $item;
				}
			}
			$sender->getInventory()->setArmorContents($armorInventory);
			$sender->getInventory()->setContents($inventory);
			$sender->sendMessage(Color::YELLOW . "[SortInventory] " . ($ik ? "당신의 인벤토리를 정렬했습니다." : "Sort your inventory"));
		}
		return true;
	}
}