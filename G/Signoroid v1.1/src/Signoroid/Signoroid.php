<?php

namespace Signoroid;

use pocketmine\block\Block;
use pocketmine\Player;
use pocketmine\level\Position;
use pocketmine\utils\TextFormat as Color;
use pocketmine\network\protocol\UpdateBlockPacket;

class Signoroid extends \pocketmine\plugin\PluginBase implements \pocketmine\event\Listener{
	private $touch = [];
	private $place = [];
	private $rotate = 0;

	const ANDROID = 0;
	const ROTATE = 1;

	static $modes = [
		self::ANDROID => [
			"android",
			"a",
			"npc",
			"안드로이드",
			"엔피시"
		],
		self::ROTATE => [
			"rotate",
			"r",
			"회전",
		]
	];

	public function onEnable(){
		$this->loadData();
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
		$this->getServer()->getScheduler()->scheduleRepeatingTask(new Task($this, [$this,"onSchedule"]), 2);
	}
	
	public function onCommand(\pocketmine\command\CommandSender $sender, \pocketmine\command\Command $cmd, $label, array $sub){
		if(!isset($sub[0])){
			return false;
		}else{
			$ik = $this->isKorean();
			switch(strtolower($sub[0])){
				case "add":
				case "a":
				case "추가":
					if(isset($this->touch[$name = $sender->getName()])){
						$r = Color::YELLOW . "[Signoroid] " . ($ik ? "싸이노로이드 편집모드 해제" : "Signolroid Edit Mode Disable");
						unset($this->touch[$name]);
					}elseif(!isset($sub[1])){
						$r = Color::RED .  "Usage: /Signoroid Add(A) " . ($ik ? "<안드로이드|회전>" : "<Android|Rotate>");
					}else{
						if(!in_array(strtolower($sub[1]), self::$modes[$mode = self::ANDROID]) && !in_array(strtolower($sub[1]), self::$modes[$mode = self::ROTATE])){
							$r = Color::RED . "[Signoroid] " . "$sub[1] " . ($ik ? "는 잘못된 모드입니다. (안드로이드/회전)" : "is invalid Mode (Android/Rotate)");
						}else{
							$r = Color::YELLOW . "[Signoroid] " . ($ik ? "대상 표지판을 터치해주세요." : "Touch the target sign");
							$this->touch[$sender->getName()] = ["Type" => "Add", "Mode" => $mode];
						}
					}
				break;
				case "del":	
				case "d":
				case "삭제":
				case "제거":
					if(isset($this->touch[$name = $sender->getName()])){
						$r = Color::YELLOW . "[Signoroid] " . ($ik ? "싸이노로이드 편집모드 해제" : "Signolroid Edit Mode Disable");
						unset($this->touch[$name]);
	 				}elseif(isset($sub[1]) && strtolower($sub[1]) == "mode"){
						$r = Color::YELLOW . "[Signoroid] " . ($ik ? "[제거모드] 제거할 표지판을 터치해주세요." : "[DelMode] Touch the target sign");
						$this->touch[$name] = ["Type" => "DelMode"];					
					}else{
						$r = Color::YELLOW . "[Signoroid] " . ($ik ? "제거할 상점을 터치해주세요. " : "Touch the target shop");
						$this->touch[$name] = ["Type" => "Del"];
					}
				break;
				case "change":
				case "c":
				case "바꾸기":
					if(isset($this->touch[$name = $sender->getName()])){
						$r = Color::YELLOW . "[Signoroid] " . ($ik ? "싸이노로이드 편집모드 해제" : "Signolroid Edit Mode Disable");
						unset($this->touch[$name]);
					}elseif(!isset($sub[1])){
						$r = Color::RED .  "Usage: /Signoroid Change(C) " . ($ik ? "<안드로이드|회전>" : "<Android|Rotate>");
					}else{
						if(!in_array(strtolower($sub[1]), self::$modes[$mode = self::ANDROID]) && !in_array(strtolower($sub[1]), self::$modes[$mode = self::ROTATE])){
							$r = Color::RED . "[Signoroid] " . "$sub[1] " . ($ik ? "는 잘못된 모드입니다. (안드로이드/회전)" : "is invalid Mode (Android/Rotate)");
						}else{
							$r = Color::YELLOW . "[Signoroid] " . ($ik ? "대상 표지판을 터치해주세요." : "Touch the target sign");
							$this->touch[$sender->getName()] = ["Type" => "Change", "Mode" => $mode];
						}
					}
				break;
				case "reset":
				case "r":
				case "리셋":
				case "초기화":
					foreach($this->data as $pos => $item){
						$this->removeSignoroid($pos);
					}
					$this->data= [];
					$r = Color::YELLOW . "[Signoroid] " . ($ik ? " 리셋됨." : " Reset");
				break;
				default:
					return false;
				break;
			}
			if(isset($r)){
				$sender->sendMessage($r);
			}
			$this->saveData();
			return true;
		}
	}

