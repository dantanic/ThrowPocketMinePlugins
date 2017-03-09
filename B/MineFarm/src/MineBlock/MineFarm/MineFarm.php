<?php

namespace MineBlock\MineFarm;

use pocketmine\Player;
use pocketmine\block\Block;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\event\Listener;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\block\BlockUpdateEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\item\Item;
use pocketmine\level\Position;
use pocketmine\level\format\mcregion\Chunk;
use pocketmine\level\generator\Generator;
use pocketmine\math\Math;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;
use pocketmine\Server;

class MineFarm extends PluginBase implements Listener{
 	public function onLoad(){
 		Generator::addGenerator(MineFarmGenerator::class, $this->name = "minefarm");
		$this->getServer()->getLogger()->info("[MineFarm] MineFarmGenerator is Loaded");
	}

	public function onEnable(){
		$this->player = [];
		$s = $this->getServer();
		$this->path = $this->getDataFolder();
		$this->loadYml();
		$this->nt = ["Time" => 0, "Count" => 0];
		$gn = $s->getLevelType();
		$n = $this->name;
		$s->setConfigString("level-type", $n);
		if(!$s->isLevelLoaded($n) && !$s->loadLevel($n)) $s->generateLevel($n);
		$s->setConfigString("level-type", $gn);
		$s->getLogger()->info("[MineFarm] MineFarmWorld is Loaded");
		$this->level = $s->getLevelByName($n);
		$pm = $this->getServer()->getPluginManager();
		if(!($this->money = $pm->getPlugin("PocketMoney")) && !($this->money = $pm->getPlugin("EconomyAPI")) && !($this->money = $pm->getPlugin("MassiveEconomy")) && !($this->money = $pm->getPlugin("Money"))){
			$this->getServer()->getLogger()->info("Failed find economy plugin...");
		}else{
			$this->getServer()->getLogger()->info("Finded economy plugin : " . $this->money->getName());
		}
		$s->getPluginManager()->registerEvents($this, $this);
		$s->getScheduler()->scheduleRepeatingTask(new Task($this, [$this, "onTick"]), 10);
		$this->tick = 0;
	}
	
	public function onDisable(){
		$this->getServer()->getScheduler()->cancelTasks($this);
	}

