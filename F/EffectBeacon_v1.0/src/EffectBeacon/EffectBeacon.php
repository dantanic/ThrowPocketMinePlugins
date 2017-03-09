<?php

namespace EffecrBeacon;

use pocketmine\plugin\PluginBase;
use pocketmine\command\CommandSender;
use pocketmine\command\Command;
use pocketmine\utils\TextFormat;
use pocketmine\utils\Config;
use pocketmine\event\Listener;
use pocketmine\scheduler\CallbackTask;
use pocketmine\entity\Effect;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\Player;
use pocketmine\math\Vector3;

class EffectBeacon extends \pocketmine\plugin\PluginBase implements \pocketmine\event\Listener{
	const MODE_ADD = 0;
	const MODE_REMOVE = 2;
	const MODE_REMOVEMODE = 3;
	const MODE_BUILD = 4;
	const INFO_MODE = 0;
	const INFO_EFFECTS = 1;
	const EFFECT_ID = 0;
	const EFFECT_TIME = 0;

	private $data = [], $editTouch = [], $placed = [], $players = [], $effectList = [
		Effect::SPEED => ["SP", "FAST", "신속함", "신속"],
		Effect::SLOWNESS => ["SL", "SLOW", "구속함", "구속"],
		Effect::HASTE => ["HA", "SW", "SWIFT", "성급함", "성급"],
		Effect::FATIGUE => ["FA", "MI", "피로함", "피로"],
		Effect::STRENGTH => ["ST", "힘", "강화"],
		Effect::JUMP => ["JU", "점프강화", "점프", "가벼움"],
		Effect::NAUSEA => ["NA","CO", "멀미함", "멀미", "어지러움", "메스꺼움"],
		Effect::REGENERATION => ["RE", "HEAL", "HEALING", "재생함", "재생", "회복함", "회복"],
		Effect::DAMAGE_RESISTANCE => ["DAMAGERESISTANCE", "DA", "DAMAGE", "저항함", "저항"],
		Effect::FIRE_RESISTANCE => ["FIRERESISTANCE", "FI", "FIRE", "화염저항", "화염"],
		Effect::WATER_BREATHING => ["WATERBREATHING", "WA", "BREATHING", "BREATH", "GRILL", "수중호흡", "수중", "호흡", "아가미"],
		Effect::INVISIBILITY => ["IN", "INVISIBLE", "투명화", "투명"],
		Effect::BLINDNESS => ["BL", "BLIND", "실명"],
		Effect::NIGHT_VISION => ["NIGHTVISION", "NI", "NIGHT", "야간투시", "야간", "투시"],
		Effect::HUNGER => ["HU", "HUNGRY", "허기", "배고픔"],
		Effect::WEAKNESS => ["WE", "WEAK", "나약함", "나약", "허약함", "허약", "약함"],
		Effect::POISON => ["PO", "중독", "독", "감염"],
		Effect::WITHER => ["WI", "위더"],
		Effect::HEALTH_BOOST => ["HEALTHBOOST", "HE", "HEALTH", "HB", "체력신장", "체력증가", "체력추가", "체력"],
		Effect::ABSORPTION => ["AB", "DRAIN", "흡수", "보호"],
		Effect::SATURATION => ["SA", "포화", "배부름"]
	];

	public function onLoad(){
		if($this->isNewAPI()){
			$this->effects[Effect::BLINDNESS] = ["BL", "BLIND", "실명함", "실명"];
			$this->effects[Effect::NIGHT_VISION] = ["NIGHTVISION", "NI", "NIGHT", "야간투시", "야간"];
		}
	}

	public function onEnable(){
		$this->property = (new \ReflectionClass("\\pocketmine\\entity\\Entity"))->getProperty("effects");
		$this->property->setAccessible(true);
		$this->loadData();
		$this->getServer()->getPluginManager()->registerEvents($this,$this);
		$this->getServer()->getScheduler()->scheduleRepeatingTask(new Task($this),5);
	}

