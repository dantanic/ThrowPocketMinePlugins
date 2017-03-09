<?php

/*
__Ability Plugins__
name=Ability
version=0.1.0
author=DeBe
apiversion=12
class=Ability
*/

class Ability implements Plugin{
	private $api;

	public function __construct(ServerAPI $api, $server =false){
		$this->api = $api;
		$this->Ability = array();
		$this->Set = array("Start" => 0, "List" => range(1,44));
	}

	public function init(){
		$Register = array(
			array("aa","OP"),
			array("a","Player")
		);
		$Alias = array(
			array("시작","aa start"),
			array("중지","aa stop"),
			array("셋","aa set") 
		);
		$AddHandler = array(
			array("player.join","Join"),
			array("player.spawn","Join"),
			array("player.respawn","Join"),
			array("player.quit","Join"),
	 		array("player.action","Touch"),
			array("player.block.touch","Touch"),
			array("player.block.break","Touch"),
 			array("player.interact","Interact"),
 			array("player.death","Death"),
			array("entity.health.change","Health"),
			array("entity.move","Move")
		);
		foreach($Register as $r) $this->api->console->register($r[0], "Ability.".$r[1]." Command", array($this, "Commander"));
		foreach($Alias as $a)	$this->api->console->alias($a[0],$a[1]);
		$this->api->ban->cmdWhitelist("a");
		foreach($AddHandler as $ah) $this->api->addHandler($ah[0], array($this,$ah[1]."_Handler"));
	}

	public function Commander($cmd,$params,$issuer){
		$cmd =strtolower($cmd);
		switch($cmd){
			case "aa":
				if(strtolower($params[0]) == " " or $params[0] == null){
					if($this->Set["Start"] == 0){
						$params[0] = "start";
			 		}else{
						$params[0] = "stop";
					}
				}
				switch(strtolower($params[0])){
					case "faststart":
					case "fstart":
					case "f":
					case "start":
					case "s":
						if($this->Set["Start"] !== 0){
				 			return "  [A] 이미 게임중입니다.";
				 		}elseif(count($this->api->player->getAll()) < 0){
				 			return "  [A] 플레이어가 너무 적습니다.";
				 		}else{
	 						switch(strtolower($params[0])){
								case "faststart":
								case "fstart":
								case "f":
							 		$this->Set["Start"] = 1;
									$this->GameStart();
								break;
								case "start":
								case "s":
								 $this->Wait();
								break;
							}
						}
					break;	
					case "stop":
					case "st":
						if($this->Set["Start"] == 0){
	 						return "  [A] 아직 게임중이 아닙니다.";
						}else{
							$this->GameStop(0);
						}
					break;
					case "set":
					 if($this->Set["Start"] == 0){
					 		return "  [A] 게임이 시작되지 않았습니다.";
					 	}elseif(!isset($params[2])){
					 		if(!$issuer instanceof Player){
					 			return "  [A] 플레이어명 or 능력코드를 입력해주세요.";
					 		}else{
					 	 		$this->setAbility($issuer,$params[1]);
					 	 		$this->Broadcast("  [A] 누군가의 능력이 강제할당되었습니다.");
								return "[A] 능력을 [".$this->Ability[$issuer->username]["Name"]."]으로 지정합니다.";
					 		}
					 	}else{
					 		$Target = $this->api->player->get($params[1]);
					 		if(!$Target instanceof Player) return "[A] 플레이어명을 확인해주세요.".
							$this->setAbility($Target,$params[2]);
							$this->Broadcast("  [A] 누군가의 능력이 강제할당되었습니다.");
							return "[A] ".$Target->username."님의 능력을 [".$this->Ability[$Target->username]["Name"]."]으로 지정합니다.";
						}
					break;
					case "inv":
			 			if($this->Set["Start"] == 0){
	 						return "  [A] 아직 게임중이 아닙니다.";
						}elseif($this->Set["Start"] == 2){
							$this->Set["Start"] = 3;
	 						$this->Broadcast("  [A] 초반무적이 해제됩니다.");
						}else{
							$this->Set["Start"] = 2;	
							$this->Broadcast("  [A] 초반무적이 실행됩니다.");
						}
					break;
					default:
						return "/AA <Start|Faststart|Stop|Inv|Set";
	 				break;
				}
			break;
			case "a":
			 if($this->Set["Start"] == 0){
			 		return "  [A] 게임이 시작되지 않았습니다.";
			 }elseif(!$issuer instanceof Player){
					return "[A] 게임내에서만 사용해주세요.";
				}else{
			 		$A = $this->Ability[$issuer->username];
					$M = array(
					"°°°°°°°°°°°°°°°°",
	 				"  이름 : ".$A["Name"]." [".$A["AP"]." / ".$A["Rank"]." 랭크]",
					"  능력 : ".$A["Info"],
					"  쿨탐 : ".$A["Cool"]."초     Code-[".$A["Ability"]."/44]",
					"°°°°°°°°°°°°°°°°",
					);
					foreach ($M as $m){
						$issuer->sendChat($m);
					}
				}
			break;
		}
	}

	public function Join_Handler($data,$event){
	 if($this->Set["Start"] !== 0){
			switch($event){
				case "player.join":
				case "player.spawn":
				case "player.respawn":
				 if(!isset($this->Ability[$data->username])) $this->setAbility($data,-1);
				 $A = $this->Ability[$data->username];
				 if($A["Ability"] == "11" and $A["Life"] !== 0) $data->teleport($A["Use"]);
				break;
				case "player.quit":
					$count = 0;
				 	foreach($this->api->player->getAll() as $Pl){
						if($Pl !== $data and $this->Ability[$Pl->username]["Life"] !== 0){
 					 		$count += 1;
 					 		$Pn = $Pl->username;
 						}
 					}
					if($count == 1){
					 $this->GameStop(1,$Pn);
					}
 				break;
 			}
		}
	}

