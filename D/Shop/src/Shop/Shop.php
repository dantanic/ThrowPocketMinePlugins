<?php

namespace Shop;

use pocketmine\Player;
use pocketmine\item\Item;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat as Color;

class Shop extends \pocketmine\plugin\PluginBase implements \pocketmine\event\Listener{
	const BUY = 0;
	const SELL = 1;

	static $modes = [
		self::BUY => [
			"buy",
			"b",
			"구매",
			"삼"
		],
		self::SELL => [
			"sell",
			"s",
			"판매",
			"팜"
		]
	];

	public function onLoad(){
		$this->AddItemEntityPacket = new \pocketmine\network\protocol\AddItemEntityPacket();
		$this->AddItemEntityPacket->speedX = $this->AddItemEntityPacket->speedY = $this->AddItemEntityPacket->speedZ = 0;
		$this->RemoveEntityPacket = new \pocketmine\network\protocol\RemoveEntityPacket();
		$this->MovePlayerPacket = new \pocketmine\network\protocol\MovePlayerPacket();
	 	$this->touch = $this->tap = $this->place = $this->players = $this->eids = $this->editors = [];
	}

	public function onEnable(){
		$this->getServer()->getLogger()->info(Color::GREEN . "Find economy plugin...");
		$pluginManager = $this->getServer()->getPluginManager();
		if(!($this->money = $pluginManager->getPlugin("PocketMoney")) && !($this->money = $pluginManager->getPlugin("EconomyAPI")) && !($this->money = $pluginManager->getPlugin("MassiveEconomy")) && !($this->money = $pluginManager->getPlugin("Money"))){
			$this->getServer()->getLogger()->info(Color::RED . "Failed find economy plugin...");
			$this->getLogger()->info(Color::RED . ($this->isKorean() ? "이 플러그인은 머니 플러그인이 반드시 있어야합니다." : "This plugin need the Money plugin"));
			$this->getServer()->shutdown();
		}else{
			$this->getServer()->getLogger()->info(Color::GREEN . "Finded economy plugin : " . $this->money->getName());
			$this->loadYml();
			$this->getServer()->getPluginManager()->registerEvents($this, $this);
			$this->getServer()->getScheduler()->scheduleRepeatingTask(new Task($this, [$this, "onTick"]), 5);
		}
	}

