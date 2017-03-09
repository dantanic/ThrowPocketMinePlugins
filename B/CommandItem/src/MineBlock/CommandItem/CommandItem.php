<?php

namespace MineBlock\CommandItem;

use pocketmine\Player;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\ConsoleCommandSender;
use pocketmine\event\Listener;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\player\PlayerCommandPreprocessEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\server\ServerCommandEvent;
use pocketmine\inventory\PlayerInventory;
use pocketmine\item\Item;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;

class CommandItem extends PluginBase implements Listener{
	public function onEnable(){
		$this->place = [];
		$this->player = [];
		$pm = $this->getServer()->getPluginManager();
 		if(!($this->money = $pm->getPlugin("PocketMoney")) && !($this->money = $pm->getPlugin("EconomyAPI")) && !($this->money = $pm->getPlugin("MassiveEconomy")) && !($this->money = $pm->getPlugin("Money"))){
			$this->getServer()->getLogger()->info("[CommandBlock] Failed find economy plugin...");
		}else{
			$this->getServer()->getLogger()->info("[CommandBlock] Finded economy plugin : ".$this->money->getName());
		}
		$this->loadYml();
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
		$this->getServer()->getScheduler()->scheduleRepeatingTask(new Task($this, [$this,"onTick"]), 5);
	}
	
	public function onDisable(){
		$this->getServer()->getScheduler()->cancelTasks($this);
	}

	public function onCommand(CommandSender $sender, Command $cmd, $label, array $sub){
		$n = $sender->getName();
		if(!isset($sub[0])) return false;
		$ci = $this->ci;
		$rm = "Usage: /CommandItem ";
		$mm = "[CommandItem] ";
		$ik = $this->isKorean();
		switch(strtolower(array_shift($sub))){
			case "add":
			case "a":
			case "추가":
				if(!isset($sub[1])){
					$r = $rm . "Add(A) " . ($ik ? "<아이템ID> <명령어>" : "<ItemID> <Command>");
				}else{
					if(!$id = $this->getId(Item::fromString(array_shift($sub)))){
						$r = $sub[0] . " " . ($ik ? "는 잘못된 아이템ID입니다.." : "is invalid ItemID");
					}else{
						$command = implode(" ", $sub);
						if(!isset($ci[$id])) $ci[$id] = [];
						$ci[$id][] = $command;
						$r = $mm . ($ik ? " 추가됨" : " add") . "[$id] => $command";
					}
				}
			break;
			case "del":
			case "d":
			case "삭제":
			case "제거":
				if(!isset($sub[0])){
					$r = $rm . "Del(D) " . ($ik ? "<명령어>" : "<Alias>");
				}else{
					if(!$id = $this->getId(Item::fromString(array_shift($sub)))){
						$r = $sub[0] . " " . ($ik ? "는 잘못된 아이템ID입니다.." : "is invalid ItemID");
					}else{
						if(!isset($ci[$id])){
							$r = "$mm [$id] " . ($ik ? " 목록에 존재하지 않습니다..\n   $rm 목록 " : " does not exist.\n   $rm List(L)");
						}else{
							unset($ci[$id]);
							$r = $mm . ($ik ? " 제거됨" : " del") . "[$id]";
						}
					}
				}
			break;
			case "reset":
			case "r":
			case "리셋":
			case "초기화":
				$ci = [];
				$r = $mm . ($ik ? " 리셋됨." : " Reset");
			break;
			case "list":
			case "l":
			case "목록":
			case "리스트":
				$page = 1;
				if(isset($sub[1]) && is_numeric($sub[1])) $page = max(floor($sub[1]), 1);
				$list = array_chunk($ci, 5, true);
				if($page >= ($c = count($list))) $page = $c;
				$r = $mm . ($ik ? "커맨드아이템 목록 (페이지" : "CommandItem List (Page") . " $page/$c) \n";
				$num = ($page - 1) * 5;
				if($c > 0){
					foreach($list[$page - 1] as $k => $v){
						$num++;
						$r .= "  [$num] $k\n";
					}
				}
			break;
			default:
				return false;
			break;
		}
		if(isset($r)) $sender->sendMessage($r);
		if($this->ci !== $ci){
			$this->ci = $ci;
			$this->saveYml();
		}
		return true;
	}

	public function onPlayerInteract(PlayerInteractEvent $event){
		$this->onBlockEvent($event, true);
	}

	public function onBlockBreak(BlockBreakEvent $event){
		$this->onBlockEvent($event);
	}

	public function onBlockPlace(BlockPlaceEvent $event){
		$this->onBlockEvent($event);
	}

