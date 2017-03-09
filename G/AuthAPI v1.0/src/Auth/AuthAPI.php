<?php

namespace Auth;

use pocketmine\Player;
use pocketmine\utils\TextFormat as Color;

class AuthAPI extends \pocketmine\plugin\PluginBase implements \pocketmine\event\Listener{	
	const PLAYERS = 0;
	const SETTINGS = 1;

	public function onLoad(){
		$this->players = [];
	}

	public function onEnable(){
		$this->loadData();
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
		$this->getServer()->getScheduler()->scheduleRepeatingTask(new Task($this, [$this, "onTick"]), 5);
	}

	public function onCommand(\pocketmine\command\CommandSender $sender, \pocketmine\command\Command $cmd, $label, array $sub){
		$ik = $this->isKorean();
		if(!$sender instanceof Player && ($cmd = strtolower($cmd->getName())) !== "auth"){
			$r = Color::RED . "[Auth] " . ($ik ? "게임내에서만 사용가능합니다. " : "Please run this command in-game");
		}elseif(!isset($sub[0]) || $sub[0] == ""){
			return false;
		}else{
			$name = $sender->getName();
			switch($cmd){
				case "login":
					if($this->isLogin($sender)){
						$r = Color::RED . "[Auth] " . ($ik ? "당신은 이미 로그인되었습니다." : "You are already logined");
					}elseif(!$this->isRegister($name)){
						$r = Color::RED . "[Auth] " . ($ik ? "당신은 가입되지 않았습니다." : "You are not registered");
					}elseif(!$this->isCorrectPassword($name, $sub[0])){
						$r = Color::RED . "[Auth] " . ($ik ? "비밀번호가 틀렸습니다." : "You are not registered");
					}else{
						$this->login($sender);
						$r = Color::YELLOW . "[Auth] " . ($ik ? "당신은 로그인이 완료되었습니다." : "You are login to complete");
					}
				break;
				case "register":
					if($this->isRegister($sender)){
						$r = Color::RED . "[Auth] " . ($ik ? "당신은 이미 가입되었습니다. " : "You are already registered");
					}elseif(!isset($sub[1]) || $sub[1] == "" || $sub[0] !== $sub[1]){
						return false;
					}elseif(strlen($sub[0]) < $this->data[self::SETTINGS]["Password_Minimum_Length"]){
						$r = Color::RED . "[Auth] " . ($ik ? "비밀번호가 너무 짧습니다. " : "Password is too short");
					}else{
						$this->register($name, $sub[0]);
						$this->login($sender);
						$r = Color::YELLOW . "[Auth] " . ($ik ? "당신은 가입이 완료되었습니다." : "You are register to complete");
					}
				break;
				case "change":
					if(!isset($sub[1]) || $sub[1] == "" || $sub[0] !== $sub[1]){
						return false;
					}elseif(strlen($sub[0]) < $this->data[self::SETTINGS]["Password_Minimum_Length"]){
						$r = Color::RED . "[Auth] " . ($ik ? "비밀번호가 너무 짧습니다. " : "Password is too short");
					}else{
						$this->register($name, $sub[0]);
						$r = Color::YELLOW . "[Auth] " . ($ik ? "당신의 비밀번호가 변경되었습니다." : "Your password is changed");
					}
				break;
				case "auth":
					switch(strtolower($sub[0])){
						case "unregister":
						case "u":
						case "삭제":
							if(!isset($sub[1]) || $sub[1] == ""){
								$r = Color::RED . "Usage: /Auth Unregister(U) " . ($ik ? "<플레이어명>" : "<PlayerName>");
							}elseif(!$this->isRegister($sub[1])){
								$r = Color::RED . "[Auth] $sub[1]" . ($ik ? "는 잘못된 플레이어명입니다." : " is invalid player");
							}else{
								$this->unregister($sub[1]);
								$r = Color::YELLOW . "[Auth] $sub[1]" . ($ik ? "님의 계정이 제거되었습니다. " : "'s account is deleted");
							}
						break;
						case "length":
						case "l":
						case "길이":
							if(!isset($sub[1]) || !is_numeric($sub[0])){
								$r = Color::RED . "Usage: /Auth Length(L) " . ($ik ? "<길이>" : "<Length>");
							}else{
								$this->data[self::SETTINGS]["Password_Minimum_Length"] = floor($sub[1]);
								$r = Color::YELLOW . "[Auth] " . ($ik ? "비밀번호 최소 길이가 $sub[1]로 변경되었습니다." : "Password mininum length is change to $sub[1]");
							}
						break;
					}
				break;
			}
		}
		if(isset($r)){
			$sender->sendMessage($r);
		}
		return true;
	}

	public function onPlayerPreLogin(\pocketmine\event\player\PlayerPreLoginEvent $event){
		$ik = $this->isKorean();
		if($this->isLogin($player = $event->getPlayer(), true)){
			$address = $player->getAddress();
			foreach($this->getServer()->getOnlinePlayers() as $onlinePlayer){
				if($onlinePlayer !== $player && strtolower($onlinePlayer->getName()) === strtolower($player->getName()) && $onlinePlayer->getAddress() === $address){
					$onlinePlayer->close($reason = Color::RED . ($ik ? "새로운 접속" : "New connections"), $reason);
					$address = false;
					break;
				}
			}
			if($address){
				$event->setKickMessage(Color::RED . ($ik ? "이미 접속중인 ID입니다." : "Already Login this Id"));
				$event->setCancelled();
			}
		}
	}

	public function onPlayerQuit(\pocketmine\event\player\PlayerQuitEvent $event){
		$this->unLogin($event->getPlayer());
	}