	public function Touch_Handler($data,$event){
	 if($this->Set["Start"] > 1){
			$P = $data["player"];
			$I = $event == "player.action" ? $data["item"] : $data["item"]->getID();
			$A = $this->Ability[$P->username];
			if($A["Life"] == 0){
				return false;
			}elseif(in_array($I,$A["Item"]) or $A["Item"][0] == -1){
				$CT = time(true) - $A["Cool"]; 						
	 			$LT = time(true) - $A["Lock"]; 						
	 			if($LT < 0){
	 				$P->sendChat("  [A] 능력봉인 : ".$LT*-1 ."초");
 					return false;
	 			}elseif($CT < 0){
	 				$P->sendChat("  [A] 쿨타임 : ".$CT*-1 ."초");
 					return false;
 				}else{
 					$o = "  [A] 대상이 너무 멀거나 없습니다.";
 					switch($A["Ability"]){
 						case 4: //반연금술
  						switch($event){
 								case "player.action":
 									$P->removeItem(266,0,1);
									$P->entity->heal(10);
								break;
 								case "player.block.touch":
 									if($data["type"] == "break"){
 										$cnt = 0;
 									 	foreach($P->inventory as $slot => $I){
											if($I->getID() == 266) $cnt += $I->count;
										}
										if($cnt < 3){
											$uncool = true;
				 							$P->sendChat("  [A] 금이 부족합니다. !".$cnt."개 소유중");
				 						}else{
				 							$P->removeItem(266,0,3);
				 							$P->addItem(264,0,1);
				 						}
				 					}else{
 										$P->removeItem(266,0,1);
										$P->addItem(265,0,1);
				 					}
								break;
 							}
 						break;
 						case 6: //거식증
							$i = array(
 								260 => 8,
								282 => 20,
								459 => 20,
								297 => 20,
								319 => 6,
								320 => 16,
								363 => 6,
								364 => 16,
								365 => 12,
								366 => 4,
								360 => 4,
								400 => 16,
								391 => 8,
								392 => 2,
								393=> 12,
							);
							if($P->entity->getHealth() < 20){
								$P->entity->heal($i[$I],"거식증");
 								$P->removeItem($I,0,1);
 							}
 						break;
 						case 7: //점퍼
 						case 20: //C9
 							$V = $this->View($P,1,50);
 							if($V == false){
 								$uncool = true;
 								$P->sendChat($o);
 							}else{
 								$P->teleport(new Vector3($V->x,$V->y+1,$V->z));	
 								if($A["Ability"] == 20){
									$pk = new ExplodePacket;
									$pk->x = $P->x;
									$pk->y = $P->y;
									$pk->z = $P->z;
									$pk->radius = 1;
									$pk->records = array();
									$this->api->player->broadcastPacket($P->level->players,$pk);
								}
 							}
 						break;
 						case 8: //메딕
							if($P->entity->getHealth() >= 20){
								$uncool = true;
								$P->sendChat("  [A] 체력이 최대입니다.");
							}else{
								$P->entity->heal(4);
							}
 						break;
 						case 10: //타임
 							foreach($P->level->players as $Pl){
 								//if($P !== $Pl)
 								$e = $Pl->entity;
 								$this->Ability[$e->name]["Move"]["Time"] = time(true) + 5;
 								$this->Ability[$e->name]["Move"]["Vec"] = array("X" => $e->x, "Y" => $e->y, "Z" => $e->z);
 							}
 						break;
						case 14: //이지스
 							$this->Ability[$p->username]["Inv"] = time(true) + 10;
 						break;
 						case 15:
 							if($I == 265){
 								$this->Ability[$Pn]["Use"] = 10;
 								$P->removeItem(265,0,1);
 							}else{
 								$V = $this->View($P,2,30);
								$pk = new ExplodePacket;
								$pk->x = $P->x;
								$pk->y = $P->y;
								$pk->z = $P->z;
								$pk->radius = 1;
								$pk->records = array();
								$this->api->player->broadcastPacket($P->level->players,$pk);
								if($V !== false){
									$e = $V->entity;
									$V->harm(2,"Gun");
								}
 							}
						break;
 						case 16: //쇼크웨이브
 							$V = $this->View($P,1,30);
							if($V == false){
 								$uncool = true;
 								$P->sendChat($o);
 							}else{
								$pk = new ExplodePacket;
								$pk->x = $V->x;
								$pk->y = $V->y;
								$pk->z = $V->z;
								$pk->radius = 1;
								$pk->records = array();
								$this->api->player->broadcastPacket($P->level->players,$pk);
	 							$Vec = new Vector3($V->x,$V->y,$V->z);
								foreach($P->level->players as $Pl){
									$Pe = $Pl->entity;
									$Plvec = new Vector3($Pe->x,$Pe->y,$Pe->z);
									if($Vec->distance($Plvec) <= 7){
										$Pe->harm(10,"explosion");
									}
								}
							}
						break;
						case 21: //포세이돈
						case 24: //목둔
						case 25: //가아라
						case 27: //미나토
						case 28: //트랜스폼
						case 31: //아마테라스
						case 35: //블리츠크랭크
							$V = $this->View($P,2,40);
							if($V !== false){
								$Ve = $V->entity;
 								switch($A["Ability"]){
									case 25: //가아라
										$pk = new ExplodePacket;
										$pk->x = $Ve->x;
										$pk->y = $Ve->y;
										$pk->z = $Ve->z;
										$pk->radius = 1;
										$pk->records = array();
										$this->api->player->broadcastPacket($P->level->players,$pk);
										$Ve->harm(5,"explosion");	
									break;
									case 27: //미나토
										$P->teleport(new Vector3($Ve->x,$Ve->y,$Ve->z));
									break;
			 						case 28: //트랜스폼
			 							$e = $P->entity;
			 							$Vec = new Vector3($e->x,$e->y,$e->z);
										$P->teleport(new Vector3($Ve->x,$Ve->y,$Ve->z));
										$V->teleport($Vec);	
									break;
					 				case 35: //블리츠크랭크
										$e = $P->entity;
										$V->teleport(new Vector3($e->x,$e->y,$e->z));
									break;
	 						 		case 31: //아마테라스
										$Ve->fire = 200;
									default:
										$LB = $this->LoadBlock($P);
										foreach($LB as $D) $P->level->setBlockRaw(new Vector3($Ve->x+$D[0],$Ve->y+$D[1],$Ve->z+$D[2]),BlockAPI::get($D[3],$D[4]),false);
									break;
								}
								break;
							}elseif(in_array($A["Ability"],array(25,27,28,35))){
								$uncool = true;
 								$P->sendChat($o);
 								break;
							}
						case 17: //아폴론
						case 22: //아카이누
						case 32: //아오키지
						case 63: //거미
 							$V = $this->View($P,1,40);
							if($V == false){
 								$uncool = true;
 								$P->sendChat($o);
 							}else{
 								$LB = $this->LoadBlock($P);
								foreach($LB as $D){
									$Vec = new Vector3($V->x+$D[0],$V->y+$D[1],$V->z+$D[2]);
									if($A["Ability"] == 32 and $P->level->getBlock($Vec)->getID() !== 0){
										$P->level->setBlockRaw($Vec,BlockAPI::get($D[3],$D[4]),false);
									}
								}
							}
						break;
				 		case 18: //에이스
				 		case 30: //패기
				 		case 36: //테마리
				 			$e = $P->entity;
	 						$Vec = new Vector3($e->x,$e->y,$e->z);
							foreach($P->level->players as $Pl){
								$Pe = $Pl->entity;
								$Plvec = new Vector3($Pe->x,$Pe->y,$Pe->z);
								if($Vec->distance($Plvec) <= 15){
									switch($A["Ability"]){
										case 18: //에이스
											$Pe->fire = 400;
										break;
				 						case 30: //패기
											$Pe->harm(3,"패기"); 	
				 						break;
				 						case 36: //테마리
											$Pl->teleport(new Vector3($Pe->x,$Pe->y+30,$Pe->z));
				 						break;
									}
								}
							}
						break;
					}
					if(!isset($uncool)) $this->Ability[$P->username]["Cool"] = time(true) + $A["Cooltime"];
				}
			}
		}
	}

