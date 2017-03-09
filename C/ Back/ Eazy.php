<?php
/*
__PocketMine Plugin__
name=eazy
version=
author=
class=eazy
apiversion=12
*/

class eazy implements Plugin{
	private $api;

	public function __construct(ServerAPI $api, $server = false){
		$this->api = $api;
		$this->Day = 0;
		$this->PVP = 0;
	}

	public function init(){
 		$Alias = array(
			array("pr","property eazy"),
 			array("ivc","iv create"),
			array("ivr","iv reload"),
			array("부자", "topmoney"),
			array("내돈", "mymoney"),
			array("돈", "mymoney"), 
			array("지불", "pay"), 
			array("도박", "casino"), 
			array("저금", "bank deposit"), 
			array("출금", "bank withdraw"), 
			array("은행돈", "bank mymoney"),
			array("땅", "land"), 
			array("땅여기", "land here"), 
			array("땅목록", "land list"), 
			array("땅팔기", "landsell"), 
			array("땅공유", "land invite"), 
			array("땅공유목록", "land invitee"), 
			array("낮", "time day"), 
			array("밤", "time night"),
			array("ws", "worlds"),
			array("wse", "worlds export"),
			array("wsu", "worlds unload"),
			array("wsl", "worlds load"),
			array("wsg", "worlds generate"),
			array("pt1", "portal pos1"),
			array("pt2", "portal pos2"),
			array("pt3", "portal pos3"),
			array("ss", "setspawn"),
			array("sp", "spawn"),
			array("sm", "spawn main"), 
			array("스폰", "spawn"), 
			array("메인", "spawn main"), 
		);
		foreach($Alias as $a) $this->api->console->alias($a[0],$a[1]);
	 	$Whitelist = array(
			"im",
			"ima",
			"op",
			"tp"
	 		);
		foreach($Whitelist as $w) $this->api->ban->cmdWhitelist($w);
		$this->api->console->register("day","Time",array($this,"Day"));
		$this->api->console->register("pvp","Damage",array($this,"PVP"));
		$this->api->addHandler("entity.health.change",array($this,"SetPVP"));
		$this->api->addHandler("player.craft",array($this,"s"));
		$this->api->schedule(100,array($this,"SetDay"),true,"server.schedule");
	}

	public function s($data){
		$this->api->dhandle("entity.event", array("entity" => $data["player"]->entity, "event" => 4));
//		foreach($data["recipe"] as $i) console("R:".$i->getName()."(".$i->getID().":".$i->getMetadata().") [".$i->count."]");
		foreach($data["craft"] as $i){
//			console("R:".$i->getName()."(".$i->getID().":".$i->getMetadata().") [".$i->count."]");
			if($i->getID() == 5){
				$data["player"]->sendChat(" [테러방지] ".$i->getName()."(".$i->getID().":".$i->getMetadata().") 는 제작금지입니다.");
			}
		}
	}
	public function Day(){
		if($this->Day == 0){
			$this->Day = 1;
		 return "  [시간] 밤으로 고정.";
		}elseif($this->Day == 1){
			$this->Day = 0;
		 return "  [시간] 아침으로 고정.";
		}
	}

	public function SetDay(){
		if($this->Day == 0) $t = 0;
		if($this->Day == 1) $t = 19200;
		foreach($this->api->player->getAll() as $P) $this->api->time->set($t,$P->level);
	}

	public function PVP(){
		if($this->PVP == 0){
			$this->PVP = 1;
		 return "  [PVP] 온";
		}elseif($this->PVP == 1){
			$this->PVP = 0;
		 return "  [PVP] 오프";
		}
	}

	public function SetPVP(){
		if($this->PVP == 0) return false;
	}

	public function __destruct(){
	}
}