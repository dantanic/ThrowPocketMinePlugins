<?php
 
/*
__PocketMine Plugin__
name=Item Master
version=1.0.0
apiversion=11,12
author=DeBe
class=Item_Master
*/

class Item_Master implements Plugin{
	private $api;

	public function __construct(ServerAPI $api,$server=false){
		$this->api	= $api;
		$this->Drop = array();
	}

	public function init(){
	 	$this->api->addhandler("player.block.touch", array($this, "MainHandler"));
		$this->api->console->register("im", "Item Master", array($this, "PlayerCommander"));
		$this->api->console->register("ima", "Item Master Admins", array($this, "AdminCommander"));
		$this->api->ban->cmdWhitelist("im");
 		$Alias = array(
			array("택배", "im gift"),
			array("보기", "im view"),
			array("렉", "im rack"),
			array("없애기", "im delete"),
			array("다없애기", "im clear"),
			array("뿌리기", "im drop"),
			array("빼앗기", "ima take"),
			array("검색", "ima search"),
			array("인벤", "ima inv"),
			array("슬롯", "ima slot"),
			array("빼앗기", "ima take"),
			array("체크", "ima check"),
			array("클리어", "ima clear"),
			array("이벤트", "ima drop"),
		);
		foreach($Alias as $A) $this->api->console->alias($A[0],$A[1]);
 }
 
	public function MainHandler($data){
		$P = $data["player"];
		$U = $P->username;
		if(isset($this->Drop[$U])){
			$TB = $this->Drop[$U][1];
			if($TB == 1){
				$N = "이벤트";
			}else{
				$N = "뿌리기";
			}
			$I = $this->Drop[$U][0];
			$T = $data["target"];	$X = $T->x;	$Y = $T->y;	$Z = $T->z;	$L = $T->level;
			$Iic = 0;	$TF = false;	$FT = false;
			foreach($P->inventory as $slot => $Ii){
				if($I->getID() == $Ii->getID() and $I->getMetadata() == $Ii->getMetadata()){
					$Iic += $Ii->count;
					if($Iic >= $I->count){
						$TF = true;
					}if($Iic > 1){
						$FT = true;
					}
				}
			} 			
			if($P->level->getBlock(new Vector3($X, $Y+1, $Z))->getID() !== 0){
				$P->sendChat("[IM: $N] 바닥에만 뿌릴수있습니다.");
			}elseif($FT == false && $TB !== 1){
				unset($this->Drop[$U]);
 				$P->sendChat("[IM: $N] ".$I->getName()." (".$I->getID().":".$I->getMetadata().")를 가지고있지않습니다.");
				$P->sendChat("[IM: $N] ".$N."를 해제합니다."); 				
			}else{
				if($TF == false && $TB !==1) $I->count = $Iic;
				$D = array(
					"x" => $X + 0.5,
					"y" => $Y + 1.19,
					"z" => $Z + 0.5,
					"level" => $L,
					"item" => $I,
					);
				$this->api->entity->spawnToAll($this->api->entity->add($L, ENTITY_ITEM,$I->getID(),$D));
				$P->sendChat("[IM: $N] ".$I->getName()." (".$I->getID().":".$I->getMetadata().") ".$I->count."개를 뿌렸습니다.");
				if($TB !== 1){
					$P->removeItem($I->getID(),$I->getMetadata(),$I->count);
					if($TB == 0){
						unset($this->Drop[$U]);
					}
				}
			} return false;
		}
	}
	