	public function Interact_Handler($data){
		if($this->Set["Start"] > 1){
	 		$Te = $data["targetentity"];	
	 		$Pe = $data["entity"];
	 		$T = $Te->player;	
	 		$P = $Pe->player;
	 		$A = $this->Ability[$P->username];
	 		if($A["Life"] == 0){
	 		 return false;
			}elseif($I == $A["Item"] or $A["Item"][0] == -1){
	 			$CT = time(true) - $A["Cool"]; 						
	 			$Av = time(true) - $A["Attack"]; 						
	 			$LT = time(true) - $A["Lock"]; 						
	 			if($LT < 0){
	 				$P->sendChat("  [A] 능력봉인 : ".$CT*-1 ."초");
 					return false;
	 			}elseif($CT < 0){
	 				$P->sendChat("  [A] 쿨타임 : ".$CT*-1 ."초");
 					return false;
				}elseif($Av < 0){
					return false;
 				}else{
 					switch($A["Ability"]){
 						case 8: //메딕
							if($Te->getHealth() >= 20){
								$uncool = true;
								$P->sendChat("  [A] 체력이 최대입니다.");
							}else{
								$Te->heal(10);
							}
 						break;
	 				}
					$this->Ability[$P->username]["Attack"] = time(true) + 1;
					$this->Ability[$P->username]["Cool"] = time(true) + $A["Cooltime"];
				}
			}
		}
	}

	public function Death_Handler($data){
		if($this->Set["Start"] > 1){
			$P = $data["player"];
			$this->Ability[$P->username]["Life"] -= 1;
			$count = 0; 			
			foreach($this->api->player->getAll() as $Pl){
				if($Pl !== $P and $this->Ability[$P->username]["Life"] !== 0){
 					$count += 1;
 					$Pn = $Pl->username;
 				}
 			}
			if($count == 1){
			 $this->GameStop(1,$Pn);
 			}
		}
	}


