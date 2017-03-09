<?php

namespace OreBlock;

use pocketmine\block\Block;
use pocketmine\item\Item;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat as Color;

class OreBlock extends \pocketmine\plugin\PluginBase implements \pocketmine\event\Listener{
	public function onEnable(){
		$this->loadYml();
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
	}

	public function onCommand(\pocketmine\command\CommandSender $sender, \pocketmine\command\Command $cmd, $label, array $sub){
 		if(!isset($sub[0])) return false;
		$ob = $this->ob;
		$set = $ob["Set"];
		$drop = $ob["Drop"];
		$ik = $this->isKorean();
		switch(strtolower($sub[0])){
			case "ore":
			case "o":
			case "on":
			case "off":
			case "광물":
			case "광물블럭":
			case "온":
			case "오프":
				if($set["Mine"] == "On"){
					$set["Mine"] = "Off";
					$r = Color::YELLOW . "[OreBlock] " . ($ik ? "마인블럭을  끕니다.": "OreBlock is Off");
				}else{
					$set["Mine"] = "On";
					$r = Color::YELLOW . "[OreBlock] " . ($ik ? "마인블럭을 켭니다.": "OreBlock is On");
				}
			break;
			case "regen":
			case "r":
			case "리젠":
			case "소생":
				if($set["Regen"] == "On"){
					$set["Regen"] = "Off";
					$r = Color::YELLOW . "[OreBlock] " . ($ik ? "블럭리젠을  끕니다.": "Regen is Off");
				}else{
					$set["Regen"] = "On";
					$r = Color::YELLOW . "[OreBlock] " . ($ik ? "블럭리젠을 켭니다.": "Regen is On");
				}
			break;
			case "block":
			case "b":
			case "블럭":
			case "광물":
				if(!isset($sub[1])){
					$r = Color::RED . "Usage: /OreBlock Block(B) " . ($ik ? "<블럭ID>": "<BlockID>");
				}else{
					$i = Item::fromString($sub[1]);
					$i = $i->getID() . ":" . $i->getDamage();
					$set["Block"] = $i;
					$r = Color::YELLOW . "[OreBlock] " . ($ik ? "블럭을 [$i] 로 설정했습니다.": "Block is set [$i]");
				}
			break;
			case "delay":
			case "d":
			case "time":
			case "t":
			case "딜레이":
			case "시간":
			case "타임":
				if(!isset($sub[1])){
					$r = Color::RED . "Usage: /OreBlock Delay(D) " . ($ik ? "<시간>": "<Num>");
				}else{
					if($sub[1] < 0 || !is_numeric($sub[1])) $sub[1] = 0;
					if(isset($sub[2]) && $sub[2] > $sub[1] && is_numeric($sub[2]) !== false) $sub[1] = $sub[1] . "~" . $sub[2];
					$set["Time"] = $sub[1];
					$r = Color::YELLOW . "[OreBlock] " . ($ik ? "블럭리젠 딜레이를 [$sub[1]] 로 설정했습니다.": "Block Regen Delay is set [$sub[1]]");
				}
			break;
			case "count":
			case "c":
			case "갯수":
			case "횟수":
				if(!isset($sub[1])){
					$r = Color::YELLOW . "[OreBlock] Count(C) " . ($ik ? Color::RED . "Usage: /OreBlock " . "<횟수>": Color::RED . "Usage: /OreBlock " . "<Num>");
				}else{
					if($sub[1] < 1 || !is_numeric($sub[1])) $sub[1] = 1;
					if(isset($sub[2]) && $sub[2] > $sub[1] && is_numeric($sub[2]) !== false) $sub[1] = $sub[1] . "~" . $sub[2];
					$set["Count"] = $sub[1];
					$r = Color::YELLOW . "[OreBlock] " . ($ik ? "드랍 횟수를 [$sub[1]] 로 설정했습니다.": "Drop count is set [$sub[1]]");
				}
			break;
			case "issafe":
			case "is":
				$set["IsSafe"] = !$set["IsSafe"];
				$r = Color::YELLOW . "[OreBlock] " . ($ik ? "블럭보호를 " . ($set["IsSafe"] ? "적용합니다" : "무시합니다") : ($set["IsSafe"] ? "Apply" : "Ignore") . "block protect");
			break;
			case "isdrop":
			case "id":
				$set["IsDrop"] = !$set["IsDrop"];
				$r = Color::YELLOW . "[OreBlock] " . ($ik ? "아이템을 " . ($set["IsDrop"] ? "드롭합니다" : "즉시획득합니다") : "Item is " . ($set["IsDrop"] ? "drop" : "give to inventory"));
			break;
			case "drop":
			case "drops":
			case "dr":
			case "드롭":
			case "드롭템":
			case "드랍":
			case "드랍템":
				if(!isset($sub[1])){
					$r = Color::RED . "Usage: /OreBlock Drop(Dr) <Add|Del|Reset|List>";
				}else{
					switch(strtolower($sub[1])){
						case "add":
						case "a":
						case "추가":
							if(!isset($sub[2]) || !isset($sub[3])){
								$r = Color::RED . "Usage: /OreBlock Drop(Dr) Add(A) " . ($ik ? "<아이템ID> <확률> <갯수1> <갯수2>" : "<ItemID> <Petsent> <Count1> <Count2>");
							}else{
								$i = Item::fromString($sub[2]);
								if($sub[3] < 1 || !is_numeric($sub[3])) $sub[3] = 1;
								if(!isset($sub[4]) < 0 || !is_numeric($sub[4])) $sub[4] = 0;
								if(isset($sub[5]) && $sub[5] > $sub[4] && is_numeric($sub[5])) $sub[4] = $sub[4] . "~" . $sub[5];
								$drop[] = $sub[3]." % ".$i->getID() . ":" . $i->getDamage()." % $sub[4]";
								$r = Color::YELLOW . "[OreBlock] " . ($ik ? "드롭템 추가됨 [" . $i->getID() . ":" . $i->getDamage() . " 갯수:$sub[4] 확률:$sub[3]]": "Drops add [" . $i->getID() . ":" . $i->getDamage() . " Count:$sub[4] Persent:$sub[3]]");
							}
						break;
						case "del":
						case "d":
						case "삭제":
						case "제거":
							if(!isset($sub[2])){
								$r = Color::RED . "Usage: /OreBlock Drop(Dr) Del(D) " . ($ik ? "<번호>": "<Num>");
							}else{
								if($sub[2] < 0 || !is_numeric($sub[2])) $sub[2] = 0;
								if(!isset($drop[$sub[2] - 1])){
									$r = Color::YELLOW . "[OreBlock] " . ($ik ? "[$sub[2]] 는 존재하지않습니다. \n  " . Color::RED . "Usage: /OreBlock " . "드롭템 목록 ": "[$sub[2]] does not exist.\n  " . Color::RED . "Usage: /OreBlock " . "Drops(Dr) List(L)");
								}else{
									$d = $fish[$sub[2] - 1];
									unset($fish[$sub[2] - 1]);
									$r = Color::YELLOW . "[OreBlock] " . ($ik ? "드롭템 제거됨 [" . $d[1] . ":" . $i->getDamage() . " 갯수:" . $d[2] . " 확률:" . $d[0] . "]": "Fish del [" . $d[1] . ":" . $i->getDamage() . " Count:" . $d[2] . " Persent:" . $d[0] . "]");
								}
							}
						break;
						case "reset":
						case "r":
						case "리셋":
						case "초기화":
							$drop = [];
							$r = Color::YELLOW . "[OreBlock] " . ($ik ? "드롭템 목록을 초기화합니다.": "Drop list is Reset");
						break;
						case "list":
						case "l":
						case "목록":
						case "리스트":
							$page = 1;
							if(isset($sub[2]) && is_numeric($sub[2])) $page = round($sub[2]);
							$list = ceil(count($drop) / 5);
							if($page >= $list) $page = $list;
							$r = Color::YELLOW . "[OreBlock] " . (ik ? "목록 (페이지 $page/$list) \n": "List (Page $page/$list) \n");
							$num = 0;
							foreach($drop as $k){
								$num++;
								if($num + 5 > $page * 5 && $num <= $page * 5) $r .= Color::YELLOW . ($ik ? "  [$num] 아이디:" . $k["ID"] . " 갯수:" . $k["Count"] . " 확률:" . $k["Percent"] . " \n": "  [$num] ID:" . $k["ID"] . " Count:" . $k["Count"] . " Percent:" . $k["Percent"] . " \n");
							}
						break;
						default:
							return false;
						break;
					}
				}
			break;
			default:
				return false;
			break;
		}
		if(isset($r)) $sender->sendMessage($r);
		if($ob["Set"] !== $set || $ob["Drop"] !== $drop){
			$this->ob = ["Set" => $set, "Drop" => $drop];
			$this->saveYml();
		}
		return true;
	}

