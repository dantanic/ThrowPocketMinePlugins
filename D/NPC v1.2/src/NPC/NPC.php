<?php

namespace NPC;

use pocketmine\block\Block;
use pocketmine\network\protocol\Info as ProtocolInfo;
use pocketmine\item\Item;
use pocketmine\entity\Entity;
use pocketmine\Player;
use pocketmine\level\Position;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat as Color;
use pocketmine\event\player\PlayerCommandPreprocessEvent;
use pocketmine\event\server\ServerCommandEvent;
use pocketmine\command\ConsoleCommandSender;

class NPC extends \pocketmine\plugin\PluginBase implements \pocketmine\event\Listener{
	const NAME = 0;
	const EID = 1;
	const HEAD = 2;
	const SLEEP = 3;
	const SNEAK = 4;
	const SIT = 5;
	const POSITION = 6;
		const X = 0;
		const Y = 1;
		const Z = 2;
		const LEVEL = 3;
	const ROTATION = 7;
		const YAW = 0;
		const PITCH = 1;
	const ARMOR = 8;
		const HELMET = 0;
		const CHESTPLATE = 1;
		const LEGGINGS = 2;
		const BOOTS = 3;
	const ITEM = 9;
	const SKIN = 10;
	const COMMAND = 11;

 	public function onLoad(){
		$this->armorTable = [Item::LEATHER_CAP => 0, Item::LEATHER_TUNIC => 1, Item::LEATHER_PANTS => 2, Item::LEATHER_BOOTS => 3, Item::CHAIN_HELMET => 0, Item::CHAIN_CHESTPLATE => 1, Item::CHAIN_LEGGINGS => 2, Item::CHAIN_BOOTS => 3, Item::GOLD_HELMET => 0, Item::GOLD_CHESTPLATE => 1, Item::GOLD_LEGGINGS => 2, Item::GOLD_BOOTS => 3, Item::IRON_HELMET => 0, Item::IRON_CHESTPLATE => 1, Item::IRON_LEGGINGS => 2, Item::IRON_BOOTS => 3, Item::DIAMOND_HELMET => 0, Item::DIAMOND_CHESTPLATE => 1, Item::DIAMOND_LEGGINGS => 2, Item::DIAMOND_BOOTS => 3];
		foreach($this->armorTable as $id => $slot){
			if(!Item::isCreativeItem($item = new Item($id))){
				Item::addCreativeItem($item);
			}
		}
		foreach([8, 10, 27, [38,1], [38,2], [38,3], [38,4], [38,5], [38,6], [38,7], [38,8], 51, 60, 62, 63, 66, 71, 92, 99, 111, 127, 175, [175,1], [175,2], [175,3], [175,4], [175,5], 246, 248, 249, 260, 262, 263, 264, 265, 266, 268, 269, 270, 271, 272, 273, 275, 275, 280, 281, 282, 283, 284, 285, 286, 287, 288, 289, 290, 291, 292, 294, 297, 318, 319, 320, 321, 329, 330, 331, 332, 334, 336, 337, 339, 340, 341, 344, 348, 352, 353, 357, 360, 363, 364, 365, 366, 388, 393, 400, 405, 406, 457, 459] as $id){
			if(!Item::isCreativeItem($item = new Item(is_array($id) ? $id[0] : $id, is_array($id) ? $id[1] : 0))){
				Item::addCreativeItem($item);
			}
		}
		$this->tap = [];
		$this->players = [];
		$this->log = [];
		$this->AddPlayerPacket = new \pocketmine\network\protocol\AddPlayerPacket();
		$this->AddPlayerPacket->slim = false;
		$this->AddPlayerPacket->metadata = [
			Entity::DATA_FLAGS => [Entity::DATA_TYPE_BYTE, 0 << Entity::DATA_FLAG_INVISIBLE],
			Entity::DATA_SHOW_NAMETAG => [Entity::DATA_TYPE_BYTE, 1],
			Entity::DATA_NO_AI => [Entity::DATA_TYPE_BYTE, 0]
		];
		$this->RemovePlayerPacket = new \pocketmine\network\protocol\RemovePlayerPacket();
		$this->MovePlayerPacket = new \pocketmine\network\protocol\MovePlayerPacket();
		$this->SetEntityLinkPacket = new \pocketmine\network\protocol\SetEntityLinkPacket();
		$this->SetEntityLinkPacket->type = 1;
	}

