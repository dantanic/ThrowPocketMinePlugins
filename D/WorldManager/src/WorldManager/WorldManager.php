<?php

namespace WorldManager;

use pocketmine\Player;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat as Color;

class WorldManager extends \pocketmine\plugin\PluginBase implements \pocketmine\event\Listener{
	public function onEnable(){
		$this->loadYml();
		$this->loadWorlds();
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
	}

	public function onCommand(\pocketmine\command\CommandSender $sender, \pocketmine\command\Command $cmd, $label, array $sub){
		$ik = $this->isKorean();
		$wm = $this->wm;
		switch(strtolower($cmd->getName())){
			case "worldmanager":
				if(!isset($sub[0])) return false;
				switch(strtolower($sub[0])){
					case "generate":
					case "generator":
					case "g":
					case "생성":
					case "add":
					case "a":
					case "추가":
						if(!isset($sub[1])){
							$r = Color::RED . "Usage: /" . $cmd->getName() . " Generate(G) " . ($ik ? "<이름> <타입> <시드>" : "<Name> <Type> <Seed>");
						}else{
							$gn = $this->getServer()->getLevelType();
							$this->getServer()->setConfigString("level-type", isset($sub[2]) ? $sub[2] : null);
							$this->getServer()->generateLevel(strtolower($sub[1]), isset($sub[3]) ? $sub[3] : null);
							$this->getServer()->setConfigString("level-type", $gn);
							$r = Color::YELLOW . "[WorldManager] " . ($ik ? "월드가 생성되었습니다. 월드명: " : "World is generate. World: ") . strtolower($sub[1]);
				 			$this->loadWorlds();
				 			$wm = $this->wm;
						}
					break;
					case "load":
					case "l":
					case "로딩":
					case "로드":
					case "불러오기":
						if(!isset($sub[1])){
							$r = Color::RED . "Usage: /" . $cmd->getName() . " Load(L)" . ($ik ? "<이름>" : "<Name>");
						}else{
							if(!$this->getServer()->loadLevel($levelName = strtolower($sub[1]))){
								$r = Color::RED . "[WorldManager] " . $levelName . ($ik ? "는 잘못된 월드명입니다." : "is invalid world name");
							}else{
								$wm["Load"][$levelName] = true;
								$r = Color::YELLOW . "[WorldManager] " . ($ik ? "$levelName 월드를 로딩햇습니다." : "Load $levelName world");
							}
						}
					break;
					case "list":
					case "목록":
						$page = 1;
						if(isset($sub[0]) && is_numeric($sub[0])) $page = max(floor($sub[0]), 1);
						$list = array_chunk($this->getServer()->getLevels(), 5, true);
						if($page >= ($c = count($list))) $page = $c;
						$r = Color::YELLOW . "[WorldManager] " . ($ik ? "월드 목록 (페이지" : "World List (Page") . " $page/$c) \n";
						$num = ($page - 1) * 5;
						if($c > 0){
							foreach($list[$page - 1] as $v){
								$num++;
								$r .= Color::YELLOW . "  [$num] " . $v->getName() . " : " . $v->getFolderName() . "\n";
							}
						}
					break;
					case "spawn":
					case "s":
					case "스폰":
						$wm["MainSpawn"] = !$wm["MainSpawn"];
						$r = Color::YELLOW . "[WorldManager] " . ($wm["MainSpawn"] ? ($ik ? "스폰시 메인월드에서 스폰합니다." : "Main spawn is On") : ($ik ? "스폰시 해당 월드에서 스폰합니다." : "Main spawn is off"));
					break;
					default:
						return false;
					break;
				}
			break;
			case "worldprotect":
				if(!isset($sub[0])) return false;
				$wp = $wm["Protect"];
				switch(strtolower($sub[0])){
					case "add":
					case "a":
					case "추가":
						if(!isset($sub[1]) || !$sub[1]){
							$r = Color::RED . "Usage: /" . $cmd->getName() . " Add(A) " . ($ik ? "<월드명>" : "<WorldName>");
						}else{
							if(!in_array($levelName = strtolower($sub[1]), $wp)) $wp[] = $levelName;
							$r = Color::YELLOW . "[WorldProtect] " . ($ik ? " 추가됨 " : "Add") . " : $levelName";
						}
					break;
					case "del":
					case "d":
					case "삭제":
					case "제거":
						if(!isset($sub[1])){
							$r = Color::RED . "Usage: /" . $cmd->getName() . " Del(D) " . ($ik ? "<월드명>" : "<WorldName>");
						}else{
							if(!in_array($levelName = strtolower($sub[1]), $wp)){
								$r = Color::RED. "[WorldProtect] [$levelName] " . ($ik ? "목록에 존재하지 않습니다." : "does not exist.") . "\n" . Color::RED . "Usage: /" . $cmd->getName() . " List(L)";
							}else{
								foreach($wp as $k => $v){
									if($v == $levelName){
										unset($wp[$k]);
										$r = Color::YELLOW . "[WorldProtect] " . ($ik ? " 제거됨 " : "Del") . " : $levelName";
										break;
									}
								}
							}
						}
					break;
					case "reset":
					case "r":
					case "리셋":
					case "초기화":
						$wp = [];
						$r = Color::YELLOW . "[WorldProtect] " . ($ik ? " 리셋됨." : " Reset");
					break;
					case "list":
					case "l":
					case "목록":
					case "리스트":
						$page = 1;
						if(isset($sub[0]) && is_numeric($sub[0])) $page = max(floor($sub[0]), 1);
						$list = array_chunk($wp, 5, true);
						if($page >= ($c = count($list))) $page = $c;
						$r = Color::YELLOW . "[WorldProtect] " . ($ik ? "월드보호 목록 (페이지" : "WorldProtect List (Page") . " $page/$c) \n";
						$num = ($page - 1) * 5;
						if($c > 0){
							foreach($list[$page - 1] as $v){
								$num++;
								$r .= Color::YELLOW . "  [$num] $v\n";
							}
						}
					break;
					default:
						return false;
					break;
				}
				$wm["Protect"] = $wp;
			break;
			case "worldpvp":
				if(!isset($sub[0])) return false;
				$wpvp = $wm["PVP"];
				switch(strtolower($sub[0])){
					case "add":
					case "a":
					case "추가":
						if(!isset($sub[1]) || !$sub[1]){
							$r = Color::RED . "Usage: /" . $cmd->getName() . " Add(A) " . ($ik ? "<월드명>" : "<WorldName>");
						}else{
							if(!in_array($levelName = strtolower($sub[1]), $wpvp)) $wpvp[] = $levelName;
							$r = Color::YELLOW . "[WorldPVP] " . ($ik ? " 추가됨 " : "Add") . " : $levelName";
						}
					break;
					case "del":
					case "d":
					case "삭제":
					case "제거":
						if(!isset($sub[1])){
							$r = Color::RED . "Usage: /" . $cmd->getName() . " Del(D) " . ($ik ? "<월드명>" : "<WorldName>");
						}else{
							if(!in_array($levelName = strtolower($sub[1]), $wpvp)){
								$r = Color::RED . "[WorldPVP] [$levelName] " . ($ik ? "목록에 존재하지 않습니다." : "does not exist.") . "\n" . Color::RED . "Usage: /" . $cmd->getName() . " List(L)";
							}else{
								foreach($wpvp as $k => $v){
									if($v == $levelName){
										unset($wpvp[$k]);
										$r = Color::YELLOW . "[WorldPVP] " . ($ik ? " 제거됨 " : "Del") . " : $levelName";
										break;
									}
								}
							}
						}
					break;
					case "reset":
					case "r":
					case "리셋":
					case "초기화":
						$wpvp = [];
						$r = Color::YELLOW . "[WorldPVP] " . ($ik ? " 리셋됨." : " Reset");
					break;
					case "list":
					case "l":
					case "목록":
					case "리스트":
						$page = 1;
						if(isset($sub[0]) && is_numeric($sub[0])) $page = max(floor($sub[0]), 1);
						$list = array_chunk($wpvp, 5, true);
						if($page >= ($c = count($list))) $page = $c;
						$r = Color::YELLOW . "[WorldPVP] " . ($ik ? "PVP 월드 목록 (페이지" : "PVP World List (Page") . " $page/$c) \n";
						$num = ($page - 1) * 5;
						if($c > 0){
							foreach($list[$page - 1] as $v){
								$num++;
								$r .= Color::YELLOW . "  [$num] $v\n";
							}
						}
					break;
					default:
						return false;
					break;
				}
				$wm["PVP"] = $wpvp;
			break;
			case "worldinv":
				if(!isset($sub[0])) return false;
				$winv = $wm["Inv"];
				switch(strtolower($sub[0])){
					case "add":
					case "a":
					case "추가":
						if(!isset($sub[1]) || !$sub[1]){
							$r = Color::RED . "Usage: /" . $cmd->getName() . " Add(A) " . ($ik ? "<월드명>" : "<WorldName>");
						}else{
							if(!in_array($levelName = strtolower($sub[1]), $winv)) $winv[] = $levelName;
							$r = Color::YELLOW . "[WorldInv] " . ($ik ? " 추가됨 " : "Add") . " : $levelName";
						}
					break;
					case "del":
					case "d":
					case "삭제":
					case "제거":
						if(!isset($sub[1])){
							$r = Color::RED . "Usage: /" . $cmd->getName() . " Del(D) " . ($ik ? "목록에 존재하지 않습니다." : "does not exist.") . "\n" . Color::RED . "Usage: /" . $cmd->getName() . " List(L)";
						}else{
							if(!in_array($levelName = strtolower($sub[1]), $winv)){
								$r = Color::RED . "[WorldInv] [$levelName] " . ($ik ? "목록에 존재하지 않습니다." : "does not exist.") . "\n" . Color::RED . "Usage: /" . $cmd->getName() . " List(L)";
							}else{
								foreach($winv as $k => $v){
									if($v == $levelName){
										unset($winv[$k]);
										$r = Color::YELLOW . "[WorldInv] " . ($ik ? " 제거됨 " : "Del") . " : $levelName";
										break;
									}
								}
							}
						}
					break;
					case "reset":
					case "r":
					case "리셋":
					case "초기화":
						$winv = [];
						$r = Color::YELLOW . "[WorldInv] " . ($ik ? " 리셋됨." : " Reset");
					break;
					case "list":
					case "l":
					case "목록":
					case "리스트":
						$page = 1;
						if(isset($sub[0]) && is_numeric($sub[0])) $page = max(floor($sub[0]), 1);
						$list = array_chunk($winv, 5, true);
						if($page >= ($c = count($list))) $page = $c;
						$r = Color::YELLOW . "[WorldInv] " . ($ik ? "인벤세이브 월드 목록 (페이지" : "InventorySave World List (Page") . " $page/$c) \n";
						$num = ($page - 1) * 5;
						if($c > 0){
							foreach($list[$page - 1] as $v){
								$num++;
								$r .= Color::YELLOW . " [$num] $v\n";
							}
						}
					break;
					default:
						return false;
					break;
				}
				$wm["Inv"] = $winv;
			break;
			case "setspawn":
				$player = (isset($sub[0]) && ($player = $this->getServer()->getPlayer($sub[0])) instanceof Player) ? $player : $sender;
				$wm["Spawn"][$levelName = strtolower($player->getLevel()->getFolderName())] = round($player->x,2) . ":" . round($player->y,2) . ":" . round($player->z,2);
				$player->getLevel()->setSpawn($player);
				$r = Color::YELLOW . "[SetSpawn] " . ($ik ? "스폰 설정되었습니다.  월드명: $levelName , 좌표: " : "Spawn set. World: $levelName , Position: ") . $wm["Spawn"][$levelName];
			break;
			case "spawn":
				if($wm["MainSpawn"]) $world = $this->getServer()->getDefaultLevel();
				else $world = $sender->getLevel();
				$sender->teleport($world->getSpawn());
				$r = Color::YELLOW . "[Spawn] " . ($ik ? "스폰으로 텔레포트되었습니다. 월드명: " : "Teleport to spawn. World: ") . $world->getFolderName();
			break;
		}
		if(isset($r)) $sender->sendMessage($r);
		if($this->wm !== $wm){
			$this->wm = $wm;
			$this->saveYml();
		}
		return true;
	}

