<?php

namespace MineBlock\RubyNPC;

use pocketmine\block\Block;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\event\Listener;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\network\protocol\DataPacket;
use pocketmine\network\protocol\AddPlayerPacket;
use pocketmine\network\protocol\RemovePlayerPacket;
use pocketmine\network\protocol\MovePlayerPacket;
use pocketmine\network\protocol\PlayerArmorEquipmentPacket;
use pocketmine\network\protocol\Info as ProtocolInfo;
use pocketmine\item\Item;
use pocketmine\entity\Entity;
use pocketmine\math\Vector3;
use pocketmine\level\Position;
use pocketmine\utils\Random;
use pocketmine\entity\Effect;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat as Color;
use pocketmine\command\ConsoleCommandSender;
use pocketmine\event\player\PlayerCommandPreprocessEvent;
use pocketmine\event\server\ServerCommandEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityRegainHealthEvent;
use pocketmine\level\particle\BubbleParticle;
use pocketmine\level\particle\CriticalParticle;
use pocketmine\level\particle\DestroyBlockParticle;
use pocketmine\level\particle\DustParticle;
use pocketmine\level\particle\EnchantParticle;
use pocketmine\level\particle\EntityFlameParticle;
use pocketmine\level\particle\ExplodeParticle;
use pocketmine\level\particle\FlameParticle;
use pocketmine\level\particle\HeartParticle;
use pocketmine\level\particle\InkParticle;
use pocketmine\level\particle\ItemBreakParticle;
use pocketmine\level\particle\LavaDripParticle;
use pocketmine\level\particle\LavaParticle;
use pocketmine\level\particle\MobSpawnParticle;
use pocketmine\level\particle\PortalParticle;
use pocketmine\level\particle\RedstoneParticle;
use pocketmine\level\particle\SmokeParticle;
use pocketmine\level\particle\SplashParticle;
use pocketmine\level\particle\SporeParticle;
use pocketmine\level\particle\TerrainParticle;
use pocketmine\level\particle\WaterDripParticle;
use pocketmine\level\particle\WaterParticle;
use pocketmine\level\sound\BatSound;
use pocketmine\level\sound\ClickSound;
use pocketmine\level\sound\DoorSound;
use pocketmine\level\sound\FizzSound;
use pocketmine\level\sound\LaunchSound;
use pocketmine\level\sound\PopSound;

class RubyNPC extends PluginBase implements Listener{
	const MODE_NOTHING = 0;
	const MODE_NORMAL = 1;
	const MODE_OP = 2;
	const MODE_CONSOLE = 3;
	const MODE_MESSAGE = 4;
	const MODE_BROADCAST = 5;

	public function onEnable(){
		$this->tap = [];
		$this->players = [];
		$this->log = [];
		$this->loadYml();
		$this->AddPlayerPacket = new AddPlayerPacket();
		$this->AddPlayerPacket->slim = false;
		$this->AddPlayerPacket->metadata = [
			Entity::DATA_FLAGS => [Entity::DATA_TYPE_BYTE, 0 << Entity::DATA_FLAG_INVISIBLE],
			Entity::DATA_SHOW_NAMETAG => [Entity::DATA_TYPE_BYTE, 1],
			Entity::DATA_NO_AI => [Entity::DATA_TYPE_BYTE, 0]
		];
		$this->RemovePlayerPacket = new RemovePlayerPacket();
		$this->MovePlayerPacket = new MovePlayerPacket();
		$this->getServer()->getLogger()->info(Color::GREEN . "Find economy plugin...");
		$pm = $this->getServer()->getPluginManager();
		if(!($this->money = $pm->getPlugin("PocketMoney")) && !($this->money = $pm->getPlugin("EconomyAPI")) && !($this->money = $pm->getPlugin("MassiveEconomy")) && !($this->money = $pm->getPlugin("Money"))){
			$this->getServer()->getLogger()->info(Color::RED . "Failed find economy plugin...");
		}else{
			$this->getServer()->getLogger()->info(Color::GREEN . "Finded economy plugin : " . $this->money->getName());
		}
 		$this->loadYml();
		$pm->registerEvents($this, $this);
		$this->getServer()->getScheduler()->scheduleRepeatingTask(new Task($this, [$this,"onTick"]), 4);
	}
	
	public function onDisable(){
		$this->getServer()->getScheduler()->cancelTasks($this);
	}

