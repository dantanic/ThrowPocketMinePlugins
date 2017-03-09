<?php

namespace Clothes;

use pocketmine\utils\TextFormat as Color;
use pocketmine\Player;
use pocketmine\event\TranslationContainer as Translation;
use Clothes\event\ClothesAddEvent;
use Clothes\event\ClothesRemoveEvent;
use Clothes\event\ClothesGiveEvent;
use Clothes\event\ClothesTakeEvent;
use Clothes\event\ChangeClothesPriorityEvent;
use Clothes\event\PlayerDressUpEvent;

class ClothesAPI extends \pocketmine\plugin\PluginBase implements \pocketmine\event\Listener{
	const CLOTHES = 0;
	const PLAYERS = 1;
	const SMALL_SKIN = 8192; // 64*32*4
	const BIG_SKIN = 16384; // 64*64*4

	protected static $instance = null, $emptySkin = "\x01";

	protected $data = [], $players = [];

	public static function getInstance(){
		return self::$instance; 		
	}

	public static function getEmptySkin(){
		return self::$emptySkin; 		
	}

	public function onLoad(){
		if(self::$instance == null){
			self::$instance = $this;
		}
		if(self::$emptySkin == "\x01"){
			for($i = 1; $i < 15; $i++){
				self::$emptySkin .= self::$emptySkin;
			}
		}
 	}

 	public function onEnable(){
		$this->loadData();
		$this->players = [];
		foreach($this->getServer()->getOnlinePlayers() as $player){
			$this->players[$playerName = strtolower($player->getName())] = $player->getSkinData();
			if(isset($this->data[self::PLAYERS][$playerName])){
				$this->dressUp($player);
			}
		}
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
	}

	public function onDisable(){
		$this->saveData();
 		foreach($this->getServer()->getOnlinePlayers() as $player){
			if(isset($this->players[$playerName = strtolower($player->getName())])){
				$player->setSkin($this->players[$playerName], false);
			}
		}
	}

