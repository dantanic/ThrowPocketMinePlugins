<?php

/*
__PocketMine Plugin__
name=Portal
version=
author=
class=Portal
apiversion=12
*/

define("MOVE", 0);
define("TOUCH", 1);

class Portal implements Plugin{
	private $api, $path, $portals, $config, $temp;
	
	public function __construct(ServerAPI $api, $server = false){
		$this->api = $api;
	}
	
	public function init(){
		$this->sss = 0;
		$this->temp = array();
		$this->path = $this->api->plugin->configPath($this);
		$this->api->addHandler("player.move", array($this, "move"));
		$this->api->console->register("portal", "", array($this, "command"));
		$this->config = new Config($this->path."config.yml", CONFIG_YAML, array(
			'Sensitivity' => 1,
			'Broadcast' => false,
			'Defaults' => array(
				'Disposable' => false,
				'Type' => MOVE,
			),
		));
		$this->config = $this->api->plugin->readYAML($this->path . "config.yml");
		$this->portals = array();
		if(file_exists($this->path . "portals.dat")){
			$file = file_get_contents($this->path . "portals.dat");
			$this->portals = json_decode($file, true);
		}
	}
	
	public function move($data, $event){
		if(count($this->portals) == 0) return true;
		if($this->sss <= $this->config["Sensitivity"]){
			$this->sss = $this->sss + 1;
			return true;
		}else{
			$this->sss = 0;
		}
		$player = $data->player;
		$x = ceil($data->x);
		$y = ceil($data->y);
		$z = ceil($data->z);
		$level = $player->entity->level->getName();
		foreach($this->portals as $portalname => $portal){
			if($this->trigger($player, $portal, $x, $y, $z, $level, $portalname)){
				break;
			}
		}
	}
	
	private function trigger($player, $portal, $x, $y, $z, $level, $portalname){
		if($portal["type"] !== MOVE) return false;
		if($portal["trigger"]["pt1"]["level"] !== $level) return false;
		if($x >= (int)$portal["trigger"]["pt1"]["x"] and $x <= (int)$portal["trigger"]["pt2"]["x"] and $y >= (int)$portal["trigger"]["pt1"]["y"] and $y <= (int)$portal["trigger"]["pt2"]["y"] and $z >= (int)$portal["trigger"]["pt1"]["z"] and $z <= (int)$portal["trigger"]["pt2"]["z"]){
			if(isset($this->temp[$player->username]["edit"]) and $this->temp[$player->username]["edit"] == true){
				if($this->temp[$player->username]["inside"] != $portalname){
					$this->temp[$player->username]["inside"] = $portalname;
					$player->sendChat("[포탈] 포탈:[".$portalname."]");
				}
			}else{
				$targetLevel = $this->api->level->get($portal["target"]["level"]);
				if($targetLevel == false) return false;
				$targetX = (int)$portal["target"]["x"];
				$targetY = (int)$portal["target"]["y"];
				$targetZ = (int)$portal["target"]["z"];
				$pos = new Position($targetX, $targetY, $targetZ, $targetLevel);
				$player->teleport($pos);
				if($this->config["Broadcast"]){
					$builder = $portal["creator"];
					$playername = $player->username;
					$this->api->chat->broadcast("[포탈]".$playername."님이".$portalname."포탈을 사용했습니다.".$builder."목적지: ".$targetLevel." x : ".ceil($targetX)." y : ".ceil($targetY)." z : ".ceil($targetZ));
				}
				if($portal["disposable"]) $this->delete($portalname);
			}
			$this->temp[$player->username]["inside"] = $portalname;
			return true;
		}else{
			if(isset($this->temp[$player->username]["edit"]) and $this->temp[$player->username]["edit"] == true and $this->temp[$player->username]["inside"] != null){
				$player->sendChat("[포탈]당신은[".$portalname."]를 이용했습니다.");
			}
			$this->temp[$player->username]["inside"] = null;
			return false;
		}
	}
	
	private function delete($name){
		if(isset($this->portals[$name])){
			unset($this->portals[$name]);
			$this->save();
			return true;
		}else{
			return false;
		}
	}
	
