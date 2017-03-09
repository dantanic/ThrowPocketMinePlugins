<?php

namespace Chair;

class SetEntityLinkPacket extends \pocketmine\network\protocol\DataPacket{
	const NETWORK_ID = 0xa0;

	public $rider; //RiderID long
	public $ridden; //RiddenID long
	public $linkType = 0; //LinkType byte

	public function pid(){
		return 0xa0;
	}

	public function decode(){}

	public function encode(){
		$this->reset();
		$this->putLong($this->ridden);
		$this->putLong($this->rider);
		$this->putByte($this->linkType);
	}
}