	public function onCommand(\pocketmine\command\CommandSender $sender, \pocketmine\command\Command $cmd, $label, array $sub){
		if(!isset($sub[0]) || $sub[0] == ""){
			return false;
		}else{
			$ik = $this->isKorean();
			if(($cmd = strtolower($cmd->getName())) == "clothes"){
				if(!($isApiCommand = $sender->hasPermission("clothes.cmd") && in_array(strtolower($sub[0]), ["add", "a", "추가", "remove", "del", "delete", "제거", "give", "g", "지급", "take", "회수", "view", "v", "보기", "reload", "save", "reset", "리셋", "초기화"])) && !($sender instanceof Player)){
					$r = Color::RED . "[Clothes] " . ($ik ? "게임 내에서만 사용해주세요." : "Please run this command in game");
				}else{
					switch(strtolower($sub[0])){
						case "dress":
						case "d":
						case "입기":
							if(!$sender->hasPermission("clothes.cmd.user.dress")){
								$r = new Translation(Color::RED . "%commands.generic.permission");
							}elseif(!isset($sub[1]) || $sub[1] == ""){
								$r = Color::RED . "Usage: /Clothes Dress(D) " . ($ik ? "<옷이름> (우선도)" : "<ClothesName> (Priority)");
							}elseif(!$this->clothesExists($sub[1])){
 								$r = Color::RED . "[Clothes] $sub[1]" . ($ik ? "은(는) 잘못된 옷 이름입니다." : " is invaild clothing name");
							}elseif(empty($this->getAllPlayerClothes($playerName = strtolower($sender->getName())))){
								$r = Color::RED . "[Clothes] " . ($ik ? "당신은 옷이 하나도 없습니다." : "You don't have any clothes");
							}elseif(!$this->hasClothes($playerName, $sub[1] = $this->getExactName($sub[1], false, $playerName))){
								$r = Color::RED . "[Clothes] " . ($ik ? "당신은 $sub[1]" . Color::RESET . Color::RED . "(이)라는 옷을 가지고있지않습니다." : "You don't have $sub[1]");
							}else{
								$this->setPriority($playerName, $sub[1], $sub[2] = isset($sub[2]) && is_numeric($sub[2]) && $sub[2] >= 1 ? floor($sub[2]) : 1);
								$r = Color::YELLOW . "[Clothes] " . ($ik ? "당신은 $sub[1]" . Color::RESET . Color::YELLOW . "을(를) 입었습니다. 우선도 : " : "You dressed $sub[1]" . Color::RESET . Color::YELLOW . ". Priority : ") . $sub[2];
							}
						break;
						case "undress":
						case "u":
						case "벗기":
							if(!$sender->hasPermission("clothes.cmd.user.undress")){
								$r = new Translation(Color::RED . "%commands.generic.permission");
							}elseif(!isset($sub[1]) || $sub[1] == ""){
								$r = Color::RED . "Usage: /Clothes Undress(U) " . ($ik ? "<옷이름>" : "<ClothesName>");
							}elseif(!$this->clothesExists($sub[1])){
 								$r = Color::RED . "[Clothes] $sub[1]" . ($ik ? "은(는) 잘못된 옷 이름입니다." : " is invaild clothing name");
							}elseif(empty($this->getAllPlayerClothes($playerName = strtolower($sender->getName())))){
								$r = Color::RED . "[Clothes] " . ($ik ? "당신은 옷이 하나도 없습니다." : "You don't have any clothes");
							}elseif(!$this->hasClothes($playerName, $sub[1] = $this->getExactName($sub[1], false, $playerName))){
								$r = Color::RED . "[Clothes] " . ($ik ? "당신은 $sub[1]" . Color::RESET . Color::RED . "(이)라는 옷을 가지고있지않습니다." : "You don't have $sub[1]");
							}elseif(!$this->isDress($playerName, $sub[1])){
								$r = Color::RED . "[Clothes] " . ($ik ? "당신은 이미 $sub[1]" . Color::RESET . Color::RED . "을(를) 벗고 있습니다." : "You are already not wearing $sub[1]");
							}else{
								$this->setPriority($playerName, $sub[1], 0);
								$r = Color::YELLOW . "[Clothes] " . ($ik ? "당신은 $sub[1]" . Color::RESET . Color::YELLOW . "을(를) 벗었습니다." : "You undressed $sub[1]");
							}
						break;
						case "throwaway":
						case "throw":
						case "ta":
						case "t":
						case "버리기":
							if(!$sender->hasPermission("clothes.cmd.user.remove")){
								$r = new Translation(Color::RED . "%commands.generic.permission");
							}elseif(!isset($sub[1]) || $sub[1] == ""){
								$r = Color::RED . "Usage: /Clothes ThrowAway(T) " . ($ik ? "<옷이름>" : "<ClothesName>");
							}elseif(!$this->clothesExists($sub[1])){
 								$r = Color::RED . "[Clothes] $sub[1]" . ($ik ? "은(는) 잘못된 옷 이름입니다." : " is invaild clothing name");
							}elseif(empty($this->getAllPlayerClothes($playerName = strtolower($sender->getName())))){
								$r = Color::RED . "[Clothes] " . ($ik ? "당신은 옷이 하나도 없습니다." : "You don't have any clothes");
							}elseif(!$this->hasClothes($playerName, $sub[1] = $this->getExactName($sub[1], false, $playerName))){
								$r = Color::RED . "[Clothes] " . ($ik ? "당신은 $sub[1]" . Color::RESET . Color::RED . "(이)라는 옷을 가지고있지않습니다." : "You don't have $sub[1]");
							}else{
								$this->takeClothes($playerName, $sub[1]);
								$r = Color::YELLOW . "[Clothes] " . ($ik ? "당신은 $sub[1]" . Color::RESET . Color::YELLOW . "을(를) 버렸습니다." : "You throw away the $sub[1]");
							}
						break;
						case "reverse":
						case "r":
						case "거꾸로":
						case "뒤집기":
							if(!$sender->hasPermission("clothes.cmd.user.reverse")){
								$r = new Translation(Color::RED . "%commands.generic.permission");
							}elseif(empty($this->getAllPlayerClothes($playerName = strtolower($sender->getName())))){
								$r = Color::RED . "[Clothes] " . ($ik ? "당신은 옷이 하나도 없습니다." : "You don't have any clothes");
							}else{
								$this->sortPriority($playerName, true);
								$r = Color::YELLOW . "[Clothes] " . ($ik ? "당신은 당신의 옷의 우선도를 거꾸로 정렬했습니다." : "You reverse sort your clothes priority.");
							}
						break;
						case "list":
						case "l":
						case "목록":
						case "리스트":
							if(!$sender->hasPermission("clothes.cmd.user.list")){
								$r = new Translation(Color::RED . "%commands.generic.permission");
							}elseif(empty($this->getAllPlayerClothes($playerName = strtolower($sender->getName())))){
								$r = Color::RED . "[Clothes] " . ($ik ? "당신은 옷이 하나도 없습니다." : "You don't have any clothes");
							}else{
								$this->sortPriority($playerName);
								asort($this->data[self::PLAYERS][$playerName]);
								$undressList = [];
								$r = Color::YELLOW . "[Clothes] " . ($ik ? "옷 목록 (페이지" : "Clothes List (Page") . ($page = $page = min(count($lists = array_chunk($this->data[self::PLAYERS][$playerName], 5)), isset($sub[1]) && is_numeric($sub[1]) && isset($lists[$sub[1] - 1]) ? $sub[1] : 1)) . "/" . count($lists) . ") (" . count($this->data[self::PLAYERS][$playerName]) . ")";
								if(isset($lists[--$page])){
									$indexs = $this->getAllPlayerClothes($playerName);
									$index = 0;
									foreach($lists[$page] as $key => $priority){
										if($priority == 0){
											$undressList[$indexs[$index = $page * 5 + $key]] = $priority;
										}else{
											$r .= "\n" . Color::GOLD . "    [" . (($index = $page * 5 + $key) + 1 - count($undressList)) . "]" . Color::RED . ($ik ? "[입음] " : "[Dress] ") . Color::GOLD . $indexs[$index] . Color::RESET . Color::GOLD . ", " . ($ik ? "우선도: " : "Priority: ") . $priority;
										}
									}
									foreach($undressList as $clothesName => $priority){
										$r .= "\n" . Color::GOLD . "    [" . ((++$index) + 1 - count($undressList)) . "]" . $clothesName;
									}
								}
							}
						break;
						case "help":
						case "?":
						case "도움말":
							$r = Color::YELLOW . "[Clothes] " . ($ik ? "도움말" : "Help") . Color::AQUA . "  /Clothes <Dress|Undress|ThrowAway|Reverse|List| Help>" ;
							$r .= "\n  " . Color::GOLD . "Dress(D) " . ($ik ? "<옷이름> (우선도) : " . Color::GREEN . "옷을 입습니다." : "<ClothesName> (Priority) : " . Color::GREEN . "Dress clothes");
							$r .= "\n  " . Color::GOLD . "Undress(U) " . ($ik ? "<옷이름> : " . Color::GREEN . "옷을 벗습니다." : "<ClothesName> : " . Color::GREEN . "Undress clothes");
							$r .= "\n  " . Color::GOLD . "ThrowAway(T) " . ($ik ? "<옷이름> : " . Color::GREEN . "옷을 버립니다." : "<ClothesName> : " . Color::GREEN . "Throw away the clothes");
							$r .= "\n  " . Color::GOLD . "Reverse(R) : " . ($ik ? Color::GREEN . "옷의 우선도를 거꾸로 정렬합니다." : Color::GREEN . "Reverse sort clothes priority");
							$r .= "\n  " . Color::GOLD . "List(L) " . ($ik ? "<페이지> : " . Color::GREEN . "당신이 소유한 옷의 목록을 보여줍니다." : "<Page> : " . Color::GREEN . "Shows a list of you have clothing");
							$r .= "\n  " . Color::GOLD . "Help(?) : " . Color::GREEN . ($ik ? "도움말을 보여줍니다." : "Shows the help message.");
 						break;
						default:
							if($isApiCommand){
								$cmd = "clothesapi";
							}else{
								return false;
							}
						break;
					}
				}
			}
			if($cmd == "clothesapi"){
	 			switch(strtolower($sub[0])){

	 				// Not uesd
					case "loadold":
						@mkdir($folder = $this->getServer()->getDataPath() . "plugins/Clothes/");
						if(!file_exists($path = $folder . "OldClothes.sl")){
							$r = Color::RED . "[Clothes] /PocketMine/plugins/Clothes/OldClothes.sl" . ($ik ? "이 없습니다." : " is not exists.");
						}else{
							$sender->sendMessage(Color::YELLOW . "[Clothes] " . ($ik ? "기존 데이터를 로드합니다." : "Load the old data"));
							if(!empty($clothesList = unserialize(file_get_contents($path)))){
								foreach($clothesList as $clothesName => $clothesData){
									if(!$this->clothesExists($clothesName, true)){
										$this->addClothes($clothesName, $clothesData[0x00], "\x02");
										$sender->sendMessage(Color::YELLOW . "  [Clothes] $clothesName" . ($ik ? "이 로드완료" : " is loaded"));
									}else{
										$sender->sendMessage(Color::YELLOW . "  [Clothes] $clothesName" . ($ik ? "이 로드실패 : 이미 존재" : " is load failed : Already Exists"));											
									}
								}
							}
							$r = Color::RED . "[Clothes] " . ($ik ? "로드 완료." : "load complete");
						}
					break;

					case "add":
					case "a":
					case "추가":
						if(!$sender->hasPermission("clothes.cmd.add")){
							$r = new Translation(Color::RED . "%commands.generic.permission");
						}elseif(!isset($sub[1]) || Color::clean($sub[1]) == ""){
							$r = Color::RED . "Usage: /ClothesAPI Add(A) " . ($ik ? "<옷이름>" : "<ClothesName>");
						}elseif($this->clothesExists($sub[1], false, null, true)){
							$r = Color::RED . "[Clothes] " . $this->getExactName($sub[1]) . Color::RESET . Color::RED . ($ik ? "은(는) 이미 존재하는 옷입니다." : " is already exists clothing");
						}elseif(!($sender instanceof Player)){
							$r = Color::RED . "[Clothes] " . ($ik ? "게임 내에서만 사용해주세요." : "Please run this command in game");
						}else{
							$this->addClothes($sub[1], $sender->getSkinData());
							$r = Color::YELLOW . "[Clothes] " . ($ik ? "옷을 추가했습니다. " : "Added clothing");
						}
					break;
					case "remove":
					case "r":
					case "del":
					case "delete":
					case "d":
					case "제거":
						if(!$sender->hasPermission("clothes.cmd.remove")){
							$r = new Translation(Color::RED . "%commands.generic.permission");
						}elseif(!isset($sub[1]) || $sub[1] == ""){
							$r = Color::RED . "Usage: /ClothesAPI Remove(R) " . ($ik ? "<옷이름>" : "<ClothesName>");
						}elseif(!$this->clothesExists($sub[1])){
 							$r = Color::RED . "[Clothes] $sub[1] " . ($ik ? "은(는) 잘못된 옷 이름입니다." : " is invaild clothing name");
						}else{
							$this->removeClothes($sub[1] = $this->getExactName($sub[1]), true);
							$r = Color::YELLOW . "[Clothes] " . ($ik ? $sub[1] . Color::RESET . Color::YELLOW . "을(를) 제거하였습니다." : "Removed the $sub[1]");
						}
					break;
					case "give":
					case "g":
					case "지급":
						if(!$sender->hasPermission("clothes.cmd.give")){
							$r = new Translation(Color::RED . "%commands.generic.permission");
						}elseif(!isset($sub[2]) || $sub[1] == "" || $sub[2] == ""){
							$r = Color::RED . "Usage: /ClothesAPI Give(G) " . ($ik ? "<플레이어명> <옷이름>" : "<PlayerName> <ClothesName>");
						}elseif(!($player = $this->getServer()->getPlayer($sub[1])) instanceof Player && !isset($this->data[self::PLAYERS][$sub[1] = strtolower($sub[1])])){
							$r = Color::RED . "[Clothes] $sub[1]" . ($ik ? "은(는) 잘못된 플레이어명입니다." : " is invaild player name");							
						}elseif(!$this->clothesExists($sub[2])){
							$r = Color::RED . "[Clothes] $sub[2]" . ($ik ? "은(는) 잘못된 옷 이름입니다." : " is invaild clothing name");
						}elseif(isset($this->data[self::PLAYERS][$sub[1] = $player instanceof Player ? strtolower($player->getName()) : $sub[1]][$sub[2] = $this->getExactName($sub[2])])){
							$r = Color::RED . "[Clothes] $sub[1]" . ($ik ? "은(는) $sub[2]" . Color::RESET . Color::RED . "을(를) 이미 가지고 있습니다." : " is already have the $sub[2]");							
						}else{
							$this->giveClothes($sub[1], $sub[2]);
							if($sender === $player){
								$r = Color::YELLOW . "[Clothes] " . ($ik ? "당신은 $sub[2]" . Color::RESET . Color::YELLOW . "을(를) 스스로에게 지급하셨습니다." : "You gave $sub[2]" . Color::RESET . Color::YELLOW . " to yourself");
							}else{
								$r = Color::YELLOW . "[Clothes] " . ($ik ? "당신은 $sub[2]" . Color::RESET . Color::YELLOW . "을(를) $sub[1]에게 지급하셨습니다." : "You gave $sub[2]" . Color::RESET . Color::YELLOW . " to $sub[1]");
								if($player instanceof Player){
									$player->sendMessage(Color::YELLOW . "[Clothes] " . ($ik ? "당신은 $sub[2]" . Color::RESET . Color::YELLOW . "을(를) 지급받으셨습니다." : "You get $sub[2]"));
								}
							}
						}
					break;
					case "take":
					case "t":
					case "회수":
						if(!$sender->hasPermission("clothes.cmd.take")){
							$r = new Translation(Color::RED . "%commands.generic.permission");
						}elseif(!isset($sub[2]) || $sub[1] == "" || $sub[2] == ""){
							$r = Color::RED . "Usage: /ClothesAPI Take(T) " . ($ik ? "<플레이어명> <옷이름> (페이지)" : "<PlayerName> <ClothesName> (Page)");
						}elseif(!($player = $this->getServer()->getPlayer($sub[1])) instanceof Player && !isset($this->data[self::PLAYERS][$sub[1] = strtolower($sub[1])])){
							$r = Color::RED . "[Clothes] $sub[1]" . ($ik ? "은(는) 잘못된 플레이어명입니다." : " is invaild player name");							
						}elseif(empty($this->getAllPlayerClothes($sub[1] = $player instanceof Player ? strtolower($player->getName()) : $sub[1]))){
							$r = Color::RED . "[Clothes] $sub[1]" . ($ik ? "은(는) 옷이 하나도 없습니다." : " is don't have any clothes");
						}elseif(!$this->clothesExists($sub[2])){
							$r = Color::RED . "[Clothes] $sub[2]" . ($ik ? "은(는) 잘못된 옷 이름입니다." : " is invaild clothing name");
						}elseif(!isset($this->data[self::PLAYERS][$sub[1]][$sub[2] = $this->getExactName($sub[2])])){
							$r = Color::RED . "[Clothes] $sub[1]" . ($ik ? "은(는) $sub[2]" . Color::RESET . Color::RED . "을(를) 가지고 있지않습니다." : " is not have the $sub[2]");							
						}else{
							$this->takeClothes($sub[1], $sub[2]);
							if($sender === $player){
								$r = Color::YELLOW . "[Clothes] " . ($ik ? "당신은 $sub[2]" . Color::RESET . Color::YELLOW . "을(를) 스스로에게서 빼았으셨습니다." : "You took $sub[2]" . Color::RESET . Color::YELLOW ." from yourself");
							}else{
								$r = Color::YELLOW . "[Clothes] " . ($ik ? "당신은 $sub[2]" . Color::RESET . Color::YELLOW . "을(를) $sub[1]에게서 빼았으셨습니다." : "You took $sub[2]" . Color::RESET . Color::YELLOW ." from $sub[1]");
								if($player instanceof Player){
									$player->sendMessage(Color::YELLOW . "[Clothes] " . ($ik ? "당신은 $sub[2]" . Color::RESET . Color::YELLOW . "을(를) 빼았겼습니다." : $sub[2] . Color::RESET . Color::YELLOW . " was taken from you."));
								}
							}
						}
					break;
					case "view":
					case "v":
					case "보기":
						if(!$sender->hasPermission("clothes.cmd.view")){
							$r = new Translation(Color::RED . "%commands.generic.permission");
						}elseif(!isset($sub[1]) || $sub[1] == ""){
							$r = Color::RED . "Usage: /ClothesAPI View(V) " . ($ik ? "<플레이어명>" : "<PlayerName>");
						}elseif(!($player = $this->getServer()->getPlayer($sub[1])) instanceof Player && !isset($this->data[self::PLAYERS][$sub[1] = strtolower($sub[1])])){
							$r = Color::RED . "[Clothes] $sub[1]" . ($ik ? "은(는) 잘못된 플레이어명입니다." : " is invaild player name");							
						}elseif(empty($this->getAllPlayerClothes($sub[1] = $player instanceof Player ? strtolower($player->getName()) : $sub[1]))){
							$r = Color::RED . "[Clothes] $sub[1]" . ($ik ? "은(는) 옷이 하나도 없습니다." : " is don't have any clothes");
						}else{
							$this->sortPriority($sub[1]);
							asort($this->data[self::PLAYERS][$sub[1]]);
							$undressList = [];
							$r = Color::YELLOW . "[Clothes] $sub[1]" . ($ik ? "님의 옷 목록 (페이지" : "'s Clothes List (Page") . ($page = $page = min(count($lists = array_chunk($this->data[self::PLAYERS][$sub[1]], 5)), isset($sub[2]) && is_numeric($sub[2]) && isset($lists[$sub[2] - 1]) ? $sub[2] : 1)) . "/" . count($lists) . ") (" . count($this->data[self::PLAYERS][$sub[1]]) . ")";
							if(isset($lists[--$page])){
								$indexs = $this->getAllPlayerClothes($sub[1]);
								$index = 0;
								foreach($lists[$page] as $key => $priority){
									if($priority == 0){
										$undressList[$indexs[$index = $page * 5 + $key]] = $priority;
									}else{
										$r .= "\n" . Color::GOLD . "    [" . (($index = $page * 5 + $key) + 1 - count($undressList)) . "]" . Color::RED . ($ik ? "[입음] " : "[Dress] ") . Color::GOLD . $indexs[$index] . Color::RESET . Color::GOLD . ", " . ($ik ? "우선도: " : "Priority: ") . $priority;
									}
								}
								foreach($undressList as $clothesName => $priority){
									$r .= "\n" . Color::GOLD . "    [" . ((++$index) + 1 - count($undressList)) . "]" . $clothesName;
								}
							}
						}
					break;
					case "list":
					case "l":
					case "목록":
						if(!$sender->hasPermission("clothes.cmd.list")){
							$r = new Translation(Color::RED . "%commands.generic.permission");
						}else{
							ksort($this->data[self::CLOTHES]);
 							$r = Color::YELLOW . "[Clothes] " . ($ik ? "옷 목록 (페이지" : "Clothes List (Page") . ($page = $page = min(count($lists = array_chunk($this->data[self::CLOTHES], 5)), isset($sub[1]) && is_numeric($sub[1]) && isset($lists[$sub[1] - 1]) ? $sub[1] : 1)) . "/" . count($lists) . ") (" . count($this->data[self::CLOTHES]) . ")";
							if(isset($lists[--$page])){
								$indexs = array_keys($this->data[self::CLOTHES]);
								foreach($lists[$page] as $key => $clothes){
									$r .= "\n" . Color::GOLD . "    [" . (($index = $page * 5 + $key) + 1) . "] $indexs[$index]" . Color::RESET . Color::GOLD;
								}
							}
						}
					break;
					case "reload":
						if(!$sender->hasPermission("clothes.cmd.reload")){
							$r = new Translation(Color::RED . "%commands.generic.permission");
						}else{
							$this->loadData();
							$r = Color::YELLOW . "[Clothes] " . ($this->isKorean() ? "데이터를 로드했습니다." : "Load thedata");
						}
					break;
					case "save":
						if(!$sender->hasPermission("clothes.cmd.save")){
							$r = new Translation(Color::RED . "%commands.generic.permission");
						}else{
							$this->saveData();
							$r = Color::YELLOW . "[Clothes] " . ($this->isKorean() ? "데이터를 저장했습니다." : "Save the data");
						}
					break;
					case "reset":
					case "리셋":
					case "초기화":
						if(!$sender->hasPermission("clothes.cmd.reset")){
							$r = new Translation(Color::RED . "%commands.generic.permission");
						}else{
							foreach($this->data[self::CLOTHES] as $clothesName => $clothes){
								$this->removeClothes($clothesName);
							}
							$r = Color::YELLOW . "[Clothes] " . ($ik ? "당신은 모든 데이터를 지웠습니다." : "You reset the all data");
						}
					break;
					case "help":
					case "?":
					case "도움말":
						$r = Color::YELLOW . "[Clothes] " . ($ik ? "도움말" : "Help") . Color::AQUA . "  /ClothesAPI <Add|Remove|List|Give|Take|View|Reset | Help>";
						$r .= "\n  " . Color::GOLD . "Add(A) " . ($ik ? "<옷이름> : " . Color::GREEN . "옷을 추가합니다." : "<ClothesName> : " . Color::GREEN . "Add clothes");
						$r .= "\n  " . Color::GOLD . "Remove(R) " . ($ik ? "<옷이름> : " . Color::GREEN . "옷을 제거합니다." : "<ClothesName> : " . Color::GREEN . "Delete clothes");
 						$r .= "\n  " . Color::GOLD . "Give(G) " . ($ik ? "<플레이어명> <옷이름> : " . Color::GREEN . "플레이어에게 옷을 지급합니다." : "<PlayerName> <ClothesName> : " . Color::GREEN . "Gives player a clothing");
						$r .= "\n  " . Color::GOLD . "Take(T) " . ($ik ? "<플레이어명> <옷이름> : " . Color::GREEN . "플레이어의 옷을 빼앗습니다." : "<PlayerName> <ClothesName> : " . Color::GREEN . "Takes clothes of player's");
						$r .= "\n  " . Color::GOLD . "View(V) " . ($ik ? "<플레이어명> (페이지) : " . Color::GREEN . "플레이어의 옷 목록을 보여줍니다." : "<PlayerName> (Page) : " . Color::GREEN . "Shows a list of clothes in the player's clothes");
						$r .= "\n  " . Color::GOLD . "List(L) " . ($ik ? "(페이지) : " . Color::GREEN . "모든 옷의 목록을 보여줍니다." : "(Page) : " . Color::GREEN . "Shows a list of all clothes");
						$r .= "\n  " . Color::GOLD . "Reload : " . Color::GREEN . ($ik ?  "데이터를 리로드합니다." : "Reloade the data");
						$r .= "\n  " . Color::GOLD . "Save : " . Color::GREEN . ($ik ?  "데이터를 저장합니다." : "Save the data");
						$r .= "\n  " . Color::GOLD . "Reset " . Color::GREEN . ": " . ($ik ? "데이터를 리셋합니다." : "Reset the data.");
						$r .= "\n  " . Color::GOLD . "Help(?) : " . Color::GREEN . ($ik ? "도움말을 보여줍니다." : "Shows the help message.");
 					break;				
					default:
						return false;
					break;
				}
			}
			if(isset($r)){
				$sender->sendMessage($r);
			}
			return true;
		}
	}


