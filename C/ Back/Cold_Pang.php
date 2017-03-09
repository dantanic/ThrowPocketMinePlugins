<?php

/*
__Freeze Tag__
name=Cold Pang  
version=0.1.0
author=DeBe
class=Freeze_Tag
apiversion=12
*/

class Freeze_Tag implements Plugin{
	private $api;
	
	public function __construct(ServerAPI $api, $server = false){
		$this->api = $api;
		$this->Cold = array();
		$this->Death = array();
		$this->Set = array();
		$this->Start = 0;
		$this->Schedulusername = 0;
	}

	public function init(){
		$this->api->console->register("bh", "BlockHideTag",array($this,"Commander"));
	 		$AddHandler = array(
			array("player.join","Join"),
			array("player.spawn","Join"),
			array("player.respawn","Join"),
 			array("player.quit","Join"),
			array("player.block.touch","Touch"),
			array("player.block.break","Touch"),
 			array("player.interact","Interact"),
 			array("player.death","Death"),
			array("entity.move","Move")
		);
		foreach($AddHandler as $ah) $this->api->addHandler($ah[0], array($this,$ah[1]."_Handler"));
		DataPacketSendEvent::register(array($this, "PacketHandler"), EventPriority::HIGHEST);
	}

	public function Commander($cmd,$params,$issuer){
		switch(strtolower($params[0])){
			case "start":
				if($this->Start == 0){
					if(count($this->api->player->getAll()) > 1){
						$T = 5;
						if(isset($params[1])) $T = (int) $params[1];
						$Tagger = $this->api->player->get($params[2]);
	 					$Ps = $this->api->player->getAll();
	 					if($Tagger == false) $Tagger = $Ps[array_rand($Ps)];
						$this->Set = array($Tagger->username,$Tagger->username,$T);
						$this->Wait();
						return "  [블럭숨바꼭질] 게임을 시작했습니다.";
					}else{
						return "  [블럭숨바꼭질] 플레이어가 너무 적습니다.";
					}
				}else{
					return "  [블럭숨바꼭질] 이미 게임중입니다.";
				}
			break;	
			
			case "stop":
				if($this->Start !== 0){
					$this->GameStop($this->Schedulusername,0);
				}else{
					return "  [블럭숨바꼭질] 아직 게임중이 아닙니다.";
				}
			break;
			default:
				return "/ICE Start <Time> <Player> or /ICE Stop";
	 		break;
		}
	}
	
	public function Join_Handler($data,$event){
		if($this->Start !== 0){
			switch($event){
				case "player.join":
				case "player.spawn":				
				case "player.respawn":				
				 	$data->removeItem(280,0,999);
			 		$data->addItem(280,0,1);
				break;
				case "player.quit":
					if($data->username == $this->Set[0]) $this->GameStop($this->Schedulusername,4);
				 	$data->removeItem(280,0,999);
				 	foreach($this->api->player->getAll() as $P){
				 		$C = 0;
						if($data->username !== $this->Set[0] and !isset($this->Death[$data->username])){
 					 		$C += 1;
 						}
 					}if($C == 0) $this->GameStop($this->Schedulusername,5);
				break;
			}
		}
	}

	public function Touch_Handler($data){
	 if($this->Start !== 0){
			$T = $data["target"];
			$P = $data["player"];
			$I = $data["item"];
			$X = $T->x;
			$Y = $T->y;
			$Z = $T->z;
			if(isset($this->Death[$P->username])){
				return false;
			}elseif($P->username == $this->Set[0]){
				foreach($this->api->player->getAll() as $Pl){
					if(isset($this->Cold[$Pl->username])){ 
						$H = $this->Cold[$Pl->username];
						if($X == $H["X"] and $Y == $H["Y"] and $Z == $H["Z"]	or $X == $H["X"] and $Y-1 == $H["Y"] and $Z == $H["Z"]){
							$P->sendChat("  [블럭숨바꼭질] 대상이 얼음상태입니다.");
							return false;
						}elseif($I->getID() == 280){
							$CT = time(true) - $this->Cool[$P->username];
							if($CT >= 0){
								$P->teleport(new Vector3($X,($Y+1),$Z));
								$this->Cool[$P->username] = time(true) + 1;
							}
						}
					}
				}
			}else{
				if($I->getID() == 280){
					$CT = time(true) - $this->Cool[$P->username]; 						
					if($CT >= 0){
						$this->ICE($P);
						$this->Cool[$P->username] = time(true) + 3;
					}else{
						$P->sendChat("  [블럭숨바꼭질] 아직 얼음할수 없습니다. 쿨타임 : ".$CT*-1 ."초");
					}
				}else{
					foreach($this->api->player->getAll() as $Pl){
						if(isset($this->Cold[$Pl->username])){ 
							$H = $this->Cold[$Pl->username];
							if($X == $H["X"] and $Y == $H["Y"] and $Z == $H["Z"] or $X == $H["X"] and $Y-1 == $H["Y"] and $Z == $H["Z"]){
								$this->ICE($H["Player"]);
								return false;
							}
						}
					}
				}
			}
		}
	}

