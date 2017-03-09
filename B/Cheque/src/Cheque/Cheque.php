<?php

namespace Cheque;

use pocketmine\item\Item;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat as Color;

class Cheque extends \pocketmine\plugin\PluginBase implements \pocketmine\event\Listener{
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
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
		$this->getServer()->getScheduler()->scheduleRepeatingTask(new Task($this, [$this,"onTick"]), 15);
	}
	
	public function onCommand(\pocketmine\command\CommandSender $sender, \pocketmine\command\Command $cmd, $label, array $sub){
		if(!isset($sub[0])) return false;
		$ik = $this->isKorean();
		if(!$sender instanceof \pocketmine\Player){
			$r = Color::RED . "[Cheque] " . ($ik ? "게임내에서 실행해주세요." : "Please run this command in game");
		}elseif(!is_numeric($sub[0]) || $sub[0] < 1){
			$r = Color::RED . "[Cheque] " . $sub[0] . ($ik ? "는 잘못된 숫자입니다." : "is invalid number");
		}else{
			$sub[0] = floor($sub[0]);
			if($this->getMoney($sender) < $sub[0]){
				$r = Color::RED . "[Cheque] " . ($ik ? "당신은 돈이 " . $sub[0] . "$ 보다 적습니다. 당신의 돈 : " : "You has less money than " . $sub[0] . "$ . Your money : ") . $this->getMoney($sender);
			}else{
				$this->giveMoney($sender, -$sub[0]);
				$sender->getInventory()->addItem(Item::get(339, $sub[0], 1));
				$r = Color::YELLOW . "[Cheque] " . ($ik ? "당신은 " . $sub[0] . "$ 수표를 받앗습니다. 당신의 돈 : " : "You have been " . $sub[0] . "$ check. Your money : ") . $this->getMoney($sender);
			}
		}
		if(isset($r)) $sender->sendMessage($r);
		return true;
	}

	public function onPlayerInteract(\pocketmine\event\player\PlayerInteractEvent $event){
		$player = $event->getPlayer();
		$item = $event->getItem();
		if($item->getID() == 339 && ($money = $item->getDamage()) >= 1){
			$m = "[Cheque] ";
			$ik = $this->isKorean();
			if($event->getFace() == 255){
				$item->setCount($item->getCount() - 1);
				$player->getInventory()->setItem($player->getInventory()->getHeldItemSlot(), $item);
				$this->giveMoney($player, $money);
				$player->sendMessage(Color::YELLOW . ($ik ? "수표를 사용하셨습니다.\n" . Color::GOLD . " 수표 정보 : " . $money . "$" : "You use the check.\n" . Color::GOLD . " Cheque Info : " . $money . "$");
			}else{
				$m = Color::YELLOW . ($ik ? "수표를 사용하시려면 꾹눌러주세요.\n" . Color::GOLD . " 수표 정보 : " . $money . "$" : "If you want to use this check, Please long touch\n" . Color::GOLD . " Cheque Info : " . $money . "$");
			}
			else{
			}
			$event->setCancelled();
		}
	}

	public function onTick(){
		foreach($this->getServer()->getOnlinePlayers() as $player){
			if($player->spawned){
				$item = $player->getInventory()->getItemInHand();
				if($item->getID() === 339 && $item->getDamage() > 0) $player->sendPopup(Color::GOLD . "[" . Color::WHITE . "Cheque" . Color::GOLD . "] " . Color::YELLOW .$item->getDamage() . Color::GOLD ."$");
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

	public function isKorean(){
		return strtolower($this->getServer()->getLanguage()->getName()) == "korean";
	}
}