	public function onDataPacketReceive(\pocketmine\event\server\DataPacketReceiveEvent $event){
		$packet = $event->getPacket();
		if($packet->pid() == \pocketmine\network\protocol\LoginPacket::NETWORK_ID){
			$this->players[strtolower(Color::clean($packet->username))] = $this->skin2BigSkin($packet->skin);
		}
 	}
 
	public function onPlayerJoin(\pocketmine\event\player\PlayerJoinEvent $event){
		$this->dressUp($event->getPlayer());
	}

	public function addClothes($clothesName, $clothes, $ignore = "\x01"){
		if(!$this->clothesExists($clothesName, true)){
			$this->getServer()->getPluginManager()->callEvent($event = new ClothesAddEvent($this, $clothesName, $clothes));
			if($event->isCancelled()){
				return $event->getCancelMessage();
			}else{
				$this->data[self::CLOTHES][$clothesName] = $this->skin2Clothes($clothes, $ignore);
				if(empty($this->data[self::CLOTHES][$clothesName])){
					unset($this->data[self::CLOTHES][$clothesName]);
				}
			}
		}
	}
			
	public function removeClothes($clothesName, $isExact = true){
		if($this->clothesExists($clothesName, $isExact)){
			$clothesName = $this->getExactName($clothesName);
			foreach($this->data[self::PLAYERS] as $playerName => $clothesList){
				if(isset($clothesList[$clothesName])){
					if($clothesList[$clothesName] >= 1){
						unset($this->data[self::PLAYERS][$playerName][$clothesName]);
						if(($player = $this->getServer()->getPlayerExact($playerName)) instanceof Player){
							$this->dressUp($player);
						}
					}else{
						unset($this->data[self::PLAYERS][$playerName][$clothesName]);
					}
				}
			}
			unset($this->data[self::CLOTHES][$clothesName]);
		}
	}