	public function onBlockBreak(\pocketmine\event\block\BlockBreakEvent $event){
		if(count($this->drops) > 0 && (!$event->isCancelled() || !$this->ob["Set"]["IsSafe"]) && $this->ob["Set"]["Mine"] == "On"){
			$block = $event->getBlock();
			$oreBlock = Item::fromString($this->ob["Set"]["Block"]);
			if($oreBlock->getID() == $block->getID() && $oreBlock->getDamage() == $block->getDamage()){
				$block->onBreak($item = $event->getItem());
				$count = explode("~", $this->ob["Set"]["Count"]);
				$player = $event->getPlayer();
				if(!$player->isCreative() && $item->isPickaxe()) $player->getInventory()->setItemInHand($item->getDamage() + 2 < $item->getMaxDurability() ? Item::get($item->getID(), $item->getDamage() + 2, 1) : Item::get(0, 0, 0));
				for($for = 0; $for < rand($count[0], isset($cnt[1]) ? $count[1] : $count[0]); $for++){
					shuffle($this->drops);
					$d = $this->drops[0];
					$dc = explode("~", $d[2]);
					if($this->ob["Set"]["IsDrop"]) $block->getLevel()->dropItem($block->add(0.5, 0.25, 0.5), $this->getItem($d[1], rand($dc[0], isset($dc[1]) ? $dc[1] : $dc[0])));
					else $player->getInventory()->addItem($this->getItem($d[1], rand($dc[0], isset($dc[1]) ? $dc[1] : $dc[0])));
				}
				if($this->ob["Set"]["Regen"] == "On"){
					$t = explode("~", $this->ob["Set"]["Time"]);
					$this->getServer()->getScheduler()->scheduleDelayedTask(new Task($this, [$block->getLevel(),"setBlock"], [$block, $block, false]), rand($t[0], isset($t[1]) ? $t[1] : $t[0]) * 20);
				}
				$event->setCancelled();
			}
		}
	}

