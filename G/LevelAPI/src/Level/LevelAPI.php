<?php

namespace Level;

use pocketmine\Player;
use pocketmine\item\Item;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat as Color;

class LevelAPI extends \pocketmine\plugin\PluginBase implements \pocketmine\event\Listener{
	public function onEnable(){
		$this->loadYml();
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
	}

	public function onCommand(\pocketmine\command\CommandSender $sender, \pocketmine\command\Command $cmd, $label, array $sub){
		if(!isset($sub[0])) return false;
		$la = $this->la;
		$ik = $this->isKorean();
		switch(strtolower($cmd->getName())){
			case "level":
				Color::RED . "Usage: /Level" .= "Level ";
				switch(strtolower($sub[0])){
					case "me":
					case "my":
					case "m":
					case "내경험치":
					case "나":
						$r = Color::YELLOW . "[Level] " . ($ik ? "나의 레벨 : " : "My Level: ") . $this->exp2Level($exp = $this->getExp($sender)) . " ($exp)" . Color::GOLD . "   " . ($ik ? "랭킹 : " : "Rank : ") . $this->getRank($sender);
					break;
					case "see":
					case "view":
					case "v":
					case "보기":
						if(!isset($sub[1])){
							$r = Color::RED . "Usage: /Level View(V) " . ($ik ? "<플레이어명>" : "<PlayerName>");
						}elseif(!($playerName = $this->getPlayer($sub[1]))){
							$r = Color::RED . "[Level] $sub[1]" . ($ik ? "은 잘못된 이름입니다." : " is invalid name");
						}else{
							$r = Color::YELLOW . "[Level] $playerName" . ($ik ? "의 레벨 : " : "'s Level : ") . $this->exp2Level($exp = $this->getExp($playerName)) . " ($exp)" . Color::GOLD . "    " . ($ik ? "랭킹 : " : "Rank : ") . $this->getRank($playerName);
						}
					break;
					case "rank":
					case "r":
					case "랭킹":
					case "순위":
						arsort($la);
						$lists = array_chunk($la, 5);
						$page = min(isset($sub[2]) && is_numeric($sub[2]) && isset($lists[$sub[2] - 1]) ? $sub[2] : 1, count($lists));
						$r = Color::YELLOW . "[Level] " . ($ik ? "레벨 랭킹 (페이지: " : "Level Rank (Page: ") . $page . "/" . count($lists) . ") (" . count($la) . ")";
						$keys = array_keys($sc);
						foreach($lists[$page - 1] as $key => $exp) $r .= "\n" . Color::GOLD . "    [" . (($expKey = (($page - 1) * 5 + $key)) + 1) .  "] " . $keys[$expKey] . " : " . $this->exp2Level($exp) . "($exp)";
					break;
					default:
						return false;
					break;
				}
			break;
 			case "levelapi":
				switch(strtolower($sub[0])){
					case "set":
					case "s":
					case "설정":
						if(!isset($sub[1])){
							$r = Color::RED . "Usage: /LevelAPI Set(S) " . ($ik ? "<플레이어명> <경험치>" : "<PlayerName> <EXP>");
						}elseif(!($playerName = $this->getPlayer($sub[1]))){
							$r = Color::RED . "[Level] $sub[1]" . ($ik ? "은 잘못된 이름입니다." : " is invalid name");
						}elseif(!is_numeric($sub[2]) || $sub[2] < 0){
							$r = Color::RED . "[Level] $sub[2]" . ($ik ? "은 잘못된 숫자입니다." : " is invalid number");
						}else{
							$sub[2] = $sub[2] < 0 ? 0 : floor($sub[2]);
							$this->setExp($playerName, $sub[2]);
							$la = $this->la;
							$r = Color::YELLOW . "[Level] " . $playerName . ($ik ? "의 경험치를 $sub[2]으로 설정했습니다.  " : "'s exp is set to $sub[2]");
							if($player = $this->getServer()->getPlayerExact($playerName)) $player->sendMessage(Color::YELLOW . "[Level] " . ($ik ? "당신의 경험치가 어드민에 의해 변경되었습니다. 나의 경험치 : " : "Your money is change by admin. My Exp: ") . $this->getExp($player));
						}
					break;
					case "give":
					case "g":
					case "add":
					case "a":
					case "지급":
						if(!isset($sub[1])){
							$r = Color::RED . "Usage: /LevelAPI Give(G) " . ($ik ? "<플레이어명> <경험치>" : "<PlayerName> <EXP>");
						}elseif(!($playerName = $this->getPlayer($sub[1]))){
							$r = Color::RED . "[Level] $sub[1]" . ($ik ? "은 잘못된 이름입니다." : " is invalid name");
						}elseif(!is_numeric($sub[2]) || $sub[2] < 0){
							$r = Color::RED . "[Level] $sub[2]" . ($ik ? "은 잘못된 숫자입니다." : " is invalid number");
						}else{
							$sub[2] = $sub[2] < 0 ? 0 : floor($sub[2]);
							$this->giveExp($playerName, $sub[2]);
							$la = $this->la;
							$r = Color::YELLOW . "[Level] " . ($ik ? $playerName . "에게 $sub[2] exp를 지급했습니다." : "give $sub[2] exp to $playerName");
							if($player = $this->getServer()->getPlayerExact($playerName)) $player->sendMessage(Color::YELLOW . "[Level] " . ($ik ? "당신의 경험치가 어드민에 의해 변경되었습니다. 나의 경험치 : " : "Your money is change by admin. My Exp: ") . $this->getExp($player));
						}
					break;
					case "take":
					case "t":
					case "reduce":
					case "r":
					case "뺏기":
						if(!isset($sub[1])){
							$r = Color::RED . "Usage: /LevelAPI Set(S) " . ($ik ? "<플레이어명> <경험치>" : "<PlayerName> <EXP>");
						}elseif(!($playerName = $this->getPlayer($sub[1]))){
							$r = Color::RED . "[Level] $sub[1]" . ($ik ? "은 잘못된 이름입니다." : " is invalid name");
						}elseif(!is_numeric($sub[2]) || $sub[2] < 0){
							$r = Color::RED . "[Level] $sub[2]" . ($ik ? "은 잘못된 숫자입니다." : " is invalid number");
						}else{
							$sub[2] = $sub[2] < 0 ? 0 : floor($sub[2]);
							$this->takeExp($playerName, $sub[2]);
							$la = $this->la;
							$r = Color::YELLOW . "[Level] " . ($ik ? $playerName . "에게서 $sub[2] exp를 뺏었습니다." : "take $sub[2] exp to $playerName");
							if($player = $this->getServer()->getPlayerExact($playerName)) $player->sendMessage(Color::YELLOW . "[Level] " . ($ik ? "당신의 경험치가 어드민에 의해 변경되었습니다. 나의 경험치 : " : "Your money is change by admin. My Exp: ") . $this->getExp($player));
						}
					break;
					break;
					case "clear":
					case "c":
					case "초기화":
						$la = [];
						$m = Color::YELLOW . "[Level] " . ($ik ? "모든 플레이어의 경험치가 초기화되었습다." : "All Player's exp is reset");
					break;
					default:
						return false;
					break;
				}
			break;
		}
		if(isset($r)) $sender->sendMessage($r);
		if(isset($m)) $this->getServer()->broadcastMessage($m);
		if($this->la !== $la){
			$this->la = $la;
			$this->saveYml();
		}
		return true;
	}