	public function onCommand(CommandSender $sender, Command $cmd, $label, array $sub){
		if(!isset($sub[0])) return false;
		$ik = $this->isKorean();
		$rm = TextFormat::RED . "Usage: /";
		$mm = "[MineFarm] ";
		$smd = strtolower(array_shift($sub));
		$n = strtolower($sender->getName());
		switch(strtolower($cmd->getName())){
			case "myfarm":
				if(!$sender instanceof Player){
					$r = $mm . ($ik ? "게임내에서만 실행해주세요." : "Please run this command in-game");
				}else{
					$rm .= "MyFarm ";
					switch($smd){
						case "move":
						case "my":
						case "me":
						case "m":
						case "이동":
							if(!in_array($n, $this->mf["Farm"])){
								$r = $mm . ($ik ? "팜을 보유하고있지 않습니다." : "You don't have farm");
							}else{
								$sender->teleport($this->getPosition($n));
								$r = $mm . ($ik ? "나의 팜으로 텔레포트되었습니다. : " : "Teleported to your farm. : ") . $this->getNum($sender);
							}
						break;
						case "buy":
						case "b":
						case "구매":
							if(in_array($n, $this->mf["Farm"])){
								$r = $mm . ($ik ? "이미 팜을 보유하고있습니다." : "You already have farm");
							}elseif(!$this->mf["Sell"] || $this->money == false){
								$r = $mm . ($ik ? "이 서버는 팜을 판매하지 않습니다.." : "This server not sell the farm");
							}elseif($this->getMoney($sender) < ($pr = $this->mf["Price"])){
								$r = $mm . ($ik ? "당신은 $pr 보다 돈이 적습니다. 나의 돈 : " : "You don't have $pr $. Your money : ") . $this->getMoney($n);
							}else{
								$this->giveMoney($sender, -$pr);
								$this->giveFarm($n);
								$r = $mm . ($ik ? "팜을 구매하였습니다. 나의 돈 : " : "Buy the farm. Your money : ") . $this->getMoney($n) . "\n/‡ " . $mm . ($ik ? "팜 번호 : " : "Farm Number : ") . $this->getNum($n);
							}
						break;
						case "visit":
						case "v":
						case "방문":
							if(!isset($sub[0]) || !$sub[0] || (is_numeric($sub[0]) && $sub[0] < 1)){
								$r = $mm . ($ik ? "이동 <팜번호 or 플레이어명>" : "Move <FarmNum or PlayerName>");
							}else{
								if(is_numeric($sub[0])){
									$fn = floor($sub[0]);
									$nm = $ik ? "번" : "";
								}else{
									$fn = strtolower($sub[0]);
									if(!in_array($fn, $this->mf["Farm"])){
										$r = $mm . $fn . ($ik ? "는 잘못된 플레이어명입니다." : " is invalid player");
									}else{
										$nm = $ik ? "님의" : "'s ";
									}
								}
								if(!isset($r)){
									if(!$this->isInvite($n, $fn)){
										$r = $mm . ($ik ? "$fn $nm 팜에 초대받지 않았습니다." : "You don't invited to $fn $nm farm");
									}else{
										$sender->teleport($this->getPosition($fn));
										$r = $mm . ($ik ? "$fn $nm 팜으로 텔레포트되었습니다." : "Teleported to $fn $nm Minefarm");
										if($p = $this->getServer()->getplayerExact($this->getOwnName($fn))) $p->sendMessage("/☜ [MineFarm] " . $n . ($ik ? "님이 당신의 팜에 방문햇습니다." : " is invited to your farm."));
									}
								}
							}
						break;
						case "invite":
						case "i":
						case "초대":
							if(!in_array($n, $this->mf["Farm"])){
								$r = $mm . ($ik ? "팜을 보유하고있지 않습니다." : "You don't have farm");
							}elseif(!isset($sub[0])){
								$r = $rm . ($ik ? "초대 <플레이어명>" : "Invite <PlayerName");
							}elseif($this->isInvite($sub[0] = strtolower($sub[0]), $n)){
								$r = $mm . $sub[0] . ($ik ? "님은 이미 초대된 상태입니다." : " is already invited");
							}else{
								$this->mf["Invite"][$n][$sub[0]] = false;
								$this->saveYml();
								$r = $mm . ($ik ? "$sub[0] 님을 팜에 초대합니다." : "Invite $sub[0] on my farm");
								if($p = $this->getServer()->getplayerExact($sub[0])) $p->sendMessage("/☜ [MineFarm] " . $n . ($ik ? "님이 당신을 팜에 초대하였습니다." : " invite you out to farm"));
							}
						break;
						case "share":
						case "s":
						case "초대":
							if(!in_array($n, $this->mf["Farm"])){
								$r = $mm . ($ik ? "마인팜을 보유하고있지 않습니다." : "You don't have MineFarm");
							}elseif(!isset($sub[0])){
								$r = $rm . ($ik ? "공유 <플레이어명>" : "Share <PlayerName");
							}elseif($this->isShare($sub[0] = strtolower($sub[0]), $n)){
								$r = $mm . $sub[0] . ($ik ? "님은 이미 공유된 상태입니다." : " is already shared");
							}else{
								$this->mf["Invite"][$n][$sub[0]] = true;
								$this->saveYml();
								$r = $mm . ($ik ? "$sub[0] 님에게 팜을 공유합니다." : "Shared your farm to $sub[0]");
								if($p = $this->getServer()->getplayerExact($sub[0])) $p->sendMessage("/☜ [MineFarm] " . $n . ($ik ? "님이 당신에게 팜을 공유하였습니다." : " shared the farm with you"));
							}
						break;
						case "kick":
						case "k":
						case "강퇴":
							if(!in_array($n, $this->mf["Farm"])){
								$r = $mm . ($ik ? "마인팜을 보유하고있지 않습니다." : "You don't have MineFarm");
							}elseif(!isset($sub[0])){
								$r = $rm . ($ik ? "강퇴 <플레이어명>" : "Kick <PlayerName");
							}elseif(!$this->isInvite($sub[0] = strtolower($sub[0]), $n)){
								$r = $mm . $sub[0] . ($ik ? "님은 초대되지 않았습니다." : " is not invited");
							}else{
								unset($this->mf["Invite"][$n][$sub[0]]);
								$this->saveYml();
								$r = $mm . ($ik ? "$sub[0] 님을 마인팜에서 강퇴합니다." : "Kick $sub[0] on my minefarm");
								if($p = $this->getServer()->getplayerExact($sub[0])) $p->sendMessage("/☜ [MineFarm] " . ($ik ? "$n 님의 팜에서 강퇴되었습니다." : "You are kicked from $n's Minefarm."));
							}
						break;
						case "list":
						case "l":
						case "목록":
							if(!in_array($n, $this->mf["Farm"])){
								$r = $mm . ($ik ? "마인팜을 보유하고있지 않습니다." : "You don't have MineFarm");
							}else{
								$page = 1;
								if(isset($sub[0]) && is_numeric($sub[0])) $page = round($sub[0]);
								$list = array_chunk($this->mf["Invite"][$n], 5, true);
								if($page >= ($c = count($list))) $page = $c;
								$r = $mm . ($ik ? "초대 (공유) 목록 (페이지" : "Invite(Share) List (Page") . " $page/$c) \n";
								$num = ($page - 1) * 5;
								if($c > 0){
									foreach($list[$page - 1] as $k => $v){
										$num++;
										$r .= "  [$num] " . (strlen($k) <= 3 ? ($ik ? "오류." : "Error.") : ("[" . ($ik ? ($v ? "공유" : "초대") : ($v ? "Share" : "Invite")) . "] $k\n"));
									}
								}
							}
						break;
						case "here":
						case "h":
						case "여기":
							if(!$this->isFarm($sender)){
								$r = $mm . ($ik ? "이곳은 팜이 아닙니다." : "Here is not Farm");
							}else{
								$r = $mm . ($ik ? "이곳의 팜 번호 : " : "Here farm number : ") . $this->getNum($sender, true) . ",  " . ($this->getOwnName($sender, true) !== false ? ($ik ? "주인 : " : "Own : ") . $this->getOwnName($sender, true) : "");
							}
						break;
						default:
							return false;
						break;
					}
				}
			break;
			case "minefarm":
				$rm .= "MineFarm ";
				switch($smd){
					case "give":
					case "g":
					case "지급":
						if(!isset($sub[0])){
							$r = $rm . ($ik ? "지급 <플레이어명> (지역번호)" : "Give(G) <PlayerName> (FarmNumber)");
						}elseif(!($p = $this->getServer()->getPlayer($sub[0]))){
							$r = $mm . $sub[0] . ($ik ? "는 잘못된 플레이어명입니다." : " is invalid player");
						}elseif(in_array(strtolower($p->getName()), $this->mf["Farm"])){
							$r = $mm . $sub[0] . ($ik ? "님은 이미 마인팜을 소유중입니다. " : " is already have minefarm");
						}else{
							$num = $this->giveFarm($p) + 1;
							$pn = $p->getName();
							$r = $mm . ($ik ? "$pn 님에게 마인팜을 지급했습니다. : " : "Give the minefarm to $pn : ") . ($num = $this->getNum($p));
							$p->sendMessage($mm . ($ik ? "마인팜을 지급받았습니다. : " : "Now you have your minefarm : ") . $num);
						}
					break;
					case "move":
					case "m":
					case "이동":
						if(!isset($sub[0]) || !$sub[0] || (is_numeric($sub[0]) && $sub[0] < 1)){
							$r = $mm . ($ik ? "이동 <땅번호 or 플레이어명>" : "Move <FarmNum or PlayerName>");
						}else{
							if(is_numeric($sub[0])){
								$n = floor($sub[0]);
								$nm = $ik ? "번" : "";
							}else{
								$n = $sub[0];
								if(!in_array($n, $this->mf["Farm"])){
									$r = $mm . $n . ($ik ? "는 잘못된 플레이어명입니다." : " is invalid player");
								}else{
									$nm = $ik ? "님의" : "'s ";
								}
							}
							if(!isset($r)){
								$sender->teleport($this->getPosition($n));
								$r = $mm . ($ik ? "$n $nm 마인팜으로 텔레포트되었습니다." : "Teleported to $n $nm Minefarm");
							}
						}
					break;
					case "here":
					case "h":
					case "여기":
						if(!$sender instanceof Player){
							$r = $mm . ($ik ? "게임내에서만 실행해주세요." : "Please run this command in-game");
						}elseif(!$this->isFarm($sender)){
							$r = $mm . ($ik ? "이곳은 팜이 아닙니다." : "Here is not Farm");
						}else{
							$r = $mm . ($ik ? "이곳의 팜 번호 : " : "Here farm number : ") . $this->getNum($sender, true) . ",  " . ($this->getOwnName($sender, true) !== false ? ($ik ? "주인 : " : "Own : ") . $this->getOwnName($sender, true) : "");
						}
					break;
					case "distace":
					case "d":
					case "거리":
					case "간격":
						if(!isset($sub[0])){
							$r = $rm . ($ik ? "거리 <숫자>" : "Distance(D) (Number)");
						}elseif(!is_numeric($sub[0]) || $sub[0] < 0){
							$r = $mm . $sub[0] . ($ik ? "는 잘못된 숫자입니다." : " is invalid number");
						}else{
							$this->mf["Distance"] = floor($sub[0]);
							$this->saveYml();
							$r = $mm . ($ik ? " 마인팜간 간격이 $sub[0] 으로 설정되엇습니다." : "minefarm distance is set to $sub[0]");
						}
					break;
					case "size":
					case "sz":
					case "크기":
						if(!isset($sub[0])){
							$r = $rm . ($ik ? "크기 <숫자>" : "Size(SZ) (Number)");
						}elseif(!is_numeric($sub[0])){
							$r = $mm . $sub[0] . ($ik ? "는 잘못된 숫자입니다." : " is invalid number");
						}else{
							$this->mf["Size"] = floor($sub[0]);
							$this->saveYml();
							$r = $mm . ($ik ? " 마인팜의 크기가 $sub[0] 으로 설정되엇습니다." : "minefarm size is set to $sub[0]");
						}
					break;
					case "air":
					case "a":
					case "공기":
						if(!isset($sub[0])){
							$r = $rm . ($ik ? "공기 <숫자>" : "Air(A) (Number)");
						}elseif(!is_numeric($sub[0]) || $sub[0] < 0){
							$r = $mm . $sub[0] . ($ik ? "는 잘못된 숫자입니다." : " is invalid number");
						}else{
							$this->mf["Air"] = floor($sub[0]);
							$this->saveYml();
							$r = $mm . ($ik ? " 마인팜의 공기지역 크기가 $sub[0] 으로 설정되엇습니다." : "minefarm air place size is set to $sub[0]");
						}
					break;
					case "sell":
					case "s":
					case "판매":
						$a = !$this->mf["Sell"];
						$this->mf["Sell"] = $a;
						$this->saveYml();
						$m = $mm . ($ik ? "이제 마인팜을 판매" . ($a ? "합" : "하지않습") . "니다." : "Now " . ($a ? "" : "not ") . "sell the minefarm");
					break;
					case "price":
					case "p":
					case "가격":
						if(!isset($sub[0])){
							$r = $rm . ($ik ? "가격 <숫자>" : "Money(Mn) (Number)");
						}elseif(!$sub[0] || !is_numeric($sub[0])){
							$r = $mm . $sub[0] . ($ik ? "는 잘못된 숫자입니다." : " is invalid number");
						}else{
							$this->mf["Price"] = floor($sub[0]);
							$this->saveYml();
							$m = $mm . ($ik ? "마인팜의 가격이 $sub[0] 으로 설정되엇습니다." : "minefarm distance is set to $sub[0]");
						}
					break;
					case "auto":
					case "at":
					case "자동":
						$a = !$this->mf["Auto"];
						$this->mf["Auto"] = $a;
						$this->saveYml();
						if($a){
							foreach($this->getServer()->getOnlinePlayers() as $p){
								if($this->giveFarm($p)) $p->sendMessage("[MineFarm] [Auto] " . ($ik ? "마인팜을 지급받았습니다. : " : "Now you gave minefarm. : ") . $this->getNum($p));
							}
						}
						$m = $mm . ($ik ? "이제 마인팜을 자동 분배" . ($a ? "합" : "하지않습") . "니다." : "Now " . ($a ? "" : "not ") . "auto give the minefarm");
					break;
					case "item":
					case "i":
					case "아이템":
						$a = !$this->mf["Item"];
						$this->mf["Item"] = $a;
						$this->saveYml();
						$m = $mm . ($ik ? "이제 기초 지급템을 " . ($a ? "줍" : "주지않습") . "니다." : "Now " . ($a ? "" : "not ") . "give the first item");
					break;
					case "list":
					case "l":
					case "목록":
						$page = 1;
						if(isset($sub[0]) && is_numeric($sub[0])) $page = max(floor($sub[0]), 1);
						$list = array_chunk($this->mf["Farm"], 5, true);
						if($page >= ($c = count($list))) $page = $c;
						$r = $mm . ($ik ? "마인팜 목록 (페이지" : "MineFarm List (Page") . " $page/$c) \n";
						$num = ($page - 1) * 5;
						if($c > 0){
							foreach($list[$page - 1] as $v){
								$num++;
								$r .= "  [$num] $v\n";
							}
						}
					break;
					case "reset":
					case "r":
					case "리셋":
						$this->mf["Farm"] = [];
						$this->mf["Invite"] = [];
						$this->saveYml();
						if($this->mf["Auto"]){
							foreach($this->getServer()->getOnlinePlayers() as $p){
								if($this->giveFarm($p)) $p->sendMessage("[MineFarm] [Auto] " . ($ik ? "마인팜을 지급받았습니다. : " : "Now you gave minefarm. : ") . $this->getNum($p));
							}
						}
						$r = $mm . ($ik ? "리셋됨" : "Reset");
					break;
					default:
						return false;
					break;
				}
			break;
		}
		if(isset($r)) $sender->sendMessage($r);
		if(isset($m)) $this->getServer()->broadcastMessage($m);
		return true;
	}

