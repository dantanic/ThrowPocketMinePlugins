<?php

namespace MoreInv;

use pocketmine\Player;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat as Color;
use pocketmine\inventory\PlayerInventory;

class MoreInv extends \pocketmine\plugin\PluginBase implements \pocketmine\event\Listener{
	public function onEnable(){
 		$pluginManager = $this->getServer()->getPluginManager();
		if(!($this->money = $pluginManager->getPlugin("PocketMoney")) && !($this->money = $pluginManager->getPlugin("EconomyAPI")) && !($this->money = $pluginManager->getPlugin("MassiveEconomy")) && !($this->money = $pluginManager->getPlugin("Money"))) $this->getLogger()->info(Color::RED . "Failed find economy plugin...");
		else $this->getLogger()->info(Color::GREEN . "Finded economy plugin : " . $this->money->getName());
		$this->loadYml();
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
	}

	public function onDisable(){
		foreach($this->getServer()->getOnlinePlayers() as $player){
			$this->saveInven($player);
		}
	}

	public function onCommand(\pocketmine\command\CommandSender $sender, \pocketmine\command\Command $cmd, $label, array $sub){
		if(!isset($sub[0])) return false;
		$ik = $this->isKorean();
		$mi = $this->mi;
		$name = strtolower($sender->getName());
		switch(strtolower($cmd->getName())){
			case "moreinv":
				if(!$sender instanceof Player){
					$r = Color::RED . "[MoreInv] " . ($ik ? "게임내에서만 실행해주세요." : "Please run this command in-game");
				}else{
					switch(strtolower($sub[0])){
						case "my":
						case "me":
						case "m":
						case "나":
							$r = Color::YELLOW . "[MoreInv] " . ($ik ? "나의 인벤토리 크기 : " : "My inventory size : ") . Color::GREEN . $mi["CountDatas"][$name];
						break;
						case "buy":
						case "b":
						case "구매":
							$count = isset($sub[1]) && is_numeric($sub[1]) && $sub[1] >= 1 ? floor($sub[1]) : 1;
 							if(!$this->mi["Sell"] || !$this->money) $r = Color::RED . "[MoreInv] " . ($ik ? "이 서버는 인벤토리를 판매하지 않습니다.." : "This server not sell the inventory");
							elseif($this->getMoney($sender) < ($pr = $this->mi["Price"]) * $count) $r = Color::RED . "[MoreInv] " . ($ik ? "당신은 $pr 보다 돈이 적습니다. 나의 돈 : " : "You don't have $pr $. Your money : ") . $this->getMoney($sender);
							elseif(($mi["CountDatas"][$name] + $count) >= 1000) $r = Color::RED . ($ik ? "인벤토리의 크기가 최대입니다." : "Your inventory is max size");
							else{
								$this->giveMoney($sender, -$pr);
								$mi["CountDatas"][$name] += $count;
								$this->setInven($sender, $mi["CountDatas"][$name]);
								$r = Color::YELLOW . "[MoreInv] " . ($ik ? "인벤토리를 " . $count . "개 구매하였습니다. 나의 돈 : " : "Buy the $count inventory slot. Your money : ") . $this->getMoney($sender) . "   " . Color::GREEN . ($ik ? "인벤토리 크기 : " : "Inventory Size : ") . $mi["CountDatas"][$name];
							}
						break;
						default:
							return false;
						break;
					}
				}
			break;
			case "moreinvop":
				switch(strtolower($sub[0])){
					case "set":
					case "s":
					case "설정":
						if(!isset($sub[1])) $r = Color::RED . "Usage: /MoreInvOP Set(S) " . ($ik ? "<플레이어명> <인벤크기>" : "<PlayerName> <InventorySize>");
					elseif(!($player = $this->getServer()->getPlayer($sub[1]))) $r = Color::RED . "[MoreInv] $sub[1]" . ($ik ? "는 잘못된 플레이어명입니다." : " is invalid player");
						else{
	 						$mi["CountDatas"][$playerName = strtolower($player->getName())] = isset($sub[2]) && is_numeric($sub[2]) && $sub[2] >= 1 ? min(1000, floor($sub[2])) : 0;
							$this->setInven($player, $mi["CountDatas"][$playerName]);
							$r = Color::YELLOW . "[MoreInv] " . ($ik ? $playerName . "님의 인벤토리 크기를 " . $mi["CountDatas"][$playerName] . "칸으로 설정하였습니다. " : "Set $playerName\'s inventory size to " . $mi["CountDatas"][$playerName]);
							$player->sendMessage(Color::YELLOW . Color::GREEN . "[MoreInv] " . ($ik ? "운영자에 의해 인벤토리의 크기가 설정되었습니다. 인벤토리 크기 : " : "Your inventory size is changed by admin. My inventory size : ") . $mi["CountDatas"][$playerName]);
						}
					break;
					case "default":
					case "d":
					case "기본":
						if(!isset($sub[1])) $r = Color::RED . "Usage: /MoreInv Price(P) " . ($ik ? "<가격>" : "<Price>");
						elseif(!is_numeric($sub[1]) || $sub[1] < 0) $r = Color::RED . "[MoreInv] " . $sub[1] . ($ik ? "는 잘못된 숫자입니다." : " is invalid number");
						else{
							$mi["Default"] = min(255, floor($sub[1]));
							$m = Color::GREEN . "[MoreInv] " . ($ik ? "인벤토리 기본 크기가 $sub[1] 으로 설정되엇습니다." : "invnetory default size is set to $sub[1]");
						}
					break;
					case "sell":
					case "s":
					case "판매":
						$a = !$mi["Sell"];
						$mi["Sell"] = $a;
						$m = Color::GREEN . "[MoreInv] " . ($ik ? "이제 인벤토리를 판매" . ($a ? "합" : "하지않습") . "니다." : "Now " . ($a ? "" : "not ") . "sell the inventory");
					break;
					case "price":
					case "p":
					case "가격":
						if(!isset($sub[1])) $r = Color::RED . "Usage: /MoreInv Price(P) " . ($ik ? "<가격>" : "<Price>");
						elseif(!is_numeric($sub[1]) || $sub[1] < 0) $r = Color::RED . "[MoreInv] " . $sub[1] . ($ik ? "는 잘못된 숫자입니다." : " is invalid number");
						else{
							$mi["Price"] = floor($sub[1]);
							$m = Color::GREEN . "[MoreInv] " . ($ik ? "인벤토리 확장의 가격이 $sub[1] 으로 설정되엇습니다." : "invnetory price is set to $sub[1]");
						}
					break;
					case "list":
					case "l":
					case "목록":
						arsort($mi["CountDatas"]);
 						$lists = array_chunk($mi["CountDatas"], 5);
						$page = min(isset($sub[2]) && is_numeric($sub[2]) && isset($lists[$sub[2] - 1]) ? $sub[2] : 1, count($lists));
						$r = Color::YELLOW . "[MoreInv] " . ($ik ? "인벤토리 목록 (페이지: " : "Inventory List (Page: ") . $page . "/" . count($lists) . ") (" . count($mi["CountDatas"]) . ")";
						if(isset($lists[$page - 1])){
							$keys = array_keys($mi["CountDatas"]);
							foreach($lists[$page - 1] as $key => $size) $r .= "\n" . Color::GOLD . "    [" . (($playerKey = (($page - 1) * 5 + $key)) + 1) .  "] \"" . $keys[$playerKey] . "\": $size";
						}
					break;
					case "reset":
					case "r":
					case "리셋":
						$mi["CountDatas"] = [];
						foreach($this->getServer()->getOnlinePlayers() as $player){
							$this->setInven($player, $mi["Default"]);
						}
						$r = Color::YELLOW . "[MoreInv] " . ($ik ? "리셋되었습니다." : "Reset");
					break;
					default:
						return false;
					break;
				}
			break;
		}
		if(isset($r)) $sender->sendMessage($r);
		if(isset($m)) $this->getServer()->broadcastMessage($m);
		if($this->mi !== $mi){
			$this->mi = $mi;
			$this->saveYml();
		}
		return true;
	}