	public function onDisable(){
		$this->saveData();
	}

	public function getEffectName($name){
		if(!($effect = Effect::getEffectByName($name)) instanceof Effect){
			foreach([
			] as $k => $v){
				$name2 = $name;
				foreach($v as $v2) if(Effect::getEffect((int) $name2 = str_ireplace($v2, $k, $name2)) instanceof Effect) break;
				if(Effect::getEffect((int) $name2) instanceof Effect){
					$name = $name2;
					break;
				}
			}
			$effect = Effect::getEffect((int) $name);
		}
		return !$effect instanceof Effect ? null : strtoupper(["", "SPEED", "SLOWNESS", "HASTE", "FATIGUE", "STRENGTH", "", "", "JUMP", "NAUSEA", "REGENERATION", "DAMAGE_RESISTANCE", "FIRE_RESISTANCE", "WATER_BREATHING", "INVISIBILITY", "", "", "", "WEAKNESS", "POISON", "WITHER", "HEALTH_BOOST"][$effect->getID()]);
	}

	public function onCommand(CommandSender $sender,Command $command, $label,array $args){
		$config=$this->config;
		if($sender->isOp()){
			if(!isset($args[0])) return $sender->sendMessage(TextFormat::RED."[EffectBeacon] 도움말 : /Beacon [Add || Remove || Del || Set || Effects || List || Enabled]");
			switch(strtolower($args[0])){
				case "add":
				case "a":
					if(!isset($args[1]) || $args[1] == "" || !isset($args[2]) || $args[2] == "") return $sender->sendMessage(TextFormat::RED."[EffectBeacon] 도움말 : /Beacon Add(A) [Name] [EffectName] [Time] [Amplifier]");
					elseif(!isset($config[$args[1]])) return $sender->sendMessage(TextFormat::RED."[EffectBeacon] 그 신호기는 없습니다!");
					elseif(!($effect = $this->getEffectName($args[2]))) return $sender->sendMessage(TextFormat::RED."[EffectBeacon] 그 효과는 없습니다!".$effect);
					else{
						if(!isset($args[3]) || !is_numeric($args[3])) $args[3] = 10;
						if(!isset($args[4]) || !is_numeric($args[4])) $args[4] = 1;
						$config[$args[1]]["Effects"][$effect] = [$args[3], $args[4]];
						$sender->sendMessage(TextFormat::GREEN."[EffectBeacon] 효과를 추가하였습니다! [효과 이름 : $effect || 시간 : $args[3] || 강도 : $args[4]]");
					}
				break;
				case "remove":
				case "r":
					if(!isset($args[1]) || $args[1] == "") return $sender->sendMessage(TextFormat::RED."[EffectBeacon] 도움말 : /Beacon Remove(R) [Name]");
					elseif(!isset($config[$args[1]])) return $sender->sendMessage(TextFormat::RED."[EffectBeacon] 그 신호기는 없습니다!");
					else{
						unset($config[$args[1]]);
						$sender->sendMessage(TextFormat::GREEN."[EffectBeacon] 제거 완료!");
					}
				break;
				case "del":
				case "d":
					if(!isset($args[1]) || $args[1] == "" || !isset($args[2]) || $args[2] == "") return $sender->sendMessage(TextFormat::RED."[EffectBeacon] 도움말 : /Beacon Del(D) [Name] [EffectName]");
					elseif(!isset($config[$args[1]])) return $sender->sendMessage(TextFormat::RED."[EffectBeacon] 그 신호기는 없습니다!");
					elseif(!($effect = $this->getEffectName($args[2]))) return $sender->sendMessage(TextFormat::RED."[EffectBeacon] 그 효과는 없습니다!".$effect);
					else{
						unset($config[$args[1]]["Effects"][$effect]);
						$sender->sendMessage(TextFormat::GREEN."[EffectBeacon] 효과를 삭제 하였습니다!");
					}
				break;
				case "set":
				case "s":
					if(!isset($args[1]) || $args[1] == "") return $sender->sendMessage(TextFormat::RED."[EffectBeacon] 도움말 : /Beacon Set(S) [Name] [Distance]");
					elseif(isset($config[$args[1]])) return $sender->sendMessage(TextFormat::RED."[EffectBeacon] 그것은 이미 존재합니다!");
					else{
						if(!isset($args[2]) || !is_numeric($args[2])) $args[2] = 25;
						$this->addEffect[$sender->getName()] = [$args[1],$args[2]];
						$sender->sendMessage(TextFormat::GOLD."[EffectBeacon] 원하는 블럭을 터치하시면 자동으로 신호기가 추가됩니다");
					}
				break;
				case "effects":
				case "e":
					if(!isset($args[1])) return $sender->sendMessage(TextFormat::RED."[EffectBeacon] 도움말 : /Beacon Effects(E) [Name]");
					elseif(!isset($config[$args[1]])) return $sender->sendMessage(TextFormat::RED."[EffectBeacon] 그 신호기는 없습니다!");
					else{
						$sender->sendMessage(TextFormat::GREEN."----------- $args[1] ------------");
						$sender->sendMessage(TextFormat::GREEN."좌표 : ".$config[$args[1]]["XYZ"]);
						$sender->sendMessage(TextFormat::GREEN."거리 : ".$config[$args[1]]["Distance"]);
						$sender->sendMessage(TextFormat::GREEN."활성화 상태 : ".($config[$args[1]]["Enabled"] ? "켜짐" : "꺼짐"));
						foreach($config[$args[1]]["Effects"] as $i => $j) if(count($config[$args[1]]["Effects"] > 0)) $sender->sendMessage(TextFormat::GREEN."포션 => 시간(강도) : $i => ".$j[0]."(".$j[1].") \n");
					}
				break;
				case "list":
				case "l":
					$page = 1;
					if(isset($args[1]) && is_numeric($args[1])) $page = max(floor($args[1]),1);
					$list = array_chunk($config,5,true);
					if($page >= ($c = count($list))) $page = $c;
					$sender->sendMessage(TextFormat::YELLOW."[EffectBeacon] 신호기 리스트 (페이지 : $page/$c \n");
					$num = ($page-1)*5;
					if($c > 0){
						foreach($list[$page-1] as $k=>$v){
							$num++;
							$sender->sendMessage(TextFormat::GREEN."[$num] $k / 좌표 : ".$config[$k]["XYZ"]);
						}
					}
				break;
				case "enabled":
				case "enable":
				case "en":
					if(!isset($args[1]) || $args[1] == "") return $sender->sendMessage(TextFormat::RED."[EffectBeacon] 도움말 : /Beacon Enabled(EN) [Name] [On || Off]");
					elseif(!isset($config[$args[1]])) return $sender->sendMessage(TextFormat::RED."[EffectBeacon] 그 신호기는 없습니다!");
					else{
						if(!isset($args[2]) || !is_numeric($args[2])) $args[2] = $config[$args[1]]["Enabled"] ? "Off" : "On";
						if(strtolower($args[2]) == "on"){
							if($config[$args[1]]["Enabled"]) $sender->sendMessage(TextFormat::RED."[EffectBeacon] 이미 신호기가 켜져있는 상태입니다");
							else{
								$config[$args[1]]["Enabled"] = true;
								$sender->sendMessage(TextFormat::GREEN."[EffectBeacon] 신호기를 켰습니다");
							}
						}elseif(strtolower($args[2]) == "off"){
							if(!$config[$args[1]]["Enabled"]) $sender->sendMessage(TextFormat::RED."[EffectBeacon] 이미 신호기가 꺼져있는 상태입니다");
							else{
								$config[$args[1]]["Enabled"] = false;
								$sender->sendMessage(TextFormat::GREEN."[EffectBeacon] 신호기를 껐습니다");
							}
						}else{
							return $sender->sendMessage(TextFormat::RED."[EffectBeacon] 도움말 : /Beacon Enabled(EN) [Name] [On || Off]");
						}
					}
				break;
				default:
					return $sender->sendMessage(TextFormat::RED."[EffectBeacon] 도움말 : /Beacon [Add || Remove || Del || Set || Effects || List || Enabled]");
				break;
			}
		}else $sender->sendMessage("You are not op!");
		if($this->config !== $config){
			$this->config = $config;
			$this->saveYml();
		}
	}

