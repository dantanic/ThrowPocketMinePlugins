<?php

namespace MineFarm;

use pocketmine\Player;
use pocketmine\block\Block;
use pocketmine\item\Item;
use pocketmine\level\Position;
use pocketmine\level\format\mcregion\Chunk;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat as Color;

class MineFarm extends \pocketmine\plugin\PluginBase implements \pocketmine\event\Listener{
 	public function onLoad(){
 		\pocketmine\level\generator\Generator::addGenerator(MineFarmGenerator::class, $this->name = "minefarm");
		$this->getLogger()->info("[MineFarm] MineFarmGenerator is Loaded");
		$this->player = [];
		$this->tick = 0;
	}

	public function onEnable(){
		$this->path = $this->getDataFolder();
 		$pluginManager = $this->getServer()->getPluginManager();
		if(!($this->money = $pluginManager->getPlugin("PocketMoney")) && !($this->money = $pluginManager->getPlugin("EconomyAPI")) && !($this->money = $pluginManager->getPlugin("MassiveEconomy")) && !($this->money = $pluginManager->getPlugin("Money"))) $this->getLogger()->info(Color::RED . "Failed find economy plugin...");
		else $this->getLogger()->info(Color::GREEN . "Finded economy plugin : " . $this->money->getName());
		$this->loadYml();
		$this->nt = ["Time" => 0, "Count" => 0];
		$gn = $this->getServer()->getLevelType();
		$this->getServer()->setConfigString("level-type", $this->name);
		if(!$this->getServer()->isLevelLoaded($this->name) && !$this->getServer()->loadLevel($this->name)) $this->getServer()->generateLevel($this->name);
		$this->getServer()->setConfigString("level-type", $gn);
		$this->getLogger()->info("[MineFarm] MineFarmWorld is Loaded");
		$this->level = $this->getServer()->getLevelByName($this->name);
		$pluginManager->registerEvents($this, $this);
		$this->getServer()->getScheduler()->scheduleRepeatingTask(new Task($this, [$this, "onTick"]), 10);
	}