	public function onEnable(){
		$this->getServer()->getLogger()->info(Color::GREEN . "Find economy plugin...");
		$pluginManager = $this->getServer()->getPluginManager();
		if(!($this->money = $pluginManager->getPlugin("PocketMoney")) && !($this->money = $pluginManager->getPlugin("EconomyAPI")) && !($this->money = $pluginManager->getPlugin("MassiveEconomy")) && !($this->money = $pluginManager->getPlugin("Money"))) $this->getServer()->getLogger()->info(Color::RED . "Failed find economy plugin...");
		else $this->getServer()->getLogger()->info(Color::GREEN . "Finded economy plugin : " . $this->money->getName());
		$this->loadYml();
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
		$this->getServer()->getScheduler()->scheduleRepeatingTask(new Task($this, [$this,"onTick"]), 4);
	}
	
	public function onCommand(\pocketmine\command\CommandSender $sender, \pocketmine\command\Command $cmd, $label, array $sub){
		if(!isset($sub[0])) return false;
		$npc = $this->npc;
		$ik = $this->isKorean();
		$n = $sender->getName();
		switch(strtolower($sub[0])){
			case "add":
			case "a":
			case "추가":
				if(!$sender instanceof Player){
					$r = Color::RED . "[NPC] " . ($ik ? "게임내에서만 사용 가능합니다." : "Please run this command in game");
				}elseif($sub[1] == ""){
					$r = Color::RED . "Usage: /NPC Add(A) " . ($ik ? "<이름> <IP> (Port)" : "<Name> <IP> (Port)");
				}elseif(preg_match('#^[a-zA-Z0-9_]{3,16}$#', $sub[1]) == 0){
					$r = Color::RED . "[NPC] " . $sub[1] . ($ik ? "는 잘못된 이름입니다." : " is invalid name");
				}else{
					if(!isset($sub[2])){
						$this->addNpc($sender, $sub[1], "", 0, false);
						$r = Color::YELLOW . "[NPC] Add npc : $sub[1]";
					}elseif(!isset($sub[3])) $sub[3] = 19132;
					elseif(!is_numeric($sub[3])) $r = Color::RED . "Usage: /NPC Add(A) " . ($ik ? "<이름> <IP> (포트)" : "<Name> <IP> (Port)");
					elseif(isset($npc[strtolower($sub[1])])) $r = Color::RED . "[NPC] $sub[1]" . ($ik ? "는 이미 존재합니다." : " is already");
					else{
						if(preg_match("/^[0-9]{1,3}\\.[0-9]{1,3}\\.[0-9]{1,3}\\.[0-9]{1,3}$/", $sub[2]) > 0){
							$ip = $sub[2];
						}else{
						 	$ip = gethostbyname($sub[2] = strtolower($sub[2]));
						 	if($sub[2] == $ip){
						 		$sender->sendMessage(Color::RED . "[NPC] $sub[2] is invalid IP");
						 		return true;
							}
						}
						$this->addNpc($sender, $sub[1], $ip, $sub[3]);
						$r = Color::YELLOW . "[NPC] Add npc : $sub[1]";
					}
				}
			break;
			case "del":
			case "d":
			case "삭제":
			case "제거":
				if(!isset($sub[1]) || $sub[1] == "") $r = Color::RED . "Usage: /NPC Del(D) " . ($ik ? "<이름>" : "<Name>");
				elseif(!isset($npc[strtolower($sub[1])])) $r = Color::RED . "[NPC] $sub[1]" . ($ik ? "는 존재하지않습니다." : "is invaild name");
				else{
					$this->removeNpc($sub[1]);
					$r = Color::YELLOW . "[NPC] " . ($ik ? $sub[1] . "를 제거했습니다." : "Delete the $sub[1]");
				}
			break;
			case "reset":
			case "리셋":
			case "초기화":
				foreach($npc as $name => $npc) $this->removeNpc($name);
				$npc = [];
				$change = true;
				$r = Color::YELLOW . "[NPC] " . ($ik ? " 리셋됨." : " Reset");
			break;
			case "set":
			case "s":
			case "설정":
				if(!isset($sub[2]) || $sub[1] == "") $r = Color::RED . "Usage: /NPC Set(S) " . ($ik ? "<엔피시명>" : "<NpcName>") . " <Name(N) | Position(Pos) | Skin(S) | Armor(A) | Item(I) | Sleep(SL) | Sneak(SN) | Sit(SI) | Command(C)>";
				elseif(!isset($this->npc[$sub[1] = strtolower($sub[1])])) $r = Color::RED . "[NPC] $sub[1]" . ($ik ? "는 존재하지않습니다." : "is invaild name");
				else{
					switch(strtolower($sub[2])){
						case "name":
						case "n":
						case "이름":
							if(!isset($sub[3]) || $sub[3] == "") $r = Color::RED . "Usage: /NPC Name(N) " . ($ik ? "<엔피시명>" : "<NpcName>");
							else{
								$npc[$sub[1]][self::NAME] = $name = implode(" ", array_splice($sub,3));
								$r = Color::YELLOW . "[NPC] " . ($ik ? "엔피시의 이름을 $name" . Color::RESET . "으로 변경하였습니다." : "Npc name is set to $name");
							}
						break;
						case "skin":
						case "s":
						case "스킨":
							if(!$sender instanceof Player) $r = Color::RED . "[NPC] " . ($ik ? "게임내에서만 사용 가능합니다." : "Please run this command in game");
							else{
								$npc[$sub[1]][self::SKIN] = $this->skin2Str($sender->getSkinData());
								$r = Color::YELLOW . "[NPC] " . ($ik ? "엔피시의 스킨을 변경하였습니다." : "Npc's skin is changed");
							}
						break;
						case "position":
						case "pos":
						case "위치":
						case "좌표":
							if(!$sender instanceof Player) $r = Color::RED . "[NPC] " . ($ik ? "게임내에서만 사용 가능합니다." : "Please run this command in game");
							else{
								$npc[$sub[1]][self::POSITION][self::X] = $sender->x;
								$npc[$sub[1]][self::POSITION][self::Y] = $sender->y;
								$npc[$sub[1]][self::POSITION][self::Z] = $sender->z;
								$npc[$sub[1]][self::POSITION][self::LEVEL] = strtolower($sender->getLevel()->getFolderName());
								$npc[$sub[1]][self::ROTATION][self::YAW] = $sender->yaw;
								$npc[$sub[1]][self::ROTATION][self::PITCH] = $sender->pitch;
								$r = Color::YELLOW . "[NPC] " . ($ik ? "엔피시의 위치를 변경하였습니다." : "Npc's position is changed");
							}
						break;
						case "armor":
						case "a":
						case "갑롯":
						case "장비":
							if(!$sender instanceof Player) $r = Color::RED . "[NPC] " . ($ik ? "게임내에서만 사용 가능합니다." : "Please run this command in game");
							else{
								$inven = $sender->getInventory();
								$npc[$sub[1]][self::ARMOR][self::HELMET] = $inven->getHelmet()->getID();
								$npc[$sub[1]][self::ARMOR][self::CHESTPLATE] = $inven->getChestplate()->getID();
								$npc[$sub[1]][self::ARMOR][self::LEGGINGS] = $inven->getLeggings()->getID();
								$npc[$sub[1]][self::ARMOR][self::BOOTS] = $inven->getBoots()->getID();
								$r = Color::YELLOW . "[NPC] " . ($ik ? "엔피시의 갑옷을 변경하였습니다." : "Npc's armor is changed");
							}
						break;
						case "item":
						case "i":
						case "아이템":
							if(!$sender instanceof Player) $r = Color::RED . "[NPC] " . ($ik ? "게임내에서만 사용 가능합니다." : "Please run this command in game");
							else{
								$inven = $sender->getInventory();
								$npc[$sub[1]][self::ITEM] = $inven->getItemInHand()->getID() . ":" . $inven->getItemInHand()->getDamage();
								$r = Color::YELLOW . "[NPC] " . ($ik ? "엔피시의 아이템을 변경하였습니다." : "Npc's item is changed");
							}
						break;
						case "head":
						case "h":
						case "머리":
							$npc[$sub[1]][self::HEAD] = !$npc[$sub[1]][self::HEAD];
							$r = Color::YELLOW . "[NPC] " . ($ik ? "엔피시를 ".($npc[$sub[1]][self::HEAD] ? "고정" : "고정해제")."했습니다." : "Npc is ".($npc[$sub[1]][self::HEAD] ? "Force" : "Not force"));
						break;
						case "sleep":
						case "sl":
						case "잠":
							$npc[$sub[1]][self::SLEEP] = !$npc[$sub[1]][self::SLEEP];
							$r = Color::YELLOW . "[NPC] " . ($ik ? "엔피시를 ".($npc[$sub[1]][self::SLEEP] ? "재웠습니다" : "깨웠습니다") : "Npc is ".($npc[$sub[1]][self::SLEEP] ? "Sleep" : "Wake up"));
						break;
						case "sneak":
						case "sn":
						case "숙이기":
							$npc[$sub[1]][self::SNEAK] = !$npc[$sub[1]][self::SNEAK];
							$r = Color::YELLOW . "[NPC] " . ($ik ? "엔피시를 ".($npc[$sub[1]][self::SNEAK] ? "굽혔습니다" : "세웠습니다") : "Npc is ".($npc[$sub[1]][self::SNEAK] ? "Sneak" : "Stand up"));
						break;
/*
						case "sit":
						case "si":
						case "앉기":
							$npc[$sub[1]][self::SIT] = !$npc[$sub[1]][self::SIT];
							$r = Color::YELLOW . "[NPC] " . ($ik ? "엔피시를 ".($npc[$sub[1]][self::SIT] ? "앉혔습니다" : "세웠습니다") : "Npc is ".($npc[$sub[1]][self::SIT] ? "Sit" : "Stand up"));
						break;
*/
						case "command":
						case "c":
						case "명령어":
							switch(strtolower($sub[3])){
								case "add":
								case "a":
								case "추가":
									if(!isset($sub[4])){
										$r = Color::RED . "Usage: /NPC Set(S) Command(C) Add(A) " . ($ik ? "<명령어>" : "<Command>");
									}else{
										$npc[$sub[1]][self::COMMAND][] = ($command = implode(" ", array_splice($sub,4)));
										$r = Color::YELLOW . "[NPC] " . ($ik ? "명령어를 추가했습니다. : " : "Added Commmand. : ").$command;
									}
								break;
								case "del":
								case "d":
								case "제거":
									if(!isset($sub[4]) || !is_numeric($sub[4])){
										$r = Color::RED . "Usage: /NPC " . "Set(S) Command(C) Del(D) " . ($ik ? "<명령어ID>" : "<CommandID>"); 	
									}elseif(!isset($npc[$sub[1]][self::COMMAND][$sub[4]-1])){
										$r = Color::RED . "[NPC] " . ($ik ? "해당 명령어가 없거나 잘못된 아아디입니다." : "Invalid Command Id");
									}else{
										$commands = $npc[$sub[1]][self::COMMAND];
										unset($commands[$sub[4]-1]);
										$npc[$sub[1]][self::COMMAND] = [];
										foreach($commands as $command)	$npc[$sub[1]][self::COMMAND][] = $command;
										$r = Color::YELLOW . "[NPC] " . ($ik ? "명령어를 제거했습니다." : "Deleted Commmand.");
									}
								break;
							}
							if(!isset($r)){
								$sender->sendMessage(Color::RED . "Usage: /NPC Set(S) Command(C) <Add(A) | Del(D)>");
								return true;
							}
						break;	
					}
					if(!isset($r)){
						$sender->sendMessage(Color::RED . "Usage: /NPC Set(S) " . ($ik ? "<엔피시명>" : "<NpcName>") . " <Name(N) | Position(Pos) | Skin(S) | Armor(A) | Item(I) | Sleep(SL) | Sneak(SN) | Sit(SI) | Command(C)>");
						return true;
					}else{
						foreach($this->players[$sub[1]] as $player){
							$this->RemovePlayerPacket->eid = $this->RemovePlayerPacket->clientID = $this->npc[$sub[1]][self::EID];
							$player->dataPacket($this->RemovePlayerPacket);
						}
						$this->players[$sub[1]] = [];
					}
					$change = true;
				}
			break;
			case "respawn":
			case "리스폰":
			case "rs":
				foreach($npc as $name => $npc){
					foreach($this->players[$name] as $player){
						$this->RemovePlayerPacket->eid = $this->RemovePlayerPacket->clientID = $npc[self::EID];
						$player->dataPacket($this->RemovePlayerPacket);
 					}
					$this->players[$name] = [];
				}
				$r = Color::YELLOW . "[NPC] " . ($ik ? " 엔피시 리스폰됨." : " Spawn the Npcs");
			break;
			default:
				return false;
			break;
		}
		if(isset($r)) $sender->sendMessage($r);
		if($this->npc !== $npc && isset($change)){
			$this->npc = $npc;
			$this->saveYml();
		}
		return true;
	}