	public function onCommand(\pocketmine\command\CommandSender $sender, \pocketmine\command\Command $cmd, $label, array $sub){
		if(!isset($sub[0])) return false;
		$sh = $this->sh;
		$t = $this->touch;
		$ik = $this->isKorean();
		switch(strtolower($sub[0])){
			case "add":
			case "a":
			case "추가":
				if(isset($t[$name = $sender->getName()])){
					$r = Color::YELLOW . "[Shop] " . ($ik ? "상점 편집모드 해제" : "Shop Edit Mode Disable");
					unset($t[$name]);
				}else{
					if(!isset($sub[4])){
						$r = Color::RED .  "Usage: /Shop Add(A) " . ($ik ? "<구매|판매> <아이템ID> <갯수> <가격>" : "<Buy|Sell> <ItemID> <Amount> <Price>");
					}else{
						$i = Item::fromString($sub[2]);
						if(!in_array(strtolower($sub[1]), self::$modes[$mode = self::BUY]) && !in_array(strtolower($sub[1]), self::$modes[$mode = self::SELL])){
							$r = Color::RED . "[Shop] " . "$sub[1] " . ($ik ? "는 잘못된 모드입니다. (구매/판매)" : "is invalid Mode (Buy/Sell)");
						}elseif($i->getID() == 0){
							$r = Color::RED . "[Shop] " . "$sub[2] " . ($ik ? "는 잘못된 아이템ID입니다." : "is invalid ItemID");
						}elseif(!is_numeric($sub[3]) || $sub[3] < 1){
							$r = Color::RED . "[Shop] " . "$sub[3] " . ($ik ? "는 잘못된 갯수입니다." : "is invalid count");
						}elseif(!is_numeric($sub[4]) || $sub[4] < 0){
							$r = Color::RED . "[Shop] " . "$sub[4] " . ($ik ? "는 잘못된 가격입니다." : "is invalid price");
						}else{
							$id = $i->getID() . ":" . $i->getDamage();
							$r = Color::YELLOW . "[Shop] " . ($ik ? "대상 블럭을 터치해주세요." : "Touch the target block");
							$t[$name] = ["Type" => "Add", "Mode" => $mode, "Item" => $id, "Count" => floor($sub[3]), "Price" => floor($sub[4])];
						}
					}
				}
			break;
			case "del":
			case "d":
			case "삭제":
			case "제거":
				if(isset($t[$name = $sender->getName()])){
					$r = Color::YELLOW . "[Shop] " . ($ik ? "상점 편집모드 해제" : "Shop Edit Mode Disable"); 					unset($t[$name]);
					unset($t[$name]);
 				}elseif(isset($sub[1]) && strtolower($sub[1]) == "mode"){
					$r = Color::YELLOW . "[Shop] " . ($ik ? "[제거모드] 제거할 상점을 터치해주세요." : "[DelMode] Touch the target shop");
					$t[$name] = ["Type" => "DelMode"];					
				}else{
					$r = Color::YELLOW . "[Shop] " . ($ik ? "제거할 상점을 터치해주세요. " : "Touch the target shop");
					$t[$name] = ["Type" => "Del"];
				}
			break;
			case "change":
			case "c":
			case "바꾸기":
				if(isset($t[$name = $sender->getName()])){
					$r = Color::YELLOW . "[Shop] " . ($ik ? "상점 편집모드 해제" : "Shop Edit Mode Disable");
					unset($t[$name]);
				}else{
					if(!isset($sub[4])){
						$r = Color::RED .  "Usage: /Shop Change(C) " . ($ik ? "<구매|판매> <아이템ID> <갯수> <가격>" : "<Buy|Sell> <ItemID> <Amount> <Price>");
					}else{
						$i = Item::fromString($sub[2]);
						if(!in_array(strtolower($sub[1]), self::$modes[$mode = self::BUY]) && !in_array(strtolower($sub[1]), self::$modes[$mode = self::SELL])){
							$r = Color::RED . "[Shop] " . "$sub[1] " . ($ik ? "는 잘못된 모드입니다. (구매/판매)" : "is invalid Mode (Buy/Sell)");
						}elseif($i->getID() == 0){
							$r = Color::RED . "[Shop] " . "$sub[2] " . ($ik ? "는 잘못된 아이템ID입니다." : "is invalid ItemID");
						}elseif(!is_numeric($sub[3]) || $sub[3] < 1){
							$r = Color::RED . "[Shop] " . "$sub[3] " . ($ik ? "는 잘못된 갯수입니다." : "is invalid count");
						}elseif(!is_numeric($sub[4]) || $sub[4] < 0){
							$r = Color::RED . "[Shop] " . "$sub[4] " . ($ik ? "는 잘못된 가격입니다." : "is invalid price");
						}else{
							$id = $i->getID() . ":" . $i->getDamage();
							$r = Color::YELLOW . "[Shop] " . ($ik ? "대상 블럭을 터치해주세요." : "Touch the target block");
							$t[$name] = ["Type" => "Change", "Mode" => $mode, "Item" => $id, "Count" => floor($sub[3]), "Price" => floor($sub[4])];
						}
					}
				}
			break;
			case "reset":
			case "r":
			case "리셋":
			case "초기화":
				foreach($sh as $pos => $item) $this->removeShop($pos);
				$sh = [];
				$r = Color::YELLOW . "[Shop] " . ($ik ? " 리셋됨." : " Reset");
			break;
			case "edit":
			case "e":
				if(!$sender instanceof Player){
					$r = Color::RED . "[Shop] " . ($ik ? "게임내에서만 실행해주세요." : "Please run this command in-game");
				}elseif(isset($this->editors[$name = $sender->getName()])){
					unset($this->editors[$name]);
					$r = Color::YELLOW . "[Shop] " . ($ik ? "편집모드를 해제합니다." : "Unable edit mode");
				}else{
					$this->editors[$name] = true;
					$r = Color::YELLOW . "[Shop] " . ($ik ? "편집모드를 실행합니다." : "Enable edit mode");
				}
			break;
			default:
				return false;
			break;
		}
		if(isset($r)) $sender->sendMessage($r);
		if($this->sh !== $sh){
			$this->sh = $sh;
			$this->saveYml();
		}
		$this->touch = $t;
		return true;
	}