	public function getAllClothes(){
		return array_keys($this->data[self::CLOTHES]);
	}

	public function clothesExists($checkName, $isExact = false, $playerName = null, $ignoreCho = false){
		return !empty($this->getExactName($checkName, $isExact, $playerName, $ignoreCho));
	}

	public function getClothes($clothesName, $isExact = false){
		return $this->clothesExists($clothesName, $isExact) ? $this->data[self::CLOTHES][$this->getExactName($clothesName)] : null;
	}

	public function getExactName($checkName, $isExact = false, $playerName = null, $ignoreCho = false){
		$isPlayerSearch = $playerName !== null && isset($this->data[self::PLAYERS][$playerName]) && !empty($this->data[self::PLAYERS][$playerName]);
 		if(is_string($checkName)){
			if(isset($this->data[self::CLOTHES][$checkName]) || isset($this->data[self::CLOTHES][$checkName = strtoupper($checkName)]) || isset($this->data[self::CLOTHES][$checkName = strtolower($checkName)])){
				return $checkName;
			}else{
				$check = null;
				$checkName = Color::clean($checkName);
				$isOnlyCho = $this->isOnlyCho($checkName) && !$ignoreCho;
				foreach(($isPlayerSearch ? $this->data[self::PLAYERS][$playerName] : $this->data[self::CLOTHES]) as $clothesName => $clothes){
					if($checkName == ($cleanName = strtolower(Color::clean($clothesName)))){
						return $clothesName;
					}elseif(!$isExact){
						if((stripos($cleanName, $checkName) === 0 && ($check == null || strlen($check) < strlen($clothesName))) || ($isOnlyCho && stripos($this->str2Cho($cleanName), $checkName) === 0 && ($check == null || strlen($check) < strlen($clothesName)))){
							$check = $clothesName;
						}
					}
				}
				if($check !== null){
					return $check;
				}
			}
		}
		if($isPlayerSearch && $check == null){
			return $this->getExactName($clothesName);
		}else{
			return null;
		}
	}