	public function onCommand(CommandSender $sender, Command $cmd, $label, array $sub){
		$n = $sender->getName();
		if(!isset($sub[0])) return false;
		$npc = $this->npc;
		$rm = "Usage: /NPC ";
		$mm = "[NPC] ";
		$ik = $this->isKorean();
		switch(strtolower($sub[0])){
			case "add":
			case "a":
			case "추가":
				if(!$sender instanceof Player){
					$r = $mm . ($ik ? "게임내에서만 사용 가능합니다." : "Please run this command in game");
				}elseif($sub[1] == ""){
					$r = $rm . "Add(A) " . ($ik ? "<이름> <IP> (Port)" : "<Name> <IP> (Port)");
				}elseif(isset($npc[strtolower($sub[1])])){
					$r = $mm . $sub[1] . ($ik ? "는 이미 존재합니다." : " is already");
				}else{
					if(!isset($sub[2])){
						$this->addPortal($sender, $sub[1], "", 0, false);
						$r = $mm . " Add portal : $sub[1]";
					}elseif(!isset($sub[3])) $sub[3] = 19132;
					elseif(!is_numeric($sub[3])) $r = $rm . "Add(A) " . ($ik ? "<이름> <IP> (포트)" : "<Name> <IP> (Port)");
					else{
						if(preg_match("/^[0-9]{1,3}\\.[0-9]{1,3}\\.[0-9]{1,3}\\.[0-9]{1,3}$/", $sub[2]) > 0){
							$ip = $sub[2];
						}else{
						 	$ip = gethostbyname($sub[2] = strtolower($sub[2]));
						 	if($sub[2] == $ip){
						 		$sender->sendMessage("$mm $sub[2] is invalid IP");
						 		return true;
							}
						}
						$this->addPortal($sender, $sub[1], $ip, $sub[3]);
						$r = $mm . " Add portal : $sub[1]";
					}
				}
			break;
			case "del":
			case "d":
			case "삭제":
			case "제거":
				if(!isset($sub[1]) || $sub[1] == "") $r = $rm . "Del(D) " . ($ik ? "<이름>" : "<Name>");
				elseif(!isset($npc[strtolower($sub[1])])) $r = $mm . $sub[1] . ($ik ? "는 존재하지않습니다." : "is invaild name");
				else{
					$this->removePortal($sub[1]);
					$r = $mm . ($ik ? $sub[1]."를 제거했습니다." : "Delete the ".$sub[1]);
				}
			break;
			case "reset":
			case "리셋":
			case "초기화":
				foreach($npc as $name => $portal){
					$this->removePortal($name);
				}
				$npc = [];
				$change = true;
				$r = $mm . ($ik ? " 리셋됨." : " Reset");
			break;
			case "set":
			case "s":
			case "설정":
				if(!isset($sub[2]) || $sub[1] == "") $r = $rm . "Set(S) " . ($ik ? "<포탈명>" : "<PortalName>") . " <Name(N) | Position(Pos) | Skin(S) | Armor(A) | Item(I) | Sleep(SL) | Command(C)>";
				elseif(!isset($this->npc[$sub[1] = strtolower($sub[1])])) $r = $mm . $sub[1] . ($ik ? "는 존재하지않습니다." : "is invaild name");
				else{
					$rm = $rm . "Set(S) " . ($ik ? "<포탈명>" : "<PortalName>");
					switch(strtolower($sub[2])){
						case "name":
						case "n":
						case "이름":
							if(!isset($sub[3]) || $sub[3] == "") $r = $rm . ($ik ? "<포탈명>" : "<PortalName>");
							else{
								$npc[$sub[1]]["Name"] = $name = implode(" ", array_splice($sub,3));
								$r = $mm . ($ik ? "포탈의 이름을 $name".Color::RESET."으로 변경하였습니다." : "Portal name is set to $name");
							}
						break;
						case "skin":
						case "s":
						case "스킨":
							if(!$sender instanceof Player) $r = $mm . ($ik ? "게임내에서만 사용 가능합니다." : "Please run this command in game");
							else{
								$npc[$sub[1]]["Skin"] = base64_encode(isset($this->skins[$playerName = $sender->getName()]) ? $this->skins[$playerName] : $sender->getSkinData());
								$r = $mm . ($ik ? "포탈의 스킨을 변경하였습니다." : "Portal's skin is changed");
							}
						break;
						case "position":
						case "pos":
						case "위치":
						case "좌표":
							if(!$sender instanceof Player) $r = $mm . ($ik ? "게임내에서만 사용 가능합니다." : "Please run this command in game");
							else{
								$npc[$sub[1]]["Position"]["X"] = $sender->x;
								$npc[$sub[1]]["Position"]["Y"] = $sender->y;
								$npc[$sub[1]]["Position"]["Z"] = $sender->z;
								$npc[$sub[1]]["Position"]["Level"] = strtolower($sender->getLevel()->getFolderName());
								$npc[$sub[1]]["Rotation"]["Yaw"] = $sender->yaw;
								$npc[$sub[1]]["Rotation"]["Pitch"] = $sender->pitch;
								$r = $mm . ($ik ? "포탈의 좌표을 변경하였습니다." : "Portal's position is changed");
							}
							$notRespawn = true;
						break;
						case "armor":
						case "a":
						case "갑롯":
						case "장비":
							if(!$sender instanceof Player) $r = $mm . ($ik ? "게임내에서만 사용 가능합니다." : "Please run this command in game");
							else{
								$inven = $sender->getInventory();
								$npc[$sub[1]]["Armor"]["Helmet"] = $inven->getHelmet()->getID();
								$npc[$sub[1]]["Armor"]["ChestPlate"] = $inven->getChestplate()->getID();
								$npc[$sub[1]]["Armor"]["Leggings"] = $inven->getLeggings()->getID();
								$npc[$sub[1]]["Armor"]["Boots"] = $inven->getBoots()->getID();
								$r = $mm . ($ik ? "포탈의 갑옷을 변경하였습니다." : "Portal's armor is changed");
							}
						break;
						case "item":
						case "i":
						case "아이템":
							if(!$sender instanceof Player) $r = $mm . ($ik ? "게임내에서만 사용 가능합니다." : "Please run this command in game");
							else{
								$inven = $sender->getInventory();
								$npc[$sub[1]]["Item"]["Id"] = $inven->getItemInHand()->getID();
								$npc[$sub[1]]["Item"]["Damage"] = $inven->getItemInHand()->getDamage();
								$r = $mm . ($ik ? "포탈의 아이템을 변경하였습니다." : "Portal's item is changed");
							}
						break;
						case "head":
						case "h":
						case "머리":
							$npc[$sub[1]]["Head"] = !$npc[$sub[1]]["Head"];
							$r = $mm . ($ik ? "포탈을 ".($npc[$sub[1]]["Head"] ? "고정" : "고정해제")."했습니다." : "Portal is ".($npc[$sub[1]]["Head"] ? "Force" : "Not force"));
							$notRespawn = true;
						break;
						case "sleep":
						case "sl":
						case "잠":
							$npc[$sub[1]]["Sleep"] = !$npc[$sub[1]]["Sleep"];
							$r = $mm . ($ik ? "포탈을 ".($npc[$sub[1]]["Sleep"] ? "재웠습니다" : "깨웠습니다") : "Portal is ".($npc[$sub[1]]["Sleep"] ? "Sleep" : "Wake up"));
						break;
						case "command":
						case "c":
						case "명령어":
							switch(strtolower($sub[3])){
								case "add":
								case "a":
								case "추가":
									if(!isset($sub[4])){
										$r = $rm . "Set(S) Command(C) Add(A) " . ($ik ? "<명령어>" : "<Command>");
									}else{
										$npc[$sub[1]]["Commands"][] = ($command = implode(" ", array_splice($sub,4)));
										$r = $mm . ($ik ? "명령어를 추가했습니다. : " : "Added Commmand. : ").$command;
									}
								break;
								case "del":
								case "d":
								case "제거":
									if(!isset($sub[4]) || !is_numeric($sub[4])){
										$r = $rm . "Set(S) Command(C) Del(D) " . ($ik ? "<명령어ID>" : "<CommandID>"); 	
									}elseif(!isset($npc[$sub[1]]["Commands"][$sub[4]-1])){
										$r = $mm . ($ik ? "해당 명령어가 없거나 잘못된 숫자입니다." : "Invalid Command Id");
									}else{
										$commands = $npc[$sub[1]]["Commands"];
										unset($commands[$sub[4]-1]);
										$npc[$sub[1]]["Commands"] = [];
										foreach($commands as $command)	$npc[$sub[1]]["Commands"][] = $command;
										$r = $mm . ($ik ? "명령어를 제거했습니다. : " : "Deleted Commmand. : ");
									}
								break;
							}
							$notRespawn = true;
							if(!isset($r)){
								$sender->sendMessage($rm . "Set(S) Command(C) <Add(A) | Del(D)>");
								return true;
							}
						break;	
					}
					if(!isset($r)){
						$sender->sendMessage($rm . "Set(S) " . ($ik ? "<포탈명>" : "<PortalName>") . " <Name(N) | Position(Pos) | Skin(S) | Armor(A) | Item(I) | Sleep(SL) | Command(C)>");
						return true;
					}elseif(!isset($notRespawn)){
						foreach($this->players[$sub[1]] as $player){
							$this->RemovePlayerPacket->eid = $this->RemovePlayerPacket->clientID = $this->npc[$sub[1]]["Eid"];
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
				foreach($npc as $name => $portal){
					foreach($this->players[$name] as $player){
						$this->RemovePlayerPacket->eid = $this->RemovePlayerPacket->clientID = $portal["Eid"];
						$player->dataPacket($this->RemovePlayerPacket);
 					}
					$this->players[$name] = [];
				}
				$r = $mm . ($ik ? " 포탈 리스폰됨." : " Spawn the Portals");
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
		foreach($this->npc as $name => $portal){
			$players = isset($this->players[$name]) ? [] : $this->players[$name] = [];
			$PlayerArmorEquipmentPacket = new PlayerArmorEquipmentPacket();
			$this->AddPlayerPacket->eid = $this->AddPlayerPacket->clientID = $this->RemovePlayerPacket->eid = $this->RemovePlayerPacket->clientID = $this->MovePlayerPacket->eid = $PlayerArmorEquipmentPacket->eid = $portal["Eid"];
			$this->AddPlayerPacket->username = str_replace("%n", "\n", $portal["Name"]);
			$this->AddPlayerPacket->yaw = $portal["Rotation"]["Yaw"];
			$this->AddPlayerPacket->pitch = $portal["Rotation"]["Pitch"];
			$this->AddPlayerPacket->item = $portal["Item"]["Id"];
			$this->AddPlayerPacket->meta = $portal["Item"]["Damage"];
			$this->AddPlayerPacket->skin = base64_decode($portal["Skin"]); // $this->skins->{$name};
			if(!isset($portal["Sleep"])) $portal["Sleep"] = $this->npc[$name]["Sleep"] = false;
			if(!isset($portal["Head"])) $portal["Head"] = $this->npc[$name]["Head"] = true;
			$this->AddPlayerPacket->metadata[Player::DATA_PLAYER_FLAGS] = [Entity::DATA_TYPE_BYTE, $portal["Sleep"] << Player::DATA_PLAYER_FLAG_SLEEP];
			$this->AddPlayerPacket->x = $this->MovePlayerPacket->x = $x = $portal["Position"]["X"];
			$this->AddPlayerPacket->y = $this->MovePlayerPacket->y = $y = $portal["Position"]["Y"] + ($portal["Sleep"] ? 0.3 : 1.62);
			$this->AddPlayerPacket->y -= 1.62;
			$this->AddPlayerPacket->z = $this->MovePlayerPacket->z = $z = $portal["Position"]["Z"];
			$this->MovePlayerPacket->mode = $portal["Sleep"];
			$PlayerArmorEquipmentPacket->slots = [$portal["Armor"]["Helmet"], $portal["Armor"]["ChestPlate"], $portal["Armor"]["Leggings"], $portal["Armor"]["Boots"]];
			foreach($this->getServer()->getOnlinePlayers() as $player){
				if(!$player->spawned || $portal["Position"]["Level"] !== strtolower($player->getLevel()->getFolderName()) || ($distance = sqrt(pow($dX = $x - $player->x, 2) + pow($y - $player->y, 2) + pow($dZ = $z - $player->z, 2))) > 20){
					if(isset($this->players[$name][$player->getName()])){
						$player->dataPacket($this->RemovePlayerPacket);
					}
				}else{
					if(!isset($this->players[$name][$playerName = $player->getName()])){
						$player->dataPacket($this->AddPlayerPacket);
						$player->dataPacket($PlayerArmorEquipmentPacket);
 	 				}
					$players[$playerName] = $player;
					if($portal["Head"] || $portal["Sleep"] || $distance > 6){
						$this->MovePlayerPacket->yaw = $this->MovePlayerPacket->bodyYaw = $portal["Rotation"]["Yaw"];
						$this->MovePlayerPacket->pitch = $portal["Rotation"]["Pitch"];
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
	public function onDataPacketReceive(DataPacketReceiveEvent $event){
		$player = $event->getPlayer();
		$packet = $event->getPacket();
		switch($packet->pid()){
			case ProtocolInfo::LOGIN_PACKET:
				if(strlen($packet->skin) < 64 * 32 * 4) $player->setSkin($packet->skin, $packet->slim);
				$this->skins[Color::clean($packet->username)] = $packet->skin;
 			break;
 			case ProtocolInfo::INTERACT_PACKET:
  			foreach($this->npc as $name => $portal){
					if($portal["Eid"] == $packet->target){
						if(!isset($this->tap[$player->getName()])){
							if($player->getInventory()->getItemInHand()->getID() == 288){
								$commands = "";
								foreach($portal["Commands"] as $key => $command){
									$commands .= "\n".Color::YELLOW." ›> [".($key+1)."] ".Color::WHITE.$command;
								}
								$player->sendMessage(Color::WHITE."\n".($line = Color::GOLD."≥".str_repeat("»", 10))."[".Color::GREEN.$name.Color::GOLD."]".$commands."\n".$line."\n".Color::WHITE);
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

	public function addPortal($player, $name, $ip = "0.0.0.0", $port = 0, $server = true){
 		$inven = $player->getInventory();
		$this->npc[strtolower($name)] = [
			"Name" => $name,
			"Ip" => $ip,
 			"Port" => $port,
			"Eid" => bcadd("1095216660480", mt_rand(0, 0x7fffffff)),
			"Head" => true,
			"Sleep" => false,
			"Commands" => $server ? ["%isLong %console ServerTP %player $ip $port"] : [],
			"Skin" => base64_encode(isset($this->skins[$playerName = $player->getName()]) ? $this->skins[$playerName = $player->getName()] : $player->getSkinData()),
			"Position" => [
				"X" => $player->x,
				"Y" => $player->y,
				"Z" => $player->z,
				"Level" => strtolower($player->getLevel()->getFolderName())
			],
			"Rotation" => [
				"Yaw" => $player->yaw,
				"Pitch" => $player->pitch
			],
			"Armor" => [
				"Helmet" => $inven->getHelmet()->getID(),
				"ChestPlate" => $inven->getChestplate()->getID(),
				"Leggings" => $inven->getLeggings()->getID(),
				"Boots" => $inven->getBoots()->getID()
			],
			"Item" => [
				"Id" => $inven->getItemInHand()->getID(),
				"Damage" => $inven->getItemInHand()->getDamage()
			]
		];
		$this->saveYml();
	}

	public function removePortal($name){
		if(isset($this->npc[$name = strtolower($name)])){
			if(isset($this->players[$name])){
				foreach($this->players[$name] as $player){
					$this->RemovePlayerPacket->eid = $this->RemovePlayerPacket->clientID = $this->npc[$name]["Eid"];
					$player->dataPacket($this->RemovePlayerPacket);
				}
				unset($this->players[$name]);
			}
		}
 		unset($this->npc[$name]);
		$this->saveYml();
 	}

	public function runCommand($player, $name, $isPush = false){
		if(!isset($this->npc[$name])) return false;
		if(!isset($this->log[$playerName = $player->getName()])) $this->log[$playerName] = [];
		if(!isset($this->log[$playerName][$name])) $this->log[$playerName][$name] = [];
		$l = explode(":", $name);
		$cool = 1;
		$inven = $player->getInventory();
		$armors = $inven->getArmorContents();
		$firstReplace = [
			"%getplayername;" => ($playerName = $player->getName()),
			"%playername;" => $playerName,
			"%player;" => $playerName,
			"%getPlayerX;" => $player->x,
			"%PlayerX;" => $player->x,
			"%x;" => $player->x,
			"%getPlayerY;" => $player->y,
			"%playerY;" => $player->y,
			"%y;" => $player->y,
			"%getPlayerZ;" => $player->z,
			"%playerZ;" => $player->z,
			"%z;" => $player->z,
			"%getPlayerYaw;" => $player->yaw,
			"%playerYaw;" => $player->yaw,
			"%yaw;" => $player->yaw,
			"%getPlayerPitch;" => $player->pitch,
			"%playerPitch;" => $player->pitch,
			"%pitch;" => $player->pitch,
			"%getNpcX;" => ($npcX = $this->npc[$name]["Position"]["X"]),
			"%npcX;" => $npcX,
			"%getNpcY;" => ($npcY = $this->npc[$name]["Position"]["Y"]),
			"%npcY;" => $npcY,
			"%getNpcZ;" => ($npcZ = $this->npc[$name]["Position"]["Z"]),
			"%npcZ;" => $npcZ,
			"%getNpcYaw;" => ($npcYaw = $this->npc[$name]["Rotation"]["Yaw"]),
			"%npcYaw;" => $npcYaw,
			"%getNpcPitch;" => ($npcPitch = $this->npc[$name]["Rotation"]["Pitch"]),
			"%npcPitch;" => $npcPitch,
			"%getLevelName;" => ($levelName = $player->getLevel()->getFolderName()),
			"%getWorldName;" => $levelName,
			"%getLevel;" => $levelName,
			"%getWorld;" => $levelName,
			"%level;" => $levelName,
			"%world;" => $levelName,
			"%getMoney;" => ($money = $this->getMoney($player)) !== false ? $money : 0,
			"%getItemID;" => ($itemId = $inven->getItemInHand()->getID()),
			"%getHandID;" => $itemId, 
			"%itemID;" => $itemId, 
			"%handID;" => $itemId, 
			"%getItemDamage;" => ($itemDamage = $inven->getItemInHand()->getDamage()),
			"%getHandDamage;" => $itemDamage, 
			"%itemDamage;" => $itemDamage, 
			"%handDamage;" => $itemDamage, 
			"%getHelmetID;" => ($helmetId = $armors[0]->getID()),
			"%helmetID;" => $helmetId,
			"%getHatID;" => $helmetId,
			"%hatID;" => $helmetId,
			"%getHelmetDamage;" => ($helmetDamage = $armors[0]->getDamage()),
			"%helmetDamage;" => $helmetDamage,
			"%getHatDamage;" => $helmetDamage,
			"%hatDamage;" => $helmetDamage,
			"%getChestplateID;" => ($chestplateId = $armors[1]->getID()),
			"%chestplateID;" => $chestplateId,
			"%getArmorID;" => $chestplateId,
			"%armorID;" => $chestplateId,
			"%getChestplateDamage;" => ($chestplateDamage = $armors[1]->getDamage()),
			"%chestplateDamage;" => $chestplateDamage,
			"%getArmorDamage;" => $chestplateDamage,
			"%armorDamage;" => $chestplateDamage,
			"%getLeggingsID;" => ($leggingsId = $armors[2]->getID()),
			"%leggingsID;" => $leggingsId,
			"%getPantsID;" => $leggingsId,
			"%pantsID;" => $leggingsId,
			"%getLeggingsDamage;" => ($leggingsDamage = $armors[2]->getDamage()),
			"%leggingsDamage;" => $leggingsDamage,
			"%getPantsDamage;" => $leggingsDamage,
			"%pantsDamage;" => $leggingsDamage,
			"%getBootsID;" => ($bootsId = $armors[3]->getID()),
			"%bootsID;" => $bootsId,
			"%getShoesID;" => $bootsId,
			"%shoesID;" => $bootsId,
			"%getBootsDamage;" => ($bootsDamage = $armors[3]->getDamage()),
			"%bootsDamage;" => $bootsDamage,
			"%getShoesDamage;" => $bootsDamage,
			"%shoesDamage;" => $bootsDamage,
			"%getNpcName;" => $this->npc[$name]["Name"],
			"%npcName;" => $this->npc[$name]["Name"],
			"%%Click;" => "%Sound:C,%X;,%Y;,%Z;;",
			"%%클릭;" => "%Sound:C,%X;,%Y;,%Z;;",
 		];
		$commandBools = [];
		$rands = [];
 		foreach($this->npc[$name]["Commands"] as $key => $command){
			if(!isset($this->log[$playerName][$name][$key])) $this->log[$playerName][$name][$key] = 0;
			$bool = microtime(true) - $this->log[$playerName][$name][$key] >= 0;
			$isCool = !$bool;
			$command = str_ireplace(array_keys($firstReplace), $firstReplace, $command).(($substr = substr($command, -1)) == " " ||  $substr == ";" ? "" : " ");
 			$delay = 0;
			$cool = 0;
			$mode = self::MODE_NORMAL;
			while(preg_match_all("/%([a-zA-Z]+):([^\f\n\r\t\v\%\;]+);/", $command, $matches)){
				$sub = explode(",", $matches[2][$matchKey = count($matches[0]) - 1]);
				$change = "";
				switch(strtolower($matches[1][$matchKey])){
					case "rand":
					case "dice":
						$change = rand(isset($sub[0]) && is_numeric($sub[0]) ? $sub[0] : 0, isset($sub[1]) && is_numeric($sub[1]) ? $sub[1] : 0);
						$rands[] = $change;
					break;
					case "rands":
					case "dices":
						$change = isset($sub[0]) && is_numeric($sub[0]) && isset($rands[$sub[0] -1]) ? $rands[$sub[0] - 1] : 0;
					break;
					case "getcommandlog":
					case "getcl":
					case "getvalue":
						$cl = isset($sub[0]) && isset($sub[1]) ? $this->getCl($sub[0], $sub[1]) : 0;
						$change = $cl === null ? 0 : $cl;
					break;
					case "plus":
					case "+":
						$change = (isset($sub[0]) && is_numeric($sub[0]) ? $sub[0] : 0) + (isset($sub[1]) && is_numeric($sub[1]) ? $sub[1] : 0);
					break;
					case "minus":
					case "-":
						$change = (isset($sub[0]) && is_numeric($sub[0]) ? $sub[0] : 0) - (isset($sub[1]) && is_numeric($sub[1]) ? $sub[1] : 0);
					break;
					case "floor":
						$change = isset($sub[0]) && is_numeric($sub[0]) ? floor($sub[0]) : 0;
					break;
					case "ceil":
						$change = isset($sub[0]) && is_numeric($sub[0]) ? ceil($sub[0]) : 0;
					break;
					case "round":
						$change = isset($sub[0]) && is_numeric($sub[0]) ? round($sub[0]) : 0;
					break;
					case "pow":
						$change = pow(isset($sub[0]) && is_numeric($sub[0]) ? round($sub[0]) : 0, isset($sub[1]) && is_numeric($sub[1]) ? round($sub[1]) : 2);
					break;
					case "sqrt":
						$change = sqrt(isset($sub[0]) && is_numeric($sub[0]) ? round($sub[0]) : 0);
					break;
					default:
						$change = str_replace("%", "!%!", $matches[0][$matchKey]);
					break;
				}
				$command = str_replace($matches[0][$matchKey], $change, $command);
				if(!$bool) break;
			}
			preg_match_all("/%([a-zA-Z]+):([^\f\n\r\t\v\%\;]+);/", $command = str_replace("!%!", "%", $command), $matches);
			foreach($matches[0] as $matchKey => $match){
				$explode = explode(":", substr($match, 1, -1));
				$sub = explode(",", $explode[1]);
				$change = "";
				switch(strtolower($explode[0])){
					case "cool":
						if(isset($sub[0]) && is_numeric($sub[0])) $cool = $sub[0];
					break;
					case "delay":
					case "time":
						if(isset($sub[0]) && is_numeric($sub[0])) $delay = $sub[0];
					break;
					case "havemoney":
					case "hasmoney":
						$bool = isset($sub[0]) && is_numeric($sub[0]) && $money >= $sub[0];
					break;
					case "nothavemoney":
					case "nohavemoney":
					case "nothasmoney":
					case "nohasmoney":
						$bool = isset($sub[0]) && is_numeric($sub[0]) && $money < $sub[0];
					break;
					case "isequal":
						$bool = isset($sub[0]) && is_numeric($sub[0]) && isset($sub[1]) && is_numeric($sub[1]) && $sub[0] == $sub[1];
					break;
					case "notequal":
						$bool = isset($sub[0]) && is_numeric($sub[0]) && isset($sub[1]) && is_numeric($sub[1]) && $sub[0] != $sub[1];
					break;
					case "isbigger":
					case "isbig":
						$bool = isset($sub[0]) && is_numeric($sub[0]) && isset($sub[1]) && is_numeric($sub[1]) && $sub[0] > $sub[1];
					break;
					case "notbigger":
					case "notbig":
						$bool = isset($sub[0]) && is_numeric($sub[0]) && isset($sub[1]) && is_numeric($sub[1]) && $sub[0] <= $sub[1];
					break;
					case "issmall":
					case "isless":
						$bool = isset($sub[0]) && is_numeric($sub[0]) && isset($sub[1]) && is_numeric($sub[1]) && $sub[0] < $sub[1];
					break;
					case "notsmall":
					case "notless":
						$bool = isset($sub[0]) && is_numeric($sub[0]) && isset($sub[1]) && is_numeric($sub[1]) && $sub[0] >= $sub[1];
					break;
						$cl = isset($sub[0]) && isset($sub[1]) ? $this->getCl($sub[0], $sub[1]) : 0;
						$change = $cl === null ? 0 : $cl;
					case "iscool":
						$bool = $isCool;
					break;
					case "isRun":
						$bool = isset($sub[0]) && is_numeric($sub[0]) && isset($commandBools[$sub[0] -1]) ? $commandBools[$sub[0] -1] : false;
					break;
					case "notRun":
						$bool = isset($sub[0]) && is_numeric($sub[0]) && isset($commandBools[$sub[0] -1]) ? !$commandBools[$sub[0] -1] : true;
					break;
					default:
						$change = $match;
					break;
				}
				if(!$bool) break;
				$command = str_replace($match, $change, $command);
			}
			preg_match_all("/%([a-zA-Z]+);/", $command, $matches);
			foreach($matches[1] as $matchKey => $match){
				if(!$bool) break;
				$change = "";
				switch(strtolower($match)){
					case "random":
						if(count($ps = $this->getServer()->getOnlinePlayers()) > 0) $change = $ps[array_rand($ps)]->getName();
					break;
					case "op":
						$mode = self::MODE_OP;
					break;
					case "console":
						$mode = self::MODE_CONSOLE;
					break;
					case "message":
					case "chat":
						$mode = self::MODE_MESSAGE;
					break;
					case "broadcast":
					case "broad":
					case "say":
						$mode = self::MODE_BROADCAST;
					break;
					case "isop":
						$bool = $player->isOp();
					break;
					case "notop":
						$bool = !$player->isOp();
					break;
					case "ispush":
						$bool = $isPush;
					break;
					case "notpush":
						$bool = !$isPush;
					break;
					default:
						$change = $match;
					break;
				}
				$command = str_replace($matches[0][$matchKey], $change, $command);
			}
			if($bool) $this->getServer()->getScheduler()->scheduleDelayedTask(new Task($this, [$this,"dispatchCommand"], [$player, $command, $mode, $name]), $delay*20);
			$commandBools[$key] = $bool;
			$this->log[$playerName][$name][$key] = microtime(true) + ($cool == 0 ? $delay : $cool);
		}
 	}

	public function dispatchCommand($player, $command, $mode, $name){
		preg_match_all("/%([a-zA-Z]+):([^\f\n\r\t\v\%\;]+);/", $command, $matches);
		foreach($matches[0] as $matchKey => $match){
			$sub = explode(",", $matches[2][$matchKey]);
			$change = "";
			$changeMode = self::MODE_NOTHING;
			switch(strtolower($matches[1][$matchKey])){
				case "damage":
					$event = new EntityDamageEvent($player, EntityDamageEvent::CAUSE_MAGIC, isset($sub[0]) && is_numeric($sub[0]) ? $sub[0] : 1);
				 	$player->attack($event->getFinalDamage(), $event);
				 break;
				case "heal":
					$event = new EntityRegainHealthEvent($player, isset($sub[0]) && is_numeric($sub[0]) ? $sub[0] : 1, EntityRegainHealthEvent::CAUSE_MAGIC); 
					$player->heal($event->getAmount(), $event);
 				break;
 				case "teleport":
 				case "tp":
 				case "warp":
					if(isset($sub[0]) && is_numeric($x = $sub[0]) && isset($sub[1]) && is_numeric($y = $sub[1]) && isset($sub[2]) && is_numeric($z = $sub[2])){
						$pos = [$x,$y,$z];
						if(isset($sub[3]) && $world = $this->getLevelByName($sub[3])){
							$pos[] = $world;
						}else{
							$pos[] = $player->getLevel();
						}
					}elseif(isset($sub[0]) && $world = $this->getLevelByName($sub[0])){
						if(isset($sub[1]) && is_numeric($x = $sub[1]) && isset($sub[2]) && is_numeric($y = $sub[2]) && isset($sub[3]) && is_numeric($z = $sub[3])){
							$pos = [$x,$y,$z];
						}else{
							$spawn = $world->getSafeSpawn();
							$pos = [$spawn->z,$spawn->y,$spawn->z];
						}
						$pos[] = $world;
					}
					if(isset($pos)) $player->teleport(new Position(...$pos));
 				break;
 				case "move":
 				case "jump":
					if(isset($sub[0]) && is_numeric($x = $sub[0]) && isset($sub[1]) && is_numeric($y = $sub[1]) && isset($sub[2]) && is_numeric($z = $sub[2])) $player->setMotion(new Vector3($x * 0.4, $y * 0.4 + 0.1, $z * 0.4));
				break;
				case "givemoney":
				case "addmoney":
					$this->giveMoney($player, isset($sub[0]) && is_numeric($sub[0]) ? $sub[0] : 0);
				break;
				case "takemoney":
				case "removemoney":
					$this->giveMoney($player, isset($sub[0]) && is_numeric($sub[0]) ? -$sub[0] : 0);
				break;
				case "setnpcpos":
				case "npcteleport":
				case "npctp":
				case "teleportnpc":
				case "tpnpc":
					if(isset($sub[0]) && is_numeric($sub[0])) $this->npc[$name]["Position"]["X"] = $sub[0];
					if(isset($sub[1]) && is_numeric($sub[1])) $this->npc[$name]["Position"]["Y"] = $sub[1];
					if(isset($sub[2]) && is_numeric($sub[2])) $this->npc[$name]["Position"]["Z"] = $sub[2];
					if(isset($sub[3]) && is_numeric($sub[3])) $this->npc[$name]["Rotation"]["Yaw"] = $sub[3];
					if(isset($sub[4]) && is_numeric($sub[4])) $this->npc[$name]["Rotation"]["Pitch"] = $sub[4];
				break;
				case "setnpcname":
				case "npcname":
					if(isset($sub[0]) && $sub[0] != "") $this->npc[$name]["Name"] = implode(" ", $sub);
					foreach($this->players[$name] as $player2){
						$this->RemovePlayerPacket->eid = $this->RemovePlayerPacket->clientID = $this->npc[$name]["Eid"];
						$player2->dataPacket($this->RemovePlayerPacket);
					}
					$this->players[$name] = [];
				break;
				case "addparticle":
				case "particle":
					$lists = [
						"BUBBLE" => [BubbleParticle::class, [1, "버블", "방울", "기포", "BUBLE", "BUBL", "BUBBL", "BU"]],
						"CRITICAL" => [CriticalParticle::class, [2, "크리티컬", "크리", "화살", "CRIT", "CR"]],
						"DESTROYBLOCK" => [DestoryBlockParticle::class, [3, "디스트로이블럭", "블록파괴", "블럭파괴", "블록", "블럭", "DESTROY", "DE"]],
						"DUST" => [DustParticle::class, [4, "더스트", "먼지", "COLOR", "DROP", "DU"]],
						"ENCHANT" => [EnchantParticle::class, [5, "인첸트", "INCHANT", "EN"]],
						"ENTITYFLAME" => [EntityFlameParticle::class, [6, "엔티티프레임", "엔티티플레임", "ENTITYFIRE", "EN"]],
						"EXPLODE" => [ExplodeParticle::class, [7, "익스플로드", "익스플로젼", "익스플로전", "폭발", "EXPLOSION", "BOOM", "EX"]],
						"FLAME" => [FlameParticle::class, [8, "파이어", "불", "FIRE", "FL"]],
						"HEART" => [HeartParticle::class, [9, "하트", "HART", "HE"]],
						"INK" => [InkParticle::class, [10, "잉크", "오징어", "먹물", "BLACK", "IN"]],
						"ITEMBREAK" => [ItemBreakParticle::class, [11, "브릭아이템", "아이템파괴", "아이템", "BREAKITEM", "DESTROYITEM", "ITEMDSTROY", "IT"]],
						"LAVADRIP" => [LavaDripParticle::class, [12, "라바드립", "용암드립", "마그마드립", "라바드롭", "용암드롭", "마그마드롭", "라바2", "용암2", "마그마2", "LAVADROP", "LAVA2", "MAGMA2"]],
						"LAVA" => [LavaParticle::class, [13, "라바", "용암", "마그마", "MAGMA", "LA"]],
						"MOBSPAWN" => [MobSpawnParticle::class, [14,"몹스폰", "몬스터스폰", "몹소환", "몬스터소환", "몹", "몬스터", "스폰", "소환", "MOB", "SPAWN", "MO"]],
						"PORTAL" => [PortalParticle::class, [15, "포탈", "엔더맨", "엔더", "ENDERMAN", "EMDER", "PURPLR", "PO"]],
						"REDSTONE" => [RedStoneParticle::class, [16, "레드스톤", "레드더스트", "REDDUST", "RED", "RE"]],
						"SMOKE" => [SmokeParticle::class, [17, "스모크", "가스", "연기", "GAS", "SM"]],
						"SPLASH" => [SplashParticle::class, [18, "스플래쉬", "SP"]],
						"SPORE" => [SporeParticle::class, [19, "스포어", "SPO"]],
						"TERRAIN" => [TerrainParticle::class, [20, "테레인", "블록파괴2", "블럭파괴2", "TRRAIN", "TE"]],
						"WATERDRIP" => [WaterDripParticle::class, [21, "워터드립", "물드립", "워터드롭", "물드롭", "워터2", "물2", "WATERDROP", "WATER2"]],
						"WATER" => [WaterParticle::class, [22, "워터", "물", "WA"]]
					];
					$sub[0] = isset($sub[0]) ? strtoupper($sub[0]) : "";
					foreach($lists as $particleName => $list){
						if(in_array($sub[0], $list[1])){
							$sub[0] = $particleName;
							break;
						}
					}
					if(isset($lists[$sub[0]]) && isset($sub[6]) && is_numeric($sub[1]) && is_numeric($sub[2]) && is_numeric($sub[3]) && is_numeric($sub[4]) && is_numeric($sub[5]) && is_numeric($sub[6])){
						if(!isset($sub[7]) || !is_numeric($sub[7]) || $sub[7] < 1) $sub[7] = 1;
		 				switch($sub[0]){
							case "DESTROYBLOCK": //Block
								$data = [Block::get(isset($sub[8]) && is_numeric($sub[8]) ? $sub[8] : 1, isset($sub[9]) && is_numeric($sub[9]) ? $sub[9] : 0)];
							case "TERRAIN":
							break;
							case "ITEMBREAK": //Item
								$data = [new Item(isset($sub[8]) && is_numeric($sub[8]) ? $sub[8] : 1, isset($sub[9]) && is_numeric($sub[9]) ? $sub[9] : 0)];
							break;
							case "MOBSPAWN": //Width, Height
								$data = [isset($sub[8]) && is_numeric($sub[8]) ? $sub[8] : 1, isset($sub[9]) && is_numeric($sub[9]) ? $sub[9] : 1];
							break;
							case "DUST":
								$data = [isset($sub[8]) && is_numeric($sub[8]) ? $sub[8] : 255, isset($sub[9]) && is_numeric($sub[9]) ? $sub[9] : 255 && isset($sub[10]) && is_numeric($sub[10]) ? $sub[10] : 255, isset($sub[11]) && is_numeric($sub[11]) ? $sub[11] : 255];
							break;
							default:
								$data = [isset($sub[8]) && is_numeric($sub[8]) ? $sub[8] : 1];
							break;
 						}
						$particle = new $lists[$sub[0]][0]($pos = new Vector3($sub[1], $sub[2], $sub[3]), ...$data);
						$level = $player->getLevel();
						$random = new Random((int) (microtime(true) * 1000) + mt_rand());
						for($i = 0; $i < $sub[7]; ++$i){
							$particle->setComponents(
								$pos->x + $random->nextSignedFloat() * ((float) $sub[4]),
								$pos->y + $random->nextSignedFloat() * ((float) $sub[5]),
								$pos->z + $random->nextSignedFloat() * ((float) $sub[6])
							);
							$level->addParticle($particle);
						}
					}
				break;
				case "addsound":
				case "sound":
					$lists = [
						"BAT" => [BatSound::class, [1, "배트", "박쥐", "BA", "B"]],
						"CLICK" => [ClickSound::class, [2, "클릭", "CL", "C"]],
						"DOOR" => [DoorSound::class, [3, "도어", "문", "DO", "D"]],
						"FIZZ" => [FizzSound::class, [4, "피즈", "치익", "연기", "FI", "F"]],
						"LAUNCH" => [LaunchSound::class, [5, "런치", "실행", "LA", "L"]]
					];
					$sub[0] = isset($sub[0]) ? strtoupper($sub[0]) : "";
					foreach($lists as $SoundName => $list){
						if(in_array($sub[0], $list[1])){
							$sub[0] = $SoundName;
							break;
						}
					}
					if(isset($lists[$sub[0]]) && isset($sub[3]) && is_numeric($sub[1]) && is_numeric($sub[2]) && is_numeric($sub[3])){
		 				$player->getLevel()->addSound(new $lists[$sub[0]][0]($player, 1), [$player]);//new Vector3($sub[1], $sub[2], $sub[3]), isset($sub[4]) && is_numeric($sub[4]) ? $sub[4] : 0));
					}
				break;
				case "addeffect":
				case "giveeffect":
				case "effect":
					if(!($effect = Effect::getEffectByName($sub[0] = isset($sub[0]) ? $sub[0] : "")) instanceof Effect){
						foreach([
							1 => ["SPEED", "SP", "FAST", "신속함", "신속"],
							2 => ["SLOWNESS", "SL", "SLOW", "구속함", "구속"],
							3 => ["SWIFTNESS", "SW", "SWIFT", "성급함", "성급"],
							4 => ["FATIGUE", "FA", "MINING_FATIGUE", "MI", "피로함", "피로"],
							5 => ["STRENGTH", "ST", "힘", "강화"],
							8 => ["JUMP", "JU", "점프강화", "점프", "가벼움"],
							9 => ["NAUSEA", "NA", "CONFUSION", "CO", "멀미함", "멀미", "어지러움", "메스꺼움"],
							10 => ["REGENERATION", "RE", "HEAL", "HEALING", "재생함", "재생", "회복함", "회복"],
							11 => ["DAMAGE_RESISTANCE", "DA", "DAMAGE", "저항함", "저항"],
							12 => ["FIRE_RESISTANCE", "FI", "FIRE", "화염저항", "화염"],
							13 => ["WATER_BREATHING", "WA", "BREATHING", "BREATH", "GRILL", "수중호흡", "수중", "호흡", "아가미"],
							14 => ["INVISIBILITY", "IN", "INVISIBLE", "투명화", "투명"],
							18 => ["WEAKNESS", "WE", "WEAK", "나약함", "나약", "허약함", "허약", "약함"],
							19 => ["POISON", "PO", "중독", "독", "감염"],
							20 => ["WITHER", "WI", "위더"],
							21 => ["HEALTH_BOOST", "HEALTH", "HE", "체력신장", "체력증가", "체력추가", "체력"]
						] as $effectID => $list){
							if(in_array($sub[0], $list)){
								$sub[0] = $effectID;
								break;
							}
						}
					}
					if(($effect = Effect::getEffect((int) $sub[0])) instanceof Effect){
						if($player->hasEffect($id = $effect->getID())){
							$oldEffect = $player->getEffect($id);
							$reflection = (new \ReflectionClass("\\pocketmine\\entity\\Entity"))->getProperty("effects");
							$reflection->setAccessible(true);
							$oldEffect->setDuration(isset($sub[1]) && is_numeric($sub[1]) ? $sub[1]*20 - 0.1 : 0)->setAmplifier(isset($sub[2]) && is_numeric($sub[2]) ? $sub[2] - 1 : 0)->add($player, true);
							$effects = $reflection->getValue($player);
							$effects[$oldEffect->getID()] = $oldEffect;
							$reflection->setValue($player, $effects);
						}else{
							$player->addEffect($effect->setDuration(isset($sub[1]) && is_numeric($sub[1]) ? $sub[1]*20 - 0.1 : 0)->setAmplifier(isset($sub[2]) && is_numeric($sub[2]) ? $sub[2] - 1 : 0));
						}
					}
				break;
				case "setcommandlog":
				case "setcl":
				case "setvalue":
					if(isset($sub[0]) && isset($sub[1]) && is_numeric($sub[1])){
						$this->setCl($sub[0], $sub[1], isset($sub[2]) ? $sub[2] : false);
					}
				break;
				default:
					$changeMode = $mode;
				break;
			}
			$command = str_replace($match, $change, $command);
			$mode = $changeMode;
		}
		$command = str_replace("%n", "\n", $command);
		switch($mode){
			case self::MODE_NORMAL:
				$event = new PlayerCommandPreprocessEvent($player, "/$command");
				$this->getServer()->getPluginManager()->callEvent($event);
				if(!$event->isCancelled()) $this->getServer()->dispatchCommand($player, substr($event->getMessage(), 1));
			break;
			case self::MODE_OP:
				if($notOp = (!$player->isOP())) $player->setOp(true);
				$event = new PlayerCommandPreprocessEvent($player, "/$command");
				$this->getServer()->getPluginManager()->callEvent($event);
				if(!$event->isCancelled()) $this->getServer()->dispatchCommand($player, substr($event->getMessage(), 1));
				if($notOp) $player->setOp(false);
			break;
			case self::MODE_CONSOLE:
				$event = new ServerCommandEvent(new ConsoleCommandSender(), $command);
				$this->getServer()->getPluginManager()->callEvent($event);
				if(!$event->isCancelled()) $this->getServer()->dispatchCommand(new ConsoleCommandSender(), $event->getCommand());
 			break;
			case self::MODE_MESSAGE:
				$player->sendMessage($command);
			break;
			case self::MODE_BROADCAST:
				$this->getServer()->broadcastMessage($command);
			break;
		}
		return true;
	}

	public function getMoney($player){
		if(!$this->money) return false;
		switch($this->money->getName()){
			case "PocketMoney":
			case "MassiveEconomy":
				return $this->money->getMoney($player);
			break;
			case "EconomyAPI":
				return $this->money->mymoney($player);
			break;
			case "Money":
				return $this->money->getMoney($player->getName());
			break;
			default:
				return false;
			break;
		}
	}

	public function giveMoney($player, $money){
		if(!$this->money) return false;
		switch($this->money->getName()){
			case "PocketMoney":
				$this->money->grantMoney($player, $money);
			break;
			case "EconomyAPI":
				$this->money->setMoney($player, $this->money->mymoney($player) + $money);
			break;
			case "MassiveEconomy":
				$this->money->setMoney($player, $this->money->getMoney($player) + $money);
			break;
			case "Money":
				$this->money->setMoney($name = $player->getName(), $this->money->getMoney($name) + $money);
			break;
			default:
				return false;
			break;
		}
		return true;
	}

	public function loadYml(){
		@mkdir($this->getDataFolder());
		$this->npc = (new Config($this->getDataFolder() . "RubyNPC.yml", Config::YAML))->getAll();
	}

	public function saveYml(){
		$npc = new Config($this->getDataFolder() . "RubyNPC.yml", Config::YAML);
		$npc->setAll($this->npc);
		$npc->save();
	}

	public function getCL($key, $playerKey = false){
		@mkdir($path = $this->getServer()->getPluginPath()."__CommandLog/");
		$cmdLog = (new Config($path. "CommandLog.yml", Config::YAML))->getAll();
		if($playerKey === false){
			return isset($cmdLog["Server"][$key]) ? $cmdLog["Server"][$key] : null;
		}else{
			return isset($cmdLog["Player"][$playerKey]) && isset($cmdLog["Player"][$playerKey][$key]) ? $cmdLog["Player"][$playerKey][$key] : null;
		}
	}

	public function setCL($key, $value, $playerKey = false){
		@mkdir($path = $this->getServer()->getPluginPath()."__CommandLog/");
		$cmdLog = (new Config($path. "CommandLog.yml", Config::YAML, ["Server" => [], "Players" => []]))->getAll();
		if($playerKey === false){
			$cmdLog["Server"][$key] = $value;
		}else{
			if(!isset($cmdLog["Player"][$playerKey])) $cmdLog["Player"][$playerKey] = [];
			$cmdLog["Player"][$playerKey][$key] = $value;
		}
		$cmdLog = new Config($this->getServer()->getPluginPath()."__CommandLog/CommandLog.yml", Config::YAML);
		$cmdLog>setAll($this->cmdLog);
		$cmdLog->save();
		return $value;
	}

	public function isKorean(){
		return strtolower($this->getServer()->getLanguage()->getName()) == "korean";
	}
}