	public function onBlockEvent($event, $isHand = false){
		$p = $event->getPlayer();
		if(isset($this->place[$p->getName()])){
			$event->setCancelled();
			unset($this->place[$p->getName()]);
		}
		if($isHand){
			$id = $this->getId($i = $event->getItem());
			if(isset($this->ci[$id])){
				if($i->isPlaceable()){
					$this->place[$p->getName()] = true;
					$event->setCancelled();
				}
			}
			$this->runCommand($p, $id);
		}
	}

	public function onTick(){
		foreach($this->getServer()->getOnlinePlayers() as $p){
			$id = $this->getHand($p);
			if(isset($this->ci[$id])){
				foreach($this->ci[$id] as $cmd){
					$cmd = strtolower($cmd);
					if(strpos($cmd, "%h") !== false){
						$this->runCommand($p, $id, true);
						break;
					}
				}
			}
		}
	}

	public function runCommand($p, $id, $isHand = false){
		$ci = $this->ci;
		if(!isset($ci[$id])) return false;
		$ps = $this->player;
		$n = $p->getName();
		if(!isset($ps[$n])) $ps[$n] = [];
		if(!isset($ps[$n][$id])) $ps[$n][$id] = 0;
		if(microtime(true) - $ps[$n][$id] < 0) return;
		$l = explode(":", $id);
		$cool = 1;
		foreach($ci[$id] as $str){
			$arr = explode(" ", str_ireplace(["%player", "%p", "%x", "%y", "%z", "%world", "%w", "%server", "%s", "%version", "%v", "%money", "%m"], [$p->getName(), $p->getName(), $p->x, $p->y, $p->z, $p->getLevel()->getFolderName(), $p->getLevel()->getFolderName(), $this->getServer()->getServerName(), $this->getServer()->getServerName(), $this->getServer()->getApiVersion(), $this->getServer()->getApiVersion(), ($money = $this->getMoney($p)) !== false ? $money : 0, ($money = $this->getMoney($p)) !== false ? $money : 0], $str));
			$time = 0;
			$chat = false;
			$console = false;
			$op = false;
			$deop = false;
			$safe = false;
			$hand = false;
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
							case "hand":
							case "h":
								unset($arr[$k]);
								$hand = true;
							break;
							case "say":
								unset($arr[$k]);
								$say = true;
							break;
						}
					}
				}
			}
			$this->getServer()->getScheduler()->scheduleDelayedTask(new Task($this, [$this,"dispatchCommand"], [$p,$id,$isHand,$chat,$console,$op,$deop,$safe,$hand,$arr,$heal,$damage,$say]), $time * 20);
		}
		$ps[$n][$id] = microtime(true) + $cool;
		$this->player = $ps;
	}

	public function dispatchCommand($p, $id, $isHand, $chat, $console, $op, $deop, $safe, $hand, $arr, $heal, $damage, $say){
		if(($isHand && !$hand) || (!$isHand && $hand) || ($safe && !$p->isOp()) || ($deop && $p->isOp())) return false;
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
			$ev = $console ? new ServerCommandEvent(new ConsoleCommandSender(), $cmd) : new PlayerCommandPreprocessEvent($p, "/" . $cmd);
			$this->getServer()->getPluginManager()->callEvent($ev);
			if(!$ev->isCancelled()){
				if($ev instanceof ServerCommandEvent) $this->getServer()->dispatchCommand(new ConsoleCommandSender(), $ev->getCommand());
				else $this->getServer()->dispatchCommand($p, substr($ev->getMessage(), 1));
			}
			if($op) $p->setOp(false);
		}
		return true;
	}

	public function move(Player $p, Vector3 $m, $t, $cool, $tt = false){
		if(!$tt) $tt = 0;
		if($t - $tt < 1){
			return;
		}else{
			$tt++;
			$p->setMotion($m);
			$p->onGround = true;
			if($t - $tt > 0) $this->getServer()->getScheduler()->scheduleDelayedTask(new Task($this, [$this,"move"], [$p,$m,$t,$cool,$tt]), $cool * 20);
		}
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

	public function getHand($p){
		return $p instanceof Player && ($inv = $p->getInventory()) instanceof PlayerInventory && ($i = $inv->getItemInHand()) instanceof Item ? $this->getId($i) : false;
	}

	public function getId($i){
		return !$i ? false : $i->getID() == 0 ? false : $i->getID() . ":" . $i->getDamage();
	}

	public function loadYml(){
		@mkdir($this->getDataFolder());
		$this->ci = (new Config($this->getDataFolder() . "CommandItem.yml", Config::YAML))->getAll();
	}

	public function saveYml(){
		ksort($this->ci);
		$ci = new Config($this->getDataFolder() . "CommandItem.yml", Config::YAML);
		$ci->setAll($this->ci);
		$ci->save();
	}

	public function isKorean(){
		return strtolower($this->getServer()->getLanguage()->getName()) == "korean";
	}
}