	public function Health_Handler($data){
		if($this->Set["Start"] < 2){
			return true;
		}elseif($this->Set["Start"] == 2){
			return false;
		}elseif($this->Set["Start"] == 3){
			$e = $data["entity"];
			if($e->player instanceof Player){
				$A = $this->Ability[$e->name];
				$C = $data["cause"];
				$IT = time(true) - $this->Ability[$e->name]["Inv"]; 						
				if($A["Life"] == 0 or $IT < 0 or in_array($C,$A["Immune"])){
					if(in_array($C,array("fire","burning"))) $e->fire = 0;
					return false;
				}elseif($data["health"] >= 1){
					switch($A["Ability"]){
						case 1: //깃털
							if($C == "mirroring" and rand(1,3) == 1) return false;
						break;
						case 2: //블레이즈
							if($C == "explosion" and rand(1,3) == 1) return false;
						break;
					}
				}else{
					$TA = $this->Ability[$Te->name];
					switch($A["Ability"]){
						case 11: //불사조
						case 55: //불사조의깃털
							if($A["Life"] !== 1){
								if($A["Ability"] == 59) $this->setAbility($e->player,-1);
			 					$this->Broadcast("  [A] ".$A["Name"]."가 죽었습니다. ".$A["Name"]."는 부활이 가능합니다.");
								$this->Ability[$e->name]["Use"] = new Vector3($e->x,$e->y+1,$e->z);
			 					$pk = new SetHealthPacket;
								$pk->health = 0;
								$e->player->dataPacket($pk);
								$pk = new MoveEntityPacket_PosRot;
								$pk->eid = $e->eid;
								$pk->x = -256;
								$pk->y = 128;
								$pk->z = -256;
								$pk->yaw = 0;
								$pk->pitch = 0;
								$this->api->player->broadcastPacket($e->level->players, $pk);
								$e->air = 300;
								$e->fire = 0;
								$e->crouched = false;
								$e->fallY = false;
								$e->fallStart = false;
								$e->updateMetadata();
								$e->dead = true;
								$this->api->dhandle("player.death", array("player" => $e->player, "cause" => "Ability"));
								return false;
							}
						break;
						default:
							switch($A["Ability"]){
								case 23: //익스플로젼
	 								$Vec = new Vector3($e->x,$e->y,$e->z);
									foreach($e->level->players as $Pl){
										$Pe = $Pl->entity;
										$Plvec = new Vector3($Pe->x,$Pe->y,$Pe->z);
										if($Vec->distance($Plvec) <= 15){
											$Pe->harm(999,"Ability"); 	
										}
									}
								break;
								case 46: //조커
			 						$this->Broadcast("  [A] ".$A["Name"]."가 죽엇습니다. 모든플레이어의 능력이 바뀝니다.(버프,저주도 해제)");
									$this->Set["List"] = range(1,44);
									foreach($this->api->player->getAll() as $P){
										if($this->Ability[$P->username]["Life"] >= 1) $this->setAbility($P,-1);
									}
								break;
							}
							$this->Broadcast("  [A] [".$A["Name"]."] ".$e->name." 님이 사망하셨습니다.");
			 				$pk = new SetHealthPacket;
							$pk->health = 0;
							$e->player->dataPacket($pk);
							$pk = new MoveEntityPacket_PosRot;
							$pk->eid = $e->eid;
							$pk->x = -256;
							$pk->y = 128;
							$pk->z = -256;
							$pk->yaw = 0;
							$pk->pitch = 0;
							$this->api->player->broadcastPacket($e->level->players, $pk);
							$e->spawnDrops();
							$e->air = 300;
							$e->fire = 0;
							$e->crouched = false;
							$e->fallY = false;
							$e->fallStart = false;
							$e->updateMetadata();
							$e->dead = true;
							$this->api->dhandle("player.death", array("player" => $e->player, "cause" => "Ability"));
							return false;
						break;
	 			 }
				}
			}
		}
	}

	public function Move_Handler($data){
		if($this->Set["Start"] > 1){
			if($data->class == ENTITY_PLAYER){
				$P = $data->player;
	 			$MT = time(true) - $this->Ability[$P->username]["Move"]["Time"]; 						
	 			$VT = time(true) - $this->Ability[$P->username]["View"]["Time"];
	 			if($MT < 0){
	 				$MV = $this->Ability[$P->username]["Move"]["Vec"];
					if(floor($P->x) !== floor($MV["X"]) or floor($P->y) !== floor($MV["Y"]) or floor($P->z) !== floor($MV["Z"])){
	 					$P->sendChat("  [A] 속박 : ".$MT*-1 ."초");
 						return false;
 					}
				}if($VT < 0){
					$e = $P->entity;
					$X = round($e->x - 0.5);
					$Y = round($e->y);
					$Z = round($e->z - 0.5);
					$L = $e->level;
					$MT = $A[1];
					$pk = new UpdateBlockPacket;
					$pk->x = $X;
					$pk->y = $Y;
					$pk->z = $Z;
					$pk->block = 35;
					$pk->meta = $MT;
					$this->Setting["Tagger"]->dataPacket($pk);
					$this->api->schedule(5,array($this,"Un_Hint"),array($P,$this->Hide[$P->eid]));
				}
			}
		}
	}

	public function Un_Hint($P){
		$V = $this->Ability[$P->username]["View"]["Vec"];
		$X = $V["X"];
		$Y = $V["Y"];
		$Z = $V["Z"];
		$L = $P->level;
		$pk = new UpdateBlockPacket;
		$pk->x = $X;
		$pk->y = $Y+1;
		$pk->z = $Z;
		$B = $L->getBlock(new Vector3($X, $Y+1, $Z));
		$pk->block = $B->getID();
		$pk->meta = $B->getMetadata();
		$this->Setting["Tagger"]->dataPacket($pk);
	}


