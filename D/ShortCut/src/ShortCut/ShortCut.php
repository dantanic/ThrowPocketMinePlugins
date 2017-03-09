<?php

namespace ShortCut;

use pocketmine\utils\Config;
use pocketmine\utils\TextFormat as Color;
use pocketmine\event\player\PlayerCommandPreprocessEvent;
use pocketmine\event\player\PlayerChatvent;
use pocketmine\event\server\ServerCommandEvent;

class ShortCut extends \pocketmine\plugin\PluginBase implements \pocketmine\event\Listener{
	public function onEnable(){
		$this->loadYml();
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
 	}

	public function onCommand(\pocketmine\command\CommandSender $sender, \pocketmine\command\Command $cmd, $label, array $sub){
		if(!isset($sub[0])) return false;
		$sc = $this->sc;
		$ik = $this->isKorean();
		switch(strtolower($sub[0])){
			case "add":
			case "a":
			case "추가":
				if(!isset($sub[1])){
					$r = Color::RED . "Usage: /ShortCut Add(A) " . ($ik ? "<단축명> <명령어>" : "<2Original> <Command>");
				}else{
					$sc[$shortcut = strtolower($sub[1])] = str_replace([".@", "_@", "-@"], ["@", "@", "@"], implode(" ", array_splice($sub,2)));
					$r = Color::YELLOW . "[ShortCut] " . $shortcut . ($ik ? "을 추가하였습니다." : " is added") . " [$shortcut] => " . $sc[$shortcut];
				}
			break;
			case "del":
			case "d":
			case "삭제":
			case "제거":
				if(!isset($sub[1])){
					$r = Color::RED . "Usage: /ShortCut Del(D) " . ($ik ? "<단축명>" : "<2Original>");
				}else{
					if(!isset($sc[$shortcut = strtolower($sub[1])])){
						$r = Color::RED . "[ShortCut] " . $shortcut . ($ik ? "은 목록에 존재하지 않습니다." : " is does not exist.") . "\n   " . Color::RED . "Usage: /ShortCut List(L)";
					}else{
						$r = Color::YELLOW . "[ShortCut] " . $shortcut . ($ik ? "을 제거하였습니다." : " is deleted") . " [$shortcut] => " . $sc[$shortcut];
						unset($sc[$shortcut]);
					}
				}
			break;
			case "reset":
			case "r":
			case "리셋":
			case "초기화":
				$sc = [];
				$r = Color::YELLOW . "[ShortCut] " . ($ik ? " 리셋됨." : " Reset");
			break;
			case "list":
			case "l":
			case "목록":
			case "리스트":
				$lists = array_chunk($sc, 5);
				$page = min(isset($sub[1]) && is_numeric($sub[1]) && isset($lists[$sub[1] - 1]) ? $sub[1] : 1, count($lists));
				$r = Color::YELLOW . "[ShortCut] " . ($ik ? "단축명령어 목록 (페이지: " : "Shortcut list (Page: ") . $page . "/" . count($lists) . ") (" . count($sc) . ")";
				if(isset($lists[$page - 1])){
					$keys = array_keys($sc);
					foreach($lists[$page - 1] as $key => $command) $r .= "\n" . Color::GOLD . "    [" . (($shortcutKey = (($page - 1) * 5 + $key)) + 1) .  "] " . $keys[$shortcutKey] . " : $command";
				}
			break;
			default:
				return false;
			break;
		}
		if(isset($r)) $sender->sendMessage($r);
		if($this->sc !== $sc){
			$this->sc = $sc;
			$this->saveYml();
		}
		return true;
	}

	/**
	 * @priority LOWEST
	 */
	public function onServerCommand(\pocketmine\event\server\ServerCommandEvent $event){
		$event->setCommand($this->command2Original($event->getCommand()));
		if(($command = $this->CommandShortcut($event)) && $command !== true) $event->setCommand($command);
		elseif($command === false) $event->setCancelled();
	}

	/**
	 * @priority LOWEST
	 */
	public function onPlayerCommandPreprocess(\pocketmine\event\player\PlayerCommandPreprocessEvent $event){
		if($event->getPlayer()->hasPermission("shortcut.use") &&  $isCommand = (strpos($command = $event->getMessage(), "/") === 0)) $event->setMessage("/".$this->command2Original(substr($command, 1)));
		if(($command = $this->CommandShortcut($event, $isCommand)) && $command !== true) $event->setMessage(($isCommand ? "/" : "") . $command);
		elseif($command === false) $event->setCancelled();
	}

