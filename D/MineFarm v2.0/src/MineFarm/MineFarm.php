<?php

namespace MineFarm;

use pocketmine\Player;
use pocketmine\block\Block;
use pocketmine\item\Item;
use pocketmine\math\Vector3;
use pocketmine\level\Position;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat as Color;

class MineFarm extends \pocketmine\plugin\PluginBase implements \pocketmine\event\Listener{

 	public function onLoad(){
 		\pocketmine\level\generator\Generator::addGenerator(MineFarmGenerator::class, $this->name = "minefarm");
		$this->player = [];
		$this->levels = [];
		$this->nt = ["Time" => 0, "Count" => 0];
		$this->tick = 0;
	}

	public function onEnable(){
		$this->path = $this->getDataFolder();
 		$pluginManager = $this->getServer()->getPluginManager();
		if(!($this->money = $pluginManager->getPlugin("PocketMoney")) && !($this->money = $pluginManager->getPlugin("EconomyAPI")) && !($this->money = $pluginManager->getPlugin("MassiveEconomy")) && !($this->money = $pluginManager->getPlugin("Money"))) $this->getLogger()->info(Color::RED . "Failed find economy plugin...");
		else $this->getLogger()->info(Color::GREEN . "Finded economy plugin : " . $this->money->getName());
		$this->loadData();
		for($num = 1; $num <= (floor(count($this->mf["FarmList"]) * 0.01)); $num++) $this->getLevel($num, true);
		$pluginManager->registerEvents($this, $this);
		$this->getServer()->getScheduler()->scheduleRepeatingTask(new Task($this, [$this, "onTick"]), 5);
	}

