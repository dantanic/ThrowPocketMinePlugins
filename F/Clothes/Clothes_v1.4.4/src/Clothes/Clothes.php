<?php

namespace Clothes;

use pocketmine\utils\TextFormat as Color;
use pocketmine\Player;

class Clothes extends \pocketmine\plugin\PluginBase implements \pocketmine\event\Listener{
	const CLOTHES = 0x00;
	const PLAYERS = 0x01;
	const SKIN = 0x00;
	const PRICE = 0x01;
	const IS_TOP = 0x02;

	public function onEnable(){
		$this->loadData();
		$this->skins = [];
		foreach($this->getServer()->getOnlinePlayers() as $player){
			$this->skins[$name = strtolower($player->getName())] = $player->getSkinData();
			if(!isset($this->data[self::PLAYERS][$name])){
				$this->data[self::PLAYERS][$name] = [];
			}else{
				$this->dressUp($player);
			}
		}
 		$pluginManager = $this->getServer()->getPluginManager();
		if(!($this->money = $pluginManager->getPlugin("PocketMoney")) && !($this->money = $pluginManager->getPlugin("EconomyAPI")) && !($this->money = $pluginManager->getPlugin("MassiveEconomy")) && !($this->money = $pluginManager->getPlugin("Money"))){
			$this->getLogger()->info(Color::RED . "[Clothes] " . ($this->isKorean() ? "경제 플러그인을 찾지 못했습니다." : "Failed find economy plugin..."));
		}else{
			$this->getLogger()->info(Color::GREEN . "[Clothes] " . ($this->isKorean() ? "경제 플러그인을 찾았습니다. : " : "Finded economy plugin : ") . $this->money->getName());
		}
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
	}

	public function onDisable(){
		$this->saveData();
 		foreach($this->getServer()->getOnlinePlayers() as $player){
			if(isset($this->skins[$name = strtolower($player->getName())])){
				$player->setSkin($this->skins[$name]);
			}
		}
	}