	public function onBlockBreak(\pocketmine\event\block\BlockBreakEvent $event){
		if(!isset($this->editors[$name = $event->getPlayer()->getName()])){
			if(isset($this->sh[$this->getPos($event->getBlock())])){
				$event->setCancelled();
			}
 		}
	}

	public function onBlockPlace(\pocketmine\event\block\BlockPlaceEvent $event){
		if(!isset($this->editors[$name = $event->getPlayer()->getName()])){
			if(isset($this->sh[$this->getPos($event->getBlock())])) $event->setCancelled();
			if(isset($this->place[$name])){
				$event->setCancelled();
				unset($this->place[$name]);
			}
 		}
	}

	public function onPlayerInteract(\pocketmine\event\player\PlayerInteractEvent $event){
		$block = $event->getBlock();
		if($block->getID() !== 20) $block = $block->getSide($event->getFace());
		$player = $event->getPlayer();
		if(isset($this->editors[$name = $player->getName()])) return false;
		$t = $this->touch;
		$sh = $this->sh;
		$ik = $this->isKorean();
		$pos = $this->getPos($block);
		if(isset($t[$name])){
			switch($t[$name]["Type"]){
				case "Add":
					$this->addShop($pos, $t[$name]["Mode"] == self::BUY ? "Buy" : "Sell", $t[$name]["Item"], $t[$name]["Count"], $t[$name]["Price"]);
					$player->sendMessage(Color::YELLOW . "[Shop] " . ($ik ? "상점이 생성되었습니다." : "Shop Create"));
					unset($t[$name]);
				break;
				case "Change":
					if(!isset($sh[$pos])) $player->sendMessage(Color::RED . "[Shop] " . ($ik ? "이곳에는 상점이 없습니다." : "Shop is not exist here"));
					else{
						$this->removeShop($pos);
						$this->addShop($pos, $t[$name]["Mode"] == self::BUY ? "Buy" : "Sell", $t[$name]["Item"], $t[$name]["Count"], $t[$name]["Price"]);
						$player->sendMessage(Color::YELLOW . "[Shop] " . ($ik ? "상점이 변경되었습니다." : "Shop Changed"));
						unset($t[$name]);
					}
				break;
				case "Del":
					if(!isset($sh[$pos])) $player->sendMessage(Color::RED . "[Shop] " . ($ik ? "이곳에는 상점이 없습니다." : "Shop is not exist here"));
					else{
						$this->removeShop($pos);
						$player->sendMessage(Color::YELLOW . "[Shop] " . ($ik ? "상점이 제거되었습니다." : "Shop is Delete"));
						unset($t[$name]);
					}
				break;
				case "DelMode":
					if(!isset($sh[$pos])) $player->sendMessage(Color::RED . "[Shop] " . ($ik ? "이곳에는 상점이 없습니다." : "Shop is not exist here"));
					else{
						$this->removeShop($pos);
						$player->sendMessage(Color::YELLOW . "[Shop] " . ($ik ? "[제거모드] 상점이 제거되었습니다." : "[DelMode] Shop is Delete"));
					}
				break;
			}
			$this->touch = $t;
		}elseif(isset($sh[$pos])){
			$shop = $sh[$pos];
			if($player->isCreative()){
				$player->sendMessage(Color::RED . "[Shop] " . ($ik ? " 당신은 크리에이티브입니다.\n" . Color::RED . "[$shop[0]] 상점정보 : [구매] 아이디: $shop[1] (갯수 : $shop[2]) 가격 : $shop[3] 원" : " You are Creative mode\n" . Color::RED . "[Shop] StoreInfo : [$shop[0]] ID: $shop[1] (Count: $shop[2]) Price: $shop[3] $"));
			}else{
				$tap = $this->tap;
				$item = Item::fromString($shop[1]);
				$item->setCount($shop[2]);
				if(!isset($tap[$name]) || $tap[$name][1] !== $pos) $tap[$name] = [0, $pos];
				$player->getLevel()->addSound(new \pocketmine\level\sound\ClickSound($block), [$player]);
				switch($shop[0]){
					case "Buy":
						if(microtime(true) - $tap[$name][0] > 0){
							$player->sendMessage(Color::YELLOW . "[Shop] " . ($ik ? "구매하시려면 다시한번눌러주세요.\n" . Color::YELLOW . "  [Shop] 상점정보 : [구매] 아이디: $shop[1] (갯수 : $shop[2]) 가격 : $shop[3] 원" : "If you want to buy, One more touch block\n" . Color::YELLOW . "  [Shop] StoreInfo : [Buy] ID: $shop[1] (Count: $shop[2]) Price: $shop[3] $"));
						}elseif(($money = $this->getMoney($player)) < $shop[3]){
							$player->sendMessage(Color::RED . "[Shop] " . ($ik ? "돈이 부족합니다. \n" . Color::YELLOW . "[Shop] 나의돈 : $money 원" : "You has less money than its price \n" . Color::YELLOW . "[Shop] Your money : $money $"));
						}else{
							$player->getInventory()->addItem($item);
							$this->giveMoney($player, -$shop[3]);
							$player->sendMessage(Color::YELLOW . "[Shop] " . ($ik ? "아이템을 구매하셨습니다. 아이디 : $shop[1] (갯수 : $shop[2]) 가격 : $shop[3] 원\n" . Color::YELLOW . "  [Shop] 나의 돈: " . $this->getMoney($player) . " $" : "You buy Item.  ID: $shop[1] (Count: $shop[2]) Price: $shop[3] $\n" . Color::YELLOW . "  [Shop] Your money: " . $this->getMoney($player) . " $"));
						}
					break;
					case "Sell":
						if(microtime(true) - $tap[$name][0] > 0){
							$player->sendMessage(Color::YELLOW . "[Shop] " . ($ik ? "판매하시려면 다시한번눌러주세요.\n" . Color::YELLOW . "  [Shop] 상점정보 : [판매] 아이디: $shop[1] (갯수 : $shop[2]) 가격 : $shop[3] 원" : "If you want to sell, One more touch block\n" . Color::YELLOW . "  [Shop] StoreInfo : [Sell] ID: $shop[1] (Count: $shop[2]) Price: $shop[3] $"));
						}else{
							$count = 0;
							foreach($player->getInventory()->getContents() as $ii){
								if($item->equals($ii, true)) $count += $ii->getCount();
							}
							if($count < $shop[2]){
								$player->sendMessage(Color::RED . "[Shop] " . ($ik ? "아이템이 부족합니다.\n" . Color::RED . "  [Shop] 소유갯수 : " : "You has less Item than its count\n" . Color::RED . "  [Shop] Your have : ") . $count);
							}else{
								$player->getInventory()->removeItem($item);
								$this->giveMoney($player, $shop[3]);
								$player->sendMessage(Color::YELLOW . "[Shop] " . ($ik ? "아이템을 판매하셨습니다. 아이디 : $shop[1] (갯수 : $shop[2]) 가격 : $shop[3] 원\n" . Color::YELLOW . "  [Shop] 나의 돈 : " . $this->getMoney($player) . " $" : "You sell Item.  ID: $shop[1] (Count: $shop[2]) Price: $shop[3] $\n" . Color::YELLOW . "  [Shop] Your money: " . $this->getMoney($player) . " $"));
							}
						}
					break;
				}
				$this->tap[$name] = [microtime(true) + 1, $pos];
			}	
			$event->setCancelled();
			if($event->getItem()->isPlaceable()) $this->place[$name] = true;
		}
	}