	public function onSchedule(){
		if(++$this->rotate > 360){
			$this->rotate = 0;
		}
		foreach($this->data as $posStr => $mode){
			$pos = explode(":", $posStr);
			if(($level = $this->getServer()->getLevelByName($pos[3])) == false){
				$this->removeSignoroid($posStr);
			}else{
				$block = $level->getBlock($vec = new \pocketmine\math\Vector3($pos[0], $pos[1], $pos[2]));
				if($block->getID() !== 0x3f){
					if($level->getChunk($pos[0] >> 4, $pos[2] >> 4)->isLoaded()){
 						$this->removeSignoroid($posStr);
 					}
				}else{
					$pk = new UpdateBlockPacket();
					$pk->records = [[$block->x, $block->z, $block->y, $block->getId(), $block->getDamage(), UpdateBlockPacket::FLAG_NONE]];
					switch($mode){
						case self::ANDROID:
							foreach($level->getChunkPlayers($block->x >> 4, $block->z >> 4) as $player){
								$pk->records[0][4] = floor((atan2(-$block->x - 0.5 + $player->x, -$block->z - 0.5 + $player->z) / M_PI * - 8));
								$player->dataPacket($pk);
							}
						break;
						case self::ROTATE:
							$pk->records[0][4] = $pk->records[0][4] + $this->rotate;
							$this->getServer()->broadcastPacket($level->getChunkPlayers($block->x >> 4, $block->z >> 4), $pk);
						break;
					}
				}
			}
		}
	}

	public function onBlockPlace(\pocketmine\event\block\BlockPlaceEvent $event){
		if(isset($this->data[$this->getPos($event->getBlock())])){
			$event->setCancelled();
		}
		if(isset($this->place[$name = $event->getPlayer()->getName()])){
			$event->setCancelled();
			unset($this->place[$name]);
 		}
	}

	public function onPlayerInteract(\pocketmine\event\player\PlayerInteractEvent $event){
		$player = $event->getPlayer();
		if(isset($this->touch[$name = $player->getName()])){
			$ik = $this->isKorean();
			$pos = $this->getPos($block = $event->getBlock());
			if($block->getID() !== 0x3f){
				$player->sendMessage(Color::RED . "[Signoroid] " . ($ik ? "이 블럭은 표지판이 아닙니다." : "This block is not sing block"));
			}else{
				switch($this->touch[$name]["Type"]){
					case "Add":
						$this->addSignoroid($pos, $this->touch[$name]["Mode"]);
						$player->sendMessage(Color::YELLOW . "[Signoroid] " . ($ik ? "상점이 생성되었습니다." : "Signoroid Create"));
						unset($this->touch[$name]);
					break;
					case "Change":
						if(!isset($this->data[$pos])){
							$player->sendMessage(Color::RED . "[Signoroid] " . ($ik ? "이곳에는 상점이 없습니다." : "Signoroid is not exist here"));
						}else{
							$this->removeSignoroid($pos);
							$this->addSignoroid($pos, $this->touch[$name]["Mode"]);
							$player->sendMessage(Color::YELLOW . "[Signoroid] " . ($ik ? "상점이 변경되었습니다." : "Signoroid Changed"));
							unset($this->touch[$name]);
						}
					break;
					case "Del":
						if(!isset($this->data[$pos])){
							$player->sendMessage(Color::RED . "[Signoroid] " . ($ik ? "이곳에는 상점이 없습니다." : "Signoroid is not exist here"));
						}else{
							$this->removeSignoroid($pos);
							$player->sendMessage(Color::YELLOW . "[Signoroid] " . ($ik ? "상점이 제거되었습니다." : "Signoroid is Delete"));
							unset($this->touch[$name]);
						}
					break;
					case "DelMode":
						if(!isset($this->data[$pos])){
							$player->sendMessage(Color::RED . "[Signoroid] " . ($ik ? "이곳에는 상점이 없습니다." : "Signoroid is not exist here"));
						}else{
							$this->removeSignoroid($pos);
							$player->sendMessage(Color::YELLOW . "[Signoroid] " . ($ik ? "[제거모드] 상점이 제거되었습니다." : "[DelMode] Signoroid is Delete"));
						}
					break;
				}
				$event->setCancelled();
				if($event->getItem()->isPlaceable()){
					$this->place[$name] = true;
				}
			}
		}
	}

	public function addSignoroid($pos, $mode){
		if(!isset($this->data[$pos])){
			$this->data[$pos] = $mode;
			$this->saveData();
		}
	}

	public function removeSignoroid($pos){
 		unset($this->data[$pos]);
		$pos = explode(":", $pos);
		if(($level = $this->getServer()->getLevelByName($pos[3])) != false){
			$block = $level->getBlock(new \pocketmine\math\Vector3($pos[0], $pos[1], $pos[2]));
			$level->sendBlocks($level->getChunkPlayers($block->x >> 4, $block->z >> 4), [$block], UpdateBlockPacket::FLAG_ALL_PRIORITY);
		}
		$this->saveData();
	}

	public function getPos($block){
		return $block->x . ":" . $block->y . ":" . $block->z . ":" . $block->getLevel()->getFolderName();
	}

	public function loadData(){
		@mkdir($folder = $this->getDataFolder());
		if(!file_exists($folder . "Signs.sl")){	
			file_put_contents($folder . "Signs.sl", serialize([]));
		}
		$this->data = unserialize(file_get_contents($folder . "Signs.sl"));
	}

	public function saveData(){
		@mkdir($folder = $this->getDataFolder());
		file_put_contents($folder . "Signs.sl", serialize($this->data));
	}

	public function isKorean(){
		return $this->getServer()->getLanguage()->getName() == "\"한국어\"";
	}
}