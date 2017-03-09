<?php

namespace Login;

use pocketmine\Player;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat as Color;
use pocketmine\network\protocol\ContainerSetContentPacket;
use pocketmine\network\protocol\StartGamePacket;

class Login extends \pocketmine\plugin\PluginBase implements \pocketmine\event\Listener{
	public function onEnable(){
		$this->loadYml();
		$this->player = [];
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
		$this->getServer()->getScheduler()->scheduleRepeatingTask(new Task($this, [$this, "onTick"]), 5);
	}

	public function onCommand(\pocketmine\command\CommandSender $sender, \pocketmine\command\Command $cmd, $label, array $sub){
		$ik = $this->isKorean();
		if(!$sender instanceof Player && ($cmd = strtolower($cmd->getName())) !== "loginop"){
			$sender->sendMessage(Color::YELLOW ."[Login]" . ($ik ? "게임내에서만 사용가능합니다." : "Please run this command in-game"));
		}elseif(isset($sub[0]) && $sub[0] !== ""){
			switch($cmd){
				case "login":
					if($this->isLogin($sender)){
						$sender->sendMessage(Color::YELLOW ."[Login]" . ($ik ? "이미 로그인되었습니다." : "Already logined"));
					}else{
						$this->login($sender, $sub[0], false, isset($sub[1]) ? $sub[1] : "");
					}
				break;
				case "register":
					if($this->isRegister($sender)){
						$sender->sendMessage(Color::YELLOW ."[Login]" . ($ik ? "이미 가입되었습니다." : "Already registered"));
					}elseif(!isset($sub[1]) || $sub[1] == "" || $sub[0] !== $sub[1]){
						return false;
					}elseif(strlen($sub[0]) < 5){
						$sender->sendMessage(Color::YELLOW ."[Login]" . ($ik ? "비밀번호가 너무 짧습니다." : "Password is too short"));
						return false;
					}else{
						$this->register($sender, $sub[0]);
						if(!$sender->isOp()) $this->login($sender, $sub[0]);
					}
				break;
				case "loginop":
					if(!isset($sub[1]) || $sub[1] == "" || !isset($this->lg[strtolower($sub[1])])){
						$sender->sendMessage(Color::YELLOW ."[Login]" . ($ik ? "<플레이어명>을 확인해주세요." : "Please check <PlayerName>"));
						return false;
					}else{
						$sub[1] = strtolower($sub[1]);
						$pass = $this->lg[strtolower($sub[1])]["PW"];
						switch(strtolower($sub[0])){
							case "unregister":
							case "ur":
							case "u":
							case "탈퇴":
								unset($this->lg[$sub[1]]);
								$sender->sendMessage(Color::YELLOW ."[Login]" . ($ik ? "$sub[1] 님의 비밀번호을 제거합니다." : "Delete $sub[1] 's password"));
							break;
							case "change":
							case "c":
								if(!isset($sub[2]) || $sub[2] == ""){
									$sender->sendMessage(Color::YELLOW ."[Login]" . ($ik ? "<플레이어명>을 확인해주세요." : "Please check <PlayerName>"));
									return false;
								}else{
									$this->lg[$sub[1]]["PW"] = hash("sha256", $sub[2]);
									$sender->sendMessage(Color::YELLOW ."[Login]" . $sub[1] . ($ik ? "님의 비밀번호를 바꿨습니다. : " : "'s Password is changed : ") . "$sub[2]");
								}
							break;
						}
					}
					$this->saveYml();
				break;
				default:
					return false;
				break;
			}
		}else return false;
		return true;
	}

	public function onPlayerPreLogin(\pocketmine\event\player\PlayerPreLoginEvent $event){
		if($this->isLogin($player = $event->getPlayer(), true)){
			$event->setKickMessage(Color::RED . "Already Login this Id");
			$event->setCancelled();
		}else{
			$cid = $player->getClientId();
			$name = strtolower($player->getName());
			foreach($this->lg as $key => $lg){
				if(isset($lg["CID"]) && $lg["CID"] == $cid && $key != $name){
					$event->setKickMessage(Color::RED . "Your Already Register.\n" . Color::GOLD . "     On ". Color::YELLOW . $key);
					$event->setCancelled();
					break;
				}
			}
		}
	}