	public function onPlayerJoin(PlayerJoinEvent $event){
		if($this->mf["Auto"]) $this->giveFarm($event->getPlayer());
		$event->getPlayer()->sendMessage($event->getJoinMessage());
		$event->setJoinMessage("");
	}

	public function onPlayerQuit(PlayerQuitEvent $event){
		$event->getPlayer()->sendMessage($event->getQuitMessage());
		$event->setQuitMessage("");
	}

	public function onPlayerDeath(PlayerDeathEvent $event){
		$event->getEntity()->sendMessage($event->getDeathMessage());
		$event->setDeathMessage("");
	}

	public function onPlayerMove(PlayerMoveEvent $event){
		$p = $event->getPlayer();
		if($this->isFarm($from = $event->getFrom()) && !$p->hasPermission("minefarm.block") && $this->isLand($from) && (!$this->isLand($to = $event->getTo()) || !$this->isOwn($p, $to) && !$this->isInvite($p, $to))){
			$event->setCancelled();
		}
	}

	public function onPlayerInteract(PlayerInteractEvent $event){
		$p = $event->getPlayer();
		$b = $event->getBlock();
		if(!$event->isCancelled() && $event->getFace() !== 255 && !$p->hasPermission("minefarm.block") && $this->isFarm($b)){
			if(!$this->isOwn($p, $b) && !$this->isShare($p, $b)) $event->setCancelled();
/*
			elseif($this->isMain($b) && $b->y < 8){
				$bf = $b->getSide($event->getFace());
				if($bf->y > 2 && $bf->y < 7){
					$x = $bf->x % 16;
					$z = $bf->z % 16;
					if(($x == 5 || $x == 10) && ($z == 5 || $z == 10)) $id = 17;
					elseif($x > 4 && $x < 11 && $z > 4 && $z < 11) $id = $this->drop[array_rand($this->drop)];
					else $event->setCancelled();
					if(isset($id)) $this->blockRegen($bf, $id);
				}
			}
*/
		}
/* +++ */ elseif(!$this->isFarm($b) && !$p->hasPermission("minefarm.block")) $event->setCancelled();
	}