	public function getAllPlayerClothes($player){
		if($player instanceof Player){
			$playerName = strtolower($player->getName());
		}elseif(is_string($player)){
			$playerName = strtolower($player);
		}else{ 
			return null;
		}
		return isset($this->data[self::PLAYERS][$playerName]) ? array_keys($this->data[self::PLAYERS][$playerName]) : null;
	}

	public function hasClothes($player, $clothesName, $isExact = false){
		if($this->clothesExists($clothesName, $isExact)){
			if($player instanceof Player){
				$playerName = strtolower($player->getName());
			}elseif(is_string($player)){
				$playerName = strtolower($player);
			}else{
				return false;
			}
			return isset($this->data[self::PLAYERS][$playerName]) && isset($this->data[self::PLAYERS][$playerName][$this->getExactName($clothesName)]);
		}
	}

	public function getPriority($player, $clothesName, $isExact = false){
		if($this->hasClothes($player, $clothesName, $isExact)){
			if($player instanceof Player){
				$playerName = strtolower($player->getName());
			}elseif(is_string($player)){
				$playerName = strtolower($player);
			}else{
				return null;
			}
			return $this->data[self::PLAYERS][$playerName][$this->getExactName($clothesName, $isExact)];
		}
		return null;
	}

	public function setPriority($player, $clothesName, $priority, $isExact = false){
		if($this->hasClothes($player, $clothesName, $isExact) && is_numeric($priority) && ($priority = floor($priority)) >= 0){
			if($player instanceof Player){
				$playerName = strtolower($player->getName());
			}elseif(is_string($player)){
				$playerName = strtolower($player);
			}else{
				return null;
			}
			if(!$this->data[self::PLAYERS][$playerName][$clothesMame = $this->getExactName($clothesName, $isExact)] == $priority){
				if($priority == 0){
					$this->data[self::PLAYERS][$playerName][$clothesName] = $priority;
				}else{
					arsort($this->data[self::PLAYERS][$playerName]);
					$push = false;
					$this->data[self::PLAYERS][$playerName][$clothesName] = $priority;
					foreach($this->data[self::PLAYERS][$playerName] as $clothesNameKey => $clothesPriority){
						if($priority == $clothesPriority){
							$push = true;
						}
						if($push && $clothesPriority >= 1 && $clothesName != $clothesNameKey){
							$this->data[self::PLAYERS][$playerName][$clothesNameKey]++;
						}
					}
				}
				$this->sortPriority($playerName);
			}
		}
	}

