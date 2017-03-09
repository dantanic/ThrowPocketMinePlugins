<?php

namespace MineBlock\BanCraft;

use pocketmine\Player;
use pocketmine\item\Item;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat as Color;

class BanCraft extends \pocketmine\plugin\PluginBase implements \pocketmine\event\Listener{
	public function onEnable(){
		$this->loadYml();
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
	}

	public function onCommand(\pocketmine\command\CommandSender $sender, \pocketmine\command\Command $cmd, $label, array $sub){
		if(!isset($sub[0])) return false;
		$bc = $this->bc;
		$ik = $this->isKorean();
		switch(strtolower($sub[0])){
			case "add":
			case "a":
			case "추가":
				if(!isset($sub[1])){
					$r = Color::RED . "Usage: /BanCraft Add(A) " . ($ik ? "<아이템ID>" : "<ItemID>");
				}else{
					$i = Item::fromString($sub[1]);
					if($i->getID() == 0 && $sub[1] !== 0){
						$r = $sub[1] ($ik ? "는 잘못된 아이템ID입니다.." : " is invalid ItemID");
					}else{
						$id = $i->getID() . ":" . $i->getDamage();
						$bc[] = $id;
						$r = Color::YELLOW . "[BanCraft] " . ($ik ? "추가됨 : " : "Add") . " $id";
					}
				}
			break;
			case "del":
			case "d":
			case "제거":
			case "삭제":
				if(!isset($sub[1])){
					$r = Color::RED . "Usage: /BanCraft Del(D)" . ($ik ? "<블럭ID>" : "<BlockID>");
				}else{
					$i = Item::fromString($sub[1]);
					if($i->getID() == 0 && $sub[1] !== 0){
						$r = Color::RED . "[BanCraft] " . $sub[1] . " " . ($ik ? "는 잘못된 블럭ID입니다.." : "is invalid BlockID");
					}else{
						$id = $i->getID() . ":" . $i->getDamage();
						if(!in_array($id, $bc)){
							$r = "Color::RED . "[BanCraft] " $id" . ($ik ? "는 목록에 존재하지 않습니다..\n   Color::RED . "Usage: /BanCraft " 목록 " : "is does not exist in list.\n   Color::RED . "Usage: /BanCraft " List(L)");
						}else{
							foreach($bc as $k => $v){
								if($v == $id) unset($bc[$k]);
							}
							$r = Color::YELLOW . "[BanCraft] " . ($ik ? "제거됨 : " : "Delete ") . " $id";
						}
					}
				}
			break;
			case "reset":
			case "r":
			case "리셋":
			case "초기화":
				$bc = [];
				$r = Color::YELLOW . "[BanCraft] " . ($ik ? " 리셋됨." : " Reset");
			break;
			case "list":
			case "l":
			case "목록":
			case "리스트":
				$page = 1;
				if(isset($sub[1]) && is_numeric($sub[1])) $page = max(floor($sub[1]), 1);
				$list = array_chunk($bc, 5, true);
				if($page >= ($c = count($list))) $page = $c;
				$r = Color::YELLOW . "[BanCraft] " . ($ik ? "조합금지 목록 (페이지" : "BanCraft List (Page") . " $page/$c) \n";
				$num = ($page - 1) * 5;
				if($c > 0){
					foreach($list[$page - 1] as $k => $v){
						$num++;
						$r .= Color::YELLOW . "  [$num] $v\n";
					}
				}
			break;
			default:
				return false;
			break;
		}
		if(isset($r)) $sender->sendMessage($r);
		if($this->bc !== $bc){
			$this->bc = $bc;
			$this->saveYml();
		}
		return true;
	}

	public function onCraftItem(\pocketmine\event\inventory\CraftItemEvent $event){
		$transaction = $event->getTransaction();
		if(($player = $transaction->getSource()) instanceof Player){
			$result = $transaction->getResult();
			if(!$player->hasPermission("bancraft.craft") && in_array($id = $result->getID() . ":" . $result->getDamage(), $this->bc)){
				$player->sendMessage(Color::RED . "[BanCraft] $id" . ($this->isKorean() ? "는 조합금지 아이템입니다. 조합할수없습니다." : " is Ban. You can't craft."));
				$player->getInventory()->sendContents($player);
				$event->setCancelled();
			}
		}
	}

	public function loadYml(){
		@mkdir($this->getDataFolder());
		$this->bc = (new Config($this->getDataFolder() . "BanCraft.yml", Config::YAML))->getAll();
	}

	public function saveYml(){
		sort($this->bc);
		$bc = new Config($this->getDataFolder() . "BanCraft.yml", Config::YAML);
		$bc->setAll($this->bc);
		$bc->save();
	}

	public function isKorean(){
		return strtolower($this->getServer()->getLanguage()->getName()) == "korean";
	}
}