	public function onBlockBreak(BlockBreakEvent $event){
		$p = $event->getPlayer();
		$b = $event->getBlock();
 		if(!$event->isCancelled() && !$p->hasPermission("minefarm.block") && $this->isFarm($b)){
			if(!$this->isOwn($p, $b) && !$this->isShare($p, $b)) $event->setCancelled();
/*
			elseif($this->isMain($b) && $b->y < 8){
				if($b->y > 2 && $b->y < 7){
					$x = $b->x % 16;
					$z = $b->z % 16;
					if(($x == 5 || $x == 10) && ($z == 5 || $z == 10)) $id = 17;
					elseif($x > 4 && $x < 11 && $z > 4 && $z < 11 && count($b->getDrops($event->getItem())) !== 0) $id = $this->drop[array_rand($this->drop)];
					else $event->setCancelled();
				}else $event->setCancelled();
				if(isset($id)) $this->getServer()->getScheduler()->scheduleDelayedTask(new Task($this, [$this, "blockRegen"], [$b, $id]), 50);
			}
*/
		}
/* +++ */ elseif(!$this->isFarm($b) && !$p->hasPermission("minefarm.block")) $event->setCancelled();
	}

	public function onBlockPlace(BlockPlaceEvent $event){
		$p = $event->getPlayer();
		$b = $event->getBlock();
//		if(!$event->isCancelled() && !$p->hasPermission("minefarm.block") && $this->isFarm($b) && (!$this->isOwn($p, $b) && !$this->isShare($p, $b) || $this->isMain($b) && $b->y < 8)) $event->setCancelled();
		if(!$event->isCancelled() && !$p->hasPermission("minefarm.block") && $this->isFarm($b = $event->getBlock()) && !$this->isOwn($p, $b) && !$this->isShare($p, $b)) $event->setCancelled();
/* +++ */ elseif(!$this->isFarm($b) && !$p->hasPermission("minefarm.block")) $event->setCancelled();
	}

