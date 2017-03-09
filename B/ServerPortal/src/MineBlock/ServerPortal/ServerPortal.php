<?php

namespace MineBlock\ServerPortal;

use pocketmine\block\Block;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\event\Listener;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\network\protocol\DataPacket;
use pocketmine\network\protocol\AddPlayerPacket;
use pocketmine\network\protocol\RemovePlayerPacket;
use pocketmine\network\protocol\MovePlayerPacket;
use pocketmine\network\protocol\PlayerArmorEquipmentPacket;
use pocketmine\network\protocol\Info as ProtocolInfo;
use pocketmine\item\Item;
use pocketmine\entity\Entity;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat as Color;
use pocketmine\nbt\NBT;
use pocketmine\nbt\tag\Compound;
use pocketmine\nbt\tag\String;
use pocketmine\nbt\tag\Int;
use pocketmine\nbt\tag\Double;

class ServerPortal extends PluginBase implements Listener{
	public function onEnable(){
		$this->tap = [];
		$this->players = [];
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
		$this->armorTable = [Item::LEATHER_CAP => 0, Item::LEATHER_TUNIC => 1, Item::LEATHER_PANTS => 2, Item::LEATHER_BOOTS => 3, Item::CHAIN_HELMET => 0, Item::CHAIN_CHESTPLATE => 1, Item::CHAIN_LEGGINGS => 2, Item::CHAIN_BOOTS => 3, Item::GOLD_HELMET => 0, Item::GOLD_CHESTPLATE => 1, Item::GOLD_LEGGINGS => 2, Item::GOLD_BOOTS => 3, Item::IRON_HELMET => 0, Item::IRON_CHESTPLATE => 1, Item::IRON_LEGGINGS => 2, Item::IRON_BOOTS => 3, Item::DIAMOND_HELMET => 0, Item::DIAMOND_CHESTPLATE => 1, Item::DIAMOND_LEGGINGS => 2, Item::DIAMOND_BOOTS => 3];
		foreach($this->armorTable as $id => $slot){
			Item::addCreativeItem(Item::get($id, 0));
		}
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
		$this->getServer()->getScheduler()->scheduleRepeatingTask(new Task($this, [$this,"onTick"]), 4);
	}
	
	public function onDisable(){
		$this->getServer()->getScheduler()->cancelTasks($this);
	}