	public function sortPriority($player, $isReverse = false){
		if($player instanceof Player){
			$playerName = strtolower($player->getName());
		}elseif(is_string($player)){
			$playerName = strtolower($player);
		}else{
			return null;
		}
		if(isset($this->data[self::PLAYERS][$playerName])){
			if($isReverse){
				arsort($this->data[self::PLAYERS][$playerName], SORT_NUMERIC);
			}else{
				asort($this->data[self::PLAYERS][$playerName], SORT_NUMERIC);
			}
			$priority = 0;
			foreach($this->data[self::PLAYERS][$playerName] as $clothesName => $clothesPriority){
				if($clothesPriority != 0){
					$this->data[self::PLAYERS][$playerName][$clothesName] = ++$priority;
				}
			}
			asort($this->data[self::PLAYERS][$playerName], SORT_NUMERIC);
			if(($player = $this->getServer()->getPlayerExact($playerName)) instanceof Player){
				$this->dressUp($player);
			}
		}
	}

 	public function isDress($player, $clothesName, $isExact = false){
		if($this->hasClothes($player, $clothesName, $isExact)){
			if($player instanceof Player){
				$playerName = strtolower($player->getName());
			}elseif(is_string($player)){
				$playerName = strtolower($player);
			}else{
				return false;
			}
			return $this->data[self::PLAYERS][$playerName][$this->getExactName($clothesName, $isExact)] != false;
		}
		return false;
	}


