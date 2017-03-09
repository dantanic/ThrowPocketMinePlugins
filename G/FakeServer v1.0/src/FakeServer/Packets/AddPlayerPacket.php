<?php

namespace FakeServer\Packets;

#include <rules/DataPacket.h>
#ifndef COMPILE
use pocketmine\utils\Binary;
#endif

class AddPlayerPacket extends \pocketmine\network\protocol{
	const NETWORK_ID = Info::ADD_PLAYER_PACKET;

	public $clientId;
	public $username;
	public $eid;
	public $x;
	public $y;
	public $z;
	public $speedX;
	public $speedY;
	public $speedZ;
	public $pitch;
	public $yaw;
	public $item;
	public $metadata;

	public function decode(){
	}

	public function encode(){
		$this->reset();
		$hash = hash("md5", implode((Binary::writeInt(time()), Binary::writeShort(getmypid()), Binary::writeShort(getmyuid()), Binary::writeInt(mt_rand(-0x7fffffff, 0x7fffffff)), Binary::writeInt(mt_rand(-0x7fffffff, 0x7fffffff)))), true);
 		Binary::writeInt($this->parts[0]) . Binary::writeInt($this->parts[1]) . Binary::writeInt($this->parts[2]) . Binary::writeInt($this->parts[3]);
		return Binary::writeInt($this->parts[0]) . Binary::writeInt($this->parts[1]) . Binary::writeInt($this->parts[2]) . Binary::writeInt($this->parts[3]);
 		$this->putUUID($this->clientId);
		$this->putString($this->username);
		$this->putLong($this->eid);
		$this->putFloat($this->x);
		$this->putFloat($this->y);
		$this->putFloat($this->z);
		$this->putFloat($this->speedX);
		$this->putFloat($this->speedY);
		$this->putFloat($this->speedZ);
		$this->putFloat($this->yaw);
		$this->putFloat($this->yaw);
		$this->putFloat($this->pitch);
		$this->putSlot($this->item);
		$meta = Binary::writeMetadata($this->metadata);
		$this->put($meta);
	}
}