	public function command2Original($command){
		for($i = 0; $i < 99; $i++){
			if(preg_match_all("/@sub([0-9]+)/i", $command, $matches)){
				$explode = explode(" ", $command);
				foreach($matches[1] as $matchKey => $match){
					$command = str_replace("@sub" . $match, isset($explode[$match]) ? $explode[$match] : "", $command);
				}
			}elseif(isset($this->sc[$key = strtolower(substr($command, 0, ($pos = strpos($command, " ")) === false ? strlen($command) : $pos))])){
				$command = $this->sc[$key] . substr($command, strlen($key));
			}else{
				return $command;
			}
		}
	}

	public function CommandShortcut($event, $isCommand = true){
		if($event->isCancelled()) return false;
		if($event instanceof PlayerCommandPreprocessEvent){
			$command = $event instanceof PlayerChatEvent ? $event->getMessage() : $isCommand ? substr($event->getMessage(), 1) : $event->getMessage();
			$sender = $event->getPlayer();
			if(!$sender->hasPermission("shortcut.use")) return true;
			$isPlayer = true;
		}else{
			$command = $event->getCommand();
			$sender = $event->getSender();
			$isPlayer = false;
		}
		$players = $this->getServer()->getOnlinePlayers();
		preg_match_all("/[^\.]@([a-zA-Z]+)/", $command, $matches);
		foreach($matches[1] as $matchKey => $match){
			$change = "";
			switch(strtolower($match)){
				case "player":
				case "p":
					$change = $sender->getName();
				break;
				case "x":
					if($isPlayer) $change = $sender->x;
				break;
				case "y":
					if($isPlayer) $change = $sender->y;
				break;
				case "z":
					if($isPlayer) $change = $sender->z;
				break;
				case "world":
				case "w":
					if($isPlayer) $change = $sender->getLevel()->getName();
				break;
				case "all":
				case "a":
						if($sender->isOp() && count($players) > 0) $change = "%*%";
				break;
				case "random":
				case "r":
					$change = count($players) > 0 ?  $players[array_rand($players)]->getName() : "";
				break;
				case "server":
				case "s":
					$change = $this->getServer()->getServerName();
				break;
				case "version":
				case "v":
					$change = $this->getServer()->getApiVersion();
				break;
			}
			$command = str_replace($matches[0][$matchKey], $change, $command);
		}
		if(strpos($command, "%*%") !== false){
	 		$event->setCancelled();
			foreach($players as $aplayer){
				$acommand = str_replace("%*%", $aplayer->getName(), $command);
				$isPlayetCommand = false;
				if($event instanceof PlayerCommandPreprocessEvent){
					$ev = new PlayerCommandPreprocessEvent($sender, "/" . $acommand);
					$isPlayerCommand = true;
				}elseif(!$isCommand){
					$this->getServer()->getPluginManager()->callEvent($ev = new PlayerChatEvent($sender, $acommand));
					if(!$ev->isCancelled()) $this->getServer()->broadcastMessage(sprintf($ev->getFormat(), $ev->getPlayer()->getDisplayName(), $ev->getMessage()), $ev->getRscipients());
					return false;
				}else{
					$ev = new ServerCommandEvent($sender, $acommand);
				}
				$this->getServer()->getPluginManager()->callEvent($ev);
				if(!$ev->isCancelled()) $this->getServer()->dispatchCommand($sender, $isPlayerCommand ? substr($ev->getMessage(), 1) : $ev->getCommand());
			}
			return false;
		}else{
			return $command;
		}
	}

	public function loadYml(){
		@mkdir($this->getDataFolder());
		$this->sc = (new Config($this->getDataFolder() . "ShortCut.yml", Config::YAML))->getAll();
	}

	public function saveYml(){
		ksort($this->sc);
		$sc = new Config($this->getDataFolder() . "ShortCut.yml", Config::YAML);
		$sc->setAll($this->sc);
		$sc->save();
	}

	public function isKorean(){
		return $this->getServer()->getLanguage()->getName() == "\"한국어\"";
	}
}