	public function onTick(){
		foreach($this->npc as $name => $npc){
			$players = isset($this->players[$name]) ? [] : $this->players[$name] = [];
			$PlayerArmorEquipmentPacket = new \pocketmine\network\protocol\PlayerArmorEquipmentPacket();
			$this->AddPlayerPacket->eid = $this->AddPlayerPacket->clientID = $this->RemovePlayerPacket->eid = $this->RemovePlayerPacket->clientID = $this->MovePlayerPacket->eid = $PlayerArmorEquipmentPacket->eid =	$this->SetEntityLinkPacket->rider = $this->SetEntityLinkPacket->ridden = $npc[self::EID];
			$this->AddPlayerPacket->username = str_replace("%n", "\n", $npc[self::NAME]);
			$this->AddPlayerPacket->yaw = $npc[self::ROTATION][self::YAW];
			$this->AddPlayerPacket->pitch = $npc[self::ROTATION][self::PITCH];
			$itemArr = explode(":", $npc[self::ITEM]);
			$this->AddPlayerPacket->item = $itemArr[0];
			$this->AddPlayerPacket->meta = $itemArr[1];
			$this->AddPlayerPacket->skin = $this->str2Skin($npc[self::SKIN]);
			$this->AddPlayerPacket->metadata[Player::DATA_PLAYER_FLAGS] = [Entity::DATA_TYPE_BYTE, $npc[self::SLEEP] << Player::DATA_PLAYER_FLAG_SLEEP];
			$this->AddPlayerPacket->metadata[Entity::DATA_FLAGS] = [Entity::DATA_TYPE_BYTE, $npc[self::SNEAK] << Entity::DATA_FLAG_SNEAKING];
			$this->AddPlayerPacket->x = $this->MovePlayerPacket->x = $x = $npc[self::POSITION][self::X];
			$this->AddPlayerPacket->y = $this->MovePlayerPacket->y = $y = $npc[self::POSITION][self::Y] + ($npc[self::SLEEP] ? 0.3 : ($npc[self::SNEAK] ? 1.42 : ($npc[self::SIT] ? -1 : 1.62)));
			$this->AddPlayerPacket->z = $this->MovePlayerPacket->z = $z = $npc[self::POSITION][self::Z];
			$this->MovePlayerPacket->mode = $npc[self::SLEEP];
			$PlayerArmorEquipmentPacket->slots = [$npc[self::ARMOR][self::HELMET], $npc[self::ARMOR][self::CHESTPLATE], $npc[self::ARMOR][self::LEGGINGS], $npc[self::ARMOR][self::BOOTS]];
			foreach($this->getServer()->getOnlinePlayers() as $player){
				if(!$player->spawned || $npc[self::POSITION][self::LEVEL] !== strtolower($player->getLevel()->getFolderName()) || ($distance = sqrt(pow($dX = $x - $player->x, 2) + pow($y - $player->y, 2) + pow($dZ = $z - $player->z, 2))) > 30){
					if(isset($this->players[$name][$player->getName()])){
						$player->dataPacket($this->RemovePlayerPacket);
					}
				}else{
					if(!isset($this->players[$name][$playerName = $player->getName()])){
						$player->dataPacket($this->AddPlayerPacket);
						if($npc[self::SIT]) $player->dataPacket($this->SetEntityLinkPacket);
 						$player->dataPacket($PlayerArmorEquipmentPacket);
 	 				}
					$players[$playerName] = $player;
					if($npc[self::HEAD] || $npc[self::SLEEP] || $distance > 15){
						$this->MovePlayerPacket->yaw = $this->MovePlayerPacket->bodyYaw = $npc[self::ROTATION][self::YAW];
						$this->MovePlayerPacket->pitch = $npc[self::ROTATION][self::PITCH];
	 				}else{
						$this->MovePlayerPacket->yaw = $this->MovePlayerPacket->bodyYaw = atan2(-$dX, -$dZ) / M_PI * -180;
						$this->MovePlayerPacket->pitch = atan(($player->y - $y + 1.62) / sqrt(pow(-$dX, 2) + pow(-$dZ, 2) + 0.1)) / M_PI * -180;
					}
					$player->dataPacket($this->MovePlayerPacket);
				}
			}
			$this->players[$name] = $players;
		}
	}