	public function Wait(){
		$this->Set["Start"] = 1;
		$M = array(
			" ",
			"°°°°°°°°°°°°°°°°",
			"  [A] 능력자   제작 :: 데베 (huu6677@naver.com)",
			"  [A] 능력확인은 /A입니다.",
			"°°°°°°°°°°°°°°°°",
 			" ",
			"  [A] 잠시후 게임이 시작됩니다.",
			" ",
		);
		$T = 0;
		foreach($M as $m){
			if($this->Set["Start"] == 1){
	 			$T += 15;
				$this->api->schedule($T,array($this,"Broadcast"),$m);
			}
		}
		$M = array(
			"  [A] 시작 5초전",
			"  [A] 시작 4초전",
			"  [A] 시작 3초전",
			"  [A] 시작 2초전",
			"  [A] 시작 1초전",
		);
		$T = 200;
		foreach($M as $m){
			if($this->Set["Start"] == 1){
				$this->api->schedule($T,array($this,"Broadcast"),$m);
				$T += 20;
			}
		}
		if($this->Set["Start"] == 1) $this->api->schedule(310,array($this,"GameStart")); 	
	}

	public function GameStart(){
		if($this->Set["Start"] == 1){
			$this->Broadcast("°°°°°°°°°°°°°°");
			$this->Broadcast("  [A] 게임이 시작되었습니다!");
			$this->Broadcast("  [A] 초반무적이 실행됩니다.");
			$this->Broadcast("  [A] OP가 /AA inv 명령어로 On/Off해주세요.");
 			$this->Broadcast("°°°°°°°°°°°°°°");
 			$this->Broadcast(" ");
 			$R = rand(1,99) * rand(1,99) * rand(1,99) * rand(1,99);
 			$this->ScheduleID = $R;
  		foreach($this->api->player->getAll() as $P){
 				$P->entity->setHealth(20);
 				$this->setAbility($P,-1);
			}
			$this->Set["Start"] = 2;
 		}
	}

	public function GameStop($Why,$Pn = false){
		switch($Why){
			case "0":
			 	$this->Broadcast("  [A] 관리자가 게임을 종료하였습니다.");
			break;
			case 1:
			case 2:
				if($Why == 1){
	 				$m = "  [A] 다른플레이어들이 종료하였습니다.";
				}elseif($Why == 2){
	 				$m = "  [A] 다른플레이어들이 사망하였습니다.";
	 			}
	 			$M = array(
					$m,
					" ",
					"°°°°°°°°°°°°°°°°",
					"  [A] 최후의 승자가 결정되었습니다!",
					"  [A] 최후의 승자는 ".$Pn."님입니다!",
					"°°°°°°°°°°°°°°°°",
					" "
				);
				foreach($M as $m){
					$this->Broadcast($m);
				}
 			break;
			default:
				$this->Broadcast("  [A] 오류발생 Error-".$Why);
			break;
		}
		$this->Broadcast("  [A] 게임이 종료되었습니다.");
		$this->Ability = array();
		$this->Set = array("Start" => 0, "List" => range(1,44));
	}

	public function Broadcast($m){
	 if($this->Set["Start"] !== 0) $this->api->chat->broadcast($m);
	}

