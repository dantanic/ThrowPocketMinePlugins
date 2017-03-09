<?php
namespace ShowMoney;

use pocketmine\Player;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat as Color;

class ShowMoney extends \pocketmine\plugin\PluginBase{
	public function onEnable(){
		$this->getServer()->getLogger()->info(Color::GREEN . "Find economy plugin...");
		$pm = $this->getServer()->getPluginManager();
		if(!($this->money = $pm->getPlugin("PocketMoney")) && !($this->money = $pm->getPlugin("EconomyAPI")) && !($this->money = $pm->getPlugin("MassiveEconomy")) && !($this->money = $pm->getPlugin("Money"))){
			$this->getServer()->getLogger()->info(Color::RED . "Failed find economy plugin...");
			$this->getLogger()->info(Color::RED . ($this->isKorean() ? "이 플러그인은 머니 플러그인이 반드시 있어야합니다." : "This plugin need the Money plugin"));
			$this->getServer()->shutdown();
		}else{
			$this->getServer()->getLogger()->info(Color::GREEN . "Finded economy plugin : " . $this->money->getName());
		}
		$this->loadYml();
		$this->getServer()->getScheduler()->scheduleRepeatingTask(new Task($this, [$this,"onTick"]), 10);
		$this->r = str_repeat(" ", 50);
		$this->n = Color::RESET.$this->r."\n";
	}

	public function onDisable(){
		$this->getServer()->getScheduler()->cancelTasks($this);
	}

	public function onTick(){
		if(strpos($this->sm["Format"], "%rank") !== false){
			if(!is_array($moneys = $this->getAllMoneys())) return;
			arsort($moneys);
			$num = 1;
			foreach($moneys as $name => $money){
				if($this->getServer()->isOp($name)) $rank = "OP";
				else{
					if(!isset($same)) $same = [$money,$num];
					if($money == $same[0]){
						$rank = $same[1];
					}else{
						$rank = $num;
						$same = [$money,$num];
					}
					$num++;
				}
				if(($player = $this->getServer()->getPlayerExact($name)) instanceof Player){
					$player->sendTip(str_ireplace(["%rank", "%money", "%n", "%x", "%y", "%z", "%maxhp", "%hp"], [$rank, $money, $this->n, floor($player->x), floor($player->y), floor($player->z), $player->getMaxHealth(), $player->getHealth()], $this->sm["Format"]).$this->r);
				}
			}
		}else{
			foreach($this->getServer()->getOnlinePlayers() as $player){
				$player->sendTip(str_ireplace(["%money", "%n", "%x", "%y", "%z", "%maxhp", "%hp"], [$this->getMoney($player), $this->n, floor($player->x), floor($player->y), floor($player->z), $player->getMaxHealth(), $player->getHealth()], $this->sm["Format"]).$this->r);
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

	public function getAllMoneys(){
		switch($this->money->getName()){
			case "PocketMoney":
				$property = (new \ReflectionClass("\\PocketMoney\\PocketMoney"))->getProperty("users");
				$property->setAccessible(true);
				$allMoney = [];
				foreach($property->getValue($this->money)->getAll() as $k => $v)
					$allMoney[strtolower($k)] = $v["money"];
			break;
			case "EconomyAPI":
				$allMoney = $this->money->getAllMoney()["money"];
			break;
			case "MassiveEconomy":
				$property = (new \ReflectionClass("\\MassiveEconomy\\MassiveEconomyAPI"))->getProperty("data");
				$property->setAccessible(true);
				$allMoney = [];
				$dir = @opendir($path = $property->getValue($this->money) . "users/");
				$cnt = 0;
				while($open = readdir($dir)){
					if(strpos($open, ".yml") !== false){
						$allMoney[strtolower(explode(".", $open)[0])] = (new Config($path . $open, Config::YAML, ["money" => 0 ]))->get("money");
					}
				}
			break;
			case "Money":
				$allMoney = $this->money->getAllMoneys();
			break;
			default:
				return false;
			break;
		}
		return $allMoney;
	}

	public function loadYml(){
		@mkdir($this->getDataFolder());
		$this->sm = (new Config($this->getDataFolder() . "ShowMoney.yml", Config::YAML, ["Format" => Color::BOLD.Color::GOLD."[Your Money]%n".Color::DARK_GREEN."[Rank] ".Color::GREEN."%rank%n".Color::DARK_GREEN."[Money] ".Color::GREEN."%money$%n".Color::DARK_RED."[Health] ".Color::RED."%hp/%maxhp%n".Color::DARK_AQUA." X:".Color::AQUA."%x".Color::DARK_AQUA." Y:".Color::AQUA."%y".Color::DARK_AQUA." Z:".Color::AQUA."%z"]))->getAll();
	}

	public function saveYml(){
		ksort($this->sm);
		$sm = new Config($this->getDataFolder() . "ShowMoney.yml", Config::YAML);
		$sm->setAll($this->sm);
		$sm->save();
	}

	public function isKorean(){
		@mkdir($this->getDataFolder());
		if(!isset($this->ik)) $this->ik = (new Config($this->getDataFolder() . "! Korean.yml", Config::YAML, ["Korean" => false ]))->get("Korean");
		return $this->ik;
	}
}