	public function Interact_Handler($data){
		if($this->Start !== 0){
	 		$T = $data["targetentity"];	
	 		$P = $data["entity"]; 
			if($P->player->username == $this->Set[0]){
				if(isset($this->Cold[$T->player->username])){
					$P->player->sendChat("  [블럭숨바꼭질] 대상이 얼음상태입니다.");
				}else{
				 $T->harm(999,$P->eid);
				}
			}
			return false;
		}
	}

	public function Death_Handler($data){
		if($this->Start !== 0){
			$P = $data["player"];
			if($P->username == $this->Set[0]){
				$this->GameStop($this->Schedulusername,3);
			}else{
				$this->Death[$P->username] = array();
				if(isset($this->Cold[$P->username])) unset($this->Cold[$P->username]);
				$C = 0; 			
				foreach($this->api->player->getAll() as $P){
					if($P->username !== $this->Set[0] and !isset($this->Death[$P->username])){
						console("죽음 : ".$P->username);
 				 		$C += 1;
 					}
 				}
 				if($C == 0) $this->GameStop($this->Schedulusername,2);
			}
			$P->removeItem(280,0,999);
		}
	}

	public function Move_Handler($data){
		if($this->Start !== 0){
			if($data->class == ENTITY_PLAYER){
				$P = $data->player;
				$X = round($data->x - 0.5); 
				$Y = round($data->y); 
				$Z = round($data->z - 0.5); 
				$Level = $data->level;
				if(isset($this->Cold[$P->username])){
					$H = $this->Cold[$P->username];
					if($X !== $H["X"] or $Y !== $H["Y"] or $Z !== $H["Z"]) $this->ICE($P);
				}elseif(isset($this->Death[$P->username])){
					return false;
				}
			}
		}
	}

	public function ICE($P){
		$e = $P->entity;
		$X = round($e->x - 0.5); 
		$Y = round($e->y); 
		$Z = round($e->z - 0.5); 
		$L = $e->level;
		if(isset($this->Cold[$P->username])){
			$H = $this->Cold[$P->username];
			unset($this->Cold[$P->username]);
			$P->teleport(new Vector3($X+0.5, $Y, $Z+0.5));
			$L->setBlockRaw(new Vector3($H["X"],$H["Y"],$H["Z"]),BlockAPI::get(0,0),false);
			$L->setBlockRaw(new Vector3($H["X"],$H["Y"]+1,$H["Z"]),BlockAPI::get(0,0),false);
			$P->sendChat("  [블럭숨바꼭질] 땡!");
		}else{
			$L->setBlockRaw(new Vector3($X,$Y,$Z),BlockAPI::get(20,0),false);
			$L->setBlockRaw(new Vector3($X,$Y+1,$Z),BlockAPI::get(20,0),false);
			$P->teleport(new Vector3($X+0.5, $Y, $Z+0.5));
			$this->Cold[$P->username] = array("X" => $X, "Y" => $Y, "Z" => $Z, "Player" => $P);
			$P->sendChat("  [블럭숨바꼭질] 얼음!");
			$C = 0; 			
				foreach($this->api->player->getAll() as $P){
					if($P->username !== $this->Set[0] and !isset($this->Cold[$P->username]) and !isset($this->Death[$P->username])){
 				 		$C += 1;
 					}
 				}if($C == 0) $this->UnAllICEd($this->Schedulusername);
		}
	}

	public function UnAllICEd($Schedulusername){
		$this->Broadcast("  [블럭숨바꼭질] 모든 플레이어가 얼음상태입니다.");
		$this->Broadcast("  [블럭숨바꼭질] 3초후 모두 해제됩니다..");

		$M = array(
			"  [블럭숨바꼭질] 해제 3초전",
			"  [블럭숨바꼭질] 해제 2초전",
			"  [블럭숨바꼭질] 해제 1초전",
		);
		$T = 0;
		foreach ($M as $m) {
			if($this->Schedulusername == $Schedulusername){
				$T += 20;
				$this->api->schedule($T,array($this,"Broadcast"),$m);
			}
		}
	 	if($this->Schedulusername == $Schedulusername) $this->api->schedule(70,array($this,"UnAllICE"),$this->Schedulusername); 	
	}

	public function UnAllICE($Schedulusername){
		if($this->Schedulusername == $Schedulusername){
			foreach($this->api->player->getAll() as $Pl){
				if(isset($this->Cold[$Pl->username])) $this->ICE($Pl);
			}
		}
	}