	public function setAbility($P,$R = -1){
		if($R == -1){
			if(count($this->Set["List"]) < 2) $this->Set["List"] = range(1,44);
			shuffle($this->Set["List"]);
			$A = array_shift($this->Set["List"]);
		}else{
			if(in_array($R,$this->Set["List"])){
 			 foreach($this->Set["List"] as $key => $v){
   			if($R == $v) unset($this->Set["List"][$key]);
   		} 
    }
			$A = $R;
		}
		$Pn = $P->username;
 		$this->Ability[$Pn] = array("Ability" => $A, "Name" => 0, "AP" => 0, "Rank" => 0, "Info" => 0, "Immune" => array(), "Cooltime" => 0, "Item" => array(-2), "Life" => 1, "Cool" => 0, "Attack" => 0, "Move" => array("Time" => 0,"Vec" => array()), "View" => array("Time" => 0,"Vec" => array()), "Lock" => 0, "Inv" => 0, "Use" => 0);
		$Av = "Active";
 		$Pv = "Passive";
		switch($A){
			case 0:
				$a1 = "무능력";
				$a2 = $Pv;
				$a3 = "F";
				$a4 = "능력이없다.";
			break;
			case 1:
				$a1 = "깃털";
				$a2 = $Pv;
				$a3 = "C";
				$a4 = "낙하,익사 데미지를 받지않으며, 일정확률로 미러링을 무시한다.";
				$a5 = array("fall","water");
			break;
			case 2:
				$a1 = "블레이즈";
				$a2 = $Pv;
				$a3 = "C";
				$a4 = "용암,불 데미지를 받지않으며, 일정확률로 폭팔데미지도 무시한다.";
				$a5 = array("lava","fire","burning");
			break;
			case 3:
				$a1 = "미러링";
				$a2 = $Pv;
				$a3 = "C";
				$a4 = "자신을 죽인사람을 죽인다. 단,본인이 부활하는것은 아니다.";
			break;
 			case 4:
				$a1 = "반연금술";
				$a2 = $Av;
				$a3 = "C";
				$a4 = "금괴로 땅터치하면 철괴와 1:1으로,블럭을 부수면 다이아와 1:3으로,허공을 꾹누르면 체력과 1:10으로 변환한다.";
				$a6 = 3;
				$a7 = array(266);
			break;
			case 5:
				$a1 = "블라인드";
				$a2 = $Av;
				$a3 = "C";
				$a4 = "철괴로 상대를 때리면 3초간 시야를 가린다. 중첩은 되지 않는다.";
				$a7 = array(265);
			break;
			case 6:
				$a1 = "거식증";
				$a2 = $Pv;
				$a3 = "B";
				$a4 = "음식으로 인한 체력회복량이 3배가된다.";
				$a7 = array(260,282,297,319,320,360,363,364,365,366,391,392,393,400,459);
			break;
			case 7:
				$a1 = "점퍼";
				$a2 = $Av;
				$a3 = "B";
				$a4 = "철괴를 휘두르면 바라보는곳으로 순간이동한다.단,낙하와 끼임데미지는 받는다.";
				$a6 = 20;
				$a7 = array(265);
			break;
			case 8:
				$a1 = "메딕";
				$a2 = $Av;
				$a3 = "B";
				$a4 = "철괴를 휘두르면 자신이,혹은 철괴로 때린사람의 체력이 회복된다.";
				$a6 = 5;
				$a7 = array(265);
			break;
			case 9:
				$a1 = "봉인";
				$a2 = $Av;
				$a3 = "B";
				$a4 = "철괴로 타격당한 상대의 능력이 일정시간 봉인된다.";
				$a6 = 80;
				$a7 = array(265);
			break;
			case 10:
				$a1 = "타임";
				$a2 = $Av;
				$a3 = "B";
				$a4 = "철괴를 휘두르면 모든플레이어에게 일정시간동안 속박이 걸린다.";
				$a6 = 85;
				$a7 = array(265);
			break;
			case 11:
				$a1 = "불사조";
				$a2 = $Pv;
				$a3 = "B";
				$a4 = "사망할경우 2번의 부활한다.단,능력이 공개된다.";
				$this->Ability[$Pn]["Life"] = 3;
			break;
			case 12:
				$a1 = "카운터";
				$a2 = $Av;
				$a3 = "B";
				$a4 = "철괴로 타격당한 상대에게 0.5초의 속박을 걸며, 10%로 데미지추가or회복을해준다.";
				$a7 = array(265);
			break;
			case 13:
				$a1 = "광전사";
				$a2 = $Pv;
				$a3 = "A";
				$a4 = "체력이 70%이하면 1.5배,40%이하면 2배로 데미지가 증가한다.";
				$a7 = array(0);
			break;
			case 14:
				$a1 = "이지스";
				$a2 = $Av;
				$a3 = "A";
				$a4 = "철괴를 휘두르면 일정시간동안 무적이됩니다.";
				$a6 = 28;
			break;
			case 15:
				$a1 = "기관총";
				$a2 = $Av;
 				$a3 = "A";
				$a4 = "철괴를 휘두르면 탄창이 채워지며,금괴로 총을 발사합니다. 단,총알은 보이지않습니다.";
				$a7 = array(265,266);
				$this->Ability[$Pn]["Use"] = 0;
			break;
 			case 16:
				$a1 = "쇼크웨이브";
				$a2 = $Av;
				$a3 = "A";
				$a4 = "철괴를 휘두르면 바라보는곳에 폭발데미지를줍니다.";
				$a5 = array("explosion");
				$a6 = 40;
				$a7 = array(265);
			break;
			case 17:
				$a1 = "아폴론";
				$a2 = $Av;
				$a3 = "SS";
				$a4 = "철괴를 휘두르면 바라보는곳에 불구덩이가 생깁니다.";
				$a5 = array("fire","burning");
				$a6 = 40;
				$a7 = array(265);
			break;
			case 18:
				$a1 = "에이스";
				$a2 = $Av;
				$a3 = "SS";
				$a4 = "철괴를 휘두르면 주변의 플레이어에게 20초간 불이 붙습니다.";
				$a5 = array("fire","burning","lava");
				$a6 = 80;
				$a7 = array(265);
			break;
			case 19:
				$a1 = "흡혈초";
				$a2 = $Av;
				$a3 = "SS";
				$a4 = "철괴로 타격당한 상대의 체력을 흡수합니다.";
				$a6 = 5;
				$a7 = array(265);
			break;
			case 20:
				$a1 = "CP9";
				$a2 = $Av;
				$a3 = "S";
				$a4 = "철괴를 휘두르면 폭발소리와 함께 바라보는쪽으로 이동하며,철괴로 타격시 매우 강력합니다.";
				$a5 = array("fall");
				$a6 = 15;
				$a7 = array(265);
			break;
			case 21:
				$a1 = "포세이돈";
				$a2 = $Av;
				$a3 = "SS";
				$a4 = "철괴를 휘두르면 바라보는곳에 어항이 생깁니다.";
				$a5 = array("water");
				$a6 = 60;
				$a7 = array(265);
			break;
			case 22:
				$a1 = "아카이누";
				$a2 = $Av;
				$a3 = "SS";
				$a4 = "철괴를 휘두르면 바라보는곳에 용암구덩이가 생깁니다.";
				$a5 = array("fire","burning");
				$a6 = 40;
				$a7 = array(265);
			break;
			case 23:
				$a1 = "익스플로젼";
				$a2 = $Pv;
				$a3 = "B";
				$a4 = "죽으면 매우 강력한 폭발데미지로 주변의 플레이어를 죽입니다.";
			break;
			case 24:
				$a1 = "목둔";
				$a2 = $Av;
				$a3 = "A";
				$a4 = "철괴를 휘두르면 바라보는곳에의 플레이어를 나무벽으로 가둡니다.";
				$a6 = 30;
				$a7 = array(265);
			break;
			case 25:
				$a1 = "가아라";
				$a2 = $Av;
				$a3 = "S";
				$a4 = "철괴를 휘두르면 바라보는곳에의 플레이어를 모래로 감싼뒤 폭발데미지를줍니다.";
				$a6 = 30;
				$a7 = array(265);
			break;
			case 26:
				$a1 = "록리";
				$a2 = $Av;
				$a3 = "S";
				$a4 = "철괴로 타격당한 상대가 매우높이 띄워집니다.";
				$a6 = 20;
				$a7 = array(265);
			break;
			case 27:
				$a1 = "미나토";
				$a2 = $Av;
				$a3 = "S";
				$a4 = "철괴를 휘두르면 바라보는곳에의 플레이어에게 워프합니다.";
				$a7 = array(265);
			break;
			case 28:
				$a1 = "트랜스폼";
				$a2 = $Av;
				$a3 = "SS";
				$a4 = "철괴를 휘두르면 바라보는곳에의 플레이어와 자리를 바꿉니다.";
				$a7 = array(265);
			break;
			case 29:
				$a1 = "키미마로";
				$a2 = $Av;
				$a3 = "SS";
				$a4 = "주먹을 휘두르면 뼈다귀가 생기며,뼈다귀로 타격시 매우 강력합니다.";
				$a7 = array(0,352);
			break;
			case 30:
				$a1 = "패기";
				$a2 = $Av;
				$a3 = "SS";
				$a4 = "철괴를 휘두르면 주변의 플레이어에게 약한 데미지를 줍니다.";
				$a6 = 1;
				$a7 = array(265);
			break;
			case 31:
				$a1 = "아마테라스";
				$a2 = $Av;
				$a3 = "S";
				$a4 = "철괴를 휘두르면 체력을 소모하여 바라보는곳의 사물을 불태웁니다.";
				$a5 = array("fire","burning");
				$a7 = array(265);
			break;
			case 32:
				$a1 = "아오키지";
				$a2 = $Av;
				$a3 = "S";
				$a4 = "철괴를 휘두르면 바라보는곳을 얼립니다.";
				$a7 = array(265);
			break;
			case 33:
				$a1 = "조로";
				$a2 = $Av;
				$a3 = "S";
				$a4 = "철괴를 휘두르면 칼의 데미지가 고정됩니다.";
				$a6 = 45;
				$a7 = array(265,267,268,272,276,283);
			break;
			case 34:
				$a1 = "카이지";
				$a2 = $Av;
				$a3 = "S";
				$a4 = "다이아몬드로 타격시 30%로 상대가,70%로 본인이 즉사합니다.";
				$a7 = array(264);
			break;
			case 35:
				$a1 = "블리츠크랭크";
				$a2 = $Av;
				$a3 = "SS";
				$a6 = 1;
				$a4 = "철괴를 휘두르면 바라보는곳에의 플레이어를 끌고옵니다.";
				$a7 = array(265);
			break;
			case 36:
				$a1 = "테마리";
				$a2 = $Av;
				$a3 = "S";
				$a4 = "철괴를 휘두르면 주변의 플레이어가 공중에 떠오릅니다.";
				$a6 = 60;
				$a7 = array(265);
			break;
			case 37:
				$a1 = "폭주";
				$a2 = $Pv;
				$a3 = "SS";
				$a4 = "주먹을 휘두르면 깃털이 생기며,1킬을 할때마다 깃털의 데미지가 2씩 증가합니다.(기본 5)";
				$a7 = array(288);
				$this->Ability[$Pn]["Use"] = 5;
			break;
			case 38:
				$a1 = "디그";
				$a2 = $Av;
				$a3 = "C";
				$a4 = "철괴로 블럭을 터치하면 블럭파괴후 아이템을 습득합니다.";
				$a7 = array(265);
			break;
			case 39:
				$a1 = "돌상인";
				$a2 = $Av;
				$a3 = "B";
				$a4 = "철괴로 블럭을 터치하면 돌30개를 광물로 변환합니다.";
				$a7 = array(265);
			break;
			case 40:
				$a1 = "혈세";
				$a2 = $Av;
				$a3 = "B";
				$a4 = "철괴로 블럭을 터치하면 체력을 광물로 변환합니다.";
				$a7 = array(265);
			break;
	 		case 41:
				$a1 = "인어";
				$a2 = $Pv;
				$a3 = "A";
				$a4 = "물속에서 타격시 데미지가 4배이다.";
			break;
			case 42:
				$a1 = "C4";
				$a2 = $Av;
				$a3 = "S";
				$a4 = "철괴로 블럭터치시 폭탄을 설치하며, 금괴로 터치시 폭발데미지를 준다.단,둘은 쿨타임을 공유한다.";
				$a6 = 20;
				$a7 = array(265,266);
			break;
	 		case 43:
				$a1 = "포탈건";
				$a2 = $Av;
				$a3 = "S";
				$a4 = "철괴로 블럭터치시 포탈입구를 설치하며, 금괴로 터치시 포탈출구를 설치한다.단,둘은 쿨타임을 공유한다.";
				$a6 = 20;
				$a7 = array(265,266);
			break;
			case 44:
				$a1 = "흡수";
				$a2 = $Pv;
				$a3 = "??";
				$a4 = "플레이어를 죽이면 대상의 능력으로 능력이 바뀐다.";
			break;
 			case 45:
				$a1 = "인벤토리";
				$a2 = $Av;
				$a3 = "S";
				$a4 = "철괴로 타격한 상대의 인텐토리를 초기화시키거나 자신의 인벤토리와 바꾼다.";
				$a6 = 60;
				$a7 = array(265);
			break;
 			case 46:
				$a1 = "조커";
				$a2 = $Fv;
				$a3 = "B";
				$a4 = "죽으면 모든플레이어의 능력이 새로지급된다.";
			break;
 			case 47:
				$a1 = "스파이더맨";
				$a2 = $Av;
				$a3 = "S";
				$a4 = "철괴로 타격한 상대에게 속박을 걸며, 자신을 타격한 상대를 확률적으로 속박한다.";
				$a6 = 20;
				$a7 = array();
			break;
 			case 48:
				$a1 = "사제";
				$a2 = $Av;
				$a3 = "SS";
				$a4 = "철괴를 휘두르면 자신의 체력을 회복하며, /사제 명령어로 랜덤으로 한멸을 부활시킬수있다.";
				$a6 = 10;
				$a7 = array(265);
				$this->Ability[$Pn]["Use"] = 1;
			break;
			case 49:
				$a1 = "복서";
				$a2 = $Fv;
				$a3 = "B";
				$a4 = "주먹(혹은 데미지가없는아이템)으로 공격할경우 공격력이 2배가 된다.";
				$a7 = array(-1);
			break;
 			case 50:
				$a1 = "농사꾼";
				$a2 = $Fv;
				$a3 = "B";
				$a4 = "농사물로 공격할경우 데미지가 4배가 된다.";
				$a7 = array(260,282,297,319,320,360,363,364,365,366,391,392,393,400,459);
			break;
 			case 51:
				$a1 = "어둠의신";
				$a2 = $Av;
				$a3 = "SS";
				$a4 = "철괴를 휘두르면 모든 플레이어의 시야를 가린다.";
				$a6 = 180;
				$a7 = array(265);
			break;
 			case 52:
				$a1 = "사춘기";
				$a2 = $Fv;
				$a3 = "A";
				$a4 = "자신을 타격한 상대를 확률적으로 강하게 공격한다.";
			break;
			case 53:
				$a1 = "능력탐지기";
				$a2 = $Av;
				$a3 = "S";
				$a4 = "/능력탐지기 명령어로 모든 플레이어의 능력을 알수있고, 무능력자가 된다.";
				$this->Ability[$Pn]["Use"] = 1;
 			break;
 			case 54:
				$a1 = "데스노트";
				$a2 = $Av;
				$a3 = "SS";
				$a4 = "/데스노트 명령어로 1명을 지정해 30초후에 죽게할수있으며, 자신의 좌표가 계속 공개된다.";
				$this->Ability[$Pn]["Use"] = 1;
 			break;
 			case 55:
				$a1 = "불사조의깃털";
				$a2 = $Fv;
				$a3 = "S";
				$a4 = "죽으면 단한번 부활할수잇고, 능력이 다시 설정된다.";
				$this->Ability[$Pn]["Life"] = 2;
			break;
 			case 56:
				$a1 = "헐크";
				$a2 = $Av;
				$a3 = "SS";
				$a4 = "철괴를 휘두르면 일정시간동안 공격력이 매우 강력해진다.";
			break;
			case 57:
				$a1 = "크리퍼";
				$a2 = $Av;
				$a3 = "S";
				$a4 = "철괴를 휘두르면 매우크게 자폭한다.";
			break;
 			case 58:
				$a1 = "럭키";
				$a2 = $Av;
				$a3 = "SS";
				$a4 = "모든 데미지를 일정확률로 무시한다.";
			break;
 			case 59:
				$a1 = "텔레포터";
				$a2 = $Av;
				$a3 = "S";
				$a4 = "/텔레포터 명령어로 플레이어간의 위치를 바꾼다.";
			break;
 			case 60:
				$a1 = "관리자";
				$a2 = $Av;
				$a3 = "SS";
				$a4 = "철괴를 휘두르면 모든플레이어의 능력을 잠시 봉인한다.";
				$a6 = 120;
				$a7 = array(265);
			break;
 			default:
				$a1 = "오류";
				$a2 = "오류";
				$a3 = "오류";
				$a4 = "오류";
			break;
		}
		if(isset($a1)) $this->Ability[$Pn]["Name"] = $a1;
		if(isset($a2)) $this->Ability[$Pn]["AP"] = $a2;
		if(isset($a3)) $this->Ability[$Pn]["Rank"] = $a3;
		if(isset($a4)) $this->Ability[$Pn]["Info"] = $a4;
		if(isset($a5)) $this->Ability[$Pn]["Immune"] = $a5;
		if(isset($a6)) $this->Ability[$Pn]["Cooltime"] = $a6;
		if(isset($a7)) $this->Ability[$Pn]["Item"] = $a7;
 	}