	public function onCommand(\pocketmine\command\CommandSender $sender, \pocketmine\command\Command $cmd, $label, array $sub){
		if(!isset($sub[0])) return false;
		$ik = $this->isKorean();
		$smd = strtolower(array_shift($sub));
		$n = strtolower($sender->getName());
		switch(strtolower($cmd->getName())){
			case "myfarm":
				if(!$sender instanceof Player){
					$r = Color::YELLOW . "[MineFarm] " . ($ik ? "게임내에서만 실행해주세요." : "Please run this command in-game");
				}else{
					switch($smd){
						case "move":
						case "my":
						case "me":
						case "m":
						case "이동":
							if(!in_array($n, $this->mf["Farm"])) $r = Color::RED . "[MineFarm] " . ($ik ? "팜을 보유하고있지 않습니다." : "You don't have farm");
							else{
								$sender->teleport($this->getPosition($n));
								$r = Color::YELLOW . "[MineFarm] " . ($ik ? "나의 팜으로 텔레포트되었습니다. : " : "Teleported to your farm. : ") . $this->getNum($sender);
							}
						break;
						case "buy":
						case "b":
						case "구매":
							if(in_array($n, $this->mf["Farm"])) $r = Color::RED . "[MineFarm] " . ($ik ? "이미 팜을 보유하고있습니다." : "You already have farm");
							elseif(!$this->mf["Sell"] || !$this->money) $r = Color::RED . "[MineFarm] " . ($ik ? "이 서버는 팜을 판매하지 않습니다.." : "This server not sell the farm");
							elseif($this->getMoney($sender) < ($pr = $this->mf["Price"])) $r = Color::RED . "[MineFarm] " . ($ik ? "당신은 $pr 보다 돈이 적습니다. 나의 돈 : " : "You don't have $pr $. Your money : ") . $this->getMoney($sender);
							else{
								$this->giveMoney($sender, -$pr);
								$this->giveFarm($sender);
								$r = Color::YELLOW . "[MineFarm] " . ($ik ? "팜을 구매하였습니다. 나의 돈 : " : "Buy the farm. Your money : ") . $this->getMoney($sender) . "\n/‡ " . Color::YELLOW . "[MineFarm] " . ($ik ? "팜 번호 : " : "Farm Number : ") . $this->getNum($n);
							}
						break;
						case "color":
						case "c":
						case "색":
 							if(!isset($sub[2]) || !is_numeric($sub[0]) || !is_numeric($sub[1])|| !is_numeric($sub[2])) $r = Color::RED . "[MineFarm] Color (C) " . Color::RED . "<R> " . Color::GREEN . "<G> " . Color::BLUE . "<B>";
							elseif(!in_array($n, $this->mf["Farm"])) $r = Color::RED . "[MineFarm] " . ($ik ? "팜을 보유하고있지 않습니다." : "You don't have farm");
							elseif(!$this->mf["ColorSell"] || !$this->money) $r = Color::RED . "[MineFarm] " . ($ik ? "이 서버는 팜의 색을 판매하지 않습니다.." : "This server not sell the farm color");
							elseif($this->getMoney($sender) < ($pr = $this->mf["ColorPrice"])) $r = Color::RED . "[MineFarm] " . ($ik ? "당신은 $pr 보다 돈이 적습니다. 나의 돈 : " : "You don't have $pr $. Your money : ") . $this->getMoney($sender);
							else{
								$this->giveMoney($sender, -$pr);
								$farmPos = $this->getPosition($n);
								$startChunk = $this->level->getChunk($farmPos->x >> 4, $farmPos->z >> 4);
								$limit = $this->mf["Distance"] + $this->mf["Size"] + $this->mf["Air"];
								$color = [isset($sub[0]) && is_numeric($sub[0]) ? min(255, max(0, floor($sub[0]))) : 146, isset($sub[1]) && is_numeric($sub[1]) ? min(255, max(0, floor($sub[1]))) : 188, isset($sub[2]) && is_numeric($sub[2]) ? min(255, max(0, floor($sub[2]))) : 88];
								for($chunkX = ($cx = $startChunk->getX() >> 4); $chunkX < $cx + $limit; $chunkX++){
									for($chunkZ = ($cz = $startChunk->getZ() >> 4); $chunkZ < $cz + $limit; $chunkZ++){
										$chunk = $this->level->getChunk($chunkX, $chunkZ);
										for($x = 0; $x < 16; $x++){
											for($z = 0; $z < 16; $z++){
												if($chunk !== null) $chunk->setBiomeColor($x, $z, $color[0], $color[1], $color[2]);
											}
										}
									}
								}
								$r = Color::YELLOW . "[MineFarm] " . ($ik ? "마인팜의 색을 구매하였습니다." : "Buy the $n $nm Minefarm color") . Color::RED . "R: $color[0], " . Color::GREEN . "G: $color[1], " . Color::BLUE . "B: $color[2]";
							}
						break;
						case "visit":
						case "v":
						case "방문":
							if(!isset($sub[0]) || !$sub[0] || (is_numeric($sub[0]) && $sub[0] < 1)) $r = Color::RED . "[MineFarm] Visit(V) " . ($ik ? "<팜번호 or 플레이어명>" : "<FarmNum or PlayerName>");
							else{
								if(is_numeric($sub[0])){
									$fn = floor($sub[0]);
									$nm = $ik ? "번" : "";
								}else{
									$fn = strtolower($sub[0]);
									if(!in_array($fn, $this->mf["Farm"])) $r = Color::RED . "[MineFarm] $fn" . ($ik ? "는 잘못된 플레이어명입니다." : " is invalid player");
									else $nm = $ik ? "님의" : "'s ";
								}
								if(!isset($r)){
									if(!$this->isInvite($n, $fn)) $r = Color::RED . "[MineFarm] " . ($ik ? "$fn $nm 팜에 초대받지 않았습니다." : "You don't invited to $fn $nm farm");
									else{
										$sender->teleport($this->getPosition($fn));
										$r = Color::YELLOW . "[MineFarm] " . ($ik ? "$fn $nm 팜으로 텔레포트되었습니다." : "Teleported to $fn $nm Minefarm");
										if($player = $this->getServer()->getplayerExact($this->getOwnName($fn))) $player->sendMessage(Color::GREEN . "/☜ [MineFarm] $n" . ($ik ? "님이 당신의 팜에 방문햇습니다." : " is invited to your farm."));
									}
								}
							}
						break;
						case "invite":
						case "i":
						case "초대":
							if(!in_array($n, $this->mf["Farm"])) $r = Color::RED . "[MineFarm] " . ($ik ? "팜을 보유하고있지 않습니다." : "You don't have farm");
							elseif(!isset($sub[0])) $r = Color::RED . "Usage: /MyFarm Invite(I) " . ($ik ? "<플레이어명>" : "<PlayerName");
							elseif($this->isInvite($sub[0] = strtolower($sub[0]), $n)) $r = Color::RED . "[MineFarm] $sub[0]" . ($ik ? "님은 이미 초대된 상태입니다." : " is already invited");
							else{
								$this->mf["Invite"][$n][$sub[0]] = false;
								$this->saveYml();
								$r = Color::YELLOW . "[MineFarm] " . ($ik ? "$sub[0] 님을 팜에 초대합니다." : "Invite $sub[0] on my farm");
								if($player = $this->getServer()->getplayerExact($sub[0])) $player->sendMessage(Color::GREEN . "/☜ [MineFarm] " . $n . ($ik ? "님이 당신을 팜에 초대하였습니다." : " invite you out to farm"));
							}
						break;
						case "share":
						case "s":
						case "초대":
							if(!in_array($n, $this->mf["Farm"])) $r = Color::RED . "[MineFarm] " . ($ik ? "마인팜을 보유하고있지 않습니다." : "You don't have MineFarm");
							elseif(!isset($sub[0])) $r = Color::RED . "Usage: /MyFarm Share(S) " . ($ik ? "<플레이어명>" : "<PlayerName");
							elseif($this->isShare($sub[0] = strtolower($sub[0]), $n)) $r = Color::RED . "[MineFarm] $sub[0]". ($ik ? "님은 이미 공유된 상태입니다." : " is already shared");
							else{
								$this->mf["Invite"][$n][$sub[0]] = true;
								$this->saveYml();
								$r = Color::YELLOW . "[MineFarm] " . ($ik ? "$sub[0] 님에게 팜을 공유합니다." : "Shared your farm to $sub[0]");
								if($player = $this->getServer()->getplayerExact($sub[0])) $player->sendMessage(Color::GREEN . "/☜ [MineFarm] $n" . ($ik ? "님이 당신에게 팜을 공유하였습니다." : " shared the farm with you"));
							}
						break;
						case "kick":
						case "k":
						case "강퇴":
							if(!in_array($n, $this->mf["Farm"])) $r = Color::RED . "[MineFarm] " . ($ik ? "마인팜을 보유하고있지 않습니다." : "You don't have MineFarm");
							elseif(!isset($sub[0])) $r = Color::RED . "Usage: /MyFarm Kick(K) " . ($ik ? "<플레이어명>" : "<PlayerName");
							elseif(!$this->isInvite($sub[0] = strtolower($sub[0]), $n)) $r = Color::RED . "[MineFarm] $sub[0]" . ($ik ? "님은 초대되지 않았습니다." : " is not invited");
							else{
								unset($this->mf["Invite"][$n][$sub[0]]);
								$this->saveYml();
								$r = Color::YELLOW . "[MineFarm] " . ($ik ? "$sub[0] 님을 마인팜에서 강퇴합니다." : "Kick $sub[0] on my minefarm");
								if($player = $this->getServer()->getplayerExact($sub[0])) $player->sendMessage(Color::GREEN . "/☜ [MineFarm] " . ($ik ? "$n 님의 팜에서 강퇴되었습니다." : "You are kicked from $n's Minefarm."));
							}
						break;
						case "list":
						case "l":
						case "목록":
							if(!in_array($n, $this->mf["Farm"])) $r = Color::RED . "[MineFarm] " . ($ik ? "마인팜을 보유하고있지 않습니다." : "You don't have MineFarm");
							else{
								$page = 1;
								if(isset($sub[0]) && is_numeric($sub[0])) $page = round($sub[0]);
								$list = array_chunk($this->mf["Invite"][$n], 5, true);
								if($page >= ($c = count($list))) $page = $c;
								$r = Color::YELLOW . "[MineFarm] " . ($ik ? "초대 (공유) 목록 (페이지" : "Invite(Share) List (Page") . " $page/$c) \n";
								$num = ($page - 1) * 5;
								if($c > 0){
									foreach($list[$page - 1] as $k => $v){
										$num++;
										$r .= Color::GOLD . "  [$num] " . (strlen($k) <= 3 ? ($ik ? "오류." : "Error.") : ("[" . ($ik ? ($v ? "공유" : "초대") : ($v ? "Share" : "Invite")) . "] $k\n"));
									}
								}
							}
						break;
						case "here":
						case "h":
						case "여기":
							if(!$this->isFarm($sender)) $r = Color::RED . "[MineFarm] " . ($ik ? "이곳은 팜이 아닙니다." : "Here is not Farm");
							else $r = Color::YELLOW . "[MineFarm] " . ($ik ? "이곳의 팜 번호 : " : "Here farm number : ") . $this->getNum($sender, true) . ",  " . ($this->getOwnName($sender, true) !== false ? ($ik ? "주인 : " : "Own : ") . $this->getOwnName($sender, true) : "");
						break;
						default:
							return false;
						break;
					}
				}
			break;
			case "minefarm":
				switch($smd){
					case "give":
					case "g":
					case "지급":
						if(!isset($sub[0])) $r = Color::RED . "Usage: /MineFarm Give(G) " . ($ik ? "<플레이어명>" : "<PlayerName>");
						elseif(!($player = $this->getServer()->getPlayer($sub[0]))) $r = Color::RED . "[MineFarm] $sub[0]" . ($ik ? "는 잘못된 플레이어명입니다." : " is invalid player");
						elseif(in_array(strtolower($player->getName()), $this->mf["Farm"])) $r = Color::RED . "[MineFarm] $sub[0]" . ($ik ? "님은 이미 마인팜을 소유중입니다. " : " is already have minefarm");
						else{
							$num = $this->giveFarm($player) + 1;
							$pn = $player->getName();
							$r = Color::YELLOW . "[MineFarm] " . ($ik ? "$pn 님에게 마인팜을 지급했습니다. : " : "Give the minefarm to $pn : ") . ($num = $this->getNum($player));
							$player->sendMessage(Color::YELLOW . Color::GREEN . "[MineFarm] " . ($ik ? "마인팜을 지급받았습니다. : " : "Now you have your minefarm : ") . $num);
						}
					break;
					case "move":
					case "m":
					case "이동":
						if(!isset($sub[0]) || !$sub[0] || (is_numeric($sub[0]) && $sub[0] < 1)) $r = Color::RED . "[MineFarm] Move(M) " . ($ik ? "<땅번호 or 플레이어명>" : "<FarmNum or PlayerName>");
						else{
							if(is_numeric($sub[0])){
								$n = floor($sub[0]);
								$nm = $ik ? "번" : "";
							}else{
								$n = $sub[0];
								if(!in_array($n, $this->mf["Farm"])) $r = Color::RED . "[MineFarm] $n" . ($ik ? "는 잘못된 플레이어명입니다." : " is invalid player");
								else $nm = $ik ? "님의" : "'s ";
							}
							if(!isset($r)){
								$sender->teleport($this->getPosition($n));
								$r = Color::YELLOW . "[MineFarm] " . ($ik ? "$n $nm 마인팜으로 텔레포트되었습니다." : "Teleported to $n $nm Minefarm");
							}
						}
					break;
					case "here":
					case "h":
					case "여기":
						if(!$sender instanceof Player) $r = Color::RED . "[MineFarm] " . ($ik ? "게임내에서만 실행해주세요." : "Please run this command in-game");
						elseif(!$this->isFarm($sender)) $r = Color::RED . "[MineFarm] " . ($ik ? "이곳은 팜이 아닙니다." : "Here is not Farm");
						else $r = Color::YELLOW . "[MineFarm] " . ($ik ? "이곳의 팜 번호 : " : "Here farm number : ") . $this->getNum($sender, true) . ",  " . ($this->getOwnName($sender, true) !== false ? ($ik ? "주인 : " : "Own : ") . $this->getOwnName($sender, true) : "");
					break;
					case "distace":
					case "d":
					case "거리":
					case "간격":
						if(!isset($sub[0])) $r = Color::RED . "Usage: /MineFarm Distance(D) " . ($ik ? "<거리>" : "<Distance>");
						elseif(!is_numeric($sub[0]) || $sub[0] < 0) $r = Color::RED . "[MineFarm] $sub[0]" . ($ik ? "는 잘못된 숫자입니다." : " is invalid number");
						else{
							$this->mf["Distance"] = floor($sub[0]);
							$this->saveYml();
							$r = Color::YELLOW . "[MineFarm] " . ($ik ? " 마인팜간 간격이 $sub[0] 으로 설정되엇습니다." : "minefarm distance is set to $sub[0]");
						}
					break;
					case "size":
					case "sz":
					case "크기":
						if(!isset($sub[0])) $r = Color::RED . "Usage: /MineFarm Size(Sz)" . ($ik ? "<크기>" : "<Size>");
						elseif(!is_numeric($sub[0])) $r = Color::RED . "[MineFarm] $sub[0]" . ($ik ? "는 잘못된 숫자입니다." : " is invalid number");
						else{
							$this->mf["Size"] = floor($sub[0]);
							$this->saveYml();
							$r = Color::YELLOW . "[MineFarm] " . ($ik ? " 마인팜의 크기가 $sub[0] 으로 설정되엇습니다." : "minefarm size is set to $sub[0]");
						}
					break;
					case "air":
					case "a":
					case "공기":
						if(!isset($sub[0])) $r = Color::RED . "Usage: /MineFarm Air(A)" . ($ik ? "<공기청크수>" : "<AirSize>");
						elseif(!is_numeric($sub[0]) || $sub[0] < 0) $r = Color::RED . "[MineFarm] $sub[0]" . ($ik ? "는 잘못된 숫자입니다." : " is invalid number");
						else{
							$this->mf["Air"] = floor($sub[0]);
							$this->saveYml();
							$r = Color::YELLOW . "[MineFarm] " . ($ik ? " 마인팜의 공기지역 크기가 $sub[0] 으로 설정되엇습니다." : "minefarm air place size is set to $sub[0]");
						}
					break;
					case "sell":
					case "s":
					case "판매":
						$a = !$this->mf["Sell"];
						$this->mf["Sell"] = $a;
						$this->saveYml();
						$m = Color::GREEN . "[MineFarm] " . ($ik ? "이제 마인팜을 판매" . ($a ? "합" : "하지않습") . "니다." : "Now " . ($a ? "" : "not ") . "sell the minefarm");
					break;
					case "price":
					case "p":
					case "가격":
						if(!isset($sub[0])) $r = Color::RED . "Usage: /MineFarm Price(P) " . ($ik ? "<가격>" : "<Price>");
						elseif(!is_numeric($sub[0]) || $sub[0] < 0) $r = Color::RED . "[MineFarm] " . $sub[0] . ($ik ? "는 잘못된 숫자입니다." : " is invalid number");
						else{
							$this->mf["Price"] = floor($sub[0]);
							$this->saveYml();
							$m = Color::GREEN . "[MineFarm] " . ($ik ? "마인팜의 가격이 $sub[0] 으로 설정되엇습니다." : "minefarm price is set to $sub[0]");
						}
					break;
					case "colorsell":
					case "cs":
					case "색판매":
						$a = !$this->mf["ColorSell"];
						$this->mf["ColorSell"] = $a;
						$this->saveYml();
						$m = Color::GREEN . "[MineFarm] " . ($ik ? "이제 마인팜의 색을 판매" . ($a ? "합" : "하지않습") . "니다." : "Now " . ($a ? "" : "not ") . "sell the minefarm color");
					break;
					case "colorprice":
					case "cp":
					case "색가격":
						if(!isset($sub[0])) $r = Color::RED . "Usage: /MineFarm ColorPrice(CP) " . ($ik ? "<가격>" : "<Price>");
						elseif(!is_numeric($sub[0]) || $sub[0] < 0) $r = Color::RED . "[MineFarm] " . $sub[0] . ($ik ? "는 잘못된 숫자입니다." : " is invalid number");
						else{
							$this->mf["ColorPrice"] = floor($sub[0]);
							$this->saveYml();
							$m = Color::GREEN . "[MineFarm] " . ($ik ? "마인팜색의 가격이 $sub[0] 으로 설정되엇습니다." : "minefarm color price is set to $sub[0]");
						}
					break;
					case "auto":
					case "at":
					case "자동":
						$a = !$this->mf["Auto"];
						$this->mf["Auto"] = $a;
						$this->saveYml();
						if($a){
							foreach($this->getServer()->getOnlinePlayers() as $player){
								if($this->giveFarm($player)) $player->sendMessage("[MineFarm] [Auto] " . ($ik ? "마인팜을 지급받았습니다. : " : "Now you gave minefarm. : ") . $this->getNum($p));
							}
						}
						$m = Color::GREEN . "[MineFarm] " . ($ik ? "이제 마인팜을 자동 분배" . ($a ? "합" : "하지않습") . "니다." : "Now " . ($a ? "" : "not ") . "auto give the minefarm");
					break;
					case "item":
					case "i":
					case "아이템":
						$a = !$this->mf["Item"];
						$this->mf["Item"] = $a;
						$this->saveYml();
						$m = Color::GREEN . "[MineFarm] " . ($ik ? "이제 기초 지급템을 " . ($a ? "줍" : "주지않습") . "니다." : "Now " . ($a ? "" : "not ") . "give the first item");
					break;
					case "list":
					case "l":
					case "목록":
						$page = 1;
						if(isset($sub[0]) && is_numeric($sub[0])) $page = max(floor($sub[0]), 1);
						$list = array_chunk($this->mf["Farm"], 5, true);
						if($page >= ($c = count($list))) $page = $c;
						$r = Color::YELLOW . "[MineFarm] " . ($ik ? "마인팜 목록 (페이지" : "MineFarm List (Page") . " $page/$c) \n";
						$num = ($page - 1) * 5;
						if($c > 0){
							foreach($list[$page - 1] as $v){
								$num++;
								$r .= "  [$num] $v\n";
							}
						}
					break;
					case "reset":
					case "리셋":
						$this->mf["Farm"] = [];
						$this->mf["Invite"] = [];
						$this->saveYml();
						if($this->mf["Auto"]){
							foreach($this->getServer()->getOnlinePlayers() as $p){
								if($this->giveFarm($p)) $p->sendMessage("[MineFarm] [Auto] " . ($ik ? "마인팜을 지급받았습니다. : " : "Now you gave minefarm. : ") . $this->getNum($p));
							}
						}
						$r = Color::YELLOW . "[MineFarm] " . ($ik ? "리셋됨" : "Reset");
					break;
					case "color":
					case "c":
						if(!isset($sub[0]) || !$sub[0] || (is_numeric($sub[0]) && $sub[0] < 1)) $r = Color::RED . "[MineFarm] Color(C) " . ($ik ? "<땅번호 or 플레이어명>" : "<FarmNum or PlayerName>") . Color::RED . " (R) " . Color::GREEN . "(G) " . Color::BLUE . "(B)";
						else{
							if(is_numeric($sub[0])){
								$n = floor($sub[0]);
								$nm = $ik ? "번" : "";
							}else{
								$n = $sub[0];
								if(!in_array($n, $this->mf["Farm"])) $r = Color::RED . "[MineFarm] $n" . ($ik ? "는 잘못된 플레이어명입니다." : " is invalid player");
								else $nm = $ik ? "님의" : "'s ";
							}
							if(!isset($r)){
								$farmPos = $this->getPosition($n);
								$startChunk = $this->level->getChunk($farmPos->x >> 4, $farmPos->z >> 4);
								$limit = $this->mf["Distance"] + $this->mf["Size"] + $this->mf["Air"];
								$color = [isset($sub[1]) && is_numeric($sub[1]) ? min(255, max(0, floor($sub[1]))) : 146, isset($sub[2]) && is_numeric($sub[2]) ? min(255, max(0, floor($sub[2]))) : 188, isset($sub[3]) && is_numeric($sub[3]) ? min(255, max(0, floor($sub[3]))) : 88];
								for($chunkX = ($cx = $startChunk->getX() >> 4); $chunkX < $cx + $limit; $chunkX++){
									for($chunkZ = ($cz = $startChunk->getZ() >> 4); $chunkZ < $cz + $limit; $chunkZ++){
										$chunk = $this->level->getChunk($chunkX, $chunkZ);
										for($x = 0; $x < 16; $x++){
											for($z = 0; $z < 16; $z++){
												if($chunk !== null) $chunk->setBiomeColor($x, $z, $color[0], $color[1], $color[2]);
											}
										}
									}
								}
								$r = Color::YELLOW . "[MineFarm] " . ($ik ? "$n $nm 마인팜의 바이옴색을 변경하였습니다.." : "Change the $n $nm Minefarm biome color") . Color::RED . "R: $color[0], " . Color::GREEN . "G: $color[1], " . Color::BLUE . "B: $color[2]";
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

	public function onPlayerMove(\pocketmine\event\player\PlayerMoveEvent $event){
		$player = $event->getPlayer();
		if($this->isFarm($from = $event->getFrom()) && !$player->hasPermission("minefarm.move") && $this->isLand($from) && (!$this->isLand($to = $event->getTo()) || !$this->isOwn($player, $to) && !$this->isInvite($player, $to))) $event->setCancelled();
	}

	public function onPlayerInteract(\pocketmine\event\player\PlayerInteractEvent $event){
		if(!$this->isOwn($player = $event->getPlayer(), $block = $event->getBlock()) && !$event->isCancelled() && !$player->hasPermission("minefarm.block") && $this->isFarm($block) && !$this->isShare($player, $block) || !$this->isFarm($block) && !$player->hasPermission("minefarm.block")) $event->setCancelled();
	}

	public function onBlockBreak(\pocketmine\event\block\BlockBreakEvent $event){
		if(!$this->isOwn($player = $event->getPlayer(), $block = $event->getBlock()) && !$event->isCancelled() && !$player->hasPermission("minefarm.block") && $this->isFarm($block) && !$this->isShare($player, $block) || !$this->isFarm($block) && !$player->hasPermission("minefarm.block")) $event->setCancelled();
	}

	public function onBlockPlace(\pocketmine\event\block\BlockPlaceEvent $event){
		if(!$this->isOwn($player = $event->getPlayer(), $block = $event->getBlock()) && !$event->isCancelled() && !$player->hasPermission("minefarm.block") && $this->isFarm($block) && !$this->isShare($player, $block) || !$this->isFarm($block) && !$player->hasPermission("minefarm.block")) $event->setCancelled();
	}

	public function onBlockUpdate(\pocketmine\event\block\BlockUpdateEvent $event){
		if($this->isFarm($block = $event->getBlock()) && !$this->isMain($block) && in_array($block->getID(), [8, 9, 10, 11])) $event->setCancelled();
	}

	public function onTick(){
		$ik = $this->isKorean();
		foreach($this->getServer()->getOnlinePlayers() as $p){
			$m = ["", ""];
			if(in_array(strtolower($p->getName()), $this->mf["Farm"])) $m[0] .= Color::GOLD."MyFarm: ".Color::YELLOW.$this->getNum($p);
			if($this->getMoney($p) !== false) $m[0] .= ($m[0] !== "" ? ",  " : "").Color::GOLD."Money: ".Color::YELLOW.$this->getMoney($p);
			if($m[0] !== "") $m[0] = Color::ITALIC.str_pad($m[0], 80, " ", STR_PAD_LEFT);
			if($this->isFarm($p)){
				$m[1] = Color::DARK_BLUE."Here:".Color::BLUE.$this->getNum($p, true);
				if($this->getOwnName($p, true) !== false) $m[1] .= Color::DARK_BLUE.",  Owner:".Color::BLUE.$this->getOwnName($p, true);
				$m[1] = Color::RESET."\n".Color::ITALIC.str_pad($m[1], 80, " ", STR_PAD_LEFT);
			}
			$p->sendPopup(implode($m, ""). Color::RESET."\n".str_pad(Color::ITALIC.Color::DARK_RED."X:" .Color::RED.floor($p->x).Color::DARK_RED." Y:".Color::RED.floor($p->y).Color::DARK_RED." Z: ".Color::RED.floor($p->z).Color::RESET."\n", 95, " ", STR_PAD_LEFT).str_pad(Color::ITALIC.Color::DARK_GREEN."Join[".Color::GREEN.count($this->getServer()->getOnlinePlayers()).Color::DARK_GREEN."/".Color::GREEN.$this->getServer()->getConfigString("max-players", 20).Color::DARK_GREEN."]....", 90, " ", STR_PAD_LEFT).["-", "\\", ".|", "/"][$this->tick]);
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
		}elseif($playetExact = $this->getServer()->getPlayerExact($name)){
			$player = $playerExact;
			$name = strtolower($player->getName());
		}
		if(in_array($name, $this->mf["Farm"])) return false;
		$this->mf["Farm"][] = $name;
		$this->mf["Invite"][$name] = [];
		$this->saveYml();
		if(isset($player)) $player->setSpawn($this->getPosition($name));
		if($this->mf["Item"]){
			if(isset($player)) $inventory = $player->getInventory();
			else{
				$this->level->setBlock($pos = $this->getPosition($name)->add(1, -3, 0), Block::get(54), true, true);
				$nbt = new Compound(false, [new Enum("Items", []), new String("id", 54), new Int("x", $pos->x), new Int("y", $pos->y), new Int("z", $pos->z)]);
				$nbt->Items->setTagType(NBT::TAG_Compound);
				$chest = Tile::createTile("Chest", $this->level->getChunk($pos->x >> 4, $pos->z >> 4), $nbt);
				$inventory = $chest->getInventory();
			}
			foreach($this->mf["Items"] as $info){
				$item = Item::fromString($info[0]);
				$item->setCount($info[1]);
				$inventory->addItem($item);
			}
		}
		return true;
	}

	public function isFarm($farm){
		return ($farm instanceof Position || $farm instanceof Chunk) && strtolower($farm->getLevel()->getName()) == $this->name;
	}

	public function isLand($farm){
		if($farm instanceof Position) return strtolower($farm->getLevel()->getName()) == $this->name ? $this->isFarm($this->level->getChunk($farm->x >> 4, $farm->z >> 4)) : false;
		if($farm instanceof Chunk){
			$x = $farm->getX();
			$z = $farm->getZ();
			$dd = $this->mf["Size"];
			$d = $this->mf["Distance"] + 1 + $this->mf["Air"] + $dd;
			return $x >= 0 && $x % $d < $dd && $z >= 0 && $z % $d < $dd;
		}else return false;
	}

	public function isMain($farm){
		if($farm instanceof Position) return strtolower($farm->getLevel()->getName()) == $this->name ? $this->isFarm($this->level->getChunk($farm->x >> 4, $farm->z >> 4)) : false;
		if($farm instanceof Chunk){
			$x = $farm->getX();
			$z = $farm->getZ();
			$d = $this->mf["Distance"] + 1 + $this->mf["Size"] + $this->mf["Air"];
			return $x >= 0 && $x % $d === 0 && $z >= 0 && $z % $d === 0;
		}else return false;
	}

	public function isOwn($name, $farm){
		if($name instanceof Player) $name = $name->getName();
		return in_array(strtolower($name), $this->mf["Farm"]) ? $this->getNum($name) == $this->getNum($farm, true) : false;
	}

	public function isInvite($name, $farm){
		if($this->isOwn($name, $farm)) return true;
		if($name instanceof Player) $name = $name->getName();
		if(($this->isFarm($farm) || $this->isFarm($this->getPosition($farm, true))) && $ownName = $this->getOwnName($farm, true)) return isset($this->mf["Invite"][$ownName][strtolower($name)]);
		return false;
	}

	public function isShare($name, $farm){
		if($this->isOwn($name, $farm)) return true;
		if($name instanceof Player) $name = $name->getName();
		if(($this->isFarm($farm) || $this->isFarm($this->getPosition($farm))) && $ownName = $this->getOwnName($farm, true)) return isset($this->mf["Invite"][$ownName][$name = strtolower($name)]) && $this->mf["Invite"][$ownName][$name] === true;
		return false;
	}

	public function getNum($farm, $isPos = false){
		$d = $this->mf["Distance"] + 1 + $this->mf["Size"] + $this->mf["Air"];
		if(!$isPos && $farm instanceof Player) $farm = $farm->getName();
		if($farm instanceof Position){
			$dd = $this->mf["Size"] + $this->mf["Air"];
			$d = $this->mf["Distance"] + 1 + $dd;
			return floor(($farm->x >> 4) / $d) + floor(($farm->z >> 4) / $d) * 10 + 1;
		}elseif($farm instanceof Chunk) return $this->getNum(new Position($farm->x * 16, 12, $farm->z * 16, $this->level));
		else return array_search(strtolower($farm), $this->mf["Farm"]) + 1;
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
		}elseif($farm instanceof Chunk) return new Position($farm->x * 16 * $d + 8, 12, $chunk->z * 16 * $d + 8, $this->level);
		elseif(is_numeric($farm)){
			$farm = floor($farm - 1);
			$x = $farm % 10;
			$z = floor(($farm - $x) / 10);
			for($y = 128; $y > 1; $y--){
				if($this->level->getBlock(new Position($x * $d * 16 + 8, $y, $z * $d * 16 + 8, $this->level))->getID() !== 0) break;
			}
			return $this->level->getSafeSpawn(new Position($x * $d * 16 + 8, $y + 2.123, $z * $d * 16 + 8, $this->level));
		}else return $this->getPosition($this->getNum($farm));
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
		@mkdir($this->path);
		$setting = (new Config($this->path . "Setting.yml", Config::YAML, ["Auto" => false, "Sell" => true, "Price" => 100000, "ColorSell" => true, "ColorPrice" => 10000, "Distance" => 5, "Size" => 1, "Air" => 3, "Item" => true, "Items" => [["269:0", 1], ["270:0", 1], ["271:0", 1], ["290:0", 1]]]))->getAll();
		$this->mf = ["Auto" => $setting["Auto"], "Sell" => $setting["Sell"], "Price" => $setting["Price"], "ColorSell" => $setting["ColorSell"], "ColorPrice" => $setting["ColorPrice"], "Distance" => $setting["Distance"], "Size" => $setting["Size"], "Air" => $setting["Air"], "Item" => $setting["Item"], "Items" => $setting["Items"], "Farm" => (new Config($this->path . "User_Farm.yml", Config::YAML))->getAll(), "Invite" => (new Config($this->path . "User_Invite.yml", Config::YAML))->getAll()];
		$this->an = (new Config($this->path . "AutoNotice.yml", Config::YAML, ["On" => true, "Time" => 60, "Message" => ["[MinaFarm] This server used MineFarm"]]))->getAll();
	}

	public function saveYml(){
		$setting = new Config($this->path . "Setting.yml", Config::YAML);
		$setting->setAll(["Auto" => $this->mf["Auto"], "Sell" => $this->mf["Sell"], "Price" => $this->mf["Price"], "ColorSell" => $this->mf["ColorSell"], "ColorPrice" => $this->mf["ColorPrice"], "Distance" => $this->mf["Distance"], "Size" => $this->mf["Size"], "Air" => $this->mf["Air"], "Item" => $this->mf["Item"], "Items" => $this->mf["Items"]]);
		$setting->save();
		$farm = new Config($this->path . "User_Farm.yml", Config::YAML);
		$farm->setAll($this->mf["Farm"]);
		$farm->save();
		$invite = new Config($this->path . "User_Invite.yml", Config::YAML);
		$invite->setAll($this->mf["Invite"]);
		$invite->save();
	}

	public function isKorean(){
		return $this->getServer()->getLanguage()->getName() == "\"한국어\"";
	}
}