	public function onBlockUpdate(BlockUpdateEvent $event){
		if($this->isFarm($b = $event->getBlock()) && !$this->isMain($b) && in_array($b->getID(), [8, 9, 10, 11])) $event->setCancelled();
	}

	public function onTick(){
		$ik = $this->isKorean();
//		$sr1 = str_repeat($sr2 = str_repeat(" ", 5), 9);
		foreach($this->getServer()->getOnlinePlayers() as $p){
//			$first = "";
//			$p->sendPopup(($first = (in_array(strtolower($p->getName()), $this->mf["Farm"]) ? TextFormat::ITALIC.TextFormat::GOLD."$sr1 MyFarm:".$this->getNum($p) : "")).($this->getMoney($p) !== false ? ($first == "" ? TextFormat::ITALIC.TextFormat::GOLD.$sr1 : "")."  Money:".$this->getMoney($p) : "").($this->isFarm($p) ? TextFormat::RESET."\n$sr1".TextFormat::ITALIC.TextFormat::DARK_BLUE."Here:".$this->getNum($p, true) : "").($this->getOwnName($p, true) !== false ? ",  Owner:".$this->getOwnName($p, true) : "").(TextFormat::RESET."\n$sr1$sr2".TextFormat::ITALIC.TextFormat::DARK_RED."X:" .TextFormat::RED.floor($p->x).TextFormat::DARK_RED." Y:".TextFormat::RED.floor($p->y).TextFormat::DARK_RED." Z: ".TextFormat::RED.floor($p->z)).TextFormat::RESET."\n$sr1$sr2$sr2".TextFormat::ITALIC.TextFormat::GREEN."Join[".count($this->getServer()->getOnlinePlayers())."/".$this->getServer()->getConfigString("max-players", 20)."]....".["-", "\\", ".|", "/"][$this->tick]);
			$m = ["", ""];
			if(in_array(strtolower($p->getName()), $this->mf["Farm"])) $m[0] .= TextFormat::GOLD."MyFarm: ".TextFormat::YELLOW.$this->getNum($p);
			if($this->getMoney($p) !== false) $m[0] .= ($m[0] !== "" ? ",  " : "").TextFormat::GOLD."Money: ".TextFormat::YELLOW.$this->getMoney($p);
			if($m[0] !== "") $m[0] = TextFormat::ITALIC.str_pad($m[0], 80, " ", STR_PAD_LEFT);
			if($this->isFarm($p)){
				$m[1] = TextFormat::DARK_BLUE."Here:".TextFormat::BLUE.$this->getNum($p, true);
				if($this->getOwnName($p, true) !== false) $m[1] .= TextFormat::DARK_BLUE.",  Owner:".TextFormat::BLUE.$this->getOwnName($p, true);
				$m[1] = TextFormat::RESET."\n".TextFormat::ITALIC.str_pad($m[1], 80, " ", STR_PAD_LEFT);
			}
			$p->sendPopup(implode($m, ""). TextFormat::RESET."\n".str_pad(TextFormat::ITALIC.TextFormat::DARK_RED."X:" .TextFormat::RED.floor($p->x).TextFormat::DARK_RED." Y:".TextFormat::RED.floor($p->y).TextFormat::DARK_RED." Z: ".TextFormat::RED.floor($p->z).TextFormat::RESET."\n", 95, " ", STR_PAD_LEFT).str_pad(TextFormat::ITALIC.TextFormat::DARK_GREEN."Join[".TextFormat::GREEN.count($this->getServer()->getOnlinePlayers()).TextFormat::DARK_GREEN."/".TextFormat::GREEN.$this->getServer()->getConfigString("max-players", 20).TextFormat::DARK_GREEN."]....", 90, " ", STR_PAD_LEFT).["-", "\\", ".|", "/"][$this->tick]);
/*
			if(!isset($this->player[$n = $p->getName()]) || $this->player[$n]->closed == true){
				$this->player[$n] = new FroatingText($this, $p);
			}
			$this->player[$n]->setName(($ik ? "보유 팜 : " : "Your Farm : ") . (in_array(strtolower($p->getName()), $this->mf["Farm"]) ? $this->getNum($p) : ($ik ? "없음" : "None")) . "\n" . ($this->isFarm($p) ? ($ik ? "여기 팜 : " : "Here farm : ") . $this->getNum($p, true) . ",  " . ($this->getOwnName($p, true) !== false ? ($ik ? "주인 : " : "Own : ") . $this->getOwnName($p, true) : "") : "") . "\n X: " . floor($p->x) . " Y: " . floor($p->y) . " Z: " . floor($p->z) . " World: " . $p->getLevel()->getFolderName());
*/
		}
		$this->tick = $this->tick >= 3 ? 0 : $this->tick + 1;

		if($this->an["On"] && count($this->an["Message"]) !== 0){
			if($this->an["Time"] > $this->nt["Time"]){
				$this->nt["Time"]++;
			}else{
				$this->nt["Time"] = 0;
				if(count($this->getServer()->getOnlinePlayers()) > 0){
					if(!isset($this->an["Message"][$this->nt["Count"]])) $this->nt["Count"] = 0;
					$this->getServer()->broadCastMessage(str_replace("\\n", "\n", $this->an["Message"][$this->nt["Count"]]));
					$this->nt["Count"]++;
				}
			}
		}
	}

