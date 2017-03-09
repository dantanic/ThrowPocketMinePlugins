<?php
 
/*
__PocketMine Plugin__
name=ItemPost
version=1.0.0
apiversion=11,12
author=DeBe
class=ItemPost
*/

class ItemPost implements Plugin{
	private $api;

	public function __construct(ServerAPI $api,$server=false){
		$this->api	= $api;
	}

	public function init(){
		$this->api->console->register("itempost", "ItemPost", array($this, "Commander"));
		$this->api->ban->cmdWhitelist("itempost");
 		$alias = array(
			array("ip", "itempost"),
			array("post", "itempost"),
			array("택배", "itempost"),
			array("선물", "itempost"),
		);
		foreach($alias as $a) $this->api->console->alias($a[0],$a[1]);
 }
 
 public function Commander($c,$sub,$p){
 		$m = "[ItemPost] ";
		if($p === "console") return $m."콘솔에서는 사용할수없습니다.";
 		if(!isset($sub[0]) or !isset($sub[1]) or !isset($sub[2])) return $m."Usage: /택배 <상대방이름> <아이템ID> <갯수>";
 		$t = $this->api->player->get($sub[0]);
		if(!($t instanceof Player)) return $m."$sub[0] 님은 접속중이 아닙니다.";
		if($sub[1] == "0" or $sub[1] == "0:0"){
			$i = $e->player->getSlot($e->player->slot);
			if($i->getID() == 0) return $m."들고있는 아이템이 공기입니다. 다시한번 확인해주세요.";
		}
		$i = BlockAPI::fromString($sub[1]);
		if($i->getID() == 0) return $m."<아이템ID>를 확인해 주세요. \n".$m."ID를 모르시면 택배보낼 아이템을 들고 <아이템ID>에 \"0\"이라고 적어두세요.";
		if(($t->gamemode & 0x01) === 0x01) return $m.$t->username."님은 크리에이티브입니다.";
		if($t == $p) $m."본인에게는 택배를 보낼수없습니다.";	
		if(is_numeric($sub[2]) and $sub[2] >= 1) $i->count = round($sub[2]);
		else return $m."<갯수>를 확인해주세요.";
		$c = 0;
		foreach($p->inventory as $k => $v){
			if($i->getID() == $v->getID() and $i->getMetadata() == $v->getMetadata()){
				$iic += $ii->count;
				if($iic >= $i->count){
					$check = true;
					break;
				}
			}
		}
		if(!isset($check)){
			return $m.$i->getName()." (".$i->getID().":".$i->getMetadata().") 를 ".$i->count.".개 보다 적게 가지고있습니다.";
		}else{
			$t->addItem($i->getID(),$i->getMetadata(),$i->count);
			$p->removeItem($i->getID(),$i->getMetadata(),$i->count);
			$t->sendChat($m.$p->username."님이 ".$i->getName()." (".$i->getID().":".$i->getMetadata().") 을 ".$i->count."개 택배로 보내셨습니다.");
			return $m.$t->username."님에게 ".$i->getName()." (".$i->getID().":".$i->getMetadata().") 을 ".$i->count."개 택배로 보냈습니다.";
		}
	}

 public function __destruct(){}
}