	public function getItem($id = 0, $cnt = 0){
		$id = explode(":", $id);
		return Item::get($id[0], isset($id[1]) ? $id[1] : 0, $cnt);
	}

	public function loadYml(){
		@mkdir($this->path = ($this->getDataFolder()));
		$this->ob = (new Config($this->file = $this->path . "OreBlock.yml", Config::YAML, [
			"Set" => [
				"Mine" => "On",
				"Block" => "48:0",
				"Regen" => "On",
				"Time" => "3~5",
				"Count" => "1~2",
				"IsDrop" => true,
				"IsSafe" => true
			],
			"Drop" => is_file($this->file) ? [] : [
				"700 % 4:0 % 1",
				"70 % 263 % 1~3",
				"50 % 15:0 % 1",
				"20 % 331:0 % 1~7",
				"15 % 14:0 % 1",
				"5 % 351:4 % 1~7",
				"3 % 388:0 % 1",
				"1 % 264:0 % 1"
			]
		]))->getAll();
		$this->drops = [];
		foreach($this->ob["Drop"] as $drop){
			$info = explode(" % ", $drop);
			for($for = 0; $for < $info[0]; $for++) $this->drops[] = $info;
		}
	}

	public function saveYml(){
		$ob = new Config($this->file, Config::YAML);
		$ob->setAll($this->ob);
		$ob->save();
	}

	public function isKorean(){
		return strtolower($this->getServer()->getLanguage()->getName()) == "korean";
	}
}