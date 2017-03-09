<?php

/*
__PocketMine Plugin__
name=Casino
version=1.0.0
author=DeBe
apiversion=12
class=Casino
*/

class Casino implements Plugin {
	private $api;
	public function __construct(ServerAPI $api, $server = false){
		$this->api = $api;
		$this->casino = array();
	}
	public function init(){
		if(!isset($this->api->economy) or !$this->api->economy instanceof EconomyAPI){
			console("[ERROR] EconomyAPI does not exist");
			$this->api->console->run("stop");
			return;
		}
		$this->api->console->register("도박", "<개설|참가|베팅|잿팟|퇴장|리스트|참여자>", array($this, "Commander"));
		$this->api->ban->cmdWhitelist("도박");
		$this->api->addHandler("player.quit", array($this, "onQuit"));
	}

	public function __destruct(){}

	public function Commander($cmd, $params, $issuer){
		if(!$issuer instanceof Player) return "Please run this command in-game.\n";
		$output = "";
		$pr0 = strtolower($params[0]);
		$pr1 = strtolower($params[1]);
		switch($pr0){
			case "잭팟":
			case "잭":
			case "jackpot":
			case "j":
				$money = $pr1;
				if(str_replace(" ", "", $money) == "" or !is_numeric($money)){
					return " [도박] /도박 잭팟 <금액>";
				}elseif($money <= 0){
					return " [도박] 금액이 너무 적습니다.";
				}elseif(!$this->api->economy->useMoney($issuer, $money)){
					return " [도박] 돈이 부족합니다.";
				}else{
					$jackpot = false;
					if(rand(1,10) == 1) $jackpot = true;
					if($jackpot){
						$money = $money * 5;
						$this->api->economy->takeMoney($issuer,$money);
						return " [도박] 돈을 얻엇습니다!. \$$money";
					}else{
						return " [도박] 돈을 잃었습니다. \$$money";
					}
				}
			break;
			case "베팅":
			case "베":
			case "ㅂ":
			case "suggest":
			case "s":
 				if(str_replace(" ", "", $pr1) == "" or !is_numeric($pr1)){
					return " [도박] /도박 베팅 <금액>";
				}else{
					$exist = false;
					$key = null;
					$v = null;
					foreach($this->casino as $k => $value){
						if(in_array($issuer->iusername, $value)){
							$exist = true;
							$key = $k;
							$v = $value;
							break;
						}
					}
					if(!$exist) return " [도박] 게임에 참가중이 아닙니다.";
					if(isset($this->casino[$issuer->iusername]) or $exist){
						$can = $this->api->economy->useMoney($issuer, $pr1);
						if($can == false) return " [도박] 돈이 부족합니다.";
						$foreach = isset($this->casino[$issuer->iusername]) ? $this->casino[$issuer->iusername] : in_array($issuer->iusername, $value) ? $this->casino[$key] : null;
						if($foreach == null){
							return " [도박] 알수없는 오류.";
						}elseif(count($foreach) == 1){
							return " [도박] 게임에 당신밖에 없습니다.";
						}
						$pr1 = floor($pr1);
						$allin = false;
						foreach($foreach as $value){
							if($this->api->economy->mymoney($value) < 1){
								$allin = true;
								break;
							}
						}
						if($allin == true) return " [도박] 누군가가 올인하신상태입니다.";
						$r = rand(0, count($foreach) - 1);
						if(!($player = $this->api->player->get($foreach[$r])) instanceof Player){
							foreach($foreach as $f) $this->api->player->get($f, false)->sendChat(" [도박] [베팅 : ".$issuer->username."/$".$pr1."] 오류가 발생하어 베팅이 취소됩니다.");
						}else{
							$money = 0;
							foreach($foreach as $value){
								if($value == $issuer->iusername) continue;
								$can = $this->api->economy->useMoney($value, $pr1);
								if(!$can){
									$money += $this->api->economy->mymoney($value);
									$this->api->economy->setMoney($value, 0);
									$this->api->player->get($value, false)->sendChat(" [도박] [베팅 : ".$issuer->username."] 올인하셨습니다.");
								}else{
									$money += $pr1;
									$this->api->player->get($value, false)->sendChat(" [도박] [베팅 : ".$issuer->username."] \$$pr1 이 베팅되었습니다. $pr1");
								}
								$this->api->economy->takeMoney($foreach[$r], $money);
								$player->sendChat(" [도박] [베팅 : ".$issuer->username."] \$$money 을 따셨습니다!");
								foreach($foreach as $f){
									if($f == $foreach[$r]) $this->api->player->get($f, false)->sendChat(" [도박] [베팅 : ".$issuer->username."] ".$foreach[$r]."님이 $".$money."을 휩쓸어갑니다.");
								}
							}
						}
					}
				}
			break;
			case "참가":
			case "참여":
			case "가입":
			case "접속":
			case "join":
			case "j":
				$slot = $pr1;
				if(str_replace(" ", "", $slot) == "") return " [도박] /도박 참가 <방이름>";
				if(!isset($this->casino[$slot])) return " [도박] \$$slot 이라는 이름의 방은 없습니다.";
				$exist = false;
				foreach($this->casino as $key => $value){
					if(in_array($issuer->iusername, $value)){
						$exist = true;
						break;
					}
				}
				if($exist or isset($this->casino[$issuer->iusername])) return " [도박] 이미 게임에 참가중입니다.";
				foreach($this->casino[$slot] as $value) $this->api->player->get($value, false)->sendChat(" [도박] ".$issuer->username."님이 참가하셨습니다.");
				$this->casino[$slot][] = $issuer->iusername;
				return " [도박] 도박에 참여하셨습니다.";
			break;
			case "탈퇴":
			case "나가기":
			case "닫기":
			case "종료":
			case "중지":
			case "퇴장":
			case "quit":
			case "q":
				if(!isset($this->casino[$issuer->iusername])){
					return " [도박] 도박에 참여중이 아닙니다.";
				}else{
					foreach($this->casino[$issuer->iusername] as $value){
						if($value == $issuer->iusername) continue;
						$this->api->player->get($value, false)->sendChat(" [도박] 도박장이 닫혔습니다.");
						unset($this->casino[$issuer->iusername]);
						$this->api->chat->broadcast(" [도박] ".$issuer->username."님의 도박장이 닫혔습니다.");
						$issuer->sendChat(" [도박] 도박장을 닫으셧습니다.");
						break;
					}
					$exist = false;
					foreach($this->casino as $k => $value){
						if(in_array($issuer->iusername, $value)) $exist = true;
					}
					if($exist){
						$key = null;
						foreach($this->casino as $k => $value){
							if(in_array($issuer->iusername, $value)){
								$key = $k;
								break;
							}
						}
						unset($this->casino[$key][array_search($issuer->iusername, $this->casino[$key])]);
						$issuer->sendChat(" [도박] 도박장을 나갔습니다.");
						foreach($this->casino[$key] as $value) $this->api->player->get($value, false)->sendChat("[도박장] ".$issuer->username."님이 도박장을 나가셧습니다.");
					}
				}
			break;
			case "개설":
			case "열기":
			case "시작":
			case "start":
			case "s":
				if(isset($this->casino[$issuer->iusername])) return " [도박] 이미 게임에 참가중입니다.";
				$exist = false;
				foreach($this->casino as $key => $value){
					if(in_array($issuer->iusername, $value)) return " [도박] 이미 게임에 참가중입니다.";
				}
				$this->casino[$issuer->iusername][] = $issuer->iusername;
				$this->api->chat->broadcast(" [도박] ".$issuer->username."님이 도박장을 개설하셨습니다.");
				return;
			break;
			case "목록":
			case "리스트":
			case "퇴장":
			case "list":
			case "l":
				$page = $pr1;
				$page = max($page, 1);
				$max = count($this->casino);
				$page = min($page, $max);
				$current = 1;
				$output .= "- 도박장 목록 [$page / $max] -\n";
				foreach($this->casino as $key => $value){
					$curpage = ceil($current / 5);
					if($curpage > $page) break;
					elseif($curpage == $page) $output .= "[$key] 참여자수 : $playercnt".count($value)."\n";
				}
			break;
	 		case "플레이어":
			case "참여자":
			case "접속자":
			case "player":
			case "p":
			$slot = $pr1;
				if(str_replace(" ", "", $slot) == "") return " [도박] /도박 참여자 <방이름>";
				if(!isset($this->casino[$slot])){
					return " [도박] ".$slot."이라는 방은 없습니다.";
				}else{
					$output .= $slot."의 참여자 : \n";
					$x = 0;
					foreach($this->casino[$slot] as $value){
						$output .= "[$x] $value\n";
						$x++;
					}
				}
			break;
			default:
				return " [도박] /도박 <개설|참가|베팅|퇴장|리스트|참여자>";
			break;
		}
		return $output."\n";
	}
	
	public function onQuit($issuer, $event){
		if(isset($this->casino[$issuer->iusername])){
			foreach($this->casino[$issuer->iusername] as $value){
				if($value == $issuer->iusername) 
					continue;
				$this->api->player->get($value, false)->sendChat(" [도박] 도박장이 닫혔습니다.");
			}
			unset($this->casino[$issuer->iusername]);
			return;
		}
		$exist = false;
		foreach($this->casino as $k => $value){
			if(in_array($issuer->iusername, $value)){
				$exist = true;
			}
		}
		if($exist){
			$key = null;
			foreach($this->casino as $k => $value){
				if(in_array($issuer->iusername, $value)){
					$key = $k;
					break;
				}
			}
			unset($this->casino[$key][array_search($issuer->iusername, $this->casino[$key])]);
			foreach($this->casino[$key] as $value){
				$this->api->player->get($value, false)->sendChat(" [도박] ".$issuer->iusername."님이 게임를 나가셨습니다.");
			}
		}
	}
}