	public function onPlayerDeath(\pocketmine\event\player\PlayerDeathEvent $event){
		if(in_array(strtolower($event->getEntity()->getLevel()->getFolderName()), $this->wm["Inv"])) $event->setKeepInventory(true);
	}

	public function onEntityDamage(\pocketmine\event\entity\EntityDamageEvent $event){
		if(!$event->isCancelled() && ($entity = $event->getEntity()) instanceof Player && $event->getCause() <= 11 && $event instanceof EntityDamageByEntityEvent && !in_array(strtolower($entity->getLevel()->getFolderName()), $this->wm["PVP"]) && ($damager = $event->getDamager()) instanceof Player && !$damager->hasPermission("worldmanager.worldpvp.pvp")){
			$event->setCancelled();
			$dmg->sendMessage(Color::RED . "[PVP Manager] PVP 권한이 없습니다.");
		}
	}

	public function onBlockBreak(\pocketmine\event\block\BlockBreakEvent $event){
		if(!$event->isCancelled() && in_array(strtolower($event->getBlock()->getLevel()->getFolderName()), $this->wm["Protect"]) && !$event->getPlayer()->hasPermission("worldmanager.worldprotect.block")){
//			$event->getPlayer()->sendMessage(Color::RED . "[WorldProtect] " . ($this->isKorean() ? "이 월드는 보호상태입니다." : "This world is protected"));
			$event->setCancelled();
		}
	}

