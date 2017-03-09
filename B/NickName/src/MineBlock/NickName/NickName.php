<?php

namespace MineBlock\NickName;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;

class NickName extends PluginBase{
	public function onEnable(){
		$this->loadYml();
		$this->getServer()->getScheduler()->scheduleRepeatingTask(new Task($this, [$this,"onTick"]), 20);
	}
	
	public function onDisable(){
		$this->getServer()->getScheduler()->cancelTasks($this);
	}

	public function onCommand(CommandSender $sender, Command $cmd, $label, array $sub){
		if(!(isset($sub[1]) && $sub[0] && $sub[1])) return false;
		$mm = "[NickName] ";
		$nn = $this->nn;
		$ik = $this->isKorean();
		$n = strtolower(array_Shift($sub));
		$nick = implode(" ", $sub);
		if(!isset($nn[$n])){
			$r = $mm . $n . ($ik ? "는 잘못된 플레이어명입니다." : " is invalid player");
		}elseif(strlen($nick) > 20 || strlen($nick) < 3){
			$r = $mm . ($ik ? " 닉네임이 너무 길거나 짧습니다.." : "NickName is long or short") . " : $nick";
		}else{
			$r = $mm . $n . ($ik ? "' 닉네임 : " : "' NickName : ") . $nick;
			$nn[$n] = $nick;
		}
		if(isset($r)) $sender->sendMessage($r);
		if($this->nn !== $nn){
			$this->nn = $nn;
			$this->saveYml();
		}
		return true;
	}

	public function onTick(){
		foreach($this->getServer()->getOnlinePlayers() as $p){
			$c = false;
			$n = $p->getName();
			$sn = strtolower($n);
			if(!isset($this->nn[$sn])) $this->nn[$sn] = $n;
			$nick = $this->nn[$sn];
			if($p->getDisplayName() !== $nick){
				$p->setDisplayName($nick);
				$c = true;
			}
			if(strpos($p->getNameTag(), $nick) === false){
				$r = $p->setNameTag($nick);
				$c = true;
			}
			if($c) $p->sendMessage("[NickName] " . ($this->isKorean() ? " 당신의 닉네임이 $nick 으로 변경되엇습니다." : "Your nickname is change to $nick"));
		}
	}

	public function loadYml(){
		@mkdir($this->getDataFolder());
		$this->nn = (new Config($this->getDataFolder() . "NickName.yml", Config::YAML))->getAll();
	}

	public function saveYml(){
		ksort($this->nn);
		$nn = new Config($this->getDataFolder() . "NickName.yml", Config::YAML, []);
		$nn->setAll($this->nn);
		$nn->save();
	}

	public function isKorean(){
		return strtolower($this->getServer()->getLanguage()->getName()) == "korean";
	}
}