	public function onTouch(PlayerInteractEvent $ev){
		$player = $ev->getPlayer();
		$block = $ev->getBlock();
		$config = $this->config;
		$xyz = $block->x.":".$block->y.":".$block->z.":".$block->getLevel()->getFolderName();
		if(isset($this->addEffect[$player->getName()])){
			$already = false;
			foreach($config as $k=>$v){
				if($xyz == $config[$k]["XYZ"]){
					$already = true;
					$player->sendMessage(TextFormat::RED."[EffectBeacon] 그곳은 이미 신호기가 존재합니다");
					break;
				}
			}
			if(!$already){
				$config[$this->addEffect[$player->getName()][0]] = ["XYZ" => $xyz, "Distance" => $this->addEffect[$player->getName()][1], "Effects" => [], "Enabled" => false];
				$player->sendMessage(TextFormat::GOLD."[EffectBeacon] 추가완료! [이름 : ".$this->addEffect[$player->getName()][0].", 거리 : ".$this->addEffect[$player->getName()][1]." ]");
				$this->config=$config;
				$this->saveYml();
			}
			unset($this->addEffect[$player->getName()]);
		}
	}

	public function playerEffect(){
		$config = $this->config;
		foreach($this->getServer()->getOnlinePlayers() as $players){
			foreach($config as $k => $v){
				if($v["Enabled"] && $players->getLevel()->getFolderName() == explode(":",$v["XYZ"])[3] && $players->distance((new Vector3(...explode(":",$v["XYZ"])))->add(0.5, 1, 0.5)) < $v["Distance"] - 0.5){
					foreach($v["Effects"] as $i => $j){
						if(($effect = Effect::getEffectByName($i)) !== null){
							if($players->hasEffect($id = $effect->getID())){
								$oldEffect = $players->getEffect($id);
								if($effect->getAmplifier() > $oldEffect->getAmplifier() || $effect->getDuration() < $oldEffect->getDuration()){
									$oldEffect->setAmplifier($j[1] - 1)->setDuration($j[0]*20 - 0.1)->add($players, true);
									$effects = $this->property->getValue($players);
									$effects[$oldEffect->getID()] = $oldEffect;
									$this->property->setValue($players, $effects);
								}
							}else{
								$players->addEffect($effect->setAmplifier($j[1] - 1)->setDuration($j[0]*20));		
							}
						}
					}
				}
			}
		}
	}

	public function loadYml(){
		@mkdir($this->getDataFolder());
		$this->config = (new Config($this->getDataFolder()."effect.yml",Config::YAML))->getAll();
	}

	public function saveYml(){
		$config = new Config($this->getDataFolder()."effect.yml",Config::YAML);
		$config->setAll($this->config);
		$config->save();
	}


	public function pos2str(\pocketmine\level\Position $pos){
		return floor($pos->x) . ":" . floor($pos->y) . ":" . floor($pos->z) . ":" . $pos->getLevel()->getFolderName();
	}

	public function loadData(){
		@mkdir($folder = $this->getDataFolder());
		if(!file_exists($path = $folder . "CommandBlocks.sl")){	
			file_put_contents($path, serialize([]));
		}
		$this->data = unserialize(file_get_contents($path));
	}

	public function saveData(){
		@mkdir($folder = $this->getDataFolder());
		file_put_contents($folder . "CommandBlocks.sl", serialize($this->data));
	}

	public function isKorean(){
		return $this->getServer()->getLanguage()->getName() == "\"한국어\"";
	}

	public function isNewAPI(){
		return $this->getServer()->getApiVersion() !== "1.12.0";
	}
}