	public function command($cmd, $params, $issuer, $alias){
		if(!($issuer instanceof Player)){
			$output = "[포탈]Please run this command in-game.";
			return $output;
		}
		$player = $issuer;
		$playername = $player->username;
		$x = ceil($player->entity->x);
		$y = ceil($player->entity->y);
		$z = ceil($player->entity->z);
		$level = $player->entity->level->getName();
		switch($params[0]){
			case "tal":
			case "pt3":
			case "3":
				if(isset($params[1])){
					$wn = $params[1];
					if($this->api->level->levelExists($wn)){
						$spawn = $this->api->level->get($wn)->getSafeSpawn();
						$this->temp[$playername]["target"]["level"] = $wn;
						$this->temp[$playername]["target"]["x"] = $spawn->x;
						$this->temp[$playername]["target"]["y"] = $spawn->y;
						$this->temp[$playername]["target"]["z"] = $spawn->z;
						$output = "[포탈]타겟 설정됨. (월드 :$wn)";
					}else{
						$output = "[포탈]없는 월드 입니다..";
					}
				}else{
					$this->temp[$playername]["target"]["level"] = $level;
					$this->temp[$playername]["target"]["x"] = $player->entity->x;
					$this->temp[$playername]["target"]["y"] = $player->entity->y;
					$this->temp[$playername]["target"]["z"] = $player->entity->z;
					$output = "[포탈]타겟 설정됨. (".ceil($player->entity->x).",".ceil($player->entity->y).",".ceil($player->entity->z).",".$level.")";
				}
				return $output;
			case "cre":
			case "c":
				if($params[1] == ""){
					$output = "[포탈]포탈 이름을 정해주세요..";
					return $output;
				}elseif(isset($this->portals[$params[1]])){
					$output = "[포탈]이미 있는 이름입니다..";
					return $output;
				}
				$temp = $this->gettemp($playername);
				if($temp == false){
					$output = "[포탈]생성 전애 모두 설정해주세요..";
					return $output;
				}
				$temp["disposable"] = $this->config["Defaults"]["Disposable"];
				if(isset($params[2])){
					if($params[2] == "d"){
						$temp["disposable"] = true;
					}else{
						$output = "[포탈]잘못된 값.";
						return $output;
					}
				}
				$temp["creator"] = $playername;
				$this->portals[$params[1]] = $temp;
				$this->save();
				$output = "[포탈]당신은 ".$params[1]."포탈을 생성하였습니다.";
				if($this->config["Broadcast"]){
					$this->api->chat->broadcast("[포탈]".$playername."가".$params[1]."포탈을 생성했습니다. /n x : ".$x." y : ".$y." z : ".$z." 월드 : ".$level);
				}
				return $output;
			case "pt1":
			case "1":
				if(isset($this->temp[$playername]["trigger"]["pt2"])){
					if($this->temp[$playername]["trigger"]["pt2"]["level"] !== $level){
						$output = "[포탈]당신은 다른월드에 있습니다.";
						return $output;
					}
				}
				$this->temp[$playername]["trigger"]["pt1"]["x"] = $x;
				$this->temp[$playername]["trigger"]["pt1"]["y"] = $y;
				$this->temp[$playername]["trigger"]["pt1"]["z"] = $z;
				$this->temp[$playername]["type"] = MOVE;
				$this->temp[$playername]["trigger"]["pt1"]["level"] = $level;
				$output = "[포탈]PT1 설정됨. ($x, $y, $z, $level)";
				return $output;
			case "pt2":
			case "2":
				if(isset($this->temp[$playername]["trigger"]["pt1"])){
					if($this->temp[$playername]["trigger"]["pt1"]["level"] !== $level){
						$output = "[포탈]당신은 다른 월드에 있습니다..";
						return $output;
					}
				}
				$this->temp[$playername]["trigger"]["pt2"]["x"] = $x;
				$this->temp[$playername]["trigger"]["pt2"]["y"] = $y;
				$this->temp[$playername]["trigger"]["pt2"]["z"] = $z;
				$this->temp[$playername]["type"] = MOVE;
				$this->temp[$playername]["trigger"]["pt2"]["level"] = $level;
				$output = "[포탈]PT2 설정됨. ($x, $y, $z, $level)";
				return $output;
			case "del":
			case "d":
				if(isset($params[1]) == false){
					if(isset($this->temp[$playername]["edit"]) and $this->temp[$playername]["edit"] == true){
						$params[1] = $this->temp[$player->username]["inside"];
					}else{
						$output = "[포탈] 삭제할 포탈 이름을 적어주세요..";
						return $output;
					}
				}if($this->delete($params[1])){
					$output = "[포탈] 삭제됨.";
					if($this->config["Broadcast"]){
						$this->api->chat->broadcast("[포탈]".$player->iusername."이 포탈을 삭제했습니다. : ".$params[1]);
					}
				}else{
					$output = "[포탈]없는 포탈입니다..";
				}
				return $output;
			case "ed":
			case "edit":
			case "e":
				if(isset($this->temp[$playername]["edit"]) and $this->temp[$playername]["edit"] == true){
					$this->temp[$playername]["edit"] = false;
					return "[포탈] 에딧모드 꺼짐";
				}else{
					$this->temp[$playername]["edit"] = true;
					return "[포탈] 에딧모드 켜짐";
				}
			default:
				$output = "[포탈] 잘못된 명령어 입니다..";
				return $output;
		}
	}
	
	private function gettemp($playername){
		if(isset($this->temp[$playername]) == false) return false;
		if(isset($this->temp[$playername]["target"])){
			if($this->temp[$playername]["type"] == MOVE){
				if(isset($this->temp[$playername]["trigger"]["pt1"]) and isset($this->temp[$playername]["trigger"]["pt2"])){
					$temp = $this->temp[$playername];
					$max["x"] = max($temp["trigger"]["pt1"]["x"],$temp["trigger"]["pt2"]["x"]);
					$max["y"] = max($temp["trigger"]["pt1"]["y"],$temp["trigger"]["pt2"]["y"]);
					$max["z"] = max($temp["trigger"]["pt1"]["z"],$temp["trigger"]["pt2"]["z"]);
					$min["x"] = min($temp["trigger"]["pt1"]["x"],$temp["trigger"]["pt2"]["x"]);
					$min["y"] = min($temp["trigger"]["pt1"]["y"],$temp["trigger"]["pt2"]["y"]);
					$min["z"] = min($temp["trigger"]["pt1"]["z"],$temp["trigger"]["pt2"]["z"]);
					$temp["trigger"]["pt1"]["x"] = $min["x"];
					$temp["trigger"]["pt1"]["y"] = $min["y"];
					$temp["trigger"]["pt1"]["z"] = $min["z"];
					$temp["trigger"]["pt2"]["x"] = $max["x"];
					$temp["trigger"]["pt2"]["y"] = $max["y"];
					$temp["trigger"]["pt2"]["z"] = $max["z"];
					unset($this->temp[$playername]);
					return $temp;
				}else{
					return false;
				}
			}elseif(isset($this->temp[$playername]["trigger"])){
				$temp = $this->temp[$playername];
				unset($this->temp[$playername]);
				return $temp;
			}else{
				return false;
			}
		}else{
			return false;
		}
	}
	
	private function save(){
		$file = json_encode($this->portals);
		file_put_contents($this->path . "portals.dat", $file);
	}
	
	public function __destruct(){
	}
}