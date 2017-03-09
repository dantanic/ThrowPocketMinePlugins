<?php

/*
__PocketMine Plugin__
name=Block Hide Tag
version=1.0.2
author=DeBe
class=Block_Hide_Tag
apiversion=12
*/

class Block_Hide_Tag implements Plugin{
	private $api;
	
	public function __construct(ServerAPI $api, $server = false){
		$this->api = $api;
		$this->Hide = array();
		$this->Setting = array();
		$this->Death = array();
		$this->Start = 0;
		$this->ScheduleID = 0;
	}

	public function init(){
		$this->api->console->register("bh", "BlockHide Command",array($this,"Commander"));
		$AddHandler = array(
			array("player.join","Join"),
			array("player.spawn","Join"),
			array("player.respawn","Join"),
 			array("player.quit","Join"),
			array("player.block.touch","Touch"),
			array("player.block.break","Touch"),
 			array("player.interact","Interact"),
 			array("player.death","Death"),
 			array("entity.health.change","Health"),
			array("entity.move","Move")
		);
		foreach($AddHandler as $ah) $this->api->addHandler($ah[0], array($this,$ah[1]."_Handler"));
		DataPacketSendEvent::register(array($this, "PacketHandler"), EventPriority::HIGHEST);
	 	$this->api->schedule(20*60,array($this,"SetTime"),true,"server.schedule");
	}