	public function onPlayerJoin(\pocketmine\event\player\PlayerJoinEvent $event){
		$this->getMoney($event->getPlayer());
	}

	public function getMP($name = ""){
		if($name instanceof Player) $name = $name->getName();
		if(!$name) return false;
		if(!isset($this->la[$name = strtolower($name)])){
			$this->la[$name] = 0;
			$this->saveYml();
		}
		if(isset($this->la[$name = strtolower($name)])) return ["Player" => $name, "Level" => $this->la[$name]];
		else return false;
	}

	public function getPlayer($name = ""){
		if($name instanceof Player) $name = $name->getName();
		return !$this->getMP($name) ? false : $this->getMP($name)["Player"];
	}

	public function getExp($name = ""){
		if($name instanceof Player) $name = $name->getName();
		return !$this->getMP($name) ? false : $this->getMP($name)["Level"];
	}

	public function hasExp($name = "", $money = 0){
		if($name instanceof Player) $name = $name->getName();
		if(!$m = $this->getExp($name)) return false;
		else return $money <= $m;
	}

	public function setExp($name = "", $money = 0){
		$la = $this->la["Level"];
		if($name instanceof Player) $name = $name->getName();
		$name = strtolower($name);
		if(!is_numeric($money) || $money < 0) $money = 0;
		if(!$name && !$all && !$this->getExp($name)){
			return false;
		}else{
			$la[strtolower($name)] = floor($money);
		}
		if($this->la["Level"] !== $la){
			$this->la["Level"] = $la;
			$this->saveYml();
		}
		return true;
	}

