<?php
 
/*
__PocketMine Plugin__
name=Spawn
version=
author=
class=Spawn
apiversion=12
*/

class Spawn implements Plugin{  
	private $api;

	public function __construct(ServerAPI $api, $server = false){
		$this->api = $api;
	}

	public function init(){
		$this->api->console->register("setspawn", "", array($this, "commandHandler"));
		$this->api->console->register("spawn", "", array($this, "commandHandler"));
	}

	public function __destruct(){
	}

	public function commandHandler($cmd, $args, $issuer, $params, $alias){
		switch($cmd){
			case "setspawn":
				 if(!($issuer instanceof Player)){
					console("[Spawn+]: Run command in game");
					break;
				}
				switch($args[0]){
					case "":
						$position = new Vector3($issuer->entity->x, $issuer->entity->y, $issuer->entity->z);
						$level = $issuer->level->getName();
						$this->api->level->get($level)->setSpawn($position);
						$issuer->sendChat("[스폰] 스폰 설정됨.  월드:".$level);
					break;
					case "help":
					case "?":
								$issuer->sendChat("------스폰+ Help------");
								$issuer->sendChat("/setspawn - set point of spawn in world\n");
								$issuer->sendChat("/setspawn set <world> <x> <y> <z> - set spawn with coordinates\n");
					break;
					case "set":
						$x = $args[2];
						$y = $args[3];
						$z = $args[4];
						$world = $args[1];
						if($x === null or $y === null or $z === null or $world === null)
						{   
							$issuer->sendChat("[스폰] /setspawn <world> <x> <y> <z>");
						}
						else
						{
							$position = new Vector3($x, $y, $z);
							$this->api->level->get($world)->setSpawn($position);
							$issuer->sendChat("[스폰] 스폰 설정됨. 월드:".$world." x:".$x."y:".$y."z:".$z);
						}
					break;
				}
				break;

			case "spawn":
			if(!($issuer instanceof Player)){
			console("[Spawn+]: Run command in game");
			break;
			}
				$level = $issuer->level->getName();
					 switch($args[0]){
							case "":
							  $spawn = $this->api->level->get($level)->getSpawn();
							  $issuer->teleport($spawn);
							  $issuer->sendChat("[스폰] 스폰 완료. 월드:".$level);
							break;
							case "main":
							  $issuer->teleport($this->api->level->getDefault()->getSpawn());
							  $issuer->sendChat("[스폰] 메인으로 스폰 완료.");
							break;
							case "help":
							case "?":
								$issuer->sendChat("------Spawn Help------");
								$issuer->sendChat("/spawn - teleport to world spawn\n");
								$issuer->sendChat("/spawn main - teleport to main spawn\n");
								$issuer->sendChat("/spawn help - show this massage\n");
							break;
						}
			break;
		}
	}
}