	/**
	 * @priority HIGHEST
	 */
	public function onDataPacketReceive(\pocketmine\event\server\DataPacketReceiveEvent $event){
		$player = $event->getPlayer();
		$packet = $event->getPacket();
		switch($packet->pid()){
			case ProtocolInfo::LOGIN_PACKET:
				if(strlen($packet->skin) < 64 * 32 * 4) $player->setSkin($packet->skin, $packet->slim);
				$this->skins[Color::clean($packet->username)] = $packet->skin;
 			break;
 			case ProtocolInfo::INTERACT_PACKET:
  			foreach($this->npc as $name => $npc){
					if($npc[self::EID] == $packet->target){
						if(!isset($this->tap[$player->getName()])){
							if($player->getInventory()->getItemInHand()->getID() == 288){
								$commands = "";
								foreach($npc[self::COMMAND] as $key => $command){
									$commands .= "\n".Color::YELLOW." √ [".($key+1)."] ".Color::WHITE.$command;
								}
								$player->sendMessage(Color::WHITE."\n".Color::GOLD."ÌÍÍÍÍÍÍÍÍÍÍ[".Color::GREEN.$name.Color::GOLD."]".$commands."\n".Color::GOLD."ÌÍÍÍÍÍÍÍÍÍÍ\n".Color::WHITE);
							}else{
								$this->runCommand($player, $name, true);
							}
						}else{
							$this->runCommand($player, $name);
						}
						$event->setCancelled();
 						break;
					}
 				}
 			break;
 			case ProtocolInfo::ANIMATE_PACKET: //USE_ITEM_PACKET:
				if(!isset($this->tap[$player->getName()])) $this->tap[$player->getName()] = [$this->getServer()->getScheduler()->scheduleDelayedTask(new Task($this, [$this, "unsetTouch"], [$player]), 10)];
 			break;
		}
	}