	public function onPlayerJoin(\pocketmine\event\player\PlayerJoinEvent $event){
		$player = $event->getPlayer();
		$contents = [];
		foreach($this->mi["InventoryDatas"][strtolower($player->getName())] as $info) $contents[] = \pocketmine\item\Item::get(...explode(":", $info));
		$player->getInventory()->setContents($contents);
		$this->setInven($event->getPlayer());
	}

	public function onPlayerQuit(\pocketmine\event\player\PlayerQuitEvent $event){
		$this->saveInven($event->getPlayer());
	}

	public function setInven($player, $size = false){
		$this->saveInven($player, true);
		if($player->getInventory() instanceof PlayerInventory){
			$player->getInventory()->setSize($this->mi["CountDatas"][$name = strtolower($player->getName())]);
 		}
	}

	public function saveInven($player, $isLoad = false){
		if(!isset($this->mi["CountDatas"][$name = strtolower($player->getName())])) $this->mi["CountDatas"][$name] = $this->mi["Default"];
	 	if(!isset($this->mi["InventoryDatas"][$name])){
			if($player->getInventory() instanceof PlayerInventory){
				$contents = [];
				foreach(array_slice($player->getInventory()->getContents(), 0, $this->mi["CountDatas"][$name]) as $item){
					$contents[] = $item->getID() . ":" . $item->getDamage() . ":" . $item->getCount();
				}
				$this->mi["InventoryDatas"][$name] = $contents;
			}
		}
		if(!$isLoad && $player->getInventory() instanceof PlayerInventory){
			$contents = [];
			foreach(array_slice($player->getInventory()->getContents(), 0, $this->mi["CountDatas"][$name]) as $item) $contents[] = $item->getID() . ":" . $item->getDamage() . ":" . $item->getCount();
			$this->mi["InventoryDatas"][$name] = $contents;
			$this->saveYml();
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

	public function loadYml(){
		@mkdir($this->getDataFolder());
		$setting = (new Config($this->getDataFolder() . "MoreInv_Setting.yml", Config::YAML, ["Default" => 36, "Sell" => true, "Price" => 5000]))->getAll();
		$data = (new Config($this->getDataFolder() . "MoreInv_Data.yml", Config::YAML, ["CountDatas" => [], "InventoryDatas" => []]))->getAll();
		$this->mi = ["Default" => $setting["Default"], "Sell" => $setting["Sell"], "Price" => $setting["Price"], "CountDatas" => $data["CountDatas"], "InventoryDatas" => $data["InventoryDatas"]];
	}

	public function saveYml(){
		$setting = new Config($this->getDataFolder() . "MoreInv_Setting.yml", Config::YAML);
		$setting->setAll(["Default" => $this->mi["Default"], "Sell" => $this->mi["Sell"], "Price" => $this->mi["Price"]]);	
		$setting->save();
		$data = new Config($this->getDataFolder() . "MoreInv_Data.yml", Config::YAML);
		$data->setAll(["CountDatas" => $this->mi["CountDatas"], "InventoryDatas" => $this->mi["InventoryDatas"]]);	
		$data->save();
	}

	public function isKorean(){
		return $this->getServer()->getLanguage()->getName() == "\"한국어\"";
	}
}