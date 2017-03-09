<?php

namespace AutoStopper;

use pocketmine\utils\TextFormat as Color;
use pocketmine\utils\Config;

class AutoStopper extends \pocketmine\plugin\PluginBase implements \pocketmine\event\Listener{
	private $tick = 0, $warningTick = 0;

	public function onEnable(){
		$this->getServer()->getScheduler()->scheduleRepeatingTask(new Task($this, [$this, "onTick"]), 20);
	}

	public function onTick(){
		@mkdir($folder = $this->getDataFolder());
		$messages = (new Config($this->getDataFolder() . "AutoStopper_Message.yml", Config::YAML, [
			"countdown-normal" => Color::AQUA . "[AutoStopper] The server will stop after {COUNTDOWN} seconds.",
			"countdown-lowtps" => Color::AQUA . "[AutoStopper] The server will stop after {COUNTDOWN} seconds becuase server has low TPS.",
			"stop-normal" => Color::AQUA . "[AutoStopper] The server is stopped.",
			"stop-lowtps" => Color::AQUA . "[AutoStopper] The server is stopped because server has low TPS.",
			"kick-normal" => Color::AQUA . "[AutoStopper] \n  Server is stopped.",
			"kick-lowtps" => Color::AQUA . "[AutoStopper] \n  Server is stopped becuase server has low TPS.",
			"check-tps" => Color::AQUA . "[AutoStopper] Countdown will start server TPS is until low. TPS: {TPS} ({COUNT}/{MAXCOUNT}");
		]))->getAll();
		$settings = (new Config($this->getDataFolder() . "AutoStopper_Setting.yml", Config::YAML, [
			"Enable" => true,
			"Time" => 60 * 60, // 1hour
			"TPS" => 0,
			"DisplayType" => "Chat, Popup",
			"CheckTPS" => 5,
			"Countdown" => 10
		]))->getAll();
		if($settings["Enable"] !== false){
			$this->tick++;
			$ik = $this->getServer()->getLanguage()->getName() == "\"한국어\"";
 			if($this->getServer()->getTicksPerSecondAverage() < $aettings["MinTPS"]){
				if($this->warningTick < 5){
					$this->getLogger()->warning($messages["serverstop($ik ? "서버의 TPS가 낮습니다." : "Server TPS is low") . " (" . ($this->warningTick + 1) . "/5)");
				}elseif($this->warningTick - 5 >= $aettings["Countdown"]){
					$this->shutdown($message = Color::AQUA . "[AutoStopper] " . ($ik ? "서버가 렉이 심해 종료됩니다." : "Server is stop because low TPS"));
	 			}else{
					$this->sendMessage(Color::AQUA . "[AutoStopper] " . ($ik ? "서버가 렉이 심해 " . ($aettings["Countdown"] - $this->warningTick + 5) . "초후 재시작됩니다." : "Server restarting in " . ($aettings["Countdown"] - $this->warningTick + 5) . "sec because low TPS"), $aettings["DisplayType"]);
				}
				$this->warningTick++;
			}else{
				if($this->warningTick > 0){
					if($this->warningTick > 5){
						$this->sendMessage(Color::AQUA . "[AutoStopper] " . ($ik ? "서버의 렉이 풀렸습니다." : "Now server has normal TPS"), $aettings["DisplayType"]);
					}					
					$this->warningTick = 0;
				}
				if($aettings["Time"] - $this->tick <= 0){
					$this->shutdown(Color::AQUA . "[AutoStopper] " . ($ik ? "서버가 종료됩니다." : "Server is stop"));
	 			}elseif(($countdown = $aettings["Time"] - $this->tick) <= $aettings["Countdown"]){
					$this->sendMessage(Color::AQUA . "[AutoStopper] " . ($ik ? "서버가 " . $countdown . "초후 재시작됩니다." : "Server restarting in " . $countdown . "sec..."), $aettings["DisplayType"]);
				}
			}
		}elseif($this->tick > 0){
			$this->tick = 0;
		}
	}

	public function sendMessage($message, $type){
		switch(true){
			case (stripos($type, "1") !== false || stripos($type, "chat") !== false || stripos($type, "message") !== false):
				$this->getServer()->broadcastMessage($message);
			case (stripos($type, "2") !== false || stripos($type, "popup") !== false):
				$this->getServer()->broadcastPopup($message);
			case (stripos($type, "3") !== false || stripos($type, "tip") !== false):
				$this->getServer()->broadcastTip($message);
			break;
		}		
	}

	public function shutdown($message = "AutoStopper"){
		$this->sendMessage($message, 1);
		foreach($this->getServer()->getOnlinePlayers() as $player){
			$player->close($message, $message);
		}
		$this->getServer()->getPluginManager()->callEvent($ev = new \pocketmine\event\server\ServerCommandEvent(new \pocketmine\command\ConsoleCommandSender(), "stop"));
		$this->getServer()->shutdown();
	}
}