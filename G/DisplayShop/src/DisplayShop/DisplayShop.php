<?php

namespace DisplayShop;

use pocketmine\Player;
use pocketmine\item\Item;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat as Color;
use pocketmine\entity\Entity;

class DisplayShop extends \pocketmine\plugin\PluginBase implements \pocketmine\event\Listener{
	const INFO = 0;
	const LEFT = 1;
	const RIGHT = 2;
	const QUIT = 3;
	const ITEM = 4;

	const BUY = 0;
	const SELL = 1;
	const POS = 2;
	
	const ID = 0;
	const COUNT = 1;
	const PRICE = 2;
	
	const NORTH = 1;
	const SOUTH = 2;
	const EAST = 3;
	const WEST = 4;

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
 		$this->getServer()->getLogger()->info(Color::GREEN . "Find economy plugin...");
		$pluginManager = $this->getServer()->getPluginManager();
		if(!($this->money = $pluginManager->getPlugin("PocketMoney")) && !($this->money = $pluginManager->getPlugin("EconomyAPI")) && !($this->money = $pluginManager->getPlugin("MassiveEconomy")) && !($this->money = $pluginManager->getPlugin("Money"))){
			$this->getServer()->getLogger()->info(Color::RED . "Failed find economy plugin...");
			$this->getLogger()->info(Color::RED . ($this->isKorean() ? "이 플러그인은 머니 플러그인이 반드시 있어야합니다." : "This plugin need the Money plugin"));
			$this->getServer()->dsutdown();
		}else{
			$this->getServer()->getLogger()->info(Color::GREEN . "Finded economy plugin : " . $this->money->getName());
			$basePk = new \pocketmine\network\protocol\AddEntityPacket();
			$basePk->eid = bcadd("1095216660480", mt_rand(0, 0x7fffffff)); 
			$basePk->type = 37;
			$basePk->metadata = [
				Entity::DATA_FLAGS => [Entity::DATA_TYPE_BYTE, 1 << Entity::DATA_FLAG_INVISIBLE],
 				Entity::DATA_SHOW_NAMETAG => [Entity::DATA_TYPE_BYTE, 1],
 				Entity::DATA_NAMETAG => [Entity::DATA_TYPE_STRING, "\n\n"],
				Entity::DATA_NO_AI => [Entity::DATA_TYPE_BYTE, 1]
			];
			$basePk->yaw = $basePk->pitch = $basePk->speedX = $basePk->speedY = $basePk->speedZ = 0;
			$this->infoPk = clone $basePk;
			$this->rightPk = clone $basePk;
			$this->leftPk = clone $basePk;
			$this->quitPk = clone $basePk;
			$this->infoPk->eid += self::INFO;
			$this->leftPk->eid += self::LEFT;
			$this->rightPk->eid += self::RIGHT;
			$this->quitPk->eid += self::QUIT;
			$this->infoPk->metadata[Entity::DATA_NAMETAG][1] .= Color::WHITE  . "Page : ";
			$this->leftPk->metadata[Entity::DATA_NAMETAG][1] .=  Color::BOLD . Color::AQUA . "<";
			$this->rightPk->metadata[Entity::DATA_NAMETAG][1] .= Color::BOLD . Color::AQUA . ">";
			$this->quitPk->metadata[Entity::DATA_NAMETAG][1] .= Color::BOLD . Color::RED . "Quit";
			$this->itemPks = [];
			$basePk = new \pocketmine\network\protocol\AddItemEntityPacket();
			$basePk->eid = bcadd("1095216660480", mt_rand(0, 0x7fffffff)) + self::ITEM; 
			$basePk->speedX = $basePk->speedY = $basePk->speedZ = 0;
			for($i = 0; $i < 9; $i++){
				$pk = clone $basePk;
				$pk->eid += $i;
				$this->itemPks[] = $pk;
			}
	 		$this->RemoveEntityPacket = new \pocketmine\network\protocol\RemoveEntityPacket();
			$this->MovePlayerPacket = new \pocketmine\network\protocol\MovePlayerPacket();
			$this->players = [];
		}
	}

	public function onEnable(){
		$this->loadYml();
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
		$this->getServer()->getScheduler()->scheduleRepeatingTask(new Task($this, [$this, "onTick"]), 5);
	}

	public function onCommand(\pocketmine\command\CommandSender $sender, \pocketmine\command\Command $cmd, $label, array $sub){
		if(!isset($sub[0])) return false;
		$ds = $this->ds;
		$ik = $this->isKorean();
		switch(strtolower($sub[0])){
			case "add":
			case "a":
			case "추가":
				if(!isset($sub[4])){
					$r = Color::RED .  "Usage: /DisplayShop Add(A) " . ($ik ? "<구매|판매> <아이템ID> <갯수> <가격>" : "<Buy|Sell> <ItemID> <Amount> <Price>");
				}else{
					$item = Item::fromString($sub[2]);
					if(!in_array(strtolower($sub[1]), self::$modes[$mode = self::BUY]) && !in_array(strtolower($sub[1]), self::$modes[$mode = self::SELL])){
						$r = Color::RED . "[DisplayShop] $sub[1] " . ($ik ? "는 잘못된 모드입니다. (구매/판매)" : "is invalid Mode (Buy/Sell)");
					}elseif($item->getID() == 0){
						$r = Color::RED . "[DisplayShop] $sub[2] " . ($ik ? "는 잘못된 아이템ID입니다." : "is invalid ItemID");
					}elseif(!is_numeric($sub[3]) || $sub[3] < 1){
						$r = Color::RED . "[DisplayShop] $sub[3] " . ($ik ? "는 잘못된 갯수입니다." : "is invalid count");
					}elseif(!is_numeric($sub[4]) || $sub[4] < 0){
						$r = Color::RED . "[DisplayShop] $sub[4] " . ($ik ? "는 잘못된 가격입니다." : "is invalid price");
					}else{
						$ds[$mode][] = [
							self::ID => ($id = $item->getID() . ":" . $item->getDamage()),
							self::COUNT => ($count = floor($sub[3])),
							self::PRICE => ($price = floor($sub[4]))
						];
						$r = Color::YELLOW . "[DisplayShop] " . ($ik ? "상점에 아이템이 추가되었습니다." : "Added item to shop") . "\n  " . Color::GOLD . "  Info: [" . count($ds[$mode]) ."] ID: $id  Count: $count  Price: $price\$";
					}
				}
			break;
			case "del":
			case "d":
			case "삭제":
			case "제거":
				if(!isset($sub[2])){
					$r = Color::RED .  "Usage: /DisplayShop Del(D) " . ($ik ? "<구매|판매> <상점ID>" : "<Buy|Sell> <ShopID>");
				}elseif(!in_array(strtolower($sub[1]), self::$modes[$mode = self::BUY]) && !in_array(strtolower($sub[1]), self::$modes[$mode = self::SELL])){
					$r = Color::RED . "[DisplayShop] $sub[1] " . ($ik ? "는 잘못된 모드입니다. (구매/판매)" : "is invalid Mode (Buy/Sell)");
				}elseif(!is_numeric($sub[2]) || !isset($ds[$mode][$sub[2] - 1])){
					$r = Color::RED . "[DisplayShop] $sub[2] " . ($ik ? "는 잘못된 상점ID입니다." : "is invalid ShopID");
				}else{
					$info = $ds[$mode][$sub[2]-1];
					unset($ds[$mode][$sub[2]-1]);
					$r = Color::YELLOW . "[DisplayShop] " . ($ik ? "상점에서 아이템이 제거되었습니다." : "Deleted item from shop") . "\n  " . Color::GOLD . "  Info: [$sub[2]] ID : " . $info[self::ID] . " Count: " . $info[self::COUNT] . " Price: " . $info[self::PRICE] . "\$";
				}
			break;
			case "reset":
			case "r":
			case "리셋":
			case "초기화":
				$ds = [];
				$r = Color::YELLOW . "[DisplayShop] " . ($ik ? "리셋됨." : " Reset");
			break;
			case "list":
			case "l":
			case "리스트":
			case "목록":
				if(!isset($sub[1])){
					$r = Color::RED .  "Usage: /DisplayShop List(L) " . ($ik ? "<구매|판매> <페이지>" : "<Buy|Sell> <Page>");
	 			}elseif(!in_array(strtolower($sub[1]), self::$modes[$mode = self::BUY]) && !in_array(strtolower($sub[1]), self::$modes[$mode = self::SELL])){
						$r = Color::RED . "[DisplayShop] $sub[1] " . ($ik ? "는 잘못된 모드입니다. (구매/판매)" : "is invalid Mode (Buy/Sell)");
				}else{
					$lists = array_chunk($ds[$mode], 5);
					$page = min(isset($sub[2]) && is_numeric($sub[2]) && isset($lists[$sub[2] - 1]) ? $sub[2] : 1, count($lists));
					$r = Color::YELLOW . "[DisplayShop] " . ($ik ? "상점 목록 (페이지: " : "Shop list (Page: ") . $page . "/" . count($lists) . ") (" . count($ds[$mode]) . ")";
					if(isset($lists[$page - 1])) foreach($lists[$page - 1] as $key => $info) $r .= "\n" . Color::GOLD . "    [" . (($page - 1) * 5 + $key + 1) .  "] ID:" . $info[self::ID] . " Count:" . $info[self::COUNT] . " Price:" . $info[self::PRICE] . "\$";
				}
			break;
			case "position":
			case "pos":
			case "위치":
				if(!$sender instanceof Player){
					$r = Color::RED . ($ik ? "게임 내에서 실행해주세요." : "Please run this command in-game");
				}else{
					$ds[self::POS] = [
						$x = floor($sender->x),
						$y = floor($sender->y),
						$z = floor($sender->z),
						$face = ($yaw = $sender->yaw) < 45 && $yaw > 315 ? self::NORTH : ($yaw > 135 ? self::EAST :  ($yaw > 225 ? self::SOUTH : self::WEST)),
						$world = strtolower($sender->getLevel()->getFolder())
					];
					$r = Color::YELLOW . "[DisplayShop] " . ($ik ? "상점 위치가 변경되었습니다." : "Change the shop position") . "\n  " . Color::GOLD . "X: $x  Y: $y  Z: $z  World: $world (Face: $face)";
				}
			default:
				return false;
			break;
		}
		if(isset($r)) $sender->sendMessage($r);
		if($this->ds !== $ds){
			$this->ds = $ds;
			$this->saveYml();
		}
		return true;
	}

	public function onBlockBreak(\pocketmine\event\block\BlockBreakEvent $event){
		if(isset($this->players[$event->getPlayer()->getName()])) $event->setCancelled();
	}

	public function onBlockPlace(\pocketmine\event\block\BlockPlaceEvent $event){
		if(isset($this->players[$event->getPlayer()->getName()])) $event->setCancelled();
	}

	public function onPlayerInteract(\pocketmine\event\player\PlayerInteractEvent $event){
/*
		$player = $event->getPlayer();
		$ds = $this->ds;
		$ik = $this->isKorean();
		if(isset($this->players[$event->getPlayer()->getName()])){
			$dsop = $ds[$pos];
			if($player->isCreative()){
				$player->sendMessage(Color::RED . "[DisplayShop] " . ($ik ? " 당신은 크리에이티브입니다.\n" . Color::RED . "[$dsop[0]] 상점정보 : [구매] 아이디: $dsop[1] (갯수 : $dsop[2]) 가격 : $dsop[3] 원" : " You are Creative mode\n" . Color::RED . "[DisplayShop] StoreInfo : [$dsop[0]] ID: $dsop[1] (Count: $dsop[2]) Price: $dsop[3] $"));
			}else{
				$event->setCancelled();
				$item = Item::fromString($dsop[1]);
				$item->setCount($dsop[2]);
				if(!isset($tap[$name]) || $tap[$name][1] !== $pos) $tap[$name] = [0, $pos];
				$player->getLevel()->addSound(new \pocketmine\level\sound\ClickSound($block), [$player]);
				switch($dsop[0]){
					case "Buy":
						if(microtime(true) - $tap[$name][0] > 0){
							$player->sendMessage(Color::YELLOW . "[DisplayShop] " . ($ik ? "구매하시려면 다시한번눌러주세요.\n" . Color::YELLOW . "  [DisplayShop] 상점정보 : [구매] 아이디: $dsop[1] (갯수 : $dsop[2]) 가격 : $dsop[3] 원" : "If you want to buy, One more touch block\n" . Color::YELLOW . "  [DisplayShop] StoreInfo : [Buy] ID: $dsop[1] (Count: $dsop[2]) Price: $dsop[3] $"));
						}elseif(($money = $this->getMoney($player)) < $dsop[3]){
							$player->sendMessage(Color::RED . "[DisplayShop] " . ($ik ? "돈이 부족합니다. \n" . Color::YELLOW . "[DisplayShop] 나의돈 : $money 원" : "You has less money than its price \n" . Color::YELLOW . "[DisplayShop] Your money : $money $"));
						}else{
							$player->getInventory()->addItem($item);
							$this->giveMoney($player, -$dsop[3]);
							$player->sendMessage(Color::YELLOW . "[DisplayShop] " . ($ik ? "아이템을 구매하셨습니다. 아이디 : $dsop[1] (갯수 : $dsop[2]) 가격 : $dsop[3] 원\n" . Color::YELLOW . "  [DisplayShop] 나의 돈: " . $this->getMoney($player) . " $" : "You buy Item.  ID: $dsop[1] (Count: $dsop[2]) Price: $dsop[3] $\n" . Color::YELLOW . "  [DisplayShop] Your money: " . $this->getMoney($player) . " $"));
						}
					break;
					case "Sell":
						if(microtime(true) - $tap[$name][0] > 0){
							$player->sendMessage(Color::YELLOW . "[DisplayShop] " . ($ik ? "판매하시려면 다시한번눌러주세요.\n" . Color::YELLOW . "  [DisplayShop] 상점정보 : [판매] 아이디: $dsop[1] (갯수 : $dsop[2]) 가격 : $dsop[3] 원" : "If you want to sell, One more touch block\n" . Color::YELLOW . "  [DisplayShop] StoreInfo : [Sell] ID: $dsop[1] (Count: $dsop[2]) Price: $dsop[3] $"));
						}else{
							$count = 0;
							foreach($player->getInventory()->getContents() as $ii){
								if($item->equals($ii, true)) $count += $ii->getCount();
							}
							if($count < $dsop[2]){
								$player->sendMessage(Color::RED . "[DisplayShop] " . ($ik ? "아이템이 부족합니다.\n" . Color::RED . "  [DisplayShop] 소유갯수 : " : "You has less Item than its count\n" . Color::RED . "  [DisplayShop] Your have : ") . $count);
							}else{
								$player->getInventory()->removeItem($item);
								$this->giveMoney($player, $dsop[3]);
								$player->sendMessage(Color::YELLOW . "[DisplayShop] " . ($ik ? "아이템을 판매하셨습니다. 아이디 : $dsop[1] (갯수 : $dsop[2]) 가격 : $dsop[3] 원\n" . Color::YELLOW . "  [DisplayShop] 나의 돈 : " . $this->getMoney($player) . " $" : "You sell Item.  ID: $dsop[1] (Count: $dsop[2]) Price: $dsop[3] $\n" . Color::YELLOW . "  [DisplayShop] Your money: " . $this->getMoney($player) . " $"));
							}
						}
					break;
				}
				$this->tap[$name] = [microtime(true) + 1, $pos];
			}
		}
*/
	}

 	public function onTick(){
/*
		$players = isset($this->players[$posStr]) ? [] : $this->players[$posStr] = [];
		$this->AddItemEntityPacket->eid = $this->RemoveEntityPacket->eid = $this->MovePlayerPacket->eid = isset($this->eids[$posStr]) ? $this->eids[$posStr] : $this->eids[$posStr] = bcadd("1095216660480", mt_rand(0, 0x7fffffff));
		$this->AddItemEntityPacket->item = Item::fromString($dsop[1]);
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
//					$player->dataPacket($this->MovePlayerPacket);
				}
			}
			$this->players[$posStr] = $players;
		}
*/
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
		@mkdir($this->getDataFolder());
		$this->ds = (new Config($this->getDataFolder() . "DisplayShop.yml", Config::YAML, [self::BUY => [], self::SELL => [], self::POS => [0, 128, 0, self::NORTH, ""]]))->getAll();
	}

	public function saveYml(){
		ksort($this->ds);
		$ds = new Config($this->getDataFolder() . "DisplayShop.yml", Config::YAML);
		$ds->setAll($this->ds);
		$ds->save();
	}

	public function isKorean(){
		return $this->getServer()->getLanguage()->getName() == "\"한국어\"";
	}
}