 public function PlayerCommander($cmd,$params,$issuer,$alias){
 		$U = $issuer->username;
		if($issuer === "console"){
			return "[IM] 콘솔에서는 사용할수없습니다.";
		}else{
			$Pr0 = strtolower($params[0]);
	 		$Pr1 = strtolower($params[1]);
			$Pr2 = strtolower($params[2]);
			$Pr3 = strtolower($params[3]);
			$Player = $this->api->player->get($issuer->username);
			switch($Pr0){
				case "gift":
					$Target = $this->api->player->get($Pr1);
					if($Pr2 == "0" or $Pr2 == "0:0"){
						$e = $this->api->entity->get($Player->eid);
						$S = $e->player->getSlot($e->player->slot);
						if($S->getID() == 0){
							$Player->sendChat("[IM: 택배] 공기는 선물할수없습니다.");
							return "[IM: 택배] 인벤렉일경우 /렉 을 쳐주세요.";
						}else{
					 		$I = BlockAPI::fromString($S->getID().":".$S->getMetadata());	
					 	}
					}else{
						$I = BlockAPI::fromString($Pr2);
					}
					if($Pr1 == "" or $Pr1 == null){
						return "[IM: 택배] /택배 <플레이어명> <아이템ID> <갯수>";
					}elseif(!($Target instanceof Player)){
						return "[IM: 택배] $Pr1 님은 접속중이 아닙니다.";
					}elseif($I->getID() == 0){
						$Player->sendChat("[IM: 택배] 택배보낼 아이템의 ID를 제대로 입력해주세요.");
						return "[IM: 택배] ID를 모르시면 <아이템ID>칸에 0 이라고 적어주세요.";
					}elseif(($Target->gamemode & 0x01) === 0x01){
						return "[IM: 택배] $Target->username 님은 크리에이티브입니다.";
					}elseif($Target->username == $U){
	 					return "[IM: 택배] 본인에게는 택배를 보낼수없습니다.";	
					}elseif($Pr3 < 1){
						$I->count = (int) 1;
					}else{
						$I->count = (int) $Pr3;
					}
					$Iic = 0;	$TF = false; $FT = false;
					foreach($Player->inventory as $slot => $Ii){
						if($I->getID() == $Ii->getID() and $I->getMetadata() == $Ii->getMetadata()){
							$Iic += $Ii->count;
							if($Iic >= $I->count){
								$TF = true;
							}if($Iic > 1){
								$FT = ture;
							}
						}
					}
					if($FT == false){
						return "[IM: 택배] ".$I->getName()." (".$I->getID().":".$I->getMetadata().") 를 가지고있지않습니다."; 				
					}else{
						if($TF == false) $I->count = $Iic;
						$Target->addItem($I->getID(),$I->getMetadata(),$I->count);
						$Player->removeItem($I->getID(),$I->getMetadata(),$I->count);
						$Target->sendChat("[IM: 택배] ".$Player->username."님이 ".$I->getName()." (".$I->getID().":".$I->getMetadata().") ".$I->count."개를 택배로 보내셨습니다.");
						return "[IM: 택배] ".$Target->username."님에게 ".$I->getName()." (".$I->getID().":".$I->getMetadata().") ".$I->count."개 택배로 보냈습니다.";
					}
				break;
				
				case "view":
					$e = $this->api->entity->get($Player->eid);
					$I = $e->player->getSlot($e->player->slot);
					return "[IM: 보기] 들고있는템 :: ".$I->getName()." (".$I->getID().":".$I->getMetadata().") ".$I->count."개";
				break;
				
				case "rack":
					if(($Player->gamemode & 0x01) === 0x01){
						return "[IM: 렉] 당신은 크리에이티브입니다.";
					}else{
			 		 	$Rack = array();
						foreach($Player->inventory as $slot => $I){
							if($I->getID() !== 0 || $I->count !== 0){
								if(!isset($Rack[$Player->username])) $Rack[$Player->username] = array();
								$Rack[$Player->username][] = array($I->getID().":".$I->getMetadata(),$I->count);
								$Player->removeItem($I->getID(),$I->getMetadata(),$I->count);
							}
						}
						foreach($Rack[$Player->username] as $R){
							$I = BlockAPI::fromString($R[0]);
							$Player->addItem($I->getID(),$I->getMetadata(),$R[1]);
						}
						$Rack = array();
						$Player->sendChat("[IM: 렉] 인벤토리 렉을 고칩니다.");
					}
				break;
	 			
				case "delete":
				 	if($Pr1 == "0" or $Pr1 == "0:0"){
						$e = $this->api->entity->get($Player->eid);
						$S = $e->player->getSlot($e->player->slot);
						if($S->getID() == 0){
							$Player->sendChat("[IM: 없애기] 공기는 없앨필요없습니다.");
							return "[IM: 없애기] 인벤렉일경우 /렉 을 쳐주세요.";
						}else{
					 		$I = BlockAPI::fromString($S->getID().":".$S->getMetadata());	
					 	}
					}else{
						$I = BlockAPI::fromString($Pr1);
					}
					if(($Player->gamemode & 0x01) === 0x01){
						return "[IM: 없애기] 당신은 크리에이티브입니다.";
					}else{
						$I = BlockAPI::fromString($Pr1);
						if($Pr1 == ""){
							return "[IM: 없애기] /없애기 <아이템ID> <갯수>";
						}elseif($I->getID() == 0){
							$Player->sendChat("[IM: 없애기] 얿앨 아이템의 ID를 제대로 입력해주세요.");
							return "[IM: 없애기] ID를 모르시면 <아이템ID>칸에 0 이라고 적어주세요.";
						}elseif($Pr2 < 1){
							$I->count = (int) 1;
						}else{
							$I->count = (int) $Pr2;
						}
						$Iic = 0;	$TF = false;	$FT = false;
						foreach($Player->inventory as $slot => $Ii){
							if($I->getID() == $Ii->getID() and $I->getMetadata() == $Ii->getMetadata()){
								$Iic += $Ii->count;
								if($Iic >= $I->count){
									$TF = true;
								}if($Iic > 0){
									$FT = ture;
								}
							}
						}
						if($FT == false ){
							return "[IM: 없애기] ".$I->getName()." (".$I->getID().":".$I->getMetadata().") 를 가지고있지않습니다."; 				
						}else{
							if($TF == false) $I->count = $Iic;
							$Player->removeItem($I->getID(),$I->getMetadata(),$I->count);
							return "[IM: 없애기] ".$I->getName()." (".$I->getID().":".$I->getMetadata().") ".$I->count."개를 없앴습니다.";
						}
					}
				break;
				
				case "clear":
				 	foreach($Player->inventory as $slot => $I) $Target->removeItem($I->getID(),$I->getMetadata(),$I->count);
					return "[IM: 다없애기] 인벤을 초기화합니다.";
				break;

				case "drop":
					if(($Player->gamemode & 0x01) === 0x01){
						return "[IM: 뿌리기] 당신은 크리에이티브입니다.";
					}else{
						if($Pr1 == "0" or $Pr1 == "0:0"){
							$e = $this->api->entity->get($Player->eid);
							$S = $e->player->getSlot($e->player->slot);
							if($S->getID() == 0){
								$Player->sendChat("[IM: 뿌리기] 공기는 뿌릴수없습니다.");
								return "[IM: 뿌리기] 인벤렉일경우 /렉 을 쳐주세요.";
							}else{
						 		$I = BlockAPI::fromString($S->getID().":".$S->getMetadata());	
					 		}
						}else{
							$I = BlockAPI::fromString($Pr1);
						}
					 	if(isset($this->Drop[$U])){
 		 				unset($this->Drop[$U]);
   	 			return "[IM: 뿌리기] 뿌리기를 해제합니다.";
					}elseif($Pr1 == "" || $Pr1 == null){
						return "[IM: 뿌리기] /뿌리기 <아이템ID> <갯수>";
					}elseif($I->getID() == 0){
						$Player->sendChat("[IM: 뿌리기] 뿌릴 아이템의 ID를 제대로 입력해주세요.");
						return "[IM: 뿌리기] ID를 모르시면 <아이템ID>칸에 0 이라고 적어주세요.";
					}elseif($Pr2 < 1){
						$I->count = (int) 1;
					}else{
						$I->count = (int) $Pr2;
					}if($Pr3 == null){
						$TF = 0;
						$m = "";
					}else{
						$TF = 2;
						$m = "[IM: 뿌리기] 해제하시려면 명령어를 다시쳐주세요.";
					}
					$this->Drop[$U] = array($I,$TF);
					$Player->sendChat("[IM: 뿌리기] 뿌릴곳을 터치해주세요. ");
					return "$m";
				}
				break;

				defualt:
					return "[IM] 잘못된 명령어입니다.     제작 :: DeBe (데베) ";
				break;
			}
		}
	}
	
