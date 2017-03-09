<?php

namespace RubyGame;

use pocketmine\level\Position;
use pocketmine\utils\TextFormat;
use pocketmine\Player;

class TestGame extends BaseGame{
	protected $name = "BaseGame";
	protected $min = 2;
	protected $max = 20;
	protected $time = 120;

	public function onStart(){
		if($this->isStarted) return false;
		parent::onStart();
	}

	public function onStop(){
		if(!$this->isStarted) return false;
		parent::onStop();
 	}

	public function onTick(){
		parent::onTick();
	}

	public function onJoin($event){
		$player = $event->getPlayer();
		parent::onJoin($player);
	}

	public function onQuit($event){
		$player = $event->getPlayer();
		parent::onJoin($player);
 	}
}