	public function View($P,$Type,$Limit){
		$e = $P->entity;
		$eY = $e->yaw;
		$eP = $e->pitch;
		$Vs = -sin($eY/180 *M_PI);
		$Vc = cos($eY/180*M_PI);
		$Vt = -sin($eP/180*M_PI);
		$Vp = cos($eP/180*M_PI);
		$X = round($e->x); 
		$Y = round($e->y)+1; 
		$Z = round($e->z);
		$L = $e->level;
		for($f=0; $f < 999; ++$f){
			$X += $Vs * $Vp;
			$Y += $Vt;
			$Z += $Vc * $Vp;
			console("$X : $Y : $Z");
			$Vec = new Vector3(round($X),round($Y),round($Z));
			$B = $L->getBlock($Vec);
			if($f > $Limit or $X < 0 or $X > 256 or $Y < 0 or $X > 128 or $Z < 0 or $X > 256){
				return false;
			}else{
				switch($Type){
					case 1:
						if($B->isSolid !== false){
	 						return $B;
						}
					break;
					case 2:
						foreach($L->players as $Pl){
							if(/*$P !== $Pl and */$this->Ability[$Pl->username]["Life"] !== 0){
								$Pe = $Pl->entity;
								$Plvec = new Vector3($Pe->x, $Pe->y, $Pe->z);
								if($Vec->distance($Plvec) <= 2){
									return $Pl;
								}
							}
						}
					break;
				}
			}
		}
	}

	public function LoadBlock($P){
		return array(array(0,0,0,89,0));
	}

	public function __destruct(){
	}
}