	public function onCommand(\pocketmine\command\CommandSender $sender, \pocketmine\command\Command $cmd, $label, array $sub){
		if(!isset($sub[0])){
			return false;
		}else{
			$ik = $this->isKorean();
			if(($cmd = strtolower($cmd->getName())) == "clothes"){
				if(!($sender instanceof Player)){
					$r = Color::RED . "[Clothes] " . ($ik ? "게임 내에서만 사용해주세요." : "Please run this command in game");
				}else{
					switch(strtolower($sub[0])){
						case "me":
						case "my":
						case "m":
						case "내옷":
							if($count = (count($this->data[self::PLAYERS][$name = strtolower($sender->getName())])) == 0){
								$r = Color::YELLOW . "[Clothes] " . ($ik ? "당신은 옷이 하나도 없습니다." : "You don\'t have any clothes");
							}else{
								$r = Color::YELLOW . "[Clothes] " . ($ik ? "당신은 옷이 " . $count . "벌 있습니다." : "You have " . $count . "clothes");
								sort($this->data[self::PLAYERS][$name]);
								foreach($this->data[self::PLAYERS][$name] as $key => $clothingName){
									if(!isset($this->data[self::CLOTHES][$clothingName])){
										unset($this->data[self::PLAYERS][$name][$clothingName]);
									}else{
										$r .= "\n   " . Color::GOLD . "[" . ($key + 1) . "] " . $clothingName . ($this->data[self::CLOTHES][$clothingName][self::IS_TOP] ? Color::RED . "  [TOP] " : "");
									}
								}
								sort($this->data[self::PLAYERS][$name]);
							}
						break;
						case "buy":
						case "b":
						case "구매":
							if(!$this->money){
								$r = Color::RED . "[Clothes] " . ($ik ? "이 서버는 경제 플러그인이 없습니다." : "This server does not have any economy plugins");
							}elseif(!isset($sub[1]) || $sub[1] == ""){
								$r = Color::RED . "Usage: /Clothes Buy(B) " . ($ik ? "<옷이름>" : "<ClothingName>");
							}elseif(!isset($this->data[self::CLOTHES][$sub[1] = strtolower($sub[1])])){
								$r = Color::RED . "[Clothes] $sub[1]" .  ($ik ? "은(는) 잘못된 옷 이름입니다." : " is invaild clothing name");
							}elseif(in_array($sub[1], $this->data[self::PLAYERS][$name = strtolower($sender->getName())])){
								$r = Color::RED . "[Clothes] " . ($ik ? "당신은 이미 이 옷을 가지고 있습니다" : "You already have this clothing");
							}elseif(($money = $this->getMoney($sender)) < ($price = $this->data[self::CLOTHES][$sub[1]][self::PRICE])){
								$r = Color::RED . "[Clothes] " . ($ik ? "이 옷은 너무 비쌉니다. " . Color::GOLD . "가격 : $price\$, 돈 : $money\$" : "This clothing is too expensive" . Color::GOLD . "Price : $price\$, Money : $money\$");
							}else{
								if(!$this->data[self::CLOTHES][$sub[1]][self::IS_TOP]){
									$clothing = $this->data[self::CLOTHES][$sub[1]][self::SKIN];
									foreach($this->data[self::PLAYERS][$name] as $key => $clothingName){
										if(!isset($this->data[self::CLOTHES][$clothingName])){
											unset($this->data[self::PLAYERS][$name][$clothingName]);
										}elseif(!$this->data[self::CLOTHES][$clothingName][self::IS_TOP]){
											$clothing2 = $this->data[self::CLOTHES][$clothingName][self::SKIN];
	 										for($x = 0; $x < 64; $x++){
												for($y = 0; $y < 32; $y++){
													if($clothing{$key = ($x + ($y * 64)) * 4} . $clothing{$key + 1} . $clothing{$key + 2} !== "\x02\x02\x02" && $clothing{$key + 3} !== "\x00"){
														if($clothing2{$key} . $clothing2{$key + 1} . $clothing2{$key + 2} !== "\x02\x02\x02" && $clothing2{$key + 3} !== "\x00"){
															$sender->sendMessage(Color::RED . ($ik ? "이 옷이 " . $clothingName . "와 겹쳐서 구매할 수 없습니다." : "The clothing overlays with " . $clothingName . "."));
															return true;
														}
													}
												}
											}
										}
									}
								}
								$this->data[self::PLAYERS][$name][] = $sub[1];
								$this->dressUp($sender);
								$this->giveMoney($sender, -$price);
								$r = Color::YELLOW . "[Clothes] " . ($ik ? "당신은 $sub[1]을(를) $price\$에 구매하셨습니다. " . Color::GOLD . "돈 : " : "You bought $sub[1] for $price\$. " . Color::GOLD . "Money : ") . ($money - $price) . "\$";
							}
						break;
						case "sell":
						case "s":
						case "판매":
							if($count = (count($this->data[self::PLAYERS][$name = strtolower($sender->getName())])) == 0){
								$r = Color::YELLOW . "[Clothes] " . ($ik ? "당신은 옷이 하나도 없습니다." : "You don\'t have any clothes");
							}elseif(!$this->money){
								$r = Color::RED . "[Clothes] " . ($ik ? "이 서버는 경제 플러그인이 없습니다." : "This server does not have any economy plugins");
							}elseif(!isset($sub[1]) || $sub[1] == ""){
								$r = Color::RED . "Usage: /Clothes Sell(S) " . ($ik ? "<옷이름>" : "<ClothingName>");
							}elseif(!isset($this->data[self::CLOTHES][$sub[1] = strtolower($sub[1])])){
								$r = Color::RED . "[Clothes] $sub[1]" .  ($ik ? "은(는) 잘못된 옷 이름입니다." : " is invaild clothing name");
							}elseif(!in_array($sub[1], $this->data[self::PLAYERS][$name])){
								$r = Color::RED . "[Clothes] " . ($ik ? "당신은 이 옷을 가지고 있지 않습니다" : "You don\'t have this clothing");
							}else{
								unset($this->data[self::PLAYERS][$name][array_search($sub[1], $this->data[self::PLAYERS][$name])]);
								$this->dressUp($sender);
								$this->giveMoney($sender, $price = $this->data[self::CLOTHES][$sub[1]][self::PRICE] * 0.5);
								$r = Color::YELLOW . "[Clothes] " . ($ik ? "당신은 $sub[1]을(를) $price\$에 판매하셨습니다. " . Color::GOLD . "돈 : " : "You sold $sub[1] for $price\$. " . Color::GOLD . "Money : ") . $this->getMoney($sender) . "\$";
							}
						break;
						case "allsell":
						case "as":
						case "a":
						case "전체판매":
	 						if($count = (count($this->data[self::PLAYERS][$name = strtolower($sender->getName())])) == 0){
								$r = Color::YELLOW . "[Clothes] " . ($ik ? "당신은 옷이 하나도 없습니다." : "You don\'t have any clothes");
							}elseif(!$this->money){
								$r = Color::RED . "[Clothes] " . ($ik ? "이 서버는 경제 플러그인이 없습니다." : "This server doed not have any economy plugins");
							}else{
								$price = 0;
								foreach($this->data[self::PLAYERS][$name] as $key => $clothingName){
									if(isset($this->data[self::CLOTHES][$clothingName])){
										$price += $this->data[self::CLOTHES][$clothingName][self::PRICE];
									}
								}
								$this->data[self::PLAYERS][$name] = [];
								if(isset($this->skins[$name])){
									$sender->setSkin($this->skins[$name]);
								}
								$this->giveMoney($sender, $price = $price * 0.5);
								$r = Color::YELLOW . "[Clothes] " . ($ik ? "당신은 모든 옷을 $price\$에 판매하셨습니다. " . Color::GOLD . "돈 : " : "You sold all of your clothing for $price\$. " . Color::GOLD . "Money : ") . $this->getMoney($sender) . "\$";
							}
						break;
						case "list":
						case "l":
						case "목록":
							$lists = array_chunk($this->data[self::CLOTHES], 5);
							$page = min(isset($sub[1]) && is_numeric($sub[1]) && isset($lists[$sub[1] - 1]) ? $sub[1] : 1, count($lists));
							$r = Color::YELLOW . "[Clothes] " . ($ik ? "옷 목록 (페이지" : "Clothes List (Page") . $page . "/" . count($lists) . ") (" . count($this->data[self::CLOTHES]) . ")";
							if(isset($lists[$page - 1])){
								$keys = array_keys($this->data[self::CLOTHES]);
								foreach($lists[$page - 1] as $index => $data){
									$r .= "\n" . Color::GOLD . "    [" . (($key = ($page - 1) * 5 + $index) + 1) . "] " . $keys[$key] . " => " . $data[self::PRICE] . "\$" . ($data[self::IS_TOP] ? Color::RED . "  [TOP]" : "") . (in_array($keys[$key], $this->data[self::PLAYERS][strtolower($sender->getName())]) ? Color::AQUA . "[HAVE]" : "");
								}
							}
						break;
						case "help":
						case "?":
						case "도움말":
							$r = Color::YELLOW . "[Clothes] " . ($ik ? "도움말" : "Help") . Color::AQUA . "  /Clothes <Me|Buy|Sell|AllSell|List| Help>" ;
							$r .= "\n  " . Color::GOLD . "Me(M) :" . Color::GREEN . ($ik ? "당신이 가진 옷들의 목록을 보여줍니다." : "Shows a list of the clothes you have.");
							$r .= "\n  " . Color::GOLD . "Buy(B) " . ($ik ? "<옷이름> : " . Color::GREEN . "옷을 구매합니다." : "<ClothingName> : " . Color::GREEN . "Buy clothes");
							$r .= "\n  " . Color::GOLD . "Sell(S) " . ($ik ? "<옷이름> : " . Color::GREEN . "당신이 가지고있는 옷을 판매합니다." : "<ClothingName> : " . Color::GREEN . "Sell the clothes you have.");
							$r .= "\n  " . Color::GOLD . "AllSell(A) : " . Color::GREEN . ($ik ? "당신이 가지고있는 모든 옷을 판매합니다." : "Sell all the clothes you have.");
							$r .= "\n  " . Color::GOLD . "List(L) " . ($ik ? "<페이지> : " . Color::GREEN . "옷가게의 옷의 목록을 보여줍니다." : "<Page> : " . Color::GREEN . "Shows a list of clothes in The Clothing Shop");
							$r .= "\n  " . Color::GOLD . "Help(?) : " . Color::GREEN . ($ik ? "도움말을 보여줍니다." : "Shows the help message.");
 						break;
						default:
							return false;
						break;
					}
				}
			}elseif($cmd == "clothesop"){
	 			switch(strtolower($sub[0])){
					case "add":
					case "a":
					case "추가":
						if(!isset($sub[2]) || $sub[1] == "" || !is_numeric($sub[2])){
							$r = Color::RED . "Usage: /ClothesOp Add(A) " . ($ik ? "<옷이름> <가격> (Top)" : "<ClothingName> <Price> (Top)");
						}elseif(isset($this->data[self::CLOTHES][$sub[1] = strtolower($sub[1])])){
							$r = Color::RED . "[Clothes] $sub[1]" .  ($ik ? "은(는) 이미 존재하는 옷입니다." : " is already exists clothing");
						}elseif(!($sender instanceof Player)){
							$r = Color::RED . "[Clothes] " . ($ik ? "게임 내에서만 사용해주세요." : "Please run this command in game");
						}elseif(($sub[2] = floor($sub[2])) < 0){
							$r = Color::RED . "[Clothes] " . ($ik ? "가격이 너무 적습니다." : "The price is too less");
						}else{
							$this->data[self::CLOTHES][strtolower($sub[1])] = [
								self::SKIN => $this->skins[strtolower($sender->getName())],
								self::PRICE => $sub[2],
								self::IS_TOP => ($isTop = isset($sub[3]) && strtolower($sub[3]) == "top")
							];
							$r = Color::YELLOW . "[Clothes] " . ($ik ? $sub[1] . "옷을 옷가게에 추가하였습니다. 가격 : " : "Added $sub[1] clothing to The Clothing Shop. Price : ") . $sub[2] . "\$" . ($isTop ? Color::RED . "  [TOP]" : "");
						}
					break;
					case "del":
					case "delete":
					case "d":
					case "remove":
					case "r":
					case "제거":
						if(!isset($sub[1]) || $sub[1] == ""){
							$r = Color::RED . "Usage: /ClothesOp Del(D) " . ($ik ? "<옷이름>" : "<ClothingName>");
						}elseif(!isset($this->data[self::CLOTHES][$sub[1] = strtolower($sub[1])])){
							$r = Color::RED . "[Clothes] $sub[1]" .  ($ik ? "은(는) 잘못된 옷 이름입니다." : " is invaild clothing name");
						}else{
							foreach($this->data[self::PLAYERS] as $name => $playerData){
								if(in_array($sub[1], $playerData)){
									unset($this->data[self::PLAYERS][$name][array_search($sub[1], $this->data[self::PLAYERS][$name])]);
									if(($player = $this->getServer()->getPlayerExact($name)) instanceof Player){
										$this->dressUp($player);
									}
								}
							}
							unset($this->data[self::CLOTHES][$sub[1]]);
							$r = Color::YELLOW . "[Clothes] " . ($ik ? $sub[1] . " 옷을 옷가게에 제거하였습니다." : "Deleted $sub[1] clothing from The Clothing Shop.");
						}
					break;
					case "list":
					case "l":
					case "목록":
						$lists = array_chunk($this->data[self::CLOTHES], 5);
						$page = min(isset($sub[1]) && is_numeric($sub[1]) && isset($lists[$sub[1] - 1]) ? $sub[1] : 1, count($lists));
						$r = Color::YELLOW . "[Clothes] " . ($ik ? "옷 목록 (페이지" : "Clothes List (Page") . $page . "/" . count($lists) . ") (" . count($this->data[self::CLOTHES]) . ")";
						if(isset($lists[$page - 1])){
							$keys = array_keys($this->data[self::CLOTHES]);
							foreach($lists[$page - 1] as $index => $data){
								$r .= "\n" . Color::GOLD . "  [" . (($key = ($page - 1) * 5 + $index) + 1) . "] " . $keys[$key] . " => " . $data[self::PRICE] . "\$" . ($data[self::IS_TOP] ? Color::RED . "[TOP]" : "");
							}
						}
					break;
					case "give":
					case "g":
					case "지급":
						if(!isset($sub[2]) || $sub[1] == "" || $sub[2] == ""){
							$r = Color::RED . "Usage: /ClothesOp Give(G) " . ($ik ? "<옷이름> <플레이어명>" : "<ClothingName> <PlayerName>");
						}elseif(!isset($this->data[self::CLOTHES][$sub[1] = strtolower($sub[1])])){
							$r = Color::RED . "[Clothes] $sub[1]" .  ($ik ? "은(는) 잘못된 옷 이름입니다." : " is invaild clothing name");
						}elseif(!($player = $this->getServer()->getPlayer($sub[2] = strtolower($sub[2]))) instanceof Player){
							$r = Color::RED . "[Clothes] $sub[2]" .  ($ik ? "은(는) 잘못된 플레이어명입니다." : " is invaild player name");							
						}else{
							if(!$this->data[self::CLOTHES][$sub[1]][self::IS_TOP]){
								$clothing = $this->data[self::CLOTHES][$sub[1]][self::SKIN];
								foreach($this->data[self::PLAYERS][$sub[2]] as $key => $clothingName){
									if(!isset($this->data[self::CLOTHES][$clothingName])){
										unset($this->data[self::PLAYERS][$sub[2]][$clothingName]);
									}elseif(!$this->data[self::CLOTHES][$clothingName][self::IS_TOP]){
										$clothing2 = $this->data[self::CLOTHES][$clothingName][self::SKIN];
 										for($x = 0; $x < 64; $x++){
											for($y = 0; $y < 32; $y++){
												if($clothing{$key = ($x + ($y * 64)) * 4} . $clothing{$key + 1} . $clothing{$key + 2} !== "\x02\x02\x02" && $clothing{$key + 3} !== "\x00"){
													if($clothing2{$key} . $clothing2{$key + 1} . $clothing2{$key + 2} !== "\x02\x02\x02" && $clothing2{$key + 3} !== "\x00"){
														$sender->sendMessage(Color::RED . ($ik ? "이 옷이 " . $clothingName . "와 겹쳐서 구매할 수 없습니다." : "The clothing overlays with " . $clothingName . "."));
														return true;
													}
												}
											}
										}
									}
								}
							}
							$this->data[self::PLAYERS][$sub[2]][] = $sub[1];
							$this->dressUp($player);
							$r = Color::YELLOW . "[Clothes] " . ($ik ? "당신은 $sub[1]을(를) " . $sub[2] . "에게 지급하셨습니다." : "You gave $sub[1] to $sub[2].");
							$player->sendMessage(Color::YELLOW . "[Clothes] " . ($ik ? "당신은 $sub[1]을(를) 지급받으셨습니다." : "You get $sub[1]"));
						}
					break;
					case "take":
					case "t":
					case "회수":
						if(!isset($sub[2]) || $sub[1] == "" || $sub[2] == ""){
							$r = Color::RED . "Usage: /ClothesOp Give(G) " . ($ik ? "<옷이름> <플레이어명>" : "<ClothingName> <PlayerName>");
						}elseif(!isset($this->data[self::CLOTHES][$sub[1] = strtolower($sub[1])])){
							$r = Color::RED . "[Clothes] $sub[1]" .  ($ik ? "은(는) 잘못된 옷 이름입니다." : " is invaild clothing name");
						}elseif(!($player = $this->getServer()->getPlayer($sub[2] = strtolower($sub[2]))) instanceof Player){
							$r = Color::RED . "[Clothes] $sub[2]" .  ($ik ? "은(는) 잘못된 플레이어명입니다." : " is invaild player name");							
						}else{
							unset($this->data[self::PLAYERS][$sub[2]][array_search($sub[1], $this->data[self::PLAYERS][$sub[2]])]);
							$this->dressUp($player);
							$r = Color::YELLOW . "[Clothes] " . ($ik ? "당신은 $sub[1]을(를) " . $sub[2] . "에게서 빼앗았습니다. " : "Took $sub[1] from $sub[2]");
							$player->sendMessage(Color::YELLOW . "[Clothes] " . ($ik ? "당신은 $sub[1]을(를) 빼앗겻습니다." : "$sub[1] was taken from you"));
						}
					break;
					case "view":
					case "v":
					case "보기":
						if(!isset($sub[1]) || $sub[1] == ""){
							$r = Color::RED . "Usage: /ClothesOp Give(G) " . ($ik ? "<옷이름> <플레이어명>" : "<ClothingName> <PlayerName>");
						}elseif(!($player = $this->getServer()->getPlayer($sub[1] = strtolower($sub[1]))) instanceof Player){
							$r = Color::RED . "[Clothes] $sub[1]" .  ($ik ? "은(는) 잘못된 플레이어명입니다." : " is invaild player name");							
						}elseif($count = (count($this->data[self::PLAYERS][$name = strtolower($player->getName())])) == 0){
							$r = Color::YELLOW . "[Clothes] $sub[1]" . ($ik ? "님은 옷이 하나도 없습니다." : " is not have any clothes");
						}else{
							$r = Color::YELLOW . "[Clothes] $sub[1]" . ($ik ? $sub[1] . "님은 옷이 " . $count . "벌 있습니다." : " have " . $count . "clothes");
							sort($this->data[self::PLAYERS][$name]);
							foreach($this->data[self::PLAYERS][$name] as $key => $clothingName){
								if(!isset($this->data[self::CLOTHES][$clothingName])){
									unset($this->data[self::PLAYERS][$name][$clothingName]);
								}else{
									$r .= "\n   " . Color::GOLD . "[" . ($key + 1) . "] " . $clothingName . ($this->data[self::CLOTHES][$clothingName][self::IS_TOP] ? Color::RED . "  [TOP] " : "");
								}
							}
							sort($this->data[self::PLAYERS][$name]);
						}
					break;
					case "resetplayers":
					case "resetp":
					case "플레이어리셋":
						foreach($this->data[self::PLAYERS] as $name => $data){
							if(($player = $this->getServer()->getPlayerExact($name)) instanceof Player){
								$this->data[self::PLAYERS][$name] = [];
								if(isset($this->skins[$name])){
									$player->setSkin($this->skins[$name]);
									$player->sendMessage(Color::YELLOW . "[Clothes] " . ($ik ? "당신은 모든 옷을 빼앗겻습니다." : "all clothes was taken from you"));
								}
							}
						}
						$r = Color::YELLOW . "[Clothes] " . ($ik ? "당신은 플레이어데이터를 지웠습니다.." : "You reset the player data");
					break;
					case "resetclothes":
					case "resetc":
					case "옷리셋":
						foreach($this->data[self::PLAYERS] as $name => $data){
							if(($player = $this->getServer()->getPlayerExact($name)) instanceof Player){
								$this->data[self::PLAYERS][$name] = [];
								if(isset($this->skins[$name])){
									$player->setSkin($this->skins[$name]);
									$player->sendMessage(Color::YELLOW . "[Clothes] " . ($ik ? "당신은 모든 옷을 빼앗겻습니다." : "all clothes was taken from you"));
								}
							}
						}
						$this->data[self::CLOTHES] = [];
						$r = Color::YELLOW . "[Clothes] " . ($ik ? "당신은 모든데이터를 지웠습니다.." : "You reset the all data");
					break;
					case "help":
					case "?":
					case "도움말":
						$r = Color::YELLOW . "[Clothes] " . ($ik ? "도움말" : "Help") . Color::AQUA . "  /ClothesOp <Add|Del|List|Give|Take|View|ResetPlayers|ResetClothes | Help>";
						$r .= "\n  " . Color::GOLD . "Add(A) " . ($ik ? "<옷이름> <가격> : " . Color::GREEN . "옷을 추가합니다." : "<ClothesName> <Price> : " . Color::GREEN . "Add clothes");
						$r .= "\n  " . Color::GOLD . "Del(D) " . ($ik ? "<옷이름> : " . Color::GREEN . "옷을 제거합니다." : "<ClothesName> : " . Color::GREEN . "Delete clothes");
						$r .= "\n  " . Color::GOLD . "List(L) " . ($ik ? "<페이지> : " . Color::GREEN . "옷가게의 옷의 목록을 보여줍니다." : "<Page> : " . Color::GREEN . "Shows a list of clothes in The Clothing Shop");
 						$r .= "\n  " . Color::GOLD . "Give(G) " . ($ik ? "<옷이름> <플레이어명> : " . Color::GREEN . "플레이어에게 옷을 지급합니다." : "<ClothingName> <PlayerName> : " . Color::GREEN . "Gives player a clothing");
						$r .= "\n  " . Color::GOLD . "Take(T) " . ($ik ? "<옷이름> <플레이어명> : " . Color::GREEN . "플레이어의 옷을 빼앗습니다." : "<ClothingName> <PlayerName> : " . Color::GREEN . "Takes clothes of player\'s");
						$r .= "\n  " . Color::GOLD . "ResetPlayers(ResetP) " . Color::GREEN . ": " . ($ik ? "플레이어 데이터를 리셋합니다." : "Reset the player data.");
						$r .= "\n  " . Color::GOLD . "ResetClothes(ResetC) " . Color::GREEN . ": " . ($ik ? "옷 데이터를 리셋합니다." : "Reset the clothes data.");
						$r .= "\n  " . Color::GOLD . "Help(?) : " . Color::GREEN . ($ik ? "도움말을 보여줍니다." : "Shows the help message.");
 					break;				
					default:
						return false;
					break;
				}
			}
			if(isset($r)) $sender->sendMessage($r);
			$this->saveData();
			return true;
		}
	}


