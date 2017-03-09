<?php

namespace RubyTnTTag;

use pocketmine\network\protocol\DataPacket;

class ServerTPPacket extends DataPacket{
	const NETWORK_ID = 0x1b;

	public $address;
	public $port = 19132;

	public function pid(){
		return 0x1b;
	}

	public function decode(){}

	public function encode(){
		$this->reset();
		$this->putByte(4);
		foreach(explode(".", $this->address) as $b) $this->putByte((~((int) $b)) & 0xff);
		$this->putShort($this->port);
	}
}