 	public function onTick(){
		foreach($this->sh as $posStr => $shop){
			$players = isset($this->players[$posStr]) ? [] : $this->players[$posStr] = [];
			$this->AddItemEntityPacket->eid = $this->RemoveEntityPacket->eid = $this->MovePlayerPacket->eid = isset($this->eids[$posStr]) ? $this->eids[$posStr] : $this->eids[$posStr] = bcadd("1095216660480", mt_rand(0, 0x7fffffff));
			$this->AddItemEntityPacket->item = Item::fromString($shop[1]);
			$pos = explode(":", $posStr);
			$this->AddItemEntityPacket->x = $this->MovePlayerPacket->x = $x = $pos[0] + 0.5;
			$this->AddItemEntityPacket->y = $this->MovePlayerPacket->y = $y = $pos[1];
			$this->AddItemEntityPacket->z = $this->MovePlayerPacket->z = $z = $pos[2] + 0.5;
			foreach($this->getServer()->getOnlinePlayers() as $player){
				if(!$player->spawned || strtolower($pos[3]) !== strtolower($player->getLevel()->getFolderName()) || ($distance = sqrt(pow($dX = $x - $player->x, 2) + pow($y - $player->y, 2) + pow($dZ = $z - $player->z, 2))) > 10){
					if(isset($this->players[$posStr][$player->getName()])){
						$player->dataPacket($this->RemoveEntityPacket);
					}
				}else{
					if(!isset($this->players[$posStr][$playerName = $player->getName()])) $player->dataPacket($this->AddItemEntityPacket);
					$players[$playerName] = $player;
				}
			}
			$this->players[$posStr] = $players;
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

	public function addShop($pos, $mode, $id, $cnt, $price){
		if(isset($this->sh[$pos])) return false;
		$this->sh[$pos] = [$mode, $id, $cnt, $price];
		$this->saveYml();
		$pos = explode(":", $pos);
		if(($level = $this->getServer()->getLevelByName($pos[3])) != false) $level->setBlock(new \pocketmine\math\Vector3($pos[0], $pos[1], $pos[2]), \pocketmine\block\Block::get(20));
		return true;
	}

	public function removeShop($pos){
		if(isset($this->sh[$pos])){
			if(isset($this->players[$pos])){
				foreach($this->players[$pos] as $player){
					$this->RemoveEntityPacket->eid = $this->RemoveEntityPacket->clientID = $this->eids[$pos];
					$player->dataPacket($this->RemoveEntityPacket);
				}
				unset($this->players[$pos]);
				unset($this->eids[$pos]);
			}
		}
 		unset($this->sh[$pos]);
		$this->saveYml();
		return true;
	}

	public function getPos($b){
		return $b->x . ":" . $b->y . ":" . $b->z . ":" . $b->getLevel()->getFolderName();
	}

	public function loadYml(){
		@mkdir($this->getDataFolder());
		$this->sh = (new Config($this->getDataFolder() . "Shop.yml", Config::YAML))->getAll();
	}

	public function saveYml(){
		ksort($this->sh);
		$sh = new Config($this->getDataFolder() . "Shop.yml", Config::YAML);
		$sh->setAll($this->sh);
		$sh->save();
	}

	public function isKorean(){
		return $this->getServer()->getLanguage()->getName() == "\"한국어\"";
	}
}