	public function onDataPacketReceive(\pocketmine\event\server\DataPacketReceiveEvent $event){
		$packet = $event->getPacket();
		if($packet->pid() == \pocketmine\network\protocol\LoginPacket::NETWORK_ID){
			$this->skins[strtolower(Color::clean($packet->username))] = $packet->skin;
		}
 	}
 
	public function onPlayerJoin(\pocketmine\event\player\PlayerJoinEvent $event){
		$player = $event->getPlayer();
		if(!isset($this->data[self::PLAYERS][$name = strtolower($player->getName())])){
			$this->data[self::PLAYERS][$name] = [];
		}else{
			$this->dressUp($player);
		}
	}

	public function onPlayerQuit(\pocketmine\event\player\PlayerQuitEvent $event){
		$player = $event->getPlayer();
		if(isset($this->skins[$name = strtolower($player->getName())])){
			$player->setSkin($this->skins[$name]);
		}
	}

	public function dressUp(Player $player){
		$skin = $this->skins[$name = strtolower($player->getName())];
		$topClothes = [];
		foreach($this->data[self::PLAYERS][$name] as $key => $clothingName){
			if(!isset($this->data[self::CLOTHES][$clothingName])){
				unset($this->data[self::PLAYERS][$name][array_search($clothingName, $this->data[self::PLAYERS][$name])]);
			}elseif($this->data[self::CLOTHES][$clothingName][self::IS_TOP]){
				$topClothes[] = $clothingName;
			}else{
				$skin = $this->mergeSkin($skin, $this->data[self::CLOTHES][$clothingName][self::SKIN]);
			}
		}
		sort($this->data[self::PLAYERS][$name]);
		foreach($topClothes as $clothingName){
			$skin = $this->mergeSkin($skin, $this->data[self::CLOTHES][$clothingName][self::SKIN]);
		}
		$player->setSkin($skin);
	}