	public function Wait(){
		$M = array(
			"  [블럭숨바꼭질] 얼음땡    제작 :: 데베 (huu6677@naver.com)",
			"  [블럭숨바꼭질] 아이디어 제공자 :: 하마 (9jungsh@naver.com)",
			"  [블럭숨바꼭질] 》",
			"  [블럭숨바꼭질] 막대기로 땅을 치면 사용됩니다.",
			"  [블럭숨바꼭질] 도망유저 : 얼음 (이동&도망유저가터치시 해제)",
			"  [블럭숨바꼭질] 술래유저 : 터치한곳으로 워프",
		);
		$T = 0;
		foreach ($M as $m) {
			$T += 5;
			$this->api->schedule($T,array($this,"Broadcast"),$m);
		}
		$M = array(
			"  [블럭숨바꼭질] 시작 5초전",
			"  [블럭숨바꼭질] 시작 4초전",
			"  [블럭숨바꼭질] 시작 3초전",
			"  [블럭숨바꼭질] 시작 2초전",
			"  [블럭숨바꼭질] 시작 1초전",
		);
		$T = 30;
		foreach ($M as $m) {
			$this->api->schedule($T,array($this,"Broadcast"),$m);
			$T += 20;
		}
	 	$this->api->schedule(140,array($this,"GameStart")); 	
	}

	public function Broadcast($m){
		$this->api->chat->broadcast($m);
	}

	public function GameStart(){
		$this->Broadcast(" ");
		$this->Broadcast("°°°°°°°°°°°°°°");
		$this->Broadcast("  [블럭숨바꼭질] 게임이 시작되었습니다!");
		$this->Broadcast("  [블럭숨바꼭질] 술래 : ".$this->Set[1]."님");
		$this->Broadcast("  [블럭숨바꼭질] 시간 : ".$this->Set[2]."분");
 		$this->Broadcast("°°°°°°°°°°°°°°");
		$this->Broadcast(" ");
 		$Schedulusername = mt_rand(1,999999);
 		$this->api->schedule(20*60*$this->Set[2] - 150,array($this,"StopWait"),$Schedulusername);
 		$this->Schedulusername = $Schedulusername;
 		$this->Start = 1;
 		foreach($this->api->player->getAll() as $P){
 			$this->Cool[$P->username] = time(true);
 			$TF = false;
			foreach($P->inventory as $slot => $I){
				if($I->getID() == 280){
					if($I->count > 0){
						$TF = true;
					}
				}
			}
			if($TF == false) $P->addItem(280,0,1);
		}
	}

	public function StopWait($Schedulusername){
		if($this->Schedulusername == $Schedulusername){
			$this->Broadcast("  [블럭숨바꼭질] 잠시후 게임이 종료됩니다.");
			$M = array(
			"  [블럭숨바꼭질] 종료 5초전",
			"  [블럭숨바꼭질] 종료 4초전",
			"  [블럭숨바꼭질] 종료 3초전",
			"  [블럭숨바꼭질] 종료 2초전",
			"  [블럭숨바꼭질] 종료 1초전",
			);
			$T = 50;
			foreach ($M as $m){
				if($this->Schedulusername == $Schedulusername){
					$this->api->schedule($T,array($this,"Broadcast"),$m);
					$T += 20;
				}
			}
			$this->api->schedule(150,array($this,"GameStoped"),$Schedulusername);
		}
	}
	public function GameStoped($Schedulusername){$this->GameStop($Schedulusername,1);}

	public function GameStop($Schedulusername,$Why){
		if($this->Schedulusername == $Schedulusername){
			switch($Why){
				case "0":
				 	$this->Broadcast("  [블럭숨바꼭질] 관리자가 게임을 종료하였습니다.");
				break;
				case 1:
					$this->Broadcast("  [블럭숨바꼭질] 술래가 패배하였습니다.");
				break;
				case 2:
	 				$this->Broadcast("  [블럭숨바꼭질] 술래가 승리하였습니다.");
				break;
				case 3:
					$this->Broadcast("  [블럭숨바꼭질] 술래가 사망하였습니다.");
				break;
	 			case 4:
					$this->Broadcast("  [블럭숨바꼭질] 술래가 종료하였습니다.");
				break;
	 			case 5:
					$this->Broadcast("  [블럭숨바꼭질] 모든플레이어가 종료하였습니다.");
				break;
				default:
				break;
			}
			$this->Broadcast("  [블럭숨바꼭질] 게임이 종료되었습니다.");
			$this->Cold = array();
			$this->Death = array();
			$this->Set = array();
			$this->Cool = array();
			$this->Start = 0;
			$this->Schedulusername = 0;
			foreach($this->api->player->getAll() as $P) $P->removeItem(280,0,999);
		}
	}

	public function __destruct(){
	}
}