	public function Commander($cmd,$params,$issuer){
		if(strtolower($params[0]) == " " or $params[0] == null){
			if($this->Start == 0){
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
				if($this->Start == 0){
					if(count($this->api->player->getAll()) >= 2){
						$time = 5;
						if(is_numeric($time) !== false and isset($params[1])) $time = (int) $params[1];
						$Tagger = $this->api->player->get($params[2]);
	 					if($Tagger == false){
	 						$Ps = $this->api->player->getAll();
	 						$Tagger = $Ps[array_rand($Ps)];
	 					}
						$this->Setting = array("Tagger" => $Tagger, "TaggerName" => $Tagger->username,"Time" => $time,"Hint" => 5,"HintCool" => 0);
						switch(strtolower($params[0])){
							case "faststart":
							case "fstart":
							case "f":
							 	$this->Start = 1;
								$this->GameStart();
							break;
							case "start":
							case "s":
							 $this->Wait();
							break;
						}
					}else{
						return "  [BH] 플레이어가 너무 적습니다.";
					}
				}else{
					return "  [BH] 이미 게임중입니다.";
				}
			break;	
			
			case "stop":
			case "st":
				if($this->Start !== 0){
					$this->GameStop($this->ScheduleID,0);
				}else{
					return "  [BH] 아직 게임중이 아닙니다.";
				}
			break;
			default:
				return "/BH (Fast)Start <Time> <Player> or /BH Stop";
	 		break;
		}
	}
	
	public function Join_Handler($data,$event){
		if($this->Start !== 0){
			switch($event){
				case "player.join":
				case "player.spawn":				
				case "player.respawn":				
				 	$data->removeItem(341,0,999);
			 		$data->addItem(341,0,1);
				break;
				case "player.quit":
					if($data == $this->Setting["Tagger"]){
						$this->GameStop($this->ScheduleID,4);
					}else{
						if(isset($this->Hide[$data->eid])) $this->Hide($data);
				 		$data->removeItem(341,0,999);
	 					$count = 0;
			 			foreach($this->Setting["Tagger"]->level->players as $P){
							if($P !== $data and $P !== $this->Setting["Tagger"] and !isset($this->Death[$P->username])) $count += 1;
						}
						if($count = 0){
							$this->GameStop($this->ScheduleID,5);
 						}
 					}
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
			if($I->getID() == 341){
				if($P == $this->Setting["Tagger"]){
					$CT = time(true) - $this->Setting["HintCool"]; 						
					if($this->Setting["Hint"] == 0){
						$P->sendChat("  [BH] 힌트 5번을 모두 사용하셨습니다.");
					}elseif($CT < 0){
						$P->sendChat("  [BH] 쿨타임 : ".$CT*-1 ."초");
	 				}else{
	 					$this->Hint();
						$this->Setting["HintCool"] = time(true) + 10;
						$this->Setting["Hint"] -= 1;
						$P->sendChat("  [BH] 힌트를 사용했습니다.");
						$P->sendChat("  [BH] 남은 힌트 : ".$this->Setting["Hint"]);
					}
				}else{
					if(!isset($this->Hide[$P->eid])) $this->Hide($P);
				}
			}elseif($P == $this->Setting["Tagger"]){
				foreach($this->Hide as $H){
					if($X == $H["X"] and $Y == $H["Y"] and $Z == $H["Z"]){
	 					$P = $this->api->player->get($Hide["eid"]);
						$this->Hide($P);
 						$P->entity->harm(10,"BH");
					}
				}
			}
		}
	}

	public function Interact_Handler($data){
		if($this->Start !== 0){
		 	$Pe = $data["entity"];
		 	$Te = $data["targetentity"];	
			if($Pe->player == $this->Setting["Tagger"]){
				$Te->harm(10,"BH");
			}else{
				return false;
			}
		}
	}

	public function Death_Handler($data){
		if($this->Start !== 0){
			$P = $data["player"];
			$P->removeItem(341,0,999);
			if($P == $this->Setting["Tagger"]){
				$this->GameStop($this->ScheduleID,3);
			}else{
	 			$X = round($P->x); 
	 			$Y = floor($P->y); 
	 			$Z = round($P->z); 
				$this->Death[$P->username] = array("X" => $X, "Y" => $Y, "Z" => $Z);
				$count = 0;		
				foreach($this->Setting["Tagger"]->level->players as $Pl){
 					if($P !== $Pl and $Pl !== $this->Setting["Tagger"] and !isset($this->Death[$Pl->username])) $count += 1;
 				}
 				if($count = 0) $this->GameStop($this->ScheduleID,2);
 			}
		}
	}
	
	public function Health_Handler($data){
		if($this->Start !== 0){
			$e = $data["entity"];
			if($e->class == ENTITY_PLAYER){
				if($data["cause"] !== "BH") return false;
				if($data["health"] < 1) $e->player->removeItem(341,0,999);
			}
		}
	}

	public function Move_Handler($data){
		if($this->Start !== 0){
			if($data->class == ENTITY_PLAYER){
				$X = round($data->x - 0.5); 
				$Y = floor($data->y); 
				$Z = round($data->z - 0.5); 
				if(isset($this->Hide[$data->eid])){
					$H = $this->Hide[$data->eid];
					if($X !== $H["X"] or $Y !== $H["Y"] or $Z !== $H["Z"]) $this->Hide($data->player);
				}elseif(isset($this->Death[$data->name])){
					$D = $this->Death[$data->name];
					if($X !== $D["X"] or $Y !== $D["Y"] or $Z !== $D["Z"]) return false;
				}
			}
		}
	}

	public function PacketHandler(DataPacketSendEvent $event){
		if($this->Start !== 0){
			$PK = $event->getPacket();
			if($PK instanceof MovePlayerPacket or $PK instanceof MoveEntityPacket_PosRot){
				if(isset($this->Hide[$PK->eid])) $event->setCancelled();
			}
		}
	}
	
	public function Hide($P){
		$e = $P->entity;
		$X = round($e->x - 0.5);
		$Y = floor($e->y);
		$Z = round($e->z - 0.5);
		$L = $e->level;
		$B = $L->getBlock(new Vector3($X, $Y-1, $Z));
		if(isset($this->Hide[$e->eid])){
			$H = $this->Hide[$e->eid];
			unset($this->Hide[$e->eid]);
			$pk = new MoveEntityPacket_PosRot;
			$pk->eid = $e->eid;
			$pk->x = $X;
			$pk->y = $Y;
			$pk->z = $Z;
			$pk->yaw = $e->yaw;
			$pk->pitch = $e->pitch;
			foreach($L->players as $Pl){
				if($Pl !== $P) $Pl->dataPacket($pk);
			}
			$L->setBlockRaw(new Vector3($H["X"],$H["Y"],$H["Z"]),BlockAPI::get(0,0),false);
			$P->sendChat("  [BH] 숨기가 해제되었습니다.");
		}else{
			if($B->isSolid == false or $L->getBlock(new Vector3($X, $Y, $Z))->getID() !== 0){
				$P->sendChat("  [BH] 해당블럭에는 숨을수없습니다.");			
			}else{
				$pk = new MoveEntityPacket_PosRot;
				$pk->eid = $e->eid;
				$pk->x = -256;
				$pk->y = 128;
				$pk->z = -256;
				foreach($L->players as $Pl){
					if($Pl !== $P) $Pl->dataPacket($pk);
				}
				$P->teleport(new Vector3($X + 0.5, $Y, $Z + 0.5));
				$this->Hide[$e->eid] = array("eid" => $e->eid, "X" => $X, "Y" => $Y, "Z" => $Z);
				$L->setBlockRaw(new Vector3($X,$Y,$Z),BlockAPI::get($B->getID(),$B->getMetadata()),false);
				$P->sendChat("  [BH] 숨었습니다 !");
			}
		}
	}

	public function Hint(){
		$this->Broadcast("  [BH] 술래가 힌트를 사용했습니다.");
		$T = 0;
		foreach($this->Setting["Tagger"]->level->players as $Pl){
			if($Pl !== $this->Setting["Tagger"]){
				foreach(array(1,2,3,4,5,6,10,11,14) as $B){
					$this->api->schedule($T,array($this,"Hint_Block"),array($Pl,$B));
					$T += 10;
				}
			}
		}
	}


	public function Hint_Block($A){
		$P = $A[0];
		if(isset($this->Hide[$P->eid])){
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

	public function Un_Hint($A){
		$P = $A[0];
		$H = $A[1];
		$X = $H["X"];
		$Y = $H["Y"];
		$Z = $H["Z"];
		$L = $P->level;
		$pk = new UpdateBlockPacket;
		$pk->x = $X;
		$pk->y = $Y;
		$pk->z = $Z;
		if(isset($this->Hide[$P->eid])){
			$B = $L->getBlock(new Vector3($X, $Y-1, $Z));
			$pk->block = $B->getID();
			$pk->meta = $B->getMetadata();
		}else{
			$pk->block = 0;
			$pk->meta = 0;
		}
		$this->Setting["Tagger"]->dataPacket($pk);
	}
	
	public function Wait(){
		$this->Start = 1;
		$M = array(
			" ",
			"°°°°°°°°°°°°°°°°",
			"  [BH] 블럭숨바꼭질    제작 :: 데베 (huu6677@naver.com)",
			"  [BH] 블럭유저 : 숨기 (이동시해제)",
			"  [BH] 술래유저 : 힌트 (5회).",
			"  [BH] 슬라임볼으로 터치하면 사용됩니다.",
			"°°°°°°°°°°°°°°°°",
 			" ",
			"  [BH] 잠시후 게임이 시작됩니다.",
			" ",
		);
		$T = 0;
		foreach ($M as $m) {
			if($this->Start == 1){
	 			$T += 15;
				$this->api->schedule($T,array($this,"Broadcast"),$m);
			}
		}
		$M = array(
			"  [BH] 시작 5초전",
			"  [BH] 시작 4초전",
			"  [BH] 시작 3초전",
			"  [BH] 시작 2초전",
			"  [BH] 시작 1초전",
		);
		$T = 200;
		foreach ($M as $m) {
			if($this->Start == 1){
				$this->api->schedule($T,array($this,"Broadcast"),$m);
				$T += 20;
			}
		}
		if($this->Start == 1) $this->api->schedule(310,array($this,"GameStart")); 	
	}

	public function GameStart(){
		if($this->Start == 1){
			$this->Broadcast("°°°°°°°°°°°°°°");
			$this->Broadcast("  [BH] 게임이 시작되었습니다!");
			$this->Broadcast("  [BH] 술래 : ".$this->Setting["TaggerName"]."님");
			$this->Broadcast("  [BH] 시간 : ".$this->Setting["Time"]."분");
 			$this->Broadcast("°°°°°°°°°°°°°°");
 			$R = rand(1,99) * rand(1,99) * rand(1,99) * rand(1,99);
 			$this->api->schedule(20*60*$this->Setting["Time"] - 160,array($this,"StopWait"),$R);
 			$this->ScheduleID = $R;
 			$this->Start = 2;
 			$this->api->time->set(0,$this->Setting["Tagger"]->level);
 			foreach($this->Setting["Tagger"]->level->players as $P){
 				$P->entity->setHealth(20);
				$P->removeItem(341,0,999);
				$P->addItem(341,0,1);
			}
 		}
	}

	public function StopWait($R){
		if($this->ScheduleID == $R){
			$this->Broadcast("  [BH] 잠시후 게임이 종료됩니다.");
			$M = array(
			"  [BH] 종료 5초전",
			"  [BH] 종료 4초전",
			"  [BH] 종료 3초전",
			"  [BH] 종료 2초전",
			"  [BH] 종료 1초전",
			);
			$T = 40;
			foreach ($M as $m) {
				if($this->ScheduleID == $R){
					$this->api->schedule($T,array($this,"Broadcast"),$m);
					$T += 20;
				}
			}
			$this->api->schedule(160,array($this,"GameScheduleID"),$R);
		}
	}
	public function GameScheduleID($R){
		$this->GameStop($R,1);
	}

	public function GameStop($R,$Why){
		if($this->ScheduleID == $R){
			switch($Why){
				case "0":
				 	$this->Broadcast("  [BH] 관리자가 게임을 종료하였습니다.");
				break;
				case 1:
					$this->Broadcast("  [BH] 제한시간이 끝났습니다. 블럭들의 승리입니다!");
				break;
				case 2:
	 				$this->Broadcast("  [BH] 블럭들이 모두 잡혔습니다. 술래(".$this->Setting["TaggerName"].")의 승리입니다 !");
				break;
				case 3:
					$this->Broadcast("  [BH] 술래가 사망하였습니다. 블럭들의 승리입니다 !");
				break;
				case 4:
					$this->Broadcast("  [BH] 술래(".$this->Setting["TaggerName"].")가 퇴장하였습니다. 블럭의 승리입니다.");
				break;
				case 5:
	 				$this->Broadcast("  [BH] 생존중인 블럭들이 모두 퇴장했습니다. 술래(".$this->Setting["TaggerName"].")의 승리입니다 !");
				break;
				default:
					$this->Broadcast("  [BH] 오류발생 Error-".$Why);
				break;
			}
			$this->Broadcast("  [BH] 게임이 종료되었습니다.");
			$this->Hide = array();
			$this->Setting = array();
			$this->Death = array();
			$this->Start = 0;
			$this->ScheduleID = 0;
			foreach($this->Setting["Tagger"]->level->players as $P){
				$P->removeItem(341,0,999);
			}
		}
	}

	public function Broadcast($m){
		if($this->Start !== 0)	$this->api->chat->broadcast($m);
	}

	public function SetTime($m){
		if($this->Start !== 0) $this->api->time->set(0,$this->Setting["Tagger"]->level);
	}

	public function __destruct(){
	}
}