	public function mergeSkin($skin, $clothing){
		for($x = 0; $x < 64; $x++){
			for($y = 0; $y < 32; $y++){
				if($clothing{$key = ($x + ($y * 64)) * 4} . $clothing{$key + 1} . $clothing{$key + 2} == "\x02\x02\x02"){
					$clothing{$key} = $skin{$key};
					$clothing{++$key} = $skin{$key};
					$clothing{++$key} = $skin{$key};
					$clothing{++$key} = $skin{$key};
				}
			}
		}
		return $clothing;
	}

	public function getMoney(Player $player){
		if(!$this->money){
			return false;
		}else{
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
	}

	public function giveMoney(Player $player, $money){
		if(!$this->money){
			return false;
		}else{
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
	}

	public function loadData(){
		@mkdir($folder = $this->getDataFolder());
		if(!file_exists($folder . "Clothes.sl")){	
			file_put_contents($folder . "Clothes.sl", serialize([]));
		}
		if(!file_exists($folder . "Players.sl")){	
			file_put_contents($folder . "Players.sl", serialize([]));
		}
		$this->data = [self::CLOTHES => unserialize(file_get_contents($folder . "Clothes.sl")), self::PLAYERS => unserialize(file_get_contents($folder . "Players.sl"))];
	}

	public function saveData(){
		@mkdir($folder = $this->getDataFolder());
		file_put_contents($folder . "Clothes.sl", serialize($this->data[self::CLOTHES]));
		file_put_contents($folder . "Players.sl", serialize($this->data[self::PLAYERS]));
	}

	public function isKorean(){
		return $this->getServer()->getLanguage()->getName() == "\"한국어\"";
	}
}