	public function onCommand(CommandSender $sender, Command $cmd, $label, array $sub){
		$n = $sender->getName();
		if(!isset($sub[0])) return false;
		$sp = $this->sp;
		$rm = "Usage: /ServerPortal ";
		$mm = "[ServerPortal] ";
		$ik = $this->isKorean();
		switch(strtolower($sub[0])){
			case "add":
			case "a":
			case "추가":
				if(!$sender instanceof Player){
					$r = $mm . ($ik ? "게임내에서만 사용 가능합니다." : "Please run this command in game");
				}elseif(!isset($sub[2]) || $sub[1] == "" || $sub[2] == ""){
					$r = $rm . "Add(A) " . ($ik ? "<이름> <IP> (Port)" : "<Name> <IP> (Port)");
				}else{
					if(!isset($sub[3])) $sub[3] = 19132;
					elseif(!is_numeric($sub[3])) $r = $rm . "Add(A) " . ($ik ? "<이름> <IP> (포트)" : "<Name> <IP> (Port)");
					elseif(isset($sp[strtolower($sub[1])])) $r = $mm . $sub[1] . ($ik ? "는 이미 존재합니다." : " is already");
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
				elseif(!isset($sp[strtolower($sub[1])])) $r = $mm . $sub[1] . ($ik ? "는 존재하지않습니다." : "is invaild name");
				else{
					$this->removePortal($sub[1]);
					$r = $mm . ($ik ? $sub[1]."를 제거했습니다." : "Delete the ".$sub[1]);
				}
			break;
			case "reset":
			case "r":
			case "리셋":
			case "초기화":
				foreach($sp as $name => $portal){
					$this->removePortal($name);
				}
				$sp = [];
				$change = true;
				$r = $mm . ($ik ? " 리셋됨." : " Reset");
			break;
			case "set":
			case "s":
			case "설정":
				if(!isset($sub[2]) || $sub[1] == "") $r = $rm . "Set(S) " . ($ik ? "<포탈명>" : "<PortalName>") . " <Name(N) | Message(M) | Ip | Port(P) | Position(Pos) | Skin(S) | Armor(A) | Item(I) | Server(SV)>";
				elseif(!isset($this->sp[$sub[1] = strtolower($sub[1])])) $r = $mm . $sub[1] . ($ik ? "는 존재하지않습니다." : "is invaild name");
				else{
					$rm = $rm . "Set(S) " . ($ik ? "<포탈명>" : "<PortalName>");
					switch(strtolower($sub[2])){
						case "name":
						case "n":
						case "이름":
							if(!isset($sub[3]) || $sub[3] == "") $r = $rm . ($ik ? "<포탈명>" : "<PortalName>");
							else{
								$sp[$sub[1]]["Name"] = $name = implode(" ", array_splice($sub,3));
								$r = $mm . ($ik ? "포탈의 이름을 $name".Color::RESET."으로 변경하였습니다." : "Portal name is set to $name");
							}
						break;
						case "message":
						case "m":
						case "메세지":
							if(!isset($sub[3]) || $sub[3] == "") $r = $rm . ($ik ? "<메세지>" : "<Message>");
							else{
								$sp[$sub[1]]["Message"] = $message = implode(" ", array_splice($sub,3));
								$r = $mm . ($ik ? "포탈의 메세지를 $message".Color::RESET."으로 변경하였습니다." : "Portal message is set to $message");
							}
						break;
						case "ip":
						case "아이피":
							if(!isset($sub[3]) || $sub[3] == "") $r = $rm . " <IP>";
							else{
								if(preg_match("/^[0-9]{1,3}\\.[0-9]{1,3}\\.[0-9]{1,3}\\.[0-9]{1,3}$/", $sub[3]) > 0){
									$ip = $sub[3];
								}else{
						 			$ip = gethostbyname($sub[4] = strtolower($sub[3]));
						 			if($sub[3] == $ip){
						 				$sender->sendMessage("$mm $sub[3] is invalid IP");
						 				return true;
						 			}
						 		}
								$sp[$sub[1]]["Ip"] = $ip;
								$r = $mm . ($ik ? "포탈의 Ip를 $ip 으로 변경하였습니다." : "Portal Ip is set to $ip");
							}
						break;
						case "port":
						case "p":
						case "포트":
							if(!isset($sub[3]) || !is_numeric($sub[3])) $r = $rm . " <Port>";
							else{
								$sp[$sub[1]]["Port"] = $sub[3];
								$r = $mm . ($ik ? "포탈의 포트를 $sub[3] 으로 변경하였습니다." : "Portal Port is set to $sub[3]");								
							}
						break;
						case "skin":
						case "s":
						case "스킨":
							if(!$sender instanceof Player) $r = $mm . ($ik ? "게임내에서만 사용 가능합니다." : "Please run this command in game");
							else{
								$sp[$sub[1]]["Skin"] = base64_encode(isset($this->skins[$playerName = $sender->getName()]) ? $this->skins[$playerName] : $sender->getSkinData());
								$r = $mm . ($ik ? "포탈의 스킨을 변경하였습니다." : "Portal's skin is changed");
							}
						break;
						case "position":
						case "pos":
						case "위치":
						case "좌표":
							if(!$sender instanceof Player) $r = $mm . ($ik ? "게임내에서만 사용 가능합니다." : "Please run this command in game");
							else{
								$sp[$sub[1]]["Position"]["X"] = $sender->x;
								$sp[$sub[1]]["Position"]["Y"] = $sender->y;
								$sp[$sub[1]]["Position"]["Z"] = $sender->z;
								$sp[$sub[1]]["Position"]["Level"] = strtolower($sender->getLevel()->getFolderName());
								$sp[$sub[1]]["Rotation"]["Yaw"] = $sender->yaw;
								$sp[$sub[1]]["Rotation"]["Pitch"] = $sender->pitch;
								$r = $mm . ($ik ? "포탈의 좌표을 변경하였습니다." : "Portal's position is changed");
							}
						break;
						case "armor":
						case "a":
						case "갑롯":
						case "장비":
							if(!$sender instanceof Player) $r = $mm . ($ik ? "게임내에서만 사용 가능합니다." : "Please run this command in game");
							else{
								$inven = $sender->getInventory();
								$sp[$sub[1]]["Armor"]["Helmet"] = $inven->getHelmet()->getID();
								$sp[$sub[1]]["Armor"]["ChestPlate"] = $inven->getChestplate()->getID();
								$sp[$sub[1]]["Armor"]["Leggings"] = $inven->getLeggings()->getID();
								$sp[$sub[1]]["Armor"]["Boots"] = $inven->getBoots()->getID();
								$r = $mm . ($ik ? "포탈의 갑옷을 변경하였습니다." : "Portal's armor is changed");
							}
						break;
						case "item":
						case "i":
						case "아이템":
							if(!$sender instanceof Player) $r = $mm . ($ik ? "게임내에서만 사용 가능합니다." : "Please run this command in game");
							else{
								$inven = $sender->getInventory();
								$sp[$sub[1]]["Item"]["Id"] = $inven->getItemInHand()->getID();
								$sp[$sub[1]]["Item"]["Damage"] = $inven->getItemInHand()->getDamage();
								$r = $mm . ($ik ? "포탈의 아이템을 변경하였습니다." : "Portal's item is changed");
							}
						break;
						case "server":
						case "sv":
						case "서버":
							$sp[$sub[1]]["Server"] = !$sp[$sub[1]]["Server"];
							$r = $mm . ($ik ? "포탈을 ".($sp[$sub[1]]["Server"] ? "활성화" : "비활성화")."했습니다." : "Portal is ".($sp[$sub[1]]["Server"] ? "On" : "Off"));
						break;
						case "sleep":
						case "sl":
						case "잠":
							$sp[$sub[1]]["Sleep"] = !$sp[$sub[1]]["Sleep"];
							$r = $mm . ($ik ? "포탈을 ".($sp[$sub[1]]["Sleep"] ? "활성화" : "비활성화")."했습니다." : "Portal is ".($sp[$sub[1]]["Server"] ? "On" : "Off"));
						break;
					}
					if(!isset($r)){
						$sender->sendMessage($rm . "Set(S) " . ($ik ? "<포탈명>" : "<PortalName>") . " <Name(N) | Message(M) | Ip | Port(P) | Position(Pos) | Skin(S) | Armor(A) | Item(I) | Server(SV)>");
						return true;
					}elseif(isset($this->sp[$sub[1]])){
						foreach($this->players[$sub[1]] as $player){
							$this->RemovePlayerPacket->eid = $this->RemovePlayerPacket->clientID = $this->sp[$sub[1]]["Eid"];
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
				foreach($sp as $name => $portal){
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
		if($this->sp !== $sp && isset($change)){
			$this->sp = $sp;
			$this->saveYml();
		}
		return true;
	}

	public function onTick(){
		foreach($this->sp as $name => $portal){
			$players = isset($this->players[$name]) ? [] : $this->players[$name] = [];
			$PlayerArmorEquipmentPacket = new PlayerArmorEquipmentPacket();
			$this->AddPlayerPacket->eid = $this->AddPlayerPacket->clientID = $this->RemovePlayerPacket->eid = $this->RemovePlayerPacket->clientID = $this->MovePlayerPacket->eid = $PlayerArmorEquipmentPacket->eid = $portal["Eid"];
			$this->AddPlayerPacket->username = str_replace("%n", "\n", $portal["Name"]);
			$this->AddPlayerPacket->yaw = $portal["Rotation"]["Yaw"];
			$this->AddPlayerPacket->pitch = $portal["Rotation"]["Pitch"];
			$this->AddPlayerPacket->item = $portal["Item"]["Id"];
			$this->AddPlayerPacket->meta = $portal["Item"]["Damage"];
			$this->AddPlayerPacket->skin = base64_decode($portal["Skin"]); // $this->skins->{$name};
			if(!isset($portal["Sleep"])) $portal["Sleep"] = $this->sp[$name]["Sleep"] = false;
			$this->AddPlayerPacket->metadata[Player::DATA_PLAYER_FLAGS] = [Entity::DATA_TYPE_BYTE, $portal["Sleep"] << Player::DATA_PLAYER_FLAG_SLEEP];
			$this->AddPlayerPacket->x = $this->MovePlayerPacket->x = $x = $portal["Position"]["X"];
			$this->AddPlayerPacket->y = $this->MovePlayerPacket->y = $y = $portal["Position"]["Y"] + ($portal["Sleep"] ? 0.3 : 1.62);
			$this->AddPlayerPacket->z = $this->MovePlayerPacket->z = $z = $portal["Position"]["Z"];
			$this->MovePlayerPacket->mode = $portal["Sleep"];
			$PlayerArmorEquipmentPacket->slots = [$portal["Armor"]["Helmet"], $portal["Armor"]["ChestPlate"], $portal["Armor"]["Leggings"], $portal["Armor"]["Boots"]];
			foreach($this->getServer()->getOnlinePlayers() as $player){
				if(!$player->spawned || $portal["Position"]["Level"] !== strtolower($player->getLevel()->getFolderName()) || ($distance = sqrt(pow($dX = $x - $player->x, 2) + pow($y - $player->y, 2) + pow($dZ = $z - $player->z, 2))) > 30){
					if(isset($this->players[$name][$player->getName()])){
						$player->dataPacket($this->RemovePlayerPacket);
					}
				}else{
					if(!isset($this->players[$name][$playerName = $player->getName()])){
						$player->dataPacket($this->AddPlayerPacket);
						$player->dataPacket($PlayerArmorEquipmentPacket);
 	 				}
					$players[$playerName] = $player;
					if($portal["Sleep"] || $distance > 15){
						$this->MovePlayerPacket->yaw= $this->MovePlayerPacket->bodyYaw = $portal["Rotation"]["Yaw"];
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
				$this->skins[Color::clean($packet->username)] = $packet->skin;
 			break;
 			case ProtocolInfo::INTERACT_PACKET:
  			foreach($this->sp as $name => $portal){
					if($portal["Eid"] == $packet->target){
						if(!isset($this->tap[$player->getName()])){
							if($portal["Server"] === true){
								$ServerTPPacket = new ServerTPPacket();
								$ServerTPPacket->address = $portal["Ip"];
								$ServerTPPacket->port = $portal["Port"];
								$player->dataPacket($ServerTPPacket);
							}
						}else{
							$player->sendMessage(str_replace(["%n", "%player", "%p"], ["\n", $player->getName(), $player->getName()], $portal["Message"]));
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

	public function onPlayerInteract(PlayerInteractEvent $event){
		if($event->getFace() == 255){
			$p = $event->getPlayer();
			$i = $event->getItem();
			if($p->isCreative() && isset($this->armorTable[$id = $i->getID()])){
				$inv = $p->getInventory();
				$inv->setArmorItem($this->armorTable[$id], $i, $p);
				$inv->sendArmorContents($p);
			}
		}
	}

	public function addPortal($player, $name, $ip, $port){
 		$inven = $player->getInventory();
		$this->sp[strtolower($name)] = [
			"Name" => $name,
			"Ip" => $ip,
 			"Port" => $port,
			"Eid" => bcadd("1095216660480", mt_rand(0, 0x7fffffff)),
			"Server" => true,
			"Sleep" => false,
			"Message" => Color::GREEN."Do you want teleport to $name server?".Color::RESET."%n".Color::DARK_GREEN."Please push my body!",
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
//		$this->skins->{$name} = new String($name, $player->getSkinData());
	}

	public function removePortal($name){
		if(isset($this->sp[$name = strtolower($name)])){
			if(isset($this->players[$name])){
				foreach($this->players[$name] as $player){
					$this->RemovePlayerPacket->eid = $this->RemovePlayerPacket->clientID = $this->sp[$name]["Eid"];
					$player->dataPacket($this->RemovePlayerPacket);
				}
				unset($this->players[$name]);
			}
		}
 		unset($this->sp[$name]);
		$this->saveYml();
 	}

	public function loadYml(){
		@mkdir($this->getDataFolder());
		$this->sp = (new Config($this->getDataFolder() . "ServerPortal.yml", Config::YAML))->getAll();
/*		if(!file_exists($this->getDataFolder() . "Skins.dat")){
			$nbt = new NBT(NBT::BIG_ENDIAN);
			$nbt->setData(new Compound("Skin", []));
			file_put_contents($this->getDataFolder() . "Skins.dat", $nbt->writeCompressed());
		}
		$nbt = new NBT(NBT::BIG_ENDIAN);
		$nbt->readCompressed(file_get_contents($this->getDataFolder() . "Skins.dat"));
		$this->skins = $nbt->getData();
*/
	}

	public function saveYml(){
		$sp = new Config($this->getDataFolder() . "ServerPortal.yml", Config::YAML);
		$sp->setAll($this->sp);
		$sp->save();
/*
		$nbt = new NBT(NBT::BIG_ENDIAN);
		$nbt->setData(new Compound("Skin", $this->skins));
		file_put_contents($this->getDataFolder() . "Skins.dat", $nbt->writeCompressed());
*/
	}

	public function isKorean(){
		return strtolower($this->getServer()->getLanguage()->getName()) == "korean";
	}
}

class ServerTPPacket extends DataPacket{
	public $address;
	public $port = 19132;

	public function pid(){
		return 0x1b;
	}

	public function decode(){}

	public function encode(){
		$this->reset();
		$this->putByte(4);
		foreach(explode(".", $this->address) as $b) $this->putByte((~((int) $b)) & 0xff);
		$this->putShort($this->port);
	}
}