	public function giveClothes($player, $clothesName, $priority = 0, $isExact = true){
		if($this->clothesExists($clothesName, $isExact) && !$this->hasClothes($player, $clothesName = $this->getExactName($clothesName), $isExact) && is_numeric($priority) && ($priority = floor($priority)) >= 0){
			if($player instanceof Player){
				$playerName = strtolower($player->getName());
			}elseif(is_string($player)){
				$playerName = strtolower($player);
			}else{
				return false;
			}
			if($priority == 0){
				$this->data[self::PLAYERS][$playerName][$clothesName] = $priority;
			}else{
				if(!isset($this->data[self::PLAYERS][$playerName])){
					$this->data[self::PLAYERS][$playerName] = [];
				}
				asort($this->data[self::PLAYERS][$playerName]);
				$push = false;
				$this->data[self::PLAYERS][$playerName][$clothesName] = $priority;
				foreach($this->data[self::PLAYERS][$playerName] as $clothesNameKey => $clothesPriority){
					if($priority == $clothesPriority){
						$push = true;
					}
					if($push && $clothesName != $clothesNameKey){
						$this->data[self::PLAYERS][$playerName][$clothesNameKey]++;
					}
				}
			}
			$this->sortPriority($playerName);
		}		
	}

	public function takeClothes($player, $clothesName, $isExact = true){
		if($this->clothesExists($clothesName, $isExact) && $this->hasClothes($player, $clothesName = $this->getExactName($clothesName))){
			if($player instanceof Player){
 				$playerName = strtolower($player->getName());
			}elseif(is_string($player)){
				$playerName = strtolower($player);
			}else{
				return false;
			}
			unset($this->data[self::PLAYERS][$playerName][$clothesName]);
			$this->sortPriority($playerName);
		}
	}

	public function dressUp(Player $player){
		if(isset($this->data[self::PLAYERS][$playerName = strtolower($player->getName())]) && !empty($this->getAllPlayerClothes($player))){
	 		arsort($this->data[self::PLAYERS][$playerName]);
	 		$skin = $this->getRealSkin($player);
			foreach($this->data[self::PLAYERS][$playerName] as $clothesName => $priority){
 				if($this->clothesExists($clothesName, true) && $priority != 0){
 					$skin = $this->merge($skin, $this->getClothes($clothesName, true));
				}
			}
			$player->setSkin($skin, false);
			if($this->isNewAPI()){
				$player->setDataFlag(Player::DATA_FLAGS, Player::DATA_FLAG_INVISIBLE, true);
				$player->setDataFlag(Player::DATA_FLAGS, Player::DATA_FLAG_INVISIBLE, false);
 			}
		}
 	}

	public function merge($skin, array $clothes){
		if(is_string($skin) && (strlen($skin = $this->skin2BigSkin($skin)) == self::BIG_SKIN)){
			$skinArr = str_split($skin, 4);
			foreach($clothes as $key => $color){
				if($this->isClothesIndex($key)){
					$skinArr[$key] = $color;
				}
			}
		}
		return implode("", $skinArr);
	}


	public function clothes2Skin($clothes){
		if(is_array($clothes)){
			$skinArr = str_split(self::getEmptySkin(), 4);
			foreach($clothes as $key => $color){
				$skinArr[$key] = $color;
			}
			$skin = implode("", $skinArr);
		}
		return strlen($skin) == self::BIG_SKIN ? $skin : nuul;
	}

	public function skin2Clothes($skin, $ignore = "\x01"){
		$clothes = [];
		if(is_string($skin) && (strlen($skin = $this->skin2BigSkin($skin)) == self::BIG_SKIN)){
			foreach(str_split($skin, 4) as $key => $color){
				if($this->isClothesIndex($key) && ($color{0} != $ignore || $color{1} != $ignore || $color{2} != $ignore)){
					$clothes[$key] = $color;
				}
			}
		}
		return $clothes;
	}

	public function skin2BigSkin($skin){
		if(is_string($skin) && strlen($skin) == self::SMALL_SKIN){
			$skinArr = str_split($skin . substr(self::getEmptySkin(), self::SMALL_SKIN), 4);
			foreach([[0, 16, 16 + 32 * 64], [40, 16, -8 + 32 * 64]] as $data){
				for($x = $data[0]; $x < $data[0] + 16; $x++){
					for($y = $data[1]; $y < $data[1] + 16; $y++){
						$skinArr[($index = $y * 64 + $x) + $data[2]] = $skinArr[$index];
					}
				}
			}
			$skin = implode("", $skinArr);
		}
		return $skin;
	}

	public function isClothesIndex($index, $isIndex = true){
		if(!$isIndex){
			$index = $index > 3 ? ($index - ($index % 4)) / 4 : 0;
		}
		if(!is_numeric($index) || floor($index) != $index || $index < 0 || $index > self::BIG_SKIN){
			return false;
		}
// TODO: Support this function
		return true;;
	}