	public function onPlayerCommandPreprocess(\pocketmine\event\player\PlayerCommandPreprocessEvent $event){
		if(!$this->isLogin($player = $event->getPlayer()) && !in_array(strtolower(explode(" ", substr($event->getMessage(), 1))[0]), ["register", "login"])) $event->setCancelled();
	}

	public function onPlayerInteract(\pocketmine\event\player\PlayerInteractEvent $event){
		if(!$this->isLogin($event->getPlayer())) $event->setCancelled();
	}

	public function onPlayerDropItem(\pocketmine\event\player\PlayerDropItemEvent $event){
		if(!$this->isLogin($event->getPlayer())) $event->setCancelled();
	}

	public function onPlayerItemConsume(\pocketmine\event\player\PlayerItemConsumeEvent $event){
		if(!$this->isLogin($event->getPlayer())) $event->setCancelled();
	}

	public function onPlayerItemHeld(\pocketmine\event\player\PlayerItemHeldEvent $event){
		if(!$this->isLogin($event->getPlayer())) $event->setCancelled();
	}

	public function onPlayerMove(\pocketmine\event\player\PlayerMoveEvent $event){
		if(!$this->isLogin($event->getPlayer())) $event->setCancelled();
	}		

	public function onBlockBreak(\pocketmine\event\block\BlockBreakEvent $event){
		if(!$this->isLogin($event->getPlayer())) $event->setCancelled();
	}

	public function onBlockPlace(\pocketmine\event\block\BlockPlaceEvent $event){
		if(!$this->isLogin($event->getPlayer())) $event->setCancelled();
	}

	public function onEntityDamage(\pocketmine\event\entity\EntityDamageEvent $event){
		if(($player = $event->getEntity()) instanceof Player && !$this->isLogin($player)) $event->setCancelled();
	}

	public function onEntityRegainHealth(\pocketmine\event\entity\EntityRegainHealthEvent $event){
		if(($player = $event->getEntity()) instanceof Player && !$this->isLogin($player)) $event->setCancelled();
	}

	public function onEntityShootBow(\pocketmine\event\entity\EntityShootBowEvent $event){
		if(($player = $event->getEntity()) instanceof Player && !$this->isLogin($player)) $event->setCancelled();
	}

	public function onCraftItem(\pocketmine\event\inventory\CraftItemEvent $event){
		if(($player = $event->getEntity()) instanceof Player && !$this->isLogin($player)) $event->setCancelled();
	}

	public function onInventoryOpen(\pocketmine\event\inventory\InventoryOpenEvent $event){
		if(($player = $event->getPlayer()) instanceof Player && !$this->isLogin($player)) $event->setCancelled();
	}

	public function onInventoryPickupArrow(\pocketmine\event\inventory\InventoryPickupArrowEvent $event){
		if(($player = $event->getInventory()->getHolder()) instanceof Player && !$this->isLogin($player)) $event->setCancelled();
	}

	public function onInventoryPickupItem(\pocketmine\event\inventory\InventoryPickupItemEvent $event){
		if(($player = $event->getInventory()->getHolder()) instanceof Player && !$this->isLogin($player)) $event->setCancelled();
	}

 	public function onTick(){
		$ik = $this->isKorean();
		foreach($this->getServer()->getOnlinePlayers() as $player){
			if(!$this->isRegister($player->getName())){
				$player->sendTip($message = Color::RED . ($ik ? "당신은 가입되지 않았습니다." : "You are not registered") . "\n" . Color::DARK_RED . "/Register " . ($ik ? "<비밀번호> <비밀번호>" : "<Password> <Password>"));
				$player->sendPopup($message);
			}elseif(!$this->isLogin($player)){
				$player->sendTip($message = Color::RED . ($ik ? "당신은 로그인되지 않았습니다." : "You are not logined") . "\n" . Color::DARK_RED . "/Login " . ($ik ? "<비밀번호>" : "<Password>"));
				$player->sendPopup($message);
			}
		}
	}

	public function register($name, $password){
		$this->data[self::PLAYERS][strtolower($name)] = hash("sha256", $password);
		$this->saveData();
	}

	public function isRegister($name){
		return isset($this->data[self::PLAYERS][strtolower($name)]);
	}

	public function unRegister($name){
		unset($this->data[self::PLAYERS][strtolower($name)]);
		$this->saveData();
	}

	public function login(Player $player){
		$this->players[$player->getName()] = true;
	}

	public function isLogin(Player $player){
		return isset($this->players[$player->getName()]);
	}

	public function unLogin(Player $player){
		unset($this->players[$player->getName()]);
	}

	public function isCorrectPassword($name, $password){
		return $this->isRegister($name) && $this->data[self::PLAYERS][strtolower($name)] == hash("sha256", $password);
	}

	public function loadData(){
		@mkdir($folder = $this->getDataFolder());
		if(!file_exists($folder . "Players.sl")){	
			file_put_contents($folder . "Players.sl", serialize([]));
		}
		if(!file_exists($folder . "Settings.sl")){	
			file_put_contents($folder . "Settings.sl", serialize([
				"Password_Minimum_Length" => 5
			]));
		}
		$this->data = [self::PLAYERS => unserialize(file_get_contents($folder . "Players.sl")), self::SETTINGS => unserialize(file_get_contents($folder . "Settings.sl"))];
	}

	public function saveData(){
		@mkdir($folder = $this->getDataFolder());
		file_put_contents($folder . "Players.sl", serialize($this->data[self::PLAYERS]));
		file_put_contents($folder . "Settings.sl", serialize($this->data[self::SETTINGS]));
	}

	public function isKorean(){
		return $this->getServer()->getLanguage()->getName() == "\"한국어\"";
	}
}