	public function blockRegen($b, $id){
		$i = Item::fromString($id);
		$b->getLevel()->setBlock($b, Block::get($i->getID(), $i->getDamage()), false);
	}

	public function giveFarm($name){
		if($pp = $this->getServer()->getPlayerExact($name)) $p = $pp;
		if($name instanceof Player){
			$p = $name;
			$name = $p->getName();
		}
		if(in_array(strtolower($name), $this->mf["Farm"])) return false;
		$this->mf["Farm"][] = strtolower($name);
		$this->mf["Invite"][strtolower($name)] = [];
		$this->saveYml();
		if(isset($p)) $p->setSpawn($this->getPosition($name));
		if($this->mf["Item"]){
			if(isset($p)){
				$p->sendMessage("[MineFarm] " . ($this->isKorean() ? "마인팜 지하에는 광물과 나무를 캘수있는 장소가 있습니다." : "There are infinity ores and infinity trees (at underground of minefarm)"));
				foreach($this->mf["Items"] as $item){
					$i = Item::fromString($item[0]);
					$i->setCount($item[1]);
					if($p instanceof Player){
						$p->getInventory()->addItem($i);
					}
				}
			}else{
				$this->level->setBlock($pos = $this->getPosition($name)->add(1, -3, 0), Block::get(54), true, true);
				$nbt = new Compound(false, [new Enum("Items", []), new String("id", 54), new Int("x", $pos->x), new Int("y", $pos->y), new Int("z", $pos->z)]);
				$nbt->Items->setTagType(NBT::TAG_Compound);
				$chest = Tile::createTile("Chest", $this->level->getChunk($pos->x >> 4, $pos->z >> 4), $nbt);
				foreach($this->mf["Items"] as $item){
					$i = Item::fromString($item[0]);
					$i->setCount($item[1]);
					$chest->getInventory()->addItem($i);
				}
			}
		}
		return true;
	}