	public function onBlockPlace(\pocketmine\event\block\BlockPlaceEvent $event){
		if(!$event->isCancelled() && in_array(strtolower($event->getBlock()->getLevel()->getFolderName()), $this->wm["Protect"]) && !$event->getPlayer()->hasPermission("worldmanager.worldprotect.block")){
//			$event->getPlayer()->sendMessage(Color::RED . "[WorldProtect] " . ($this->isKorean() ? "이 월드는 보호상태입니다." : "This world is protected"));
			$event->setCancelled();
		}
	}

	public function onPlayerInteract(\pocketmine\event\player\PlayerInteractEvent $event){
		if(!$event->isCancelled() && in_array(strtolower($event->getBlock()->getLevel()->getFolderName()), $this->wm["Protect"]) && !$event->getPlayer()->hasPermission("worldmanager.worldprotect.block")){
//			$event->getPlayer()->sendMessage(Color::RED . "[WorldProtect] " . ($this->isKorean() ? "이 월드는 보호상태입니다." : "This world is protected"));
			$event->setCancelled();
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

	public function loadWorlds(){
		$wm = $this->wm;
		foreach($wm["Load"] as $levelName => $isLoad){
			if(!($level = $this->getLevelByName($levelName))) unset($wm["Load"][$levelName]);
			else{
				if($isLoad) $this->getServer()->loadLevel($levelName);
				else $this->getServer()->unloadLevel($level, true);
			}
		}
		foreach($this->getServer()->getLevels() as $level){
			if(!isset($wm["Load"][$levelName = strtolower($level->getFolderName())])) $wm["Load"][$levelName] = true;
			if(!isset($wm["Spawn"][$levelName])){
				$spawn = $level->getSafeSpawn();
				$wm["Spawn"][$levelName] = round($spawn->x,2) . ":" . round($spawn->y,2) . ":" . round($spawn->z,2);
			}
		}
		if($this->wm !== $wm){
			$this->wm = $wm;
			$this->saveYml();
		}
		foreach($this->wm["Spawn"] as $levelName => $spawn){
			if($level = $this->getLevelByName($levelName)){
				$level->setSpawn(new \pocketmine\level\Position(...(explode(":", $spawn) + [$level])));
			}
		}
	}

	public function loadYml(){
		@mkdir($this->getDataFolder());
		$this->wm = (new Config($this->getDataFolder() . "WorldManager.yml", Config::YAML, ["MainSpawn" => true, "Load" => [], "Spawn" => [], "Protect" => [], "PVP" => [], "Inv" => []]))->getAll();
	}

	public function saveYml(){
		ksort($this->wm["Load"]);
		ksort($this->wm["Spawn"]);
		sort($this->wm["Protect"]);
		$wm = new Config($this->getDataFolder() . "WorldManager.yml", Config::YAML);
		$wm->setAll($this->wm);
		$wm->save();
	}

	public function isKorean(){
		@mkdir($this->getDataFolder());
		if(!isset($this->ik)) $this->ik = (new Config($this->getDataFolder() . "! Korean.yml", Config::YAML, ["Korean" => false]))->get("Korean");
		return $this->ik;
	}
}