	public function giveExp($name = "", $money = 0){
		if(!is_numeric($money) || $money < 0) $money = 0;
		if($name instanceof Player) $name = $name->getName();
		if(!$name && !$all && !$this->getExp($name)){
			return false;
		}else{
			$this->setExp($name, $this->getExp($name) + $money);
		}
		return true;
	}

	public function takeExp($name = "", $money = 0){
		if(!is_numeric($money) || $money < 0) $money = 0;
		if($name instanceof Player) $name = $name->getName();
		if(!$name && !$all && !$this->getExp($name)){
			return false;
		}else{
			$getLevel = $this->getExp($name);
			if($getLevel < $money) $money = $getLevel;
			$this->setExp($name, $this->getExp($name) - $money);
		}
		return true;
	}

	public function exp2Level($exp){
		
	}

	public function getAllExps(){
		return $this->la;
	}

	public function setAllLevels($exps){
		if(is_array($exps)){
			$this->la = $exps;
			$this->saveYml();
			return true;
		}
		return false;
	}

	public function getRanks($page = 1){
		$m = $this->la["Level"];
		arsort($m);
		$ik = $this->isKorean();
		$list = ceil(count($m) / 5);
		if($page >= $list) $page = $list;
		$r = "[Rank] (" . ($ik ? "페이지" : "Page") . " $page/$list) \n";
		$num = 1;
		foreach($m as $k => $v){
			if(!$this->la["OP"] && $this->getServer()->isOp($k)) continue;
			if(!isset($same)) $same = [$v, $num];
			if($v == $same[0]){
				$rank = $same[1];
			}else{
				$rank = $num;
				$same = [$v, $num];
			}
			if($num + 5 > $page * 5 && $num <= $page * 5) $r .= "  [" . ($v > 0 ? $rank : "-") . "] $k : $v \n";
			$num++;
		}
		return $r;
	}

	public function getRank($name = ""){
		if($name instanceof Player) $name = $name->getName();
		if(!$name) return false;
		$m = $this->la["Level"];
		arsort($m);
		$num = 1;
		if(!$this->la["OP"] && $this->getServer()->isOp($name)) return "OP";
		elseif(!$this->getExp($name)) return "-";
		else{
			foreach($m as $k => $v){
				if(!$this->la["OP"] && $this->getServer()->isOp($k)) continue;
				if(!isset($same)) $same = [$v, $num];
				if($v == $same[0]){
					$rank = $same[1];
				}else{
					$rank = $num;
					$same = [$v, $num];
				}
				$num++;
				if($k == strtolower($name)) return $rank;
				else continue;
			}
		}
		return false;
	}

	public function loadYml(){
		@mkdir($this->getServer()->getDataPath() . "plugins/Level/");
		$this->la = (new Config($this->getServer()->getDataPath() . "plugins/Level/Levels.yml", Config::YAML))->getAll();
	}

	public function saveYml(){
		asort($this->la);
		$la = new Config($this->getServer()->getDataPath() . "plugins/Level/Level.yml", Config::YAML);
		$la->setAll($this->la);
		$la->save();
	}

	public function isKorean(){
		return $this->getServer()->getLanguage()->getName() == "\"한국어\"";
	}
}