	public function isFarm($farm){
		if($farm instanceof Position || $farm instanceof Chunk) return strtolower($farm->getLevel()->getName()) == $this->name;
		else{
			return false;
		}
	}

	public function isLand($farm){
		if($farm instanceof Position) return strtolower($farm->getLevel()->getName()) == $this->name ? $this->isFarm($this->level->getChunk($farm->x >> 4, $farm->z >> 4)) : false;
		if($farm instanceof Chunk){
			$x = $farm->getX();
			$z = $farm->getZ();
			$dd = $this->mf["Size"];
			$d = $this->mf["Distance"] + 1 + $this->mf["Air"] + $dd;
			return $x >= 0 && $x % $d < $dd && $z >= 0 && $z % $d < $dd;
		}else{
			return false;
		}
	}

	public function isMain($farm){
		if($farm instanceof Position) return strtolower($farm->getLevel()->getName()) == $this->name ? $this->isFarm($this->level->getChunk($farm->x >> 4, $farm->z >> 4)) : false;
		if($farm instanceof Chunk){
			$x = $farm->getX();
			$z = $farm->getZ();
			$d = $this->mf["Distance"] + 1 + $this->mf["Size"] + $this->mf["Air"];
			return $x >= 0 && $x % $d === 0 && $z >= 0 && $z % $d === 0;
		}else{
			return false;
		}
	}

	public function isOwn($name, $farm){
		if($name instanceof Player) $name = $name->getName();
		return in_array(strtolower($name), $this->mf["Farm"]) ? $this->getNum($name) == $this->getNum($farm, true) : false;
	}

	public function isInvite($name, $farm){
		if($this->isOwn($name, $farm)) return true;
		if($name instanceof Player) $name = $name->getName();
		if(($this->isFarm($farm) || $this->isFarm($this->getPosition($farm, true))) && $on = $this->getOwnName($farm, true)){return isset($this->mf["Invite"][$on][strtolower($name)]);}
		return false;
	}

