<?php

namespace MineBlock\VipSkin;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerPreLoginEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;

class VipSkin extends PluginBase implements Listener{
	public function onEnable(){
		$this->player = [];
		$this->skin = "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ll9B/5ZfQf+WX0H/lm9b/6p9Zv+Wb1v/lm9b/4dVO/+WX0H/ll9B/5ZfQf+qfWb/lm9b/6p9Zv+Wb1v/AAAA/wAAAP8AAAD/AAAA/wAAAP8AAAD/AAAA/wAAAP8AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAP8AAAD/AAAA/wAAAP9WScz/VknM/1ZJzP9WScz/KCgo/ygoKP8oKCj/KCgo/wAAAP8AAAD/AAAA/wAAAP8AAAD/AAAA/wAAAP8AAAD/AKio/wDMzP8AzMz/AKio/1ExJf9qQDD/UTEl/2pAMP8AAAD/AAAA/wAAAP8AAAD/AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA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";
		$this->loadYml();
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
	}

	public function onCommand(CommandSender $sender, Command $cmd, $label, array $sub){
		if(!isset($sub[0])) return false;
		$rm = TextFormat::RED . "Usage: /VipSkin ";
		$mm = "[VipSkin] ";
		$ik = $this->isKorean();
		$vs = $this->vs;
		switch(strtolower($sub[0])){
			case "add":
			case "a":
			case "추가":
				if(!isset($sub[1]) || $sub[1] == "" || $sub[1] == " "){
					$r = $rm . "Add(A) " . ($ik ? "<플레이어명>" : "<PlayerName>");
				}elseif(in_array($sub[1] = strtolower($sub[1]), $this->vs)){
					$r = $mm . $sub[1] . ($ik ? "은 이미 존재합니다." : " is already");
				}else{
					$vs[] = $sub[1];
					$r = $mm . ($ik ? "추가됨 : " : "Add : ").$sub[1];   
					if($p = $this->getServer()->getPlayerExact($sub[1])){
						$p->sendMessage($mm . ($ik ? "이제 스킨이 적용됩니다." : "Now your skin is enable"));
						if(isset($this->player[$n = strtolower($p->getName())])){
							$p->setSkin($this->player[$n]);
							$p->sendMessage("§c $mm".($ik ? "스킨이 적용되었습니다." : "Skin is setting complete"));
						}
					}
				}
			break;
			case "del":
			case "d":
			case "제거":
			case "삭제":
				if(!isset($sub[1]) || $sub[1] == "" || $sub[1] == " "){
					$r = $rm . "Del(D) " . ($ik ? "<플레이어명>" : "<PlayerName>");
				}elseif(!in_array($sub[1] = strtolower($sub[1]), $this->vs)){
					$r = $mm . $sub[1] . ($ik ? "은 존재하지않습니다." : " is invalid name");
				}else{
					foreach($vs as $k => $v){
						if($v == $sub[1]) unset($vs[$k]);
					}
					$r = $mm . ($ik ? "제거됨 : " : "Del : ").$sub[1];   
					if($p = $this->getServer()->getPlayerExact($sub[1])){
						$p->sendMessage($mm . ($ik ? "이제 스킨이 해제됩니다." : "Now your skin is disable"));
						if(isset($this->player[$n = strtolower($p->getName())])){
							$p->setSkin(base64_decode($this->skin));
							$p->sendMessage("§c $mm".($ik ? "스킨이 해제되었습니다." : "Skin is deleted"));
						}
					}
				}
			break;
			case "reset":
			case "r":
			case "리셋":
			case "초기화":
				$vs = [];
				$r = $mm . ($ik ? " 리셋됨." : " Reset");
			break;
			case "list":
			case "l":
			case "목록":
			case "리스트":
				$page = 1;
				if(isset($sub[1]) && is_numeric($sub[1])) $page = max(floor($sub[1]), 1);
				$list = array_chunk($vs, 5, true);
				if($page >= ($c = count($list))) $page = $c;
				$r = $mm . ($ik ? "VIP스킨 목록 (페이지" : "VipSkin List (Page") . " $page/$c) \n";
				if($c > 0){
					foreach($list[$page - 1] as $k => $v){
						$r .= "  [$k] $v\n";
					}
				}
			break;
			default:
				return false;
			break;
		}
		if(isset($r)) $sender->sendMessage($r);
		if($this->vs !== $vs){
			$this->vs = $vs;
			$this->saveYml();
		}
		return true;
	}

	public function onPlayerPreLogin(PlayerPreLoginEvent $event){
		if($event->isCancelled()) return;
		$p = $event->getPlayer();
		$this->player[$n = strtolower($p->getName())] = $p->getSkinData();
		if(!in_array($n, $this->vs)){
			$p->setSkin(base64_decode($this->skin));
		}
	}

	public function onPlayerQuit(PlayerQuitEvent $event){
		unset($this->player[$event->getPlayer()->getName()]);
	}

	public function loadYml(){
		@mkdir($this->getDataFolder());
		$this->vs = (new Config($this->getDataFolder() . "VipSkin.yml", Config::YAML, $this->getServer()->getOps()))->getAll();
	}

	public function saveYml(){
		$vs = new Config($this->getDataFolder() . "VipSkin.yml", Config::YAML);
		$vs->setAll($this->vs);
		$vs->save();
	}

	public function isKorean(){
		return strtolower($this->getServer()->getLanguage()->getName()) == "korean";
	}
}
