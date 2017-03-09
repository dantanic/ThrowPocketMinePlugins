<?php

namespace MineBlock\SubInventory;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\item\Item;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;

class SubInventory extends PluginBase{
	public function onEnable(){
		$this->loadYml();
	}

	public function onCommand(CommandSender $sender, Command $cmd, $label, array $sub){
		$n = $sender->getName();
		$mm = "[SubInventory] ";
		$si = $this->si;
		$ik = $this->isKorean();
		if($n == "CONSOLE") return $sender->sendMessage($mm . ($ik ? "게임내에서만 사용가능합니다." : "Please run this command in-game"));
		$getInv = [];
		$inv = $sender->getInventory();
		if(!isset($si[$n])) $si[$n] = [];
		$getInv = [];
		foreach($inv->getContents() as $gI) if($gI->getID() !== 0 and $gI->getCount() > 0) $getInv[] = [$gI->getID(), $gI->getDamage(), $gI->getCount()];
		$setInv = [];
		foreach($si[$n] as $sI) $setInv[] = Item::get($sI[0], $sI[1], $sI[2]);
		$si[$n] = $getInv;
		$inv->setContents($setInv);
		if($this->si !== $si){
			$this->si = $si;
			$this->saveYml();
		}
		$sender->sendMessage($mm . ($ik ? "인벤토리가 교체되었습니다." : "Inventory is change"));
		return true;
	}

	public function loadYml(){
		@mkdir($this->getDataFolder());
		$this->si = (new Config($this->getDataFolder() . "SubInventory.yml", Config::YAML))->getAll();
	}

	public function saveYml(){
		asort($this->si);
		$si = new Config($this->getDataFolder() . "SubInventory.yml", Config::YAML);
		$si->setAll($this->si);
		$si->save();
	}

	public function isKorean(){
		return strtolower($this->getServer()->getLanguage()->getName()) == "korean";
	}
}