	public function isShare($name, $farm){
		if($this->isOwn($name, $farm)) return true;
		if($name instanceof Player) $name = $name->getName();
		if(($this->isFarm($farm) || $this->isFarm($this->getPosition($farm))) && $on = $this->getOwnName($farm, true)){return isset($this->mf["Invite"][$on][$name = strtolower($name)]) && $this->mf["Invite"][$on][$name] === true;}
		return false;
	}

	public function getNum($farm, $isPos = false){
		$d = $this->mf["Distance"] + 1 + $this->mf["Size"] + $this->mf["Air"];
		if(!$isPos && $farm instanceof Player) $farm = $farm->getName();
		if($farm instanceof Position){
			$dd = $this->mf["Size"] + $this->mf["Air"];
			$d = $this->mf["Distance"] + 1 + $dd;
			return floor(($farm->x >> 4) / $d) + floor(($farm->z >> 4) / $d) * 10 + 1;
		}elseif($farm instanceof Chunk){
			return $this->getNum(new Position($farm->x * 16, 12, $farm->z * 16, $this->level));
		}else{
			return array_search(strtolower($farm), $this->mf["Farm"]) + 1;
		}
		return false;
	}

	public function getOwnName($farm, $isPos = false){
		if(($n = $this->getNum($farm, $isPos)) === false) return false;
		return isset($this->mf["Farm"][$n - 1]) ? $this->mf["Farm"][$n - 1] : false;
	}

	public function getPosition($farm, $isPos = false){
		$d = $this->mf["Distance"] + 1 + $this->mf["Size"] + $this->mf["Air"];
		if(!$isPos && $farm instanceof Player) $farm = $farm->getName();
		if($farm instanceof Position){
			return new Position(($farm->x >> 4) * 16 * $d + 8, 12, ($farm->z >> 4) * 16 * $d + 8, $this->level);
		}elseif($farm instanceof Chunk){
			return new Position($farm->x * 16 * $d + 8, 12, $chunk->z * 16 * $d + 8, $this->level);
		}elseif(is_numeric($farm)){
			$farm = floor($farm - 1);
			$x = $farm % 10;
			$z = floor(($farm - $x) / 10);
			return $this->level->getSafeSpawn(new Position($x * 16 * $d + 8, 5, $z * 16 * $d + 8, $this->level));
		}else{
			return $this->getPosition($this->getNum($farm));
		}
	}

	public function getMoney($p){
		if(!$this->money) return false;
		switch($this->money->getName()){
			case "PocketMoney":
			case "MessiveEconomy":
			case "Money":
				return $this->money->getMoney($p);
			break;
			case "EconomyAPI":
				return $this->money->mymoney($p);
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

	public function loadYml(){
		@mkdir($this->path);
		$this->mf = (new Config($this->path . "Farm.yml", Config::YAML, ["Auto" => false, "Sell" => true, "Price" => 100000, "Distance" => 5, "Size" => 1, "Air" => 3, "MineWorld" => "Mine", "MineBlock" => "48:0", "Item" => true, "Items" => [["269:0", 1], ["270:0", 1], ["271:0", 1], ["290:0", 1]], "Farm" => [], "Invite" => []]))->getAll();
		$drops = (new Config($this->path . "Drops.yml", Config::YAML, ["Drop" => ["500" => "1:0", "100" => "16:0", "50" => "15:0", "15" => "73:0", "10" => "14:0", "10" => "21:0", "2" => "56:0", "1" => "129:0"], "MineDrop" => ["500" => "4:0", "200" => "263:0", "50" => "15:0", "3" => "14:0"]]))->getAll();
		$this->drop = [];
		foreach($drops["Drop"] as $per => $id){
			for($for = 0; $for < $per; $for++)
				$this->drop[] = $id;
		}
		$this->mine = [];
		foreach($drops["MineDrop"] as $per => $id){
			for($for = 0; $for < $per; $for++)
				$this->mine[] = $id;
		}
		$this->an = (new Config($this->path . "AutoNotice.yml", Config::YAML, ["On" => true, "Time" => 60, "Message" => ["[MinaFarm] Hello. This server is MineFarm Server \n [MineFarm] MineFarm Plugin is made by MineBlock (huu6677@naver.com)"]]))->getAll();
	}

	public function saveYml(){
		$mf = new Config($this->path . "Farm.yml", Config::YAML);
		$mf->setAll($this->mf);
		$mf->save();
	}

	public function isKorean(){
		return strtolower($this->getServer()->getLanguage()->getName()) == "korean";
	}
}