	public function unsetTouch($player){
		unset($this->tap[$player->getName()]); 		
 	}

	public function onPlayerInteract(\pocketmine\event\player\PlayerInteractEvent $event){
		if($event->getFace() == 255){
			$p = $event->getPlayer();
			$i = $event->getItem();
			if(isset($this->armorTable[$id = $i->getID()])){
				$inv = $p->getInventory();
				if($inv->getArmorItem($this->armorTable[$id])->getID() == $id){
	 				$inv->setArmorItem($this->armorTable[$id], new Item(0, 0), $p);
				}else{
					$inv->setArmorItem($this->armorTable[$id], $i, $p);
				}
				$inv->sendArmorContents($p);
			}
		}
	}

	public function addNpc($player, $name, $ip = "0.0.0.0", $port = 0, $server = true){
 		$inven = $player->getInventory();
		$this->npc[strtolower($name)] = [
			self::NAME => $name,
			self::EID => bcadd("1095216660480", mt_rand(0, 0x7fffffff)),
			self::HEAD => true,
			self::SLEEP => false,
			self::SNEAK => false,
			self::SIT => false,
			self::POSITION => [
				self::X => $player->x,
				self::Y => $player->y,
				self::Z => $player->z,
				self::LEVEL => strtolower($player->getLevel()->getFolderName())
			],
			self::ROTATION => [
				self::YAW => $player->yaw,
				self::PITCH => $player->pitch
			],
			self::ARMOR => [
				self::HELMET => $inven->getHelmet()->getID(),
				self::CHESTPLATE => $inven->getChestplate()->getID(),
				self::LEGGINGS => $inven->getLeggings()->getID(),
				self::BOOTS => $inven->getBoots()->getID()
			],
			self::ITEM => $inven->getItemInHand()->getID() . ":" . $inven->getItemInHand()->getDamage(),
			self::SKIN => $this->skin2Str($player->getSkinData()),
			self::COMMAND => $server ? ["%long %console ServerTP %player $ip $port"] : [],
		];
		$this->saveYml();
	}