	public function onCommand(\pocketmine\command\CommandSender $sender, \pocketmine\command\Command $cmd, $label, array $sub){
		if(!isset($sub[0])) return false;
		$ik = $this->isKorean();
		$name = strtolower($sender->getName());
		switch(strtolower($cmd->getName())){
			case "myfarm":
				if(!$sender instanceof Player){
					$r = Color::YELLOW . "[MineFarm] " . ($ik ? "게임내에서만 실행해주세요." : "Please run this command in-game");
				}else{
					switch(strtolower($sub[0])){
						case "move":
						case "my":
						case "me":
						case "m":
						case "이동":
							if(!in_array($name, $this->mf["FarmList"])) $r = Color::RED . "[MineFarm] " . ($ik ? "팜을 보유하고있지 않습니다." : "You don't have farm");
							else{
								$sender->teleport($this->getSpawn($num = $this->getMineFarmNumberByName($name)));
								$r = Color::YELLOW . "[MineFarm] " . ($ik ? "나의 팜으로 텔레포트되었습니다. : " : "Teleported to your farm. : ") . $num;
							}
						break;
						case "buy":
						case "b":
						case "구매":
							if(in_array($name, $this->mf["FarmList"])) $r = Color::RED . "[MineFarm] " . ($ik ? "이미 팜을 보유하고있습니다." : "You already have farm");
							elseif(!$this->mf["Sell"] || !$this->money) $r = Color::RED . "[MineFarm] " . ($ik ? "이 서버는 팜을 판매하지 않습니다.." : "This server not sell the farm");
							elseif($this->getMoney($sender) < ($pr = $this->mf["Price"])) $r = Color::RED . "[MineFarm] " . ($ik ? "당신은 $pr 보다 돈이 적습니다. 나의 돈 : " : "You don't have $pr $. Your money : ") . $this->getMoney($sender);
							else{
								$this->giveMoney($sender, -$pr);
								$this->giveFarm($sender->getName());
								$r = Color::YELLOW . "[MineFarm] " . ($ik ? "팜을 구매하였습니다. 나의 돈 : " : "Buy the farm. Your money : ") . $this->getMoney($sender) . "\n/‡ " . Color::YELLOW . "[MineFarm] " . ($ik ? "팜 번호 : " : "Farm Number : ") . $this->getMineFarmNumberByName($name);
							}
						break;
						case "color":
						case "c":
						case "색":
 							if(!isset($sub[3]) || !is_numeric($sub[1]) || !is_numeric($sub[2])|| !is_numeric($sub[3])) $r = Color::RED . "[MineFarm] Color (C) " . Color::RED . "<R> " . Color::GREEN . "<G> " . Color::BLUE . "<B>";
							elseif(!in_array($name, $this->mf["FarmList"])) $r = Color::RED . "[MineFarm] " . ($ik ? "팜을 보유하고있지 않습니다." : "You don't have farm");
							elseif(!$this->mf["ColorSell"] || !$this->money) $r = Color::RED . "[MineFarm] " . ($ik ? "이 서버는 팜의 색을 판매하지 않습니다.." : "This server not sell the farm color");
							elseif($this->getMoney($sender) < ($pr = $this->mf["ColorPrice"])) $r = Color::RED . "[MineFarm] " . ($ik ? "당신은 $pr 보다 돈이 적습니다. 나의 돈 : " : "You don't have $pr $. Your money : ") . $this->getMoney($sender);
							else{
								$this->giveMoney($sender, -$pr);
								$color = [isset($sub[1]) && is_numeric($sub[1]) ? min(255, max(0, floor($sub[1]))) : 146, isset($sub[2]) && is_numeric($sub[2]) ? min(255, max(0, floor($sub[2]))) : 188, isset($sub[3]) && is_numeric($sub[3]) ? min(255, max(0, floor($sub[3]))) : 88];
								$this->setMineFarmColor($this->getMineFarmNumberByName($name), ...$color);
								$r = Color::YELLOW . "[MineFarm] " . ($ik ? "마인팜의 색을 구매하였습니다." : "Buy the $n $nm Minefarm color") . Color::RED . "R: $color[0], " . Color::GREEN . "G: $color[1], " . Color::BLUE . "B: $color[2]";
							}
						break;
						case "visit":
						case "v":
						case "방문":
							if(!isset($sub[1]) || !$sub[1] || (is_numeric($sub[1]) && $sub[1] < 1)) $r = Color::RED . "[MineFarm] Visit(V) " . ($ik ? "<팜번호 or 플레이어명>" : "<FarmNum or PlayerName>");
							else{
								if(is_numeric($sub[1])){
									$num = floor($sub[1]);
									$msg = $ik ? $num . "번" : $num;
								}else{
									$num = $this->getMineFarmNumberByName($playerName = strtolower($sub[1]));
									if(!in_array($playerName, $this->mf["FarmList"])) $r = Color::RED . "[MineFarm] $playerName" . ($ik ? "는 잘못된 플레이어명입니다." : " is invalid player");
									else $msg = $ik ? $playerName . "님의" : $playerName . "\'s ";
								}
								if(!isset($r)){
									if(!$this->isInvite($name, $num)) $r = Color::RED . "[MineFarm] " . ($ik ? "당신은 $msg 팜에 초대받지 않았습니다." : "You don't invited to $msg farm");
									else{
										$sender->teleport($this->getSpawn($num));
										$r = Color::YELLOW . "[MineFarm] " . ($ik ? "$msg 팜으로 텔레포트되었습니다." : "Teleported to $msg Minefarm");
										if($player = $this->getServer()->getplayerExact($this->getOwnerNameByNumber($num))) $player->sendMessage(Color::GREEN . "/☜ [MineFarm] $name" . ($ik ? "님이 당신의 팜에 방문햇습니다." : " is invited to your farm."));
									}
								}
							}
						break;
						case "invite":
						case "i":
						case "초대":
							if(!in_array($name, $this->mf["FarmList"])) $r = Color::RED . "[MineFarm] " . ($ik ? "팜을 보유하고있지 않습니다." : "You don't have farm");
							elseif(!isset($sub[1])) $r = Color::RED . "Usage: /MyFarm Invite(I) " . ($ik ? "<플레이어명>" : "<PlayerName");
							elseif($this->isInvite($sub[1] = strtolower($sub[1]), $this->getMineFarmNumberByName($name))) $r = Color::RED . "[MineFarm] $sub[1]" . ($ik ? "님은 이미 초대된 상태입니다." : " is already invited");
							else{
								$this->mf["InviteList"][$name][$sub[1]] = false;
								$this->saveData();
								$r = Color::YELLOW . "[MineFarm] " . ($ik ? "$sub[1] 님을 팜에 초대합니다." : "Invite $sub[1] on my farm");
								if($player = $this->getServer()->getplayerExact($sub[1])) $player->sendMessage(Color::GREEN . "/☜ [MineFarm] $name" . ($ik ? "님이 당신을 팜에 초대하였습니다." : " invite you out to farm"));
							}
						break;
						case "share":
						case "s":
						case "초대":
							if(!in_array($name, $this->mf["FarmList"])) $r = Color::RED . "[MineFarm] " . ($ik ? "팜을 보유하고있지 않습니다." : "You don't have farm");
							elseif(!isset($sub[1])) $r = Color::RED . "Usage: /MyFarm Share(S) " . ($ik ? "<플레이어명>" : "<PlayerName");
							elseif($this->isShare($sub[1] = strtolower($sub[1]), $this->getMineFarmNumberByName($name))) $r = Color::RED . "[MineFarm] $sub[1]". ($ik ? "님은 이미 공유된 상태입니다." : " is already shared");
							else{
								$this->mf["InviteList"][$name][$sub[1]] = true;
								$this->saveData();
								$r = Color::YELLOW . "[MineFarm] " . ($ik ? "$sub[1] 님에게 팜을 공유합니다." : "Shared your farm to $sub[1]");
								if($player = $this->getServer()->getplayerExact($sub[1])) $player->sendMessage(Color::GREEN . "/☜ [MineFarm] $name" . ($ik ? "님이 당신에게 팜을 공유하였습니다." : " shared the farm with you"));
							}
						break;
						case "kick":
						case "k":
						case "강퇴":
							if(!in_array($name, $this->mf["FarmList"])) $r = Color::RED . "[MineFarm] " . ($ik ? "팜을 보유하고있지 않습니다." : "You don't have farm");
							elseif(!isset($sub[1])) $r = Color::RED . "Usage: /MyFarm Kick(K) " . ($ik ? "<플레이어명>" : "<PlayerName");
							elseif(!$this->isInvite($sub[1] = strtolower($sub[1]), $this->getMineFarmNumberByName($name))) $r = Color::RED . "[MineFarm] $sub[1]" . ($ik ? "님은 초대되지 않았습니다." : " is not invited");
							else{
								unset($this->mf["InviteList"][$name][$sub[1]]);
								$this->saveData();
								$r = Color::YELLOW . "[MineFarm] " . ($ik ? "$sub[1] 님을 마인팜에서 강퇴합니다." : "Kick $sub[1] on my minefarm");
								if($player = $this->getServer()->getplayerExact($sub[1])) $player->sendMessage(Color::GREEN . "/☜ [MineFarm] " . ($ik ? "$name 님의 팜에서 강퇴되었습니다." : "You are kicked from $name's Minefarm."));
							}
						break;
						case "allkick":
						case "ak":
							if(!in_array($name, $this->mf["FarmList"])) $r = Color::RED . "[MineFarm] " . ($ik ? "팜을 보유하고있지 않습니다." : "You don't have farm");
							else{
								foreach($this->mf["InviteList"][$name] as $inviteName){	
									if($player = $this->getServer()->getplayerExact($inviteName)) $player->sendMessage(Color::GREEN . "/☜ [MineFarm] " . ($ik ? "$name 님의 팜에서 강퇴되었습니다." : "You are kicked from $name's Minefarm."));
								}
								$this->mf["InviteList"][$name] = [];
								$this->saveData();
								$r = Color::YELLOW . "[MineFarm] " . ($ik ? "마인팜에서 모두 강퇴합니다." : "Kick all on my minefarm");
							}
						break;
						case "invitelist":
						case "sharelist":
						case "list":
						case "l":
						case "목록":
							if(!in_array($name, $this->mf["FarmList"])) $r = Color::RED . "[MineFarm] " . ($ik ? "마인팜을 보유하고있지 않습니다." : "You don't have MineFarm");
							else{
								$lists = array_chunk($this->mf["InviteList"][$name], 5);
								$page = min(isset($sub[1]) && is_numeric($sub[1]) && isset($lists[$sub[1] - 1]) ? $sub[1] : 1, count($lists));
								$r = Color::YELLOW . "[MineFarm] " . ($ik ? "초대 (공유) 목록 (페이지" : "Invite(Share) List (Page") . $page . "/" . count($lists) . ") (" . count($this->mf["InviteList"][$name]) . ")";
								if(isset($lists[$page - 1])){
									$keys = array_keys($this->mf["InviteList"][$name]);
									foreach($lists[$page - 1] as $key => $invite) $r .= "\n" . Color::GOLD . "    [" . (($inviteKey = (($page - 1) * 5 + $key)) + 1) .  "] " . $keys[$inviteKey] . ("[" . ($ik ? ($invite ? "공유" : "초대") : ($invite ? "Share" : "InviteList")) . "]");
								}
							}
						break;
						case "open":
						case "o":
						case "전체초대":
							if(!in_array($name, $this->mf["FarmList"])) $r = Color::RED . "[MineFarm] " . ($ik ? "팜을 보유하고있지 않습니다." : "You don't have farm");
							else{
								$this->mf["OpenList"][$key = $this->getMineFarmNumberByName($name) - 1] = !isset($this->mf["OpenList"][$key]) ? true : !$this->mf["OpenList"][$key];
								$this->saveData();
								$r = Color::GREEN . "[MineFarm] " . ($ik ? "마인팜을 " . ($this->mf["OpenList"][$key] ? "오픈했" : "닫았") . "습니다." : ($this->mf["OpenList"][$key] ? "Open " : "Close ") . " the minefarm");
							}
						break;
						case "here":
						case "h":
						case "여기":
							if(!$this->isMineFarmLand($sender)) $r = Color::RED . "[MineFarm] " . ($ik ? "이곳은 팜이 아닙니다." : "Here is not Farm");
							else $r = Color::YELLOW . "[MineFarm] " . ($ik ? "이곳의 팜 번호 : " : "Here farm number : ") . ($num = $this->getMineFarmNumberByPosition($sender)) . ",  " . (($owner = $this->getOwnerNameByNumber($num)) !== false ? ($ik ? "주인 : " : "Owner : ") . $owner : "");
						break;
						default:
							return false;
						break;
					}
				}
			break;
			case "minefarm":
				switch(strtolower($sub[0])){
					case "give":
					case "g":
					case "지급":
						if(!isset($sub[1])) $r = Color::RED . "Usage: /MineFarm Give(G) " . ($ik ? "<플레이어명>" : "<PlayerName>");
						elseif(!($player = $this->getServer()->getPlayer($sub[1]))) $r = Color::RED . "[MineFarm] $sub[1]" . ($ik ? "는 잘못된 플레이어명입니다." : " is invalid player");
						elseif(in_array(strtolower($player->getName()), $this->mf["FarmList"])) $r = Color::RED . "[MineFarm] $sub[1]" . ($ik ? "님은 이미 마인팜을 소유중입니다. " : " is already have minefarm");
						else{
							$num = $this->giveFarm($player->getName()) + 1;
							$r = Color::YELLOW . "[MineFarm] " . ($ik ? $player->getName() . " 님에게 마인팜을 지급했습니다. : " : "Give the minefarm to " . $player->getName() . " : ") . $num;
							$player->sendMessage(Color::YELLOW . Color::GREEN . "[MineFarm] " . ($ik ? "마인팜을 지급받았습니다. : " : "Now you have your minefarm : ") . $num);
						}
					break;
					case "move":
					case "m":
					case "이동":
						if(!isset($sub[1]) || !$sub[1] || (is_numeric($sub[1]) && $sub[1] < 1)) $r = Color::RED . "[MineFarm] Move(M) " . ($ik ? "<땅번호 or 플레이어명>" : "<FarmNum or PlayerName>");
						else{
							if(is_numeric($sub[1])){
								$num = floor($sub[1]);
								$msg = $ik ? $num . "번" : $num;
							}else{
								$num = $this->getMineFarmNumberByName($playerName = strtolower($sub[1]));
								if(!in_array($playerName, $this->mf["FarmList"])) $r = Color::RED . "[MineFarm] $playerName" . ($ik ? "는 잘못된 플레이어명입니다." : " is invalid player");
								else $msg = $ik ? $playerName . "님의" : $playerName . "\'s ";
							}
							if(!isset($r)){
								$sender->teleport($this->getSpawn($num));
								$r = Color::YELLOW . "[MineFarm] " . ($ik ? "$msg 마인팜으로 텔레포트되었습니다." : "Teleported to $msg Minefarm");
							}
						}
					break;
					case "here":
					case "h":
					case "여기":
						if(!$sender instanceof Player) $r = Color::RED . "[MineFarm] " . ($ik ? "게임내에서만 실행해주세요." : "Please run this command in-game");
						elseif(!$this->isMineFarmLand($sender)) $r = Color::RED . "[MineFarm] " . ($ik ? "이곳은 팜이 아닙니다." : "Here is not Farm");
						else $r = Color::YELLOW . "[MineFarm] " . ($ik ? "이곳의 팜 번호 : " : "Here farm number : ") . ($num = $this->getMineFarmNumberByPosition($sender)) . ",  " . (($owner = $this->getOwnerNameByNumber($num)) !== false ? ($ik ? "주인 : " : "Owner : ") . $owner : "");
					break;
					case "sell":
					case "s":
					case "판매":
						$this->mf["Sell"] = !$this->mf["Sell"];
						$this->saveData();
						$m = Color::GREEN . "[MineFarm] " . ($ik ? "이제 마인팜을 판매" . ($this->mf["Sell"] ? "합" : "하지않습") . "니다." : "Now " . ($this->mf["Sell"] ? "" : "not ") . "sell the minefarm");
					break;
					case "price":
					case "p":
					case "가격":
						if(!isset($sub[1])) $r = Color::RED . "Usage: /MineFarm Price(P) " . ($ik ? "<가격>" : "<Price>");
						elseif(!is_numeric($sub[1]) || $sub[1] < 0) $r = Color::RED . "[MineFarm] " . $sub[1] . ($ik ? "는 잘못된 숫자입니다." : " is invalid number");
						else{
							$this->mf["Price"] = floor($sub[1]);
							$this->saveData();
							$m = Color::GREEN . "[MineFarm] " . ($ik ? "마인팜의 가격이 $sub[1] 으로 설정되엇습니다." : "minefarm price is set to $sub[1]");
						}
					break;
					case "colorsell":
					case "cs":
					case "색판매":
						$this->mf["ColorSell"] = !$this->mf["ColorSell"];
						$this->saveData();
						$m = Color::GREEN . "[MineFarm] " . ($ik ? "이제 마인팜의 색을 판매" . ($this->mf["ColorSell"] ? "합" : "하지않습") . "니다." : "Now " . ($this->mf["ColorSell"] ? "" : "not ") . "sell the minefarm color");
					break;
					case "colorprice":
					case "cp":
					case "색가격":
						if(!isset($sub[1])) $r = Color::RED . "Usage: /MineFarm ColorPrice(CP) " . ($ik ? "<가격>" : "<Price>");
						elseif(!is_numeric($sub[1]) || $sub[1] < 0) $r = Color::RED . "[MineFarm] " . $sub[1] . ($ik ? "는 잘못된 숫자입니다." : " is invalid number");
						else{
							$this->mf["ColorPrice"] = floor($sub[1]);
							$this->saveData();
							$m = Color::GREEN . "[MineFarm] " . ($ik ? "마인팜색의 가격이 $sub[1] 으로 설정되엇습니다." : "minefarm color price is set to $sub[1]");
						}
					break;
					case "auto":
					case "at":
					case "자동":
						$this->mf["Auto"] = !$this->mf["Auto"];
						$this->saveData();
						if($this->mf["Auto"]){
							foreach($this->getServer()->getOnlinePlayers() as $player){
								if($this->giveFarm($player->getName())) $player->sendMessage("[MineFarm] [Auto] " . ($ik ? "마인팜을 지급받았습니다. : " : "Now you gave minefarm. : ") . $this->getMineFarmNumber($p));
							}
						}
						$m = Color::GREEN . "[MineFarm] " . ($ik ? "이제 마인팜을 자동 분배" . ($this->mf["Auto"] ? "합" : "하지않습") . "니다." : "Now " . ($this->mf["Auto"] ? "" : "not ") . "auto give the minefarm");
					break;
					case "list":
					case "l":
					case "목록":
						$lists = array_chunk($this->mf["FarmList"], 5);
						$page = min(isset($sub[1]) && is_numeric($sub[1]) && isset($lists[$sub[1] - 1]) ? $sub[1] : 1, count($lists));
						$r = Color::YELLOW . "[MineFarm] " . ($ik ? "마인팜 목록 (페이지" : "MineFarm List (Page") . $page . "/" . count($lists) . ") (" . count($this->mf["FarmList"]) . ")";
						if(isset($lists[$page - 1])){
							foreach($lists[$page - 1] as $key => $ownerName) $r .= "\n" . Color::GOLD . "    [" . (($page - 1) * 5 + $key + 1) . "] $ownerName";
						}
					break;
					case "color":
					case "c":
						if(!isset($sub[1]) || !$sub[1] || (is_numeric($sub[1]) && $sub[1] < 1)) $r = Color::RED . "[MineFarm] Color(C) " . ($ik ? "<땅번호 or 플레이어명>" : "<FarmNum or PlayerName>") . Color::RED . " (R) " . Color::GREEN . "(G) " . Color::BLUE . "(B)";
						else{
							if(is_numeric($sub[1])){
								$num = floor($sub[1]);
								$msg = $ik ? $num . "번" : $num;
							}else{
								$num = $this->getMineFarmNumberByName($name = strtolower($sub[1]));
								if(!in_array($name, $this->mf["FarmList"])) $r = Color::RED . "[MineFarm] $name" . ($ik ? "는 잘못된 플레이어명입니다." : " is invalid player");
								else $msg = $ik ? $name . "님의" : $name . "\'s ";
							}
							if(!isset($r)){
								$color = [isset($sub[2]) && is_numeric($sub[2]) ? min(255, max(0, floor($sub[2]))) : 146, isset($sub[3]) && is_numeric($sub[3]) ? min(255, max(0, floor($sub[3]))) : 188, isset($sub[4]) && is_numeric($sub[4]) ? min(255, max(0, floor($sub[4]))) : 88];
								$this->setMineFarmColor($num, ...$color);
								$r = Color::YELLOW . "[MineFarm] " . ($ik ? "$msg 마인팜의 바이옴색을 변경하였습니다.." : "Change the $msg Minefarm biome color") . Color::RED . "R: $color[0], " . Color::GREEN . "G: $color[1], " . Color::BLUE . "B: $color[2]";
							}
						}
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

	public function onPlayerJoin(\pocketmine\event\player\PlayerJoinEvent $event){
		if($this->mf["Auto"]) $this->giveFarm($event->getPlayer());
		$event->getPlayer()->sendMessage($event->getJoinMessage());
		$event->setJoinMessage("");
	}

	public function onPlayerQuit(\pocketmine\event\player\PlayerQuitEvent $event){
		$event->getPlayer()->sendMessage($event->getQuitMessage());
		$event->setQuitMessage("");
	}

	public function onPlayerDeath(\pocketmine\event\player\PlayerDeathEvent $event){
		$event->getEntity()->sendMessage($event->getDeathMessage());
		$event->setDeathMessage("");
	}

	public function onBlockUpdate(\pocketmine\event\block\BlockUpdateEvent $event){
		if($this->isMineFarmWorld($block = $event->getBlock()) && in_array($block->getID(), [8, 9, 10, 11])) $event->setCancelled();
	}

	public function onPlayerMove(\pocketmine\event\player\PlayerMoveEvent $event){
 		if($this->isMineFarmWorld($player = $event->getPlayer())){
			if(!$this->isMineFarmLand($to = $event->getTo())){
				$event->setCancelled(); 				
			}elseif(!$player->hasPermission("minefarm.move")){
				if(!$this->isOwner($name = $player->getName(), $num = $this->getMineFarmNumberByPosition($to))){
					if(!$this->isInvite($name, $num)){
						$event->setCancelled();
					}
				}
			}
		}
	}

	public function onPlayerInteract(\pocketmine\event\player\PlayerInteractEvent $event){
 		if($this->isMineFarmWorld($player = $event->getPlayer())){
			if(!$this->isMineFarmLand($player)){
				$event->setCancelled(); 				
			}elseif(!$player->hasPermission("minefarm.block")){
 				if(!$this->isOwner($name = $player->getName(), $num = $this->getMineFarmNumberByPosition($block = $event->getBlock()))){
 					if(!$this->isShare($name, $num)){
 						$event->setCancelled();
 					}
 				}
 			}
 		}
	}

	public function onBlockBreak(\pocketmine\event\block\BlockBreakEvent $event){
 		if($this->isMineFarmWorld($player = $event->getPlayer())){
			if(!$this->isMineFarmLand($player)){
				$event->setCancelled(); 				
			}elseif(!$player->hasPermission("minefarm.block")){
 				if(!$this->isOwner($name = $player->getName(), $num = $this->getMineFarmNumberByPosition($block = $event->getBlock()))){
 					if(!$this->isShare($name, $num)){
 						$event->setCancelled();
 					}
 				}
 			}
 		}
	}

	public function onBlockPlace(\pocketmine\event\block\BlockPlaceEvent $event){
 		if($this->isMineFarmWorld($player = $event->getPlayer())){
			if(!$this->isMineFarmLand($player)){
				$event->setCancelled(); 				
			}elseif(!$player->hasPermission("minefarm.block")){
 				if(!$this->isOwner($name = $player->getName(), $num = $this->getMineFarmNumberByPosition($block = $event->getBlock()))){
 					if(!$this->isShare($name, $num)){
 						$event->setCancelled();
 					}
 				}
 			}
 		}
	}

	public function onInventoryPickupItem(\pocketmine\event\inventory\InventoryPickupItemEvent $event){
		if(($inventory = $event->getInventory()) instanceof \pocketmine\inventory\PlayerInventory){
			if(($player = $inventory->getHolder()) instanceof Player){
		 		if($this->isMineFarmWorld($player)){
 					if(!$this->isOwner($name = $player->getName(), $num = $this->getMineFarmNumberByPosition($player))){
 						if(!$this->isShare($name, $num)){
 							$event->setCancelled();
 						}
 					}
 				}
 			}
 		}
	}

	public function onTick(){
		$ik = $this->isKorean();
		foreach($this->getServer()->getOnlinePlayers() as $player){
			$msg = ["", ""];
			if(in_array($name = strtolower($player->getName()), $this->mf["FarmList"])) $msg[0] .= Color::GOLD . "MyFarm: " . Color::YELLOW . $this->getMineFarmNumberByName($name);
			if($this->getMoney($player) !== false) $msg[0] .= ($msg[0] !== "" ? ",  " : "") . Color::GOLD . "Money: " . Color::YELLOW . $this->getMoney($player);
			if($msg[0] !== "") $msg[0] = Color::ITALIC . str_pad($msg[0], 80, " ", STR_PAD_LEFT);
			if($this->isMineFarmWorld($player)){
				$msg[1] = Color::DARK_BLUE . "Here:" . Color::BLUE . ($hereNum = $this->getMineFarmNumberByPosition($player));
				if(($ownerName = $this->getOwnerNameByNumber($hereNum)) !== false) $msg[1] .= Color::DARK_BLUE . ",  Owner:" . Color::BLUE . " " . $ownerName;
				$msg[1] = Color::RESET . "\n" . Color::ITALIC . str_pad($msg[1], 80, " ", STR_PAD_LEFT);
			}
			$player->sendPopup(implode($msg, "") . Color::RESET . "\n" . str_pad(Color::ITALIC . Color::WHITE . "[" . $player->getLevel()->getFolderName(). "] " . Color::DARK_RED . "X:" . Color::RED . floor($player->x) . Color::DARK_RED . " Y:" . Color::RED . floor($player->y) . Color::DARK_RED . " Z: " . Color::RED . floor($player->z) . Color::RESET . "\n", 95, " ", STR_PAD_LEFT) . str_pad(Color::ITALIC . Color::DARK_GREEN . "Join[" . Color::GREEN . count($this->getServer()->getOnlinePlayers()) . Color::DARK_GREEN . "/" . Color::GREEN . $this->getServer()->getConfigString("max-players", 20) . Color::DARK_GREEN . "]....", 90, " ", STR_PAD_LEFT) . ["-", "\\", ".|", "/"][$this->tick]);
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

	public function giveFarm($name){
		if($name instanceof Player){
			$player = $name;
			$name = strtolower($player->getName());
		}elseif($playerExact = $this->getServer()->getPlayerExact($name)){
			$player = $playerExact;
			$name = strtolower($player->getName());
		}
		if(in_array($name, $this->mf["FarmList"])) return false;
		$this->mf["FarmList"][] = $name;
		$this->mf["InviteList"][$name] = [];
		$this->saveData();
		return true;
	}

	public function isMineFarmWorld(Position $pos){
		return strpos($pos->getLevel()->getName(), "MineFarm_") === 0;
	}

	public function isMineFarmLand(Position $pos){
		return $this->isMineFarmWorld($pos) && $pos->x >= 0 && $pos->z >= 0 && $pos->x <= 10000 && $pos->z <= 10000 && ($x = $pos->x % 100) > 0 && $x < 99 && ($z = $pos->x % 100) > 0 && $z < 99;
	}

	public function isOwner($name, $num){
		return $num !== false && $this->getMineFarmNumberByName($name) == $num;
	}

	public function isInvite($name, $num){
		return $num !== false && (
			$this->isOpen($num) || 
			$this->isOwner($name = strtolower($name), $num) || 
			isset($this->mf["InviteList"][$ownName = $this->getOwnerNameByNumber($num)]) && 
			isset($this->mf["InviteList"][$ownName][$name])
		);
	}

	public function isShare($name, $num){
		return $num !== false && (
			$this->isOwner($name = strtolower($name), $num) || 
			isset($this->mf["InviteList"][$ownName = $this->getOwnerNameByNumber($num)]) && 
			isset($this->mf["InviteList"][$ownName][$name]) && $this->mf["InviteList"][$ownName][$name] === true
		);
	}

	public function isOpen($num){
		return $this->mf["OpenList"][--$num] = !isset($this->mf["OpenList"][$num]) ? false : $this->mf["OpenList"][$num];
	}

	public function getMineFarmNumberByName($name){
		return in_array($name = strtolower($name), $this->mf["FarmList"]) ? array_search($name, $this->mf["FarmList"]) + 1 : false;
	}

	public function getMineFarmNumberByPosition(Position $pos){
		return !$this->isMineFarmWorld($pos) || $pos->x < 0 || $pos->z < 0 || $pos->x > 10000 || $pos->z > 10000 ? false : (substr($pos->getLevel()->getName(), 9, 10) - 1) * 100 +  floor($pos->x * (1 / 100)) * 10 + floor($pos->z * ( 1 / 100)) + 1;
	}

	public function getOwnerNameByNumber($num){
		return isset($this->mf["FarmList"][--$num]) ? $this->mf["FarmList"][$num] : false;
	}

	public function getLevel($num, $isLevelNum = false){
		if(!$this->getServer()->isLevelGenerated($levelName = "MineFarm_" . ($isLevelNum ? $num : floor($num * 0.01) + 1))) $this->getServer()->generateLevel($levelName, null, MineFarmGenerator::class);
		else $this->getServer()->loadLevel($levelName);
		return $this->getServer()->getLevelByName($levelName);
	}

	public function getSpawn($num){
		return $this->getLevel(--$num)->getSafeSpawn(new Vector3(floor(($num = $num % 100) * 0.1) * 100 + 50, 5, ($num % 10) * 100 + 50));
	}

	public function setMineFarmColor($num, $r, $g, $b){
		$level = $this->getLevel(--$num);
		for($x = ($xx = floor(($num = $num % 100) * 0.1) * 100); $x < $xx + 100; $x++){
			for($z = ($zz = ($num % 10) * 100); $z < $zz + 100; $z++){
				$level->setBiomeColor($x, $z, $r, $g, $b);
			}			
		}
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

	public function loadData(){
		@mkdir($this->path);
		$setting = (new Config($this->path . "Setting.yml", Config::YAML, ["Auto" => false, "Sell" => true, "Price" => 100000, "ColorSell" => true, "ColorPrice" => 5000]))->getAll();
		$this->mf = ["Auto" => $setting["Auto"], "Sell" => $setting["Sell"], "Price" => $setting["Price"], "ColorSell" => $setting["ColorSell"], "ColorPrice" => $setting["ColorPrice"], "FarmList" => (new Config($this->path . "User_Farm.yml", Config::YAML))->getAll(), "InviteList" => (new Config($this->path . "User_Invite.yml", Config::YAML))->getAll(), "OpenList" => (new Config($this->path . "User_Open.yml", Config::YAML))->getAll()];
		$this->an = (new Config($this->path . "AutoNotice.yml", Config::YAML, ["On" => true, "Time" => 60, "Message" => ["[MineFarm] This server used MineFarm"]]))->getAll();
	}

	public function saveData(){
		$setting = new Config($this->path . "Setting.yml", Config::YAML);
		$setting->setAll(["Auto" => $this->mf["Auto"], "Sell" => $this->mf["Sell"], "Price" => $this->mf["Price"], "ColorSell" => $this->mf["ColorSell"], "ColorPrice" => $this->mf["ColorPrice"]]);
		$setting->save();
		$farm = new Config($this->path . "User_Farm.yml", Config::YAML);
		$farm->setAll($this->mf["FarmList"]);
		$farm->save();
		$invite = new Config($this->path . "User_Invite.yml", Config::YAML);
		$invite->setAll($this->mf["InviteList"]);
		$invite->save();
		$open = new Config($this->path . "User_Open.yml", Config::YAML);
		$open->setAll($this->mf["OpenList"]);
		$open->save();
	}

	public function isKorean(){
		return $this->getServer()->getLanguage()->getName() == "\"한국어\"";
	}
}