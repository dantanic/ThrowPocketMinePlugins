<?php

namespace RubyGame;

use pocketmine\level\Position;
use pocketmine\utils\TextFormat;
use pocketmine\Player;
use pocketmine\Server;

class BaseGame{
 /*   For Instance
  * @RubyGame $api
  * @Server $server
  */
	protected $api = null;
	protected $server = null;

 /*   For GameStatus
  * @Array $players PlayerList
  * @Boolean $isStarted GameStartInfo
  * @Int $tick GamePlayTick
  */
	protected $players = [];
	protected $isStarted = false;
	protected $tick = 0;

 /*   For GameInfo
  * @String $name GameName
  * @Int $min MinPlayerCount
  * @Int $max MaxPlayerCount
  * @Int $time MaxGameTime
  * @Position $lobbyPos GameWaitPosition
  * @Position $startPos GameStartPosition
  * @Position $minPos GamePlayPostion-Min
  * @Position $maxPos GamePlayPosition-Max
  */
	protected $name = "BaseGame";
	protected $min = 2;
	protected $max = 20;
	protected $time = 60;
	protected $lobbyPos = null;
	protected $startPos = null;
	protected $minPos = null;
	protected $maxPos = null;

	public function __construct(...$array){
		$this->api = RubyGame::getInstance();
		$this->server = Server::getInstance();
		$this->scheduleRepeatingTask($this, "onTick", 20);
	}

	public function onStart(){
		if($this->isStarted) return false;
		$this->isStarted = true;
	}

	public function onStop(){
		if(!$this->isStarted) return false;
		$this->isStarted = false;
		$this->time = 0;
	}

	public function onTick(){ //1초
		$this->sendPopup($this->getPopup());
		$this->tick++;
		if($this->time - $this->tick <= 0){
			$this->onStop();
		}
	}

	public function onJoin($event){
		$player = $event->getPlayer();
		$this->players[$player->getName()] = $player;
		if(count($this->players) >= $this->min){
			$this->onStart();
		}
	}

	public function onQuit($event){
		$player = $event->getPlayer();
		unset($this->players[$player->getName()]);
	}

	public function getPopup(){
		return TextFormat::RED."[".$this->name."] ".TextFormat::YELLOW."Status: ".($this->isStarted ? "Start" : "Stop")." ".TextFormat::BLUE."Players: ".count($this->players). TextFormat::DARK_BLUE."/".TextFormat::BULE.$this->max.")".TextFormat::GOLD."Time: ".($this->time - $this->tick);
	}

	final public function sendMessage(string $message){
		foreach($this->players as $player){
			$player->sendMessage($message);
		}
	}

	final public function sendPopup(string $popup){
		foreach($this->players as $player){
			$player->sendPopup($popup);
		}
	}

	final public function getAPI(){
		return $this->api;
	}
	
	final public function getServer(){
		return $this->server;
	}

 final public function isStarted(){
		return 	$this->isStarted;
	}

	final public function getPlayers(){
		return $this->players;
	}

	final public function getName(){
		return $this->name;
	}

	final public function setName(string $name){
		$this->name = $name;
	}

	final public function getMin(){
		return $this->min;
	}

	final public function setMin(int $min){
		$this->min = $min;
	}

	final public function getMax(){
		return $this->max;
	}
	
	final public function setMax(int $max){
		$this->max = $max;
	}

	final public function getTime(){
		return $this->time;
	}

	final public function setTime(int $time){
		$this->time = $time;
	}

	final public function getLobbyPos(){
		return $this->lobbyPos;
	}

	final public function setLobbyPos(Position $lobbyPos){
		$this->lobbyPos = $lobbyPos;
	}

	final public function getStartPos(){
		return $this->StartPos;
	}

	final public function setStartPos(Position $StartPos){
		$this->startPos = $startPos;
	}

	final public function getMinPos(){
		return $this->minPos;
	}

	final public function setMinPos(Position $minPos){
		$this->minPos = $minPos;
	}

	final public function getMaxPos(){
		return $this->maxPos;
	}

	final public function setMaxPos(Position $maxPos){
		$this->maxPos = $maxPos;
	}

	final public function scheduleDelayedRepeatingTask($class, string $function, int $delay, int $period, array $array = []){
		$this->api->scheduleDelayedRepeatingTask($class, $function, $delay, $period, $array);
	}

	final public function scheduleDelayedTask($class, string $function, int $delay, array $array = []){
		$this->api->scheduleDelayedTask($class, $function, $delay, $array);
	}

	final public function scheduleRepeatingTask($class, string $function, int $period, array $array = []){
		$this->api->scheduleRepeatingTask($class, $function, $period, $array);
	}
}