	public function removeNpc($name){
		if(isset($this->npc[$name = strtolower($name)])){
			if(isset($this->players[$name])){
				foreach($this->players[$name] as $player){
					$this->RemovePlayerPacket->eid = $this->RemovePlayerPacket->clientID = $this->npc[$name][self::EID];
					$player->dataPacket($this->RemovePlayerPacket);
				}
				unset($this->players[$name]);
			}
		}
 		unset($this->npc[$name]);
		$this->saveYml();
 	}

	public function runCommand($p, $name, $isLong = false){
		$npc = $this->npc;
		if(!isset($npc[$name])) return false;
		$log = $this->log;
		if(!isset($log[$playerName = $p->getName()])) $log[$playerName] = [];
		if(!isset($log[$playerName][$name])) $log[$playerName][$name] = 0;
		if(microtime(true) - $log[$playerName][$name] < 0) return;
		$l = explode(":", $name);
		$cool = 1;
		foreach($npc[$name][self::COMMAND] as $str){
			$arr = explode(" ", str_ireplace(["%player", "%p", "%x", "%y", "%z", "%npcx", "%nx", "%npcy", "%ny", "%npcz", "%nz", "%world", "%w", "%server", "%s", "%version", "%v", "%money", "%m", "%n"], [$p->getName(), $p->getName(), $p->x, $p->y, $p->z, $this->npc[$name][self::POSITION][self::X], $this->npc[$name][self::POSITION][self::X], $this->npc[$name][self::POSITION][self::Y], $this->npc[$name][self::POSITION][self::Y], $this->npc[$name][self::POSITION][self::Z], $this->npc[$name][self::POSITION][self::Z], $p->getLevel()->getFolderName(), $p->getLevel()->getFolderName(), $this->getServer()->getServerName(), $this->getServer()->getServerName(), $this->getServer()->getApiVersion(), $this->getServer()->getApiVersion(), ($money = $this->getMoney($p)) !== false ? $money : 0, ($money = $this->getMoney($p)) !== false ? $money : 0, "\n"], $str));
			$time = 0;
			$chat = false;
			$console = false;
			$op = false;
			$deop = false;
			$safe = false;
			$long = false;
			$heal = false;
			$damage = false;
			$say = false;
			foreach($arr as $k => $v){
				if(strpos($v, "%") === 0){
					$kk = $k;
					$sub = strtolower(substr($v, 1));
					$e = explode(":", $sub);
					if(isset($e[1])){
						$ee = explode(",", $e[1]);
						switch(strtolower($e[0])){
							case "dice":
							case "d":
								if(isset($ee[1])) $arr[$k] = rand($ee[0], $ee[1]);
								$set = true;
							break;
							case "cool":
							case "c":
								if(is_numeric($e[1])) $cool = $e[1];
							break;
							case "time":
							case "t":
								if(is_numeric($e[1])) $time = $e[1];
							break;
							case "heal":
							case "h":
								if(is_numeric($e[1])) $heal = $e[1];
							break;
							case "damage":
							case "dmg":
								if(is_numeric($e[1])) $damage = $e[1];
							break;
							case "teleport":
							case "tp":
								if(is_numeric($x = $ee[0]) && isset($ee[1]) && is_numeric($y = $ee[1]) && isset($ee[2]) && is_numeric($z = $ee[2])){
									$tpos = [$x,$y,$z];
									if(isset($ee[3]) && $world = $this->getLevelByName($ee[3])){
										$tpos[] = $world;
									}else{
										$tpos[] = $p->getLevel();
									}
								}elseif($world = $this->getLevelByName($ee[0])){
									if(isset($ee[1]) && is_numeric($x = $ee[1]) && isset($ee[2]) && is_numeric($y = $ee[2]) && isset($ee[3]) && is_numeric($z = $ee[3])){
										$tpos = [$x,$y,$z];
									}else{
										$s = $world->getSafeSpawn();
										$tpos = [$s->z,$s->y,$s->z];
									}
									$tpos[] = $world;
								}
								if(isset($tpos)) $p->teleport(new Position(...$tpos));
								else $set = true;
							break;
							case "jump":
							case "j":
								if(isset($ee[2]) && is_numeric($x = $ee[0]) && is_numeric($y = $ee[0]) && is_numeric($z = $ee[0])){
									if(isset($ee[3]) && $ee[3] == "%"){
										$d = (isset($ee[4]) && is_numeric($ee[4]) && $ee[4] >= 0) ? $ee[4] : (max($x, $y, $z) > 0 ? max($x, $y, $z): -min($x, $y, $z));
										$this->move($p, (new Vector3($x * 0.4, $y * 0.4 + 0.1, $z * 0.4))->multiply(1.11 / $d), $d, isset($ee[5]) && is_numeric($ee[5]) ? $ee[5]: 0.15);
									}else{
										$p->setMotion((new Vector3($x, $y, $z))->multiply(0.4));
									}
								}else{
									$set = true;
								}
							break;
							case "havemoney":
							case "hm":
								if(is_numeric($e[1])){
									if($this->getMoney($p) < $e[1]) return;
								}else{
									$set = true;
								}
							break;
							case "nothavemoney":
							case "nm":
								if(is_numeric($e[1])){
									if($this->getMoney($p) >= $e[1]) return;
								}else{
									$set = true;
								}
							break;
							case "givemoney":
							case "gm":
								if(is_numeric($e[1])){
									$this->giveMoney($p, $e[1]);
								}else{
									$set = true;
								}
							break;
							case "takemoney":
							case "tm":
								if(is_numeric($e[1])){
									$this->giveMoney($p, -$e[1]);
								}else{
									$set = true;
								}
							break;
							default:
								$set = true;
							break;
						}
						if(!isset($set)) unset($arr[$k]);
					}else{
 						switch($sub){
							case "random":
							case "r":
								$ps = $this->getServer()->getOnlinePlayers();
								$arr[$k] = count($ps) < 1 ? "": $ps[array_rand($ps)]->getName();
							break;
							case "op":
								unset($arr[$k]);
								$op = true;
							break;
							case "deop":
							case "do":
								unset($arr[$k]);
								$deop = true;
							break;
							case "safe":
							case "s":
								unset($arr[$k]);
								$safe = true;
							break;
							case "chat":
							case "c":
								unset($arr[$k]);
								$chat = true;
							break;
							case "console":
							case "cs":
								unset($arr[$k]);
								$console = true;
							break;
							case "long":
							case "l":
								unset($arr[$k]);
								$long = true;
							break;
							case "say":
								unset($arr[$k]);
								$say = true;
							break;
						}
					}
				}
			}
			$this->getServer()->getScheduler()->scheduleDelayedTask(new Task($this, [$this,"dinpcatchCommand"], [$p,$name,$isLong,$chat,$console,$op,$deop,$safe,$long,$arr,$heal,$damage,$say]), $time * 20);
		}
		$log[$playerName][$name] = microtime(true) + $cool;
		$this->log = $log;
	}