	Public function AdminCommander($cmd,$params,$issuer,$alias){
 		$Pr0 = strtolower($params[0]);
	 	$Pr1 = strtolower($params[1]);
		$Pr2 = strtolower($params[2]);
		$Pr3 = strtolower($params[3]);
		$Target = $this->api->player->get($Pr1);
		if(in_array($Pr0,array("search","drop"))){
			switch($Pr0){
				case "search":
					$I = BlockAPI::fromString($Pr1);
					if($I->getID() == 0){
						return "[IM: 검색] 검색할 아이템의 ID를 제대로 입력해주세요.";
					}else{
						foreach($this->api->player->getAll() as $P){
							$Iic = 0;
							$TF = false;
							$Search = array();
							foreach($P->inventory as $slot => $Ii){
								if($I->getID() == $Ii->getID() and $I->getMetadata() == $Ii->getMetadata()){
									$iii = $Ii;
									$Iic += $Ii->count;
									if($I->count > 0){
										$TF = true;
									}
								} 
							}if($TF !== false){
								$Search[] = $P->username;
							}
						}
						if(!count($Search)){
							return "[IM: 검색] ".$I->getName()." (".$I->getID().":".$I->getMetadata().") 를 가진사람이 없습니다.";
						}else{
							return "[IM: 검색] ".implode(" , ",$Search);
						}
					}
				break;
				
				case "drop":
				 	$I = BlockAPI::fromString($Pr1);
					if($issuer === "console"){
						return "[IM] 콘솔에서는 사용할수없습니다.";
					}elseif(isset($this->Drop[$issuer->username])){
 		 				unset($this->Drop[$issuer->username]);
   	 			return "[IM: 이벤트] 이벤트를 해제합니다.";
					}elseif($Pr1 == "" || $Pr1 == null){
						return "[IM: 이벤트] /이벤트 <아이템ID> <갯수>";
					}elseif($I->getID() == 0){
						return "[IM: 이벤트] 뿌릴 아이템의 ID를 제대로 입력해주세요.";
					}elseif($Pr2 < 1){
						$I->count = (int) 1;
					}else{
						$I->count = (int) $Pr2;
					}
					$this->Drop[$issuer->username] = array($I,1);
					$issuer->sendChat("[IM: 이벤트] 뿌릴곳을 터치해주세요.");
					return "[IM: 이벤트] 해제하시려면 명령어를 다시쳐주세요.";
				break;			
			}
		}elseif($Target == false){
			$Tn = $Pr1;
		}else{
			$Tn = $Target->username;
		}if($Tn == " " || $Tn == null){
			return "[IM] 플레이어명을 입력해주세요.";
		}elseif(!($Target instanceof Player)){
			return "[IM] $Tn 님은 접속중이 아닙니다.";
		}elseif(($Target->gamemode & 0x01) === 0x01){
			return "[IM] $Tn 님은 크리에이티브입니다.";
		}else{
			switch($Pr0){
				case "inv":
					$Inv = array();
					foreach($Target->inventory as $slot => $I){
						if($I->getID() !== 0 || $I->count !== 0){
							$Inv[] = $I->getName()." (".$I->getID().":".$I->getMetadata().") ".$I->count."개 ";
						}
					}if(!count($Inv)){
						return "[IM: 인벤][$Tn] 아이템이 한개도없습니다.";
					}
					return "[IM: 인벤][$Tn] ".implode(" , ",$Inv);
				break;
	
				case "slot":
					$e = $this->api->entity->get($Target->eid);
					$I = $e->player->getSlot($e->player->slot);
					return "[IM: 슬롯][$Tn] 들고있는템 :: ".$I->getName()." (".$I->getID().":".$I->getMetadata().") ".$I->count."개";
				break;
				
				case "take":
				 	$I = BlockAPI::fromString($Pr2);
					if($Pr1 == ""){
						return "[IM: 빼앗기] /빼앗기 <플레이어명> <아이템ID> <갯수>";
					}elseif($I->getID() == 0){
						return "[IM: 빼앗기] 뺏을 아이템의 ID를 제대로 입력해주세요.";
					}elseif($Pr3 < 1){
						$I->count = (int) 9999;
					}else{
						$I->count = (int) $Pr3;
						}
					$Iic = 0;	$TF = false;	$FT = false;
					foreach($Target->inventory as $slot => $Ii){
						if($I->getID() == $Ii->getID() and $I->getMetadata() == $Ii->getMetadata()){
							$Iic += $Ii->count;
							if($Iic >= $I->count){
								$TF = true;
							}if($Iic > 0){
								$FT = ture;
							}
						}
					}
					if($FT == false){
						return "[IM: 빼앗기][$Tn] ".$I->getName()." (".$I->getID().":".$I->getMetadata().") 를 가지고있지않습니다."; 				
					}else{
						if($TF == false) $I->count = $Iic;
						$Target->removeItem($I->getID(),$I->getMetadata(),$I->count);
						$Target->sendChat("[IM] ".$I->getName()." (".$I->getID().":".$I->getMetadata().") ".$I->count."개를 빼앗겼습니다.");
						return "[IM: 빼앗기][$Tn] ".$I->getName()." (".$I->getID().":".$I->getMetadata().") ".$I->count."개를 빼앗았습니다.";
					}
				break;
				
		 		case "check":
					$I = BlockAPI::fromString($Pr2);
					if($Pr1 == ""){
						return "[IM: 체크] /체크 <플레이어명> <아이템ID>";
					}elseif($I->getID() == 0){
						return "[IM: 체크] 체크할 아이템의 ID를 제대로 입력해주세요.";
					}else{
						$Iic = 0;
						$TF = false;
						$Check = array();
						foreach($Target->inventory as $slot => $Ii){
							if($I->getID() == $Ii->getID() and $I->getMetadata() == $Ii->getMetadata()){
								$iii = $Ii;
								$Iic += $Ii->count;
								if($I->count > 0){
									$TF = true;
								}
							}
						}
						if($TF == false){
							return "[IM: 체크][$Tn] ".$I->getName()." (".$I->getID().":".$I->getMetadata().") 를 가지고있지않습니다."; 				
						}else{ 			 		
							return "[IM: 체크][$Tn] ".$I->getName()." (".$I->getID().":".$I->getMetadata().") 를 가지고있습니다.";
						}
					}
				break;

				case "clear":
					foreach($Target->inventory as $slot => $I) $Target->removeItem($I->getID(),$I->getMetadata(),$I->count);
					$Target->sendChat("[IM] 당신은 인벤토리가 초기화되었습니다. ");
					return "[IM: 클리어][$Tn] 인벤을 초기화합니다.";
				break;
		
				defualt:
					return "[IM] 잘못된 명령어입니다.      제작 :: DeBe (데베) ";
				break;
			}
		}
	}
 	
 public function __destruct(){
 }
}