	public function getRealSkin($player){
		if($player instanceof Player){
			$playerName = strtolower($player->getName());
		}elseif(is_string($player)){
			$playerName = strtolower($player);
		}else{
			return null;
		}
		return isset($this->players[$playerName]) ? $this->players[$playerName] : $this->players[$playerName] = $this->skin2BigSkin($player->getSkinData());
	}

//# Event: ClothesAdd ClothesRemove ClothesGive ClothesTake PlayerDressClothes

	public function str2Cho($str){
		$cho = ["ㄱ", "ㄲ", "ㄴ", "ㄷ", "ㄸ", "ㄹ", "ㅁ", "ㅂ", "ㅃ", "ㅅ", "ㅆ", "ㅇ", "ㅈ", "ㅉ", "ㅊ", "ㅋ", "ㅌ", "ㅍ", "ㅎ"];
		$result = "";
		for($i = 0; $i < mb_strlen($str, "UTF-8"); $i++){
			if(in_array($ch = mb_substr($str, $i, 1, "UTF-8"), $cho)){
				$result .= $ch;
			}else{
				if(($len = strlen($ch)) <= 2 || ($f = ord($ch{0})) <= 0x7F || $f < 0xC2){
					continue;
				}elseif($f <= 0xEF && $len > 2){
					$code = ($f & 0x0F) << 12 | (ord($ch{1}) & 0x3F) << 6 | (ord($ch{2}) & 0x3F);
				}elseif($f <= 0xF4 && $len > 3){
					$code = ($f & 0x0F) << 18 | (ord($ch{1}) & 0x3F) << 12 | (ord($ch{2}) & 0x3F) << 6 | (ord($ch{3}) & 0x3F);
				}else{
					continue;
				}
				if($code > 44031 && $code < 55104){
					$result .= $cho[($code - 44042) / 588];
				}
			}
		}
		return $result;
	}

	public function isOnlyKor($str){
		$cho = ["ㄱ", "ㄲ", "ㄴ", "ㄷ", "ㄸ", "ㄹ", "ㅁ", "ㅂ", "ㅃ", "ㅅ", "ㅆ", "ㅇ", "ㅈ", "ㅉ", "ㅊ", "ㅋ", "ㅌ", "ㅍ", "ㅎ"];
		$result = "";
		for($i = 0; $i < mb_strlen($str, "UTF-8"); $i++){
			if(!in_array($ch = mb_substr($str, $i, 1, "UTF-8"), $cho) && !(($code = utf8_ord($ch)) > 44031 && $code < 55104)){
				return false;
			}
		}
		return true;	
	}

	public function isOnlyCho($str){
		$cho = ["ㄱ", "ㄲ", "ㄴ", "ㄷ", "ㄸ", "ㄹ", "ㅁ", "ㅂ", "ㅃ", "ㅅ", "ㅆ", "ㅇ", "ㅈ", "ㅉ", "ㅊ", "ㅋ", "ㅌ", "ㅍ", "ㅎ"];
		$result = "";
		for($i = 0; $i < mb_strlen($str, "UTF-8"); $i++){
			if(!in_array($ch = mb_substr($str, $i, 1, "UTF-8"), $cho)){
				return false;
			}
		}
		return true;	
	}


	public function loadData(){
		@mkdir($folder = $this->getServer()->getDataPath() . "plugins/Clothes/");
		if(!file_exists($clothesPath = $folder . "Clothes.sl")){	
			file_put_contents($clothesPath, serialize([]));
		}
		if(!file_exists($playersPath = $folder . "Players.sl")){	
			file_put_contents($playersPath, serialize([]));
		}
		$this->data = [self::CLOTHES => [], self::PLAYERS => unserialize(file_get_contents($playersPath))];
		if(!empty($clothesList = unserialize(file_get_contents($clothesPath)))){
			foreach($clothesList as $clothesName => $clothesSkin){
				$this->data[self::CLOTHES][$clothesName] = $this->skin2Clothes($clothesSkin);
			}
		}
		if(!empty($this->data[self::PLAYERS])){
	 		foreach($this->data[self::PLAYERS] as $playerName => $clothesList){
				foreach($clothesList as $clothesName){
					if(!$this->clothesExists($clothesName)){
						unset($this->data[self::PLAYERS][$playerName][$clothesName]);
					}
				}
			}
		}
	}

	public function saveData(){
		@mkdir($folder = $this->getServer()->getDataPath() . "plugins/Clothes/");
		file_put_contents($folder . "_Clothes_.sl", serialize($this->data[self::CLOTHES]));
		file_put_contents($folder . "_Players.sl", serialize($this->data[self::PLAYERS]));
		$clothesList = [];
		if(!empty($this->data[self::CLOTHES])){
			foreach($this->data[self::CLOTHES] as $clothesName => $clothes){
				$clothesList[$clothesName] = $this->clothes2Skin($clothes);
		 	}
 		}
		file_put_contents($folder . "Clothes.sl", serialize($clothesList));
		if(!empty($this->data[self::PLAYERS])){
	 		foreach($this->data[self::PLAYERS] as $playerName => $clothesList){
				if(empty($clothesList)){
					unset($this->data[self::PLAYERS][$playerName]);
				}else{
					foreach($clothesList as $clothesName){
						if(!$this->clothesExists($clothesName)){
							unset($this->data[self::PLAYERS][$playerName][$clothesName]);
						}
					}
				}
			}
		}
		file_put_contents($folder . "Players.sl", serialize($this->data[self::PLAYERS]));
	}

	public function isKorean(){
		return $this->getServer()->getLanguage()->getName() == "\"한국어\"";
	}

	public function isNewAPI(){
		return $this->getServer()->getApiVersion() !== "1.12.0";
	}
}