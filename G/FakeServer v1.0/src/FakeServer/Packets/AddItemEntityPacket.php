<?php

namespace FakeServer\Packets;

#include <rules/DataPacket.h>
#ifndef COMPILE
use pocketmine\utils\Binary;
#endif

class AddItemEntityPacket extends \pocketmine\network\protocol{
	const NETWORK_ID = Info::ADD_ITEM_ENTITY_PACKET;

	public $eid;
	public $item;
	public $x;
	public $y;
	public $z;
	public $speedX;
	public $speedY;
	public $speedZ;

	public function decode(){
	}

	public function encode(){
		$this->reset();
		$this->putLong($this->eid);
		$this->putSlot($this->item);
		$this->putFloat($this->x);
		$this->putFloat($this->y);
		$this->putFloat($this->z);
		$this->putFloat($this->speedX);
		$this->putFloat($this->speedY);
		$this->putFloat($this->speedZ);
	}
}