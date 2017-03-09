<?php

namespace MineBlock\PlayTime;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\event\Listener;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;

class PlayTime extends PluginBase implements Listener{
	public function onEnable(){
		$this->time = [];
		$this->loadYml();
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
		$this->getServer()->getScheduler()->scheduleRepeatingTask(new Task($this, [$this,"onTick"]), 20);
	}
	
	public function onDisable(){
		$this->getServer()->getScheduler()->cancelTasks($this);
	}

	public function onCommand(CommandSender $sender, Command $cmd, $label, array $sub){
		if(!isset($sub[0])) return false;
		if(isset($sub[1])) $sub[1] = strtolower($sub[1]);
		$pt = $this->pt;
		$rm = TextFormat::RED . "Usage: /PlayTime ";
		$mm = "[PlayTime] ";
		$ik = $this->isKorean();
		switch(strtolower($sub[0])){
			case "view":
			case "v":
			case "보기":
				if(!isset($sub[1])){
					$r = $rm . "View(V) " . ($ik ? "<플레이어명>" : "<PlayerName>");
				}elseif(!isset($jc[$sub[1]])){
					$r = $mm . $sub[1] . ($ik ? "는 잘못된 플레이어명입니다." : " is invalid player");
				}else{
					$r = $mm . $sub[1] . ($ik ? "' 접속시간 : " : "' Join Time : ") . $this->getDay($pt[$sub[1]]);
				}
			break;
			case "rank":
			case "r":
			case "랭크":
			case "랭킹":
			case "순위":
			case "목록":
				$page = 1;
				if(isset($sub[1]) && is_numeric($sub[1])) $page = max(floor($sub[1]), 1);
				$list = array_chunk($pt, 5, true);
				if($page >= ($c = count($list))) $page = $c;
				$r = $mm . ($ik ? "접속시간 랭킹 (페이지" : "PlayTime Rank (Page") . " $page/$c) \n";
				$num = ($page - 1) * 5;
				if($c > 0){
					foreach($list[$page - 1] as $k => $v){
						$num++;
						$d = floor($v / 86400);
						$t = $v - ($d * 86400);
						$h = floor($v / 3600);
						$t = $t - ($h * 3600);
						$m = floor($t / 60);
						$s = $t - ($m * 60);
						$r .= "  [$num] $k : [$d:$h:$m:$s]\n";
					}
				}
			break;
			case "clear":
			case "c":
			case "초기화":
			case "클리어":
				if(!isset($sub[1])){
					$r = $rm . "Clear(C) " . ($ik ? "<플레이어명>" : "<PlayerName>");
				}elseif(!isset($pt[$sub[1]])){
					$r = $mm . $sub[1] . ($ik ? "는 잘못된 플레이어명입니다." : " is invalid player");
				}else{
					$pt[$sub[1]] = 0;
					$r = $mm . $sub[1] . ($ik ? "' 접속시간를 초기화 합니다." : "' Join Time is Reset");
				}
			break;
			case "allclear":
			case "ac":
			case "전체초기화":
			case "전체클리어":
				$pt = [];
				$r = $mm . ($ik ? "모든 접속시간를 초기화합니다." : "Clear the All Join Time");
			break;
			default:
				return false;
			break;
		}
		if(isset($r)) $sender->sendMessage($r);
		if($this->pt !== $pt){
			$this->pt = $pt;
			$this->saveYml();
		}
		return true;
	}

	public function onTick(){
		$pt = $this->pt;
		$t = $this->time;
		foreach($this->getServer()->getOnlinePlayers() as $p){
			$n = strtolower($p->getName());
			if(!isset($pt[$n])) $pt[$n] = 0;
			$pt[$n]++;
		}
		if($this->pt !== $pt){
			$this->pt = $pt;
			$this->saveYml();
		}
	}

	public function loadYml(){
		@mkdir($this->getDataFolder());
		$this->pt = (new Config($this->getDataFolder() . "PlayTime.yml", Config::YAML))->getAll();
	}

	public function saveYml(){
		asort($this->pt);
		$pt = new Config($this->getDataFolder() . "PlayTime.yml", Config::YAML);
		$pt->setAll($this->pt);
		$pt->save();
	}

	public function isKorean(){
		return strtolower($this->getServer()->getLanguage()->getName()) == "korean";
	}
}