	public function onPlayerJoin(\pocketmine\event\player\PlayerJoinEvent $event){
		if(!$this->isLogin($player = $event->getPlayer())){
			$this->sendLogin($player, true);
/*
			$pk = new StartGamePacket();
			$pk->seed = -1;
			$pk->x = $player->x;
			$pk->y = $player->y;
			$pk->z = $player->z;
			$spawnPosition = $player->getSpawn();
			$pk->spawnX = (int) $spawnPosition->x;
			$pk->spawnY = (int) $spawnPosition->y;
			$pk->spawnZ = (int) $spawnPosition->z;
			$pk->generator = 1;
			$pk->gamemode = $player->getGamemode() & 0x01;
			$pk->eid = $player->getId();
			$player->dataPacket($pk);
 			$pk = new ContainerSetContentPacket();
			$pk->windowid = ContainerSetContentPacket::SPECIAL_CREATIVE;
			$player->dataPacket($pk);
*/
 		}
	}

	public function onPlayerQuit(\pocketmine\event\player\PlayerQuitEvent $event){
		$this->unLogin($event->getPlayer());
	}

	public function onPlayerCommandPreprocess(\pocketmine\event\player\PlayerCommandPreprocessEvent $event){
		if(!$this->isLogin($player = $event->getPlayer()) && !in_array(strtolower(explode(" ", substr($event->getMessage(), 1))[0]), ["register", "login"])) $event->setCancelled($this->sendLogin($player));
	}

	public function onPlayerInteract(\pocketmine\event\player\PlayerInteractEvent $event){
		if(!$this->isLogin($event->getPlayer())) $event->setCancelled();
	}

	public function onBlockBreak(\pocketmine\event\block\BlockBreakEvent $event){
		if(!$this->isLogin($event->getPlayer())) $event->setCancelled();
	}

	public function onBlockPlace(\pocketmine\event\block\BlockPlaceEvent $event){
		if(!$this->isLogin($event->getPlayer())) $event->setCancelled();
	}

	public function onPlayerDropItem(\pocketmine\event\player\PlayerDropItemEvent $event){
		if(!$this->isLogin($event->getPlayer())) $event->setCancelled();
	}

	public function onPlayerItemConsume(\pocketmine\event\player\PlayerItemConsumeEvent $event){
		if(!$this->isLogin($event->getPlayer())) $event->setCancelled();
	}

	public function onEntityDamage(\pocketmine\event\entity\EntityDamageEvent $event){
		if($event->getEntity() instanceof Player && !$this->isLogin($event->getEntity())) $event->setCancelled();
	}

	public function onPlayerMove(\pocketmine\event\player\PlayerMoveEvent $event){
		if(!$this->isLogin($event->getPlayer())) $event->setCancelled();
	}		

	public function onTick(){
		$n = "\n" . str_repeat(" ", 50);
		$first = $n . ($line = Color::DARK_RED . str_repeat("-=", 15));
		$second = "$line$n$n$n$n$n$n";
		foreach($this->getServer()->getOnlinePlayers() as $player){
			if(!$this->isLogin($player)){
				$player->sendMessage($first);
				$this->sendLogin($player);
				$player->sendMessage($second);
			}
		}
	}

	public function register($player, $pw){
		$player->sendMessage(Color::YELLOW . "[Login] " . ($this->isKorean() ? "가입 완료" : "Register to complete"));
		$this->lg[strtolower($player->getName())] = ["PW" => hash("sha256", $pw), "IP" => $player->getAddress(), "Client" => $player->getClientId()];
		$this->saveYml();
	}

	public function isRegister(Player $player){
		return isset($this->lg[strtolower($player->getName())]);
	}