	public function dinpcatchCommand($p, $name, $isLong, $chat, $console, $op, $deop, $safe, $long, $arr, $heal, $damage, $say){
		if(($isLong && !$long) || (!$isLong && $long) || ($safe && !$p->isOp()) || ($deop && $p->isOp())) return false;
		$cmd = implode(" ", $arr);
		if($heal) $p->heal($heal);
		if($damage) $p->attack($damage);
		if($chat){
			$p->sendMessage($cmd);
		}elseif($say){
			$this->getServer()->broadcastMessage($cmd);
		}else{
			$op = $op && !$p->isOp() && !$console;
			if($op) $p->setOp(true);
			$ev = $console ? new ServerCommandEvent(new ConsoleCommandSender(), $cmd): new PlayerCommandPreprocessEvent($p, "/" . $cmd);
			$this->getServer()->getPluginManager()->callEvent($ev);
			if(!$ev->isCancelled()){
				if($ev instanceof ServerCommandEvent) $this->getServer()->dispatchCommand(new ConsoleCommandSender(), $ev->getCommand());
				else $this->getServer()->dispatchCommand($p, substr($ev->getMessage(), 1));
			}
			if($op) $p->setOp(false);
		}
		return true;
	}

	public function move(Player $p, $m, $t, $cool, $tt = false){
		if($t - $tt < 1){
			return;
		}else{
			$tt++;
			$p->setMotion($m);
			$p->onGround = true;
			if($t - $tt > 0) $this->getServer()->getScheduler()->scheduleDelayedTask(new Task($this, [$this,"move"], [$p,$m,$t,$cool,$tt]), $cool * 20);
		}
	}

