<?php

namespace MineBlock2;

use pocketmine\block\Block;
use pocketmine\item\Item;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat as Color;

class MineBlock2 extends \pocketmine\plugin\PluginBase implements \pocketmine\event\Listener{
	const ID = 0;
	const COUNT = 1;
	const PERCENT = 2;

	public function onEnable(){
		$this->loadYml();
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
	}

	public function onCommand(\pocketmine\command\CommandSender $sender, \pocketmine\command\Command $cmd, $label, array $sub){
 		if(!isset($sub[0])) return false;
		$mb = $this->mb;
		$set = $mb["Set"];
		$drop = $mb["Drop"];
		$ik = $this->isKorean();
		switch(strtolower($sub[0])){
			case "mine":
			case "m":
			case "on":
			case "off":
			case "광물":
			case "광물블럭":
			case "온":
			case "오프":
				if($set["Mine"] == "On"){
					$set["Mine"] = "Off";
					$r = Color::YELLOW . "[MineBlock] " . ($ik ? "마인블럭을  끕니다.": "MineBlock is Off");
				}else{
					$set["Mine"] = "On";
					$r = Color::YELLOW . "[MineBlock] " . ($ik ? "마인블럭을 켭니다.": "MineBlock is On");
				}
			break;
			case "regen":
			case "r":
			case "리젠":
			case "소생":
				if($set["Regen"] == "On"){
					$set["Regen"] = "Off";
					$r = Color::YELLOW . "[MineBlock] " . ($ik ? "블럭리젠을  끕니다.": "Regen is Off");
				}else{
					$set["Regen"] = "On";
					$r = Color::YELLOW . "[MineBlock] " . ($ik ? "블럭리젠을 켭니다.": "Regen is On");
				}
			break;
			case "block":
			case "b":
			case "블럭":
			case "광물":
				if(!isset($sub[1])){
					$r = Color::RED . "Usage: /MineBlock Block(B) " . ($ik ? "<블럭ID>": "<BlockID>");
				}else{
					$i = Item::fromString($sub[1]);
					$i = $i->getID() . ":" . $i->getDamage();
					$set["Block"] = $i;
					$r = Color::YELLOW . "[MineBlock] " . ($ik ? "블럭을 [$i] 로 설정했습니다.": "Block is set [$i]");
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
					$r = Color::RED . "Usage: /MineBlock Delay(D) " . ($ik ? "<시간>": "<Num>");
				}else{
					if($sub[1] < 0 || !is_numeric($sub[1])) $sub[1] = 0;
					if(isset($sub[2]) && $sub[2] > $sub[1] && is_numeric($sub[2]) !== false) $sub[1] = $sub[1] . "~" . $sub[2];
					$set["Time"] = $sub[1];
					$r = Color::YELLOW . "[MineBlock] " . ($ik ? "블럭리젠 딜레이를 [$sub[1]] 로 설정했습니다.": "Block Regen Delay is set [$sub[1]]");
				}
			break;
			case "count":
			case "c":
			case "갯수":
			case "횟수":
				if(!isset($sub[1])){
					$r = Color::YELLOW . "[MineBlock] Count(C) " . ($ik ? Color::RED . "Usage: /MineBlock " . "<횟수>": Color::RED . "Usage: /MineBlock " . "<Num>");
				}else{
					if($sub[1] < 1 || !is_numeric($sub[1])) $sub[1] = 1;
					if(isset($sub[2]) && $sub[2] > $sub[1] && is_numeric($sub[2]) !== false) $sub[1] = $sub[1] . "~" . $sub[2];
					$set["Count"] = $sub[1];
					$r = Color::YELLOW . "[MineBlock] " . ($ik ? "드랍 횟수를 [$sub[1]] 로 설정했습니다.": "Drop count is set [$sub[1]]");
				}
			break;
			case "issafe":
			case "is":
				$set["IsSafe"] = !$set["IsSafe"];
				$r = Color::YELLOW . "[MineBlock] " . ($ik ? "블럭보호를 " . ($set["IsSafe"] ? "적용합니다" : "무시합니다") : ($set["IsSafe"] ? "Apply" : "Ignore") . "block protect");
			break;
			case "isdrop":
			case "id":
				$set["IsDrop"] = !$set["IsDrop"];
				$r = Color::YELLOW . "[MineBlock] " . ($ik ? "아이템을 " . ($set["IsDrop"] ? "드롭합니다" : "즉시획득합니다") : "Item is " . ($set["IsDrop"] ? "drop" : "give to inventory"));
			break;
			case "drop":
			case "drops":
			case "dr":
			case "드롭":
			case "드롭템":
			case "드랍":
			case "드랍템":
				if(!isset($sub[1])){
					$r = Color::RED . "Usage: /MineBlock Drop(Dr) <Add|Del|Reset|List>";
				}else{
					switch(strtolower($sub[1])){
						case "add":
						case "a":
						case "추가":
							if(!isset($sub[2]) || !isset($sub[3])){
								$r = Color::RED . "Usage: /MineBlock Drop(Dr) Add(A) " . ($ik ? "<아이템ID> <확률> <갯수1> <갯수2>" : "<ItemID> <Petsent> <Count1> <Count2>");
							}else{
								$item = Item::fromString($sub[2]);
								if($sub[3] < 1 || !is_numeric($sub[3])) $sub[3] = 1;
								if(!isset($sub[4]) < 0 || !is_numeric($sub[4])) $sub[4] = 0;
								if(isset($sub[5]) && $sub[5] > $sub[4] && is_numeric($sub[5])) $sub[4] = $sub[4] . "~" . $sub[5];
								$drop[] = [$item->getID() . ":" . $item->getDamage(), $sub[4], $sub[3]];
								$r = Color::YELLOW . "[MineBlock] " . ($ik ? "드롭템 추가됨 [" . $item->getID() . ":" . $item->getDamage() . " 갯수:$sub[4] 확률:$sub[3]]": "Drops add [" . $item->getID() . ":" . $item->getDamage() . " Count:$sub[4] Persent:$sub[3]]");
							}
						break;
						case "del":
						case "d":
						case "삭제":
						case "제거":
							if(!isset($sub[2])){
								$r = Color::RED . "Usage: /MineBlock Drop(Dr) Del(D) " . ($ik ? "<번호>": "<Num>");
							}else{
								if($sub[2] < 0 || !is_numeric($sub[2])) $sub[2] = 0;
								if(!isset($drop[$sub[2] - 1])){
									$r = Color::YELLOW . "[MineBlock] " . ($ik ? "[$sub[2]] 는 존재하지않습니다. \n  " . Color::RED . "Usage: /MineBlock " . "드롭템 목록 ": "[$sub[2]] does not exist.\n  " . Color::RED . "Usage: /MineBlock " . "Drops(Dr) List(L)");
								}else{
									$d = $drop[$sub[2] - 1];
									unset($drop[$sub[2] - 1]);
									$r = Color::YELLOW . "[MineBlock] " . ($ik ? "드롭템 제거됨 [" . $d[self::ID] . " 갯수:" . $d[self::COUNT] . " 확률:" . $d[self::PERCENT] . "]": "Drop deleted [" . $d[self::ID] . " Count:" . $d[self::COUNT] . " Percent:" . $d[self::PERCENT] . "]");
								}
							}
						break;
						case "reset":
						case "r":
						case "리셋":
						case "초기화":
							$drop = [];
							$r = Color::YELLOW . "[MineBlock] " . ($ik ? "드롭템 목록을 초기화합니다.": "Drop list is Reset");
						break;
						case "list":
						case "l":
						case "목록":
						case "리스트":
							$lists = array_chunk($mb["Drop"], 5);
							$page = min(isset($sub[2]) && is_numeric($sub[2]) && isset($lists[$sub[2] - 1]) ? $sub[2] : 1, count($lists));
							$r = Color::YELLOW . "[MineBlock] " . ($ik ? "드롭 목록 (페이지: " : "Drop list (Page: ") . $page . "/" . count($lists) . ") (" . count($mb) . ")";
							if(isset($lists[$page - 1])) foreach($lists[$page - 1] as $key => $info) $r .= "\n" . Color::GOLD . "    [" . (($page - 1) * 5 + $key + 1) .  "] ID:" . $info[self::ID] . " Count:" . $info[self::COUNT] . " Percent:" . $info[self::PERCENT];
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
		if($mb["Set"] !== $set || $mb["Drop"] !== $drop){
			$this->mb = ["Set" => $set, "Drop" => $drop];
			if($mb["Drop"] !== $drop) $this->resettingDrops();
			$this->saveYml();
		}
		return true;
	}

	public function onBlockBreak(\pocketmine\event\block\BlockBreakEvent $event){
		if(count($this->drops) > 0 && (!$event->isCancelled() || !$this->mb["Set"]["IsSafe"]) && $this->mb["Set"]["Mine"] == "On"){
			$block = $event->getBlock();
			$oreBlock = Item::fromString($this->mb["Set"]["Block"]);
			if($oreBlock->getID() == $block->getID() && $oreBlock->getDamage() == $block->getDamage()){
				$block->onBreak($item = $event->getItem());
				$count = explode("~", $this->mb["Set"]["Count"]);
				$player = $event->getPlayer();
				if(!$player->isCreative() && $item->isPickaxe()) $player->getInventory()->setItemInHand($item->getDamage() + 2 < $item->getMaxDurability() ? Item::get($item->getID(), $item->getDamage() + 2, 1) : Item::get(0, 0, 0));
				for($for = 0; $for < rand($count[0], isset($cnt[1]) ? $count[1] : $count[0]); $for++){
					shuffle($this->drops);
					$d = $this->drops[0];
					$dc = explode("~", $d[self::COUNT]);
					if($this->mb["Set"]["IsDrop"]) $block->getLevel()->dropItem($block->add(0.5, 0.25, 0.5), $this->getItem($d[self::ID], rand($dc[0], isset($dc[1]) ? $dc[1] : $dc[0])));
					else $player->getInventory()->addItem($this->getItem($d[self::ID], rand($dc[0], isset($dc[1]) ? $dc[1] : $dc[0])));
				}
				if($this->mb["Set"]["Regen"] == "On"){
					$t = explode("~", $this->mb["Set"]["Time"]);
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
		$this->mb = (new Config($this->file = $this->path . "MineBlock.yml", Config::YAML, [
			"Set" => [
				"Mine" => "On",
				"Block" => "48:0",
				"Regen" => "On",
				"Time" => "3~5",
				"Count" => "1",
				"IsDrop" => true,
				"IsSafe" => true
			],
			"Drop" => is_file($this->file) ? [] : [
				["4:0", 1, 700],
				["263:0", "1~3", 70],
				["15:0", 1, 50],
				["331:0", "1~7", 20],
				["14:0", 1, 15],
				["351:4", "1~7", 5],
				["388:0", 1, 3],
				["264:0", 1, 1]
			]
		]))->getAll();
		$this->resettingDrops();
	}

	public function resettingDrops(){
		$this->drops = [];
		foreach($this->mb["Drop"] as $info){
			for($for = 0; $for < $info[self::PERCENT]; $for++) $this->drops[] = $info;
		}
	}

	public function saveYml(){
		$mb = new Config($this->file, Config::YAML);
		$mb->setAll($this->mb);
		$mb->save();
	}

	public function isKorean(){
		return $this->getServer()->getLanguage()->getName() == "\"한국어\"";
	}
}