	public function login(Player $player, $pw = "", $auto = false, $opw = ""){
		if($this->isLogin($player, true)) return;
		$ik = $this->isKorean();
		if(!isset($this->lg[$name = strtolower($player->getName())])){
			$player->sendMessage(Color::GOLD . "[Login]" . ($ik ? "당신은 가입되지 않았습니다.\n/Register <비밀번호> <비밀번호>" : "You are not registered.\n/Register <Password> <Password>"));
			return false;
		}
		if($pw) $pw = hash("sha256", $pw);
		if(!$auto){
			if($pw !== $this->lg[$name]["PW"]){
				$player->sendMessage(Color::RED . "[Login] " . ($ik ? "로그인 실패" : "Login to failed"));
				return false;
			}
			if($player->isOp()){
				$op = (new Config($this->getDataFolder() . "! Login-OP.yml", Config::YAML, ["Op" => false, "PW" => "op"]))->getAll();
				if($op["Op"] && $op["PW"] !== $opw){
					$player->sendMessage(Color::RED . "[Login] " . ($ik ? "로그인 실패" : "Login to failed" . Color::RED . "/Login " . ($ik ? "<비밀번호> <오피비밀번호>" : "<Password> <OP PassWord>")));
					return true;
				}
			}
		}
		$this->player[$name] = true;
		$this->lg[$name]["IP"] = $player->getAddress();
/*
		$pk = new StartGamePacket();
		$pk->seed = -1;
		$pk->x = $this->x;
		$pk->y = $this->y;
		$pk->z = $this->z;
		$spawnPosition = $player->getSpawn();
		$pk->spawnX = (int) $spawnPosition->x;
		$pk->spawnY = (int) $spawnPosition->y;
		$pk->spawnZ = (int) $spawnPosition->z;
		$pk->generator = 1;
		$pk->gamemode = $player->getGamemode() & 0x01;
		$pk->eid = $player->getId();
		$player->dataPacket($pk);
		$player->getInventory()->sendContents($player);
		$player->getInventory()->sendArmorContents($player);
*/
		$player->sendMessage(Color::GREEN . "[Login] " . ($auto ? ($ik ? "자동" : "Auto") : "") . ($ik ? "로그인 완료" : "Login to complete"));
		$this->saveYml();
		return true;
	}

	public function isLogin($player, $isLogin = false){
		if($player instanceof Player && isset($this->lg[$name = strtolower($player->getName())])){
			if(isset($this->player[$name])){
				return true;
			}elseif(!$isLogin){
				if(!isset($this->lg[$name]["CID"])) $this->lg[$name]["CID"] = $player->getClientId();
				if($this->lg[$name]["CID"] == $player->getClientId() || $this->lg[$name]["IP"] == $player->getAddress()){
					$this->login($player, "", true);
					return true;
				}
			}
		}
		return false;
	}

	public function unLogin($player){
		unset($this->player[strtolower($player->getName())]);
	}

	public function sendLogin(Player $player){
		$ik = $this->isKorean();
		if(!$this->isLogin($player)){
			if(!isset($this->lg[$name = strtolower($player->getName())])){
				$player->sendMessage(Color::RED ."[Login]" . ($ik ? "당신은 가입되지 않았습니다.\n" . Color::RED . "      /Register <비밀번호> <비밀번호>" : "You are not registered.\n" . Color::RED . "     /Register <Password> <Password>"));
			}else{
				$player->sendMessage(Color::RED ."[Login]" . ($ik ? "당신은 로그인하지 않았습니다.\n" . Color::RED .  "     /Login <비밀번호>" : "You are not logined.\n" . Color::RED . "     /Login <Password>"));
			}
		}
	}

	public function loadYml(){
		@mkdir($this->getDataFolder());
		$this->lg = (new Config($this->getDataFolder() . "Login.yml", Config::YAML))->getAll();
	}

	public function saveYml(){
		ksort($this->lg);
		$lg = new Config($this->getDataFolder() . "Login.yml", Config::YAML);
		$lg->setAll($this->lg);
		$lg->save();
	}

	public function isKorean(){
		return strtolower($this->getServer()->getLanguage()->getName()) == "korean";
	}
}