	public function getLevelByName($name){
		$levels = $this->getServer()->getLevels();
		foreach($levels as $l){
			if(strtolower($l->getFolderName()) == strtolower($name)) return $l;
		}
		foreach($levels as $l){
			if(strtolower($l->getName()) == strtolower($name)) return $l;
		}
		if($this->getServer()->loadLevel($name) != false) return $this->getServer()->getLevelByName($name);
		return false;
	}

	public function getMoney($p){
		if(!$this->money) return false;
		switch($this->money->getName()){
			case "PocketMoney":
		 	case "MassiveEconomy":
				return $this->money->getMoney($p);
			break;
			case "EconomyAPI":
				return $this->money->mymoney($p);
			break;
			case "Money":
				return $this->money->getMoney($p->getName());
			break;
			default:
				return false;
			break;
		}
	}

	public function giveMoney($p, $money){
		if(!$this->money) return false;
		switch($this->money->getName()){
			case "PocketMoney":
				$this->money->grantMoney($p, $money);
			break;
			case "EconomyAPI":
				$this->money->setMoney($p, $this->money->mymoney($p) + $money);
			break;
			case "MassiveEconomy":
				$this->money->setMoney($p, $this->money->getMoney($p) + $money);
			break;
			case "Money":
				$n = $p->getName();
				$this->money->setMoney($n, $this->money->getMoney($n) + $money);
			break;
			default:
				return false;
			break;
		}
		return true;
	}

	public function skin2Str($skin){
		return base64_encode($skin);
		$str = "";
		for($i = 0; $i < strlen($skin); $i++){
			$str .= $str = str_pad(dechex(ord($skin{$i})), 2, "0", STR_PAD_LEFT);
		}
		return $str;
	}

	public function str2Skin($str){
		return base64_decode($str);
		$skin = "";
		for($i = 0; $i < strlen($str); $i+=2){
			$skin .= chr(hexdec($str{$i} . $str{$i + 1}));
		}
		return $skin;
	}

	public function loadYml(){
		@mkdir($this->getDataFolder());
		$this->npc = (new Config($this->getDataFolder() . "NPC.yml", Config::YAML))->getAll();
	}

	public function saveYml(){
		$npc = new Config($this->getDataFolder() . "NPC.yml", Config::YAML);
		$npc->setAll($this->npc);
		$npc->save();
	}

	public function isKorean(){
		return strtolower($this->getServer()->getLanguage()->getName()) == "korean";
	}
}