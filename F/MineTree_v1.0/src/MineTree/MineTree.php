<?php

namespace MineTree;

use pocketmine\utils\Config;
use pocketmine\utils\TextFormat as Color;

class MineTree extends \pocketmine\plugin\PluginBase implements \pocketmine\event\Listener{
	public function onEnable(){
		$this->loadYml();
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
	}
	public function onDisable(){
		$this->saveYml();
	}

	public function onCommand(\pocketmine\command\CommandSender $sender, \pocketmine\command\Command $cmd, $label, array $sub){
 		if(!isset($sub[0])) return false;
		$ik = $this->isKorean();
		switch(strtolower($sub[0])){
			case "on":
			case "온":
				$this->data["On"] = true;
				$r = Color::YELLOW . "[MineTree] " . ($ik ? "마인트리를 켭니다.": "MineTree is On");
			break;
			case "off":
			case "오프":
				$this->data["On"] = false;
				$r = Color::YELLOW . "[MineTree] " . ($ik ? "마인트리를  끕니다.": "MineTree is Off");
			default:
				return false;
			break;
		}
		if(isset($r)){
			$sender->sendMessage($r);
		}
		return true;
	}

	public function onBlockBreak(\pocketmine\event\block\BlockBreakEvent $event){
		if($this->data["On"] == true){
 			$block = $event->getBlock();
			$player = $event->getPlayer();
 			if($player->isSurvival() && ($block->getID() == 17 || $block->getID() == 162) && strtolower($block->getLevel()->getFolderName()) == "world" && $block->x >= 996 && $block->x <= 1004 && $block->z >= 996 && $block->z <= 1004){
 				$event->setCancelled();
				$player->getInventory()->addItem(new \pocketmine\item\Item($block->getID(), $block->getDamage(), 1));
			}
		}
	}

	public function loadYml(){
		@mkdir($this->path = ($this->getDataFolder()));
		$this->data = (new Config($this->file = $this->path . "MineTree.yml", Config::YAML, ["On" => true]))->getAll();
	}

	public function saveYml(){
		$data = new Config($this->file, Config::YAML);
		$data->setAll($this->data);
		$data->save();
	}

	public function isKorean(){
		return $this->getServer()->getLanguage()->getName() == "\"한국어\"";
	}
}