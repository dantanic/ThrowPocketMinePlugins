<?php

namespace ShortCut;

use pocketmine\utils\Config;
use pocketmine\utils\TextFormat as Color;
use pocketmine\Player;
use pocketmine\math\Vector3;
use pocketmine\level\Position;
use pocketmine\entity\Effect;
use pocketmine\event\player\PlayerCommandPreprocessEvent;
use pocketmine\event\player\PlayerChatvent;
use pocketmine\event\server\ServerCommandEvent;

class ShortCut extends \pocketmine\plugin\PluginBase implements \pocketmine\event\Listener{
	const MODE_NORMAL = 0;
	const MODE_PLAYER = 1;
	const MODE_CHAT = 2;
	const MODE_CONSOLE = 3;

	const RUN_NORMAL = 0;
	const RUN_CONSOLE = 1;
	const RUN_OP = 2;
	const RUN_MESSGAE = 3;
	const RUN_BROADCAST = 4;
	const RUN_DOING = 5;

/*
hasItem notHasItem (아이템 보유여부)
hasEffect notHasEffect (이펙트보유여부)
isArmor ~ notBoots ([모든]장비 착용여부)
isArmor:@ ~ notBoots:@ ([모든]장비 일치여부)
isHeld:@ notHeld:@(들고잇는 아이템 일치여부)

isNameTag:
setNamsTag:
setHeld:id,count,damage[,isHave] (아이템들게하기)
setArmor~setBoots:id,count,damage ([모든]장비 입히기)
*/

	protected static $instance = null;
	private $data = [], $property = null, $effectTable = [
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
//		Effect::BLINDNESS => ["BL", "BLIND", "실명"],
//		Effect::NIGHT_VISION => ["NIGHTVISION", "NI", "NIGHT", "야간투시", "야간", "투시"],
//		Effect::HUNGER => ["HU", "HUNGRY", "허기", "배고픔"],
		Effect::WEAKNESS => ["WE", "WEAK", "나약함", "나약", "허약함", "허약", "약함"],
		Effect::POISON => ["PO", "중독", "독", "감염"],
		Effect::WITHER => ["WI", "위더"],
		Effect::HEALTH_BOOST => ["HEALTHBOOST", "HE", "HEALTH", "HB", "체력신장", "체력증가", "체력추가", "체력"],
//		Effect::ABSORPTION => ["AB", "DRAIN", "흡수", "보호"],
//		Effect::SATURATION => ["SA", "포화", "배부름"]
	];

	public static function getInstance(){
		return self::$instance; 		
	}

	public static function addShortCut($shortCut, $command){
		self::$instance->sc[$shortCut] = $command;
	}

	public static function removeShortCut($shortCut){
		unset(self::$instance->sc[$shortCut]);
	}

	public function onLoad(){
		if(self::$instance == null){
			self::$instance = $this;
		}
		$this->property = (new \ReflectionClass("\\pocketmine\\entity\\Entity"))->getProperty("effects");
		$this->property->setAccessible(true);
	}

	public function onEnable(){
		$this->loadYml();
		$this->getServer()->getLogger()->notice(Color::GREEN . "Find economy plugin...");
		$pluginManager = $this->getServer()->getPluginManager();
		if(!($this->money = $pluginManager->getPlugin("PocketMoney")) && !($this->money = $pluginManager->getPlugin("EconomyAPI")) && !($this->money = $pluginManager->getPlugin("MassiveEconomy")) && !($this->money = $pluginManager->getPlugin("Money"))){
			$this->getServer()->getLogger()->notice(Color::RED . "Failed find economy plugin...");
		}else{
			$this->getServer()->getLogger()->notice(Color::GREEN . "Finded economy plugin : " . $this->money->getName());
		}
		$pluginManager->registerEvents($this, $this);
 	}

	public function onDisable(){
		$this->saveYml();
	}

	public function onCommand(\pocketmine\command\CommandSender $sender, \pocketmine\command\Command $cmd, $label, array $sub){
		if(!isset($sub[0]) || $sub[0] == ""){
			return false;
		}else{
			$ik = $this->isKorean();
			switch(strtolower($sub[0])){
				case "add":
				case "a":
				case "추가":
					if(!isset($sub[1]) || $sub[1] == ""){
						$r = Color::RED . "Usage: /ShortCut Add(A) " . ($ik ? "<단축명령어> <명령어~>" : "<Shortcut> <Command~>");
					}elseif(isset($this->data[$shortcut = strtolower($sub[1])])){
							$r = Color::RED . "[ShortCut] " . $shortcut . ($ik ? "은(는) 이미 존재합니다." : " is already exist.");
					}else{
						$this->data[$shortcut = strtolower($sub[1])] = str_replace(".@", "@", implode(" ", array_splice($sub,2)));
						$r = Color::YELLOW . "[ShortCut] " . $shortcut . ($ik ? "을 추가하였습니다." : " is added") . " [$shortcut] => " . $this->data[$shortcut];
					}
				break;
				case "del":
				case "d":
				case "삭제":
				case "제거":
					if(!isset($sub[1]) || $sub[1] == ""){
						$r = Color::RED . "Usage: /ShortCut Del(D) " . ($ik ? "<단축명령어>" : "<Shortcut>");
					}else{
						if(!isset($this->data[$shortcut = strtolower($sub[1])])){
							$r = Color::RED . "[ShortCut] " . $shortcut . ($ik ? "은(는) 존재하지 않습니다." : " is does not exist.");
						}else{
							$r = Color::YELLOW . "[ShortCut] " . $shortcut . ($ik ? "을 제거하였습니다." : " is deleted") . " [$shortcut] => " . $this->data[$shortcut];
							unset($this->data[$shortcut]);
						}
					}
				break;
				case "list":
				case "l":
				case "목록":
				case "리스트":
					$lists = array_chunk($this->data, 5);
					$r = Color::YELLOW . "[ShortCut] " . ($ik ? "단축명령어 목록 (페이지: " : "Shortcut list (Page: ") . ($page = min(isset($sub[1]) && is_numeric($sub[1]) && isset($lists[$sub[1] - 1]) ? $sub[1] : 1, count($lists))). "/" . count($lists) . ") (" . count($this->data) . ")";
					if(isset($lists[$page - 1])){
						$keys = array_keys($this->data);
						foreach($lists[$page - 1] as $key => $command){
							$r .= "\n" . Color::GOLD . "    [" . (($shortcutKey = (($page - 1) * 5 + $key)) + 1) .  "] " . $keys[$shortcutKey] . " : $command";
						}
					}
				break;
				case "reset":
				case "리셋":
				case "초기화":
					$this->data = [];
					$r = Color::YELLOW . "[ShortCut] " . ($ik ? " 리셋됨." : " Reset");
				break;
				case "help":
				case "?":
					if(!isset($sub[1]) || ($sub[1] = strtolower($sub[1])) == "" || $sub[1] == "?"){
						$r = Color::YELLOW . "[ShortCut] " . ($ik ? "도움말" : "Help") . Color::AQUA . "  /ShortCut <Add|Del|List|Reset| Help>" ;
						$r .= "\n  " . Color::GOLD . "Add(A) " . ($ik ? "<단축명령어> <명령어~> : " . Color::GREEN . "명령어를 해당 단축 명령어로 단축합니다." : "<Shortcut> <Commamd~> : " . Color::GREEN . "Command shorten to shortcut");
						$r .= "\n  " . Color::GOLD . "Del(D) " . ($ik ? "<단축명령어> : " . Color::GREEN . "단축명령어를 제거합니다." : "<Shortcut> : " . Color::GREEN . "Delete the shortcut");
						$r .= "\n  " . Color::GOLD . "List(L) " . ($ik ? "<페이지> : " . Color::GREEN . "단축명령어의 목록을 보여줍니다." : "<Page> : " . Color::GREEN . "Shows a list of shortcuts");
						$r .= "\n  " . Color::GOLD . "Reset : " . Color::GREEN . ($ik ? "모든 단축명령어를 리셋합니다." : "Delete all shortcut.");
						$r .= "\n  " . Color::GOLD . "Help(?) : " . Color::GREEN . ($ik ? "도움말을 보여줍니다." : "Shows the help message.");
					}elseif($sub[1] == "replace" || $sub[1] == "치환" || $sub[1] == "r"){
						$lists = array_chunk($helps = [
							["@Player, @P", $ik ? "명령어를 입력한 사람의 이름" : "Name of person who entered the command"],
							["@X, @FloorX, @CeilX, @RoundX", $ik ? "명령어를 입력한 사람의 X좌표" : "X position of person who entered the command"],
							["@Y , @FloorY, @CeilY, @RoundY", $ik ? "명령어를 입력한 사람의 Y좌표" : "Y position of person who entered the command"],
							["@Z , @FloorZ, @CeilZ, @RoundZ", $ik ? "명령어를 입력한 사람의 Z좌표" : "Z position of person who entered the command"],
							["@Health, @HP", $ik ? "명령어를 입력한 사람의 체력" : "Health of person who entered the command"],
							["@MaxHealth, @MHP", $ik ? "명령어를 입력한 사람의 최대 체력" : "Max health of person who entered the command"],
							["@Money, @getMoney", $ik ? "명령어를 입력한 사람의 돈" : "Money of person who entered the command"],
							["@WorldName, @World, @W", $ik ? "명령어를 입력한 사람의 월드명" : "World name of person who entered the command"],
							["@WorldFolderName, @WorldFolder, @WF", $ik ? "명령어를 입력한 사람의 월드의 폴더명" : "World folder name of person who entered the command"],
							["@Random, @Rand, @R", $ik ? "서버내의 랜덤한 플레이어 이름" : "One random name within server"],
							["@Server, @S", $ik ? "서버의 이름 (motd)" : "Server\'s name (motd)"],
							["@Version, @V", $ik ? "서버의 버전" : "Server\'s version"],
							["@Dice:\"a\",\"b\"", $ik ? "\"a\"부터 \"b\"까지의 랜덤 숫자" : "Random number within \"a\"-\"b\""]
						], 5);
						$page = min(isset($sub[2]) && is_numeric($sub[2]) && isset($lists[$sub[2] - 1]) ? $sub[2] : 1, count($lists));
						$r = Color::YELLOW . "[ShortCut] " . ($ik ? "도움말" : "Help") . Color::AQUA . ($ik ? "치환 (페이지: " : "Replacement (Page: ") . $page . "/" . count($lists) . ") (" . count($helps) . ")";
						if(isset($lists[$page - 1])){
							foreach($lists[$page - 1] as $key => $help){
								$r .= "\n" . Color::GOLD . "    [" . (($page - 1) * 5 + $key + 1) .  "] " . $help[0] . " : " . Color::GREEN . $help[1];
							}
						}
					}elseif($sub[1] == "excutable" || $sub[1] == "실행" || $sub[1] == "e"){
						$lists = array_chunk($helps = [
							["@All, @A", $ik ? "모든 플레이어의 이름으로 명령어를 반복" : "Repeat command with name of all player in server"],
							["@Heal:\"Amount\", @Regain", $ik ? "명령어를 입력한 플레이어의 체력을 \"Amount\"만큼 회복" : "Recovery \"Amount\" HP player who entered the command"],
							["@Damage:\"Amount\", @Hurt", $ik ? "명령어를 입력한 플레이어의 체력을 \"Amount\"만큼 경감" : "Reduce \"Amount\" HP player who entered the command"],
							["@Teleport:\"X\",\"Y\",\"Z\",\"WorldName\", @TP", $ik ? "명령어를 입력한 플레이어를 \"X\",\"Y\",\"Z\",\"WorldName\"좌표로 텔레포트시킵니다. " : "Teleport player who entered the command to \"X\",\"Y\",\"Z\",\"WorldName\" position"],
							["@Jump:\"X\",\"Y\",\"Z\", @Move", $ik ? "명령어를 입력한 플레이어를 \"X\",\"Y\",\"Z\"만큼 점프시킵니다. " : "Jump player who entered the command to \"X\",\"Y\",\"Z\" position"],
							["@GiveMoney:\"Money\", @GM", $ik ? "명령어를 입력한 플레이어에게 \"Money\"\$를 줍니다." : "Give \"Money\"\$ to player who entered the command"],
		 					["@TakeMoney:\"Money\", @TM", $ik ? "명령어를 입력한 플레이어에게서 \"Money\"\$를 빼앗습니다." : "Take \"Money\"\$ from player who entered the command"],
		 					["@Message:\"Message\", @MSG", $ik ? "명령어를 입력한 플레이어에게 \"Message\"라는 메세지를 보냅니다." : "Send message \"Message\" to player who entered the command"],
							["@Broadcast:\"Message\", @Say, @broad", $ik ? "\"Message\"라는 메세지를 서버에 보냅니다." : "Send message \"Message\" to server"],
							["@Effect:\"Name\",\"Duration\",\"Amplifier\", @EF, @Potion, @Pot", $ik ? "명령어를 입력한 플레이어에게 \"Name\" 이펙트(\"Duration\"초, \"Amplifier\"레벨)를 부여합니다." : "Give \"Name\" Effect (\"Duration\"sec,\"Amplifier\"level), to \" player who entered the command"],
						], 5);
						$page = min(isset($sub[2]) && is_numeric($sub[2]) && isset($lists[$sub[2] - 1]) ? $sub[2] : 1, count($lists));
						$r = Color::YELLOW . "[ShortCut] " . ($ik ? "도움말" : "Help") . Color::AQUA . ($ik ? "실행 (페이지: " : "Executable (Page: ") . $page . "/" . count($lists) . ") (" . count($helps) . ")";
						if(isset($lists[$page - 1])){
							foreach($lists[$page - 1] as $key => $help){
								$r .= "\n" . Color::GOLD . "    [" . (($page - 1) * 5 + $key + 1) .  "] " . $help[0] . " : " . Color::GREEN . $help[1];
							}
						}
					}elseif($sub[1] == "conditional" || $sub[1] == "조건" || $sub[1] == "c"){
						$lists = array_chunk($helps = [
						], 5);
						$page = min(isset($sub[2]) && is_numeric($sub[2]) && isset($lists[$sub[2] - 1]) ? $sub[2] : 1, count($lists));
						$r = Color::YELLOW . "[ShortCut] " . ($ik ? "도움말" : "Help") . Color::AQUA . ($ik ? "조건 (페이지: " : "Conditional (Page: ") . $page . "/" . count($lists) . ") (" . count($helps) . ")";
						if(isset($lists[$page - 1])){
							foreach($lists[$page - 1] as $key => $help){
								$r .= "\n" . Color::GOLD . "    [" . (($page - 1) * 5 + $key + 1) .  "] " . $help[0] . " : " . Color::GREEN . $help[1];
							}
						}
					}elseif($sub[1] == "effect" || $sub[1] == "효과" || $sub[1] == "ef"){
						$lists = array_chunk($helps = [
							[Effect::SPEED, "Speed(SP)" . ($ik ? ", 신속" : "")], 
							[Effect::SLOWNESS, "Slowness(SL)" . ($ik ? ", 구속" : "")],
							[Effect::HASTE, "Haste(HA)" . ($ik ? ", 성급" : "")],
							[Effect::FATIGUE, "Fatigue(FA)" . ($ik ? ", 피로" : "")],
							[Effect::STRENGTH, "Strenth(ST)" . ($ik ? ", 힘" : "")],
							[Effect::JUMP, "Jump(JP)" . ($ik ? ", 점프강화" : "")],
							[Effect::NAUSEA, "Nausea(NA)" . ($ik ? ", 멀미" : "")],
							[Effect::REGENERATION, "Regeneration(RE)" . ($ik ? ", 재생" : "")],
							[Effect::DAMAGE_RESISTANCE, "DamageRegistance(DA)" . ($ik ? ", 저항" : "")],
							[Effect::FIRE_RESISTANCE, "FireResistance(FI)" . ($ik ? ", 화염저항" : "")],
							[Effect::WATER_BREATHING, "WaterBreathing(WA)" . ($ik ? ", 수중호흡" : "")],
							[Effect::INVISIBILITY, "Invisiblility(IN)" . ($ik ? ", 투명화" : "")],
	//						[Effect::BLINDNESS, "Blindness(BL)" . ($ik ? ", 실명" : "")],
	//						[Effect::NIGHT_VISION, "NightVision(NI)" . ($ik ? ", 야간투시" : "")],
	//						[Effect::HUNGER, "Hunger(HU)" . ($ik ? ", 허기" : "")],
							[Effect::WEAKNESS, "Weakness(WE)" . ($ik ? ", 나약함" : "")],
							[Effect::POISON, "Poison(PO)" . ($ik ? ", 독" : "")],
							[Effect::WITHER, "Wither(WI)" . ($ik ? ", 위더" : "")],
							[Effect::HEALTH_BOOST, "HelathBoost(HE)". ($ik ? ", 체력신장" : "")],
	//						[Effect::ABSORPTION, "Absorption(AB)" . ($ik ? ", 흡수" : "")],
	//						[Effect::SATURATION, "Saturation(SA)" . ($ik ? ", 포화" : "")]
						], 5);
						$page = min(isset($sub[2]) && is_numeric($sub[2]) && isset($lists[$sub[2] - 1]) ? $sub[2] : 1, count($lists));
						$r = Color::YELLOW . "[ShortCut] " . ($ik ? "도움말" : "Help") . Color::AQUA . ($ik ? "효과 (페이지: " : "Effect (Page: ") . $page . "/" . count($lists) . ") (" . count($helps) . ")";
						if(isset($lists[$page - 1])){
							foreach($lists[$page - 1] as $key => $help){
								$r .= "\n" . Color::GOLD . "    [" . (($page - 1) * 5 + $key + 1) .  "] " . Color::AQUA . "{" . $help[0] . "} " . Color::GREEN . $help[1];
							}
						}
					}elseif($sub[1] == "particle" || $sub[1] == "파티클" || $sub[1] == "p"){
						$lists = array_chunk($helps = [
						], 5);
						$page = min(isset($sub[2]) && is_numeric($sub[2]) && isset($lists[$sub[2] - 1]) ? $sub[2] : 1, count($lists));
						$r = Color::YELLOW . "[ShortCut] " . ($ik ? "도움말" : "Help") . Color::AQUA . ($ik ? "파티클 (페이지: " : "Particle (Page: ") . $page . "/" . count($lists) . ") (" . count($helps) . ")";
						if(isset($lists[$page - 1])){
							foreach($lists[$page - 1] as $key => $help){
								$r .= "\n" . Color::GOLD . "    [" . (($page - 1) * 5 + $key + 1) .  "] " . $help[0] . " : " . Color::GREEN . $help[1];
							}
						}
					}else{
						$r = Color::RED . "Usage: /ShortCut Help(?) <?|Replace(R)|Executable(E)|Conditional(C)|Effect(Ef)|Particle(P)>";					
					}
				break;
				default:
					return false;
				break;
			}
			if(isset($r)){
				$sender->sendMessage($r);
			}
			return true;
		}
	}

	/**
	 * @priority LOWEST
	 */
	public function onServerCommand(\pocketmine\event\server\ServerCommandEvent $event){
		if(!$event->isCancelled()){
			if(($command = $this->decryptCommand($event->getSender(), $event->getCommand())) !== false){
				$event->setCommand($command);
			}else{
				$event->setCancelled();
				return;
			}
			$this->replaceMessage($event, self::MODE_CONSOLE);
		}
	}

	/**
	 * @priority LOWEST
	 */
	public function onRemoteServerCommand(\pocketmine\event\server\RemoteServerCommandEvent $event){
		if(!$event->isCancelled()){
			if(($command = $this->decryptCommand($event->getSender(), $event->getCommand())) !== false){
				$event->setCommand($command);
			}else{
				$event->setCancelled();
				return;
			}
			$this->replaceMessage($event, self::MODE_CONSOLE);
		}
	}

	/**
	 * @priority LOWEST
	 */
	public function onPlayerCommandPreprocess(\pocketmine\event\player\PlayerCommandPreprocessEvent $event){
		if(!$event->isCancelled()){
			if($isCommand = (strpos($command = $event->getMessage(), "/") === 0)){
				if(($command = $this->decryptCommand($event->getPlayer(), substr($command, 1))) !== false){
					$event->setMessage("/" . $command);
				}else{
					$event->setCancelled();
					return;
				}
			}
			$this->replaceMessage($event, $isCommand ? self::MODE_PLAYER : self::MODE_CHAT);
		}
	}

	public function decryptCommand(\pocketmine\command\CommandSender $sender, $command){
		if(isset($this->data[$key = strtolower(substr($command, 0, ($pos = strpos($command, " ")) === false ? strlen($command) : $pos))])){
			if(preg_match_all("/@([a-z]+)/i", $this->data[$key], $matches)){
				foreach($matches[0] as $matchKey => $match){
					$change = "";
					switch(strtolower($matches[1][$matchKey])){
						case "isop":
							if(!$sender->isOp()){
								return false;
							}
						break;
						case "notop":
							if($sender->isOp()){
								return false;
							}
						break;
						case "isplayer":
							if($sender instanceof Player){
								return false;
							}
						break;
						case "notplayer":
							if(!$sender instanceof Player){
								return false;
							}
						break;							
						case "issurvival":
						case "issur":
							if(!($sender instanceof Player) || !$sender->isSurvival()){
								return false;
							}
						break;
						case "notsurvival":
						case "notsur":
							if(!($sender instanceof Player) || $sender->isSurvival()){
								return false;
								}
						break;
						case "iscreative":
						case "iscre":
							if(!($sender instanceof Player) || !$sender->isCreative()){
								return false;
							}
						break;
						case "notcreative":
						case "notcre":
							if(!($sender instanceof Player) || $sender->isCreative()){
								return false;
							}
						break;
						case "isspectator":
						case "isspe":
							if(!($sender instanceof Player) || !$sender->isSpectator()){
								return false;
							}
						break;
						case "notspectator":
						case "notspe":
							if(!($sender instanceof Player) || $sender->isSpectator()){
								return false;
							}
						break;
						case "isadventure":
						case "isadv":
							if(!($sender instanceof Player) || !$sender->isAdventure()){
								return false;
							}
						break;
						case "notadventure":
						case "notadv":
							if(!($sender instanceof Player) || $sender->isAdventure()){
								return false;
							}
						break;
						case "inwater":
						case "water":
							if(!($sender instanceof Player) || !$sender->isInsideOfWater()){
								return false;
							}				
						break;
						case "notinwater":
						case "notwater":
							if(!($sender instanceof Player) || $sender->isInsideOfWater()){
								return false;
							}
						break;
						default:
							$change = $match;
						break;
					}
					foreach($scArr = explode(" ", $this->data[$key]) as $scKey => $value){
						if($match == $value){
							if($change == ""){
								unset($scArr[$scKey]);
							}else{
								$scArr[$scKey] = $change;
							}
							break;
						}
					}
					$this->data[$key] = implode(" ", $scArr);
				}
			}
			if(preg_match_all("/@([a-z]+):([^ ]+)/i", $this->data[$key], $matches)){
				foreach($matches[0] as $matchKey => $match){
					$sub = explode(",", $matches[2][$matchKey]);
					$change = "";
					switch(strtolower($matches[1][$matchKey])){
						case "havemoney":
						case "hasmoney":
						case "hm":
							if($sender instanceof Player && is_numeric($sub[0]) && is_numeric($sub[0]) && $sub[0] >= 0 && $this->getMoney($sender) < $sub[0]){
								return false;
							}
						break;
						case "nothavemoney":
						case "nothasmoney":
						case "nhm":
							if($sender instanceof Player && is_numeric($sub[0]) && is_numeric($sub[0]) && $sub[0] >= 0 && $this->getMoney($sender) >= $sub[0]){
								return false;
							}
						break;
						case "havehealth":
						case "havehp":
						case "hashealth":
						case "hashp":
						case "hh":
							if($sender instanceof Player && is_numeric($sub[0]) && is_numeric($sub[0]) && $sub[0] >= 0 && $sender->getHealth() < $sub[0]){
								return false;
							}
						break;
						case "nothavehealth":
						case "nothavehp":
						case "nothashealth":
						case "nothashp":
						case "nhh":
							if($sender instanceof Player && is_numeric($sub[0]) && is_numeric($sub[0]) && $sub[0] >= 0 && $sender->getHealth() >= $sub[0]){
								return false;
							}
						break;
						default:
							$change = $match;
						break;
					}
					foreach($scArr = explode(" ", $this->data[$key]) as $scKey => $value){
						if($match == $value){
							if($change == ""){
								unset($scArr[$scKey]);
							}else{
								$scArr[$scKey] = $change;
							}
							break;
						}
					}
					$this->data[$key] = implode(" ", $scArr);
				}
			}
			if(preg_match_all("/@([a-z]+):([^ ]+)/i", $this->data[$key], $matches)){
				foreach($matches[0] as $matchKey => $match){
					$sub = explode(",", $subs = str_replace("_", " ", $matches[2][$matchKey]));
					$change = "";
					switch(strtolower($matches[1][$matchKey])){
						case "dice":
							$change = isset($sub[1]) && is_numeric($sub[0]) && is_numeric($sub[1]) ? rand($sub[0], $sub[1]) : 0;
						break;
						case "heal":
						case "regain":
							if($sender instanceof Player && $sender->getHealth() < $sender->getMaxHealth() && isset($sub[0]) && is_numeric($sub[0]) && $sub[0] >= 1){
		 						$ev = new \pocketmine\event\entity\EntityRegainHealthEvent($sender, $sub[0], 3);
								if(!$ev->isCancelled()){
									$sender->heal($ev->getAmount(), $ev);
								}
							}
						break;
						case "damage":
						case "hurt":
							if($sender instanceof Player && isset($sub[0]) && is_numeric($sub[0]) && $sub[0] >= 1){
								$ev = new \pocketmine\event\entity\EntityDamageEvent($sender, $sub[0], 14);
								if(!$ev->isCancelled()){
									$player->attack($ev->getFinalDamage(), $ev);
								}
							}
	 					break;
						case "teleport":
						case "tp":
							if($sender instanceof Player){
								if(isset($sub[0]) && is_numeric($x = $sub[0]) && isset($sub[1]) && is_numeric($y = $sub[1]) && isset($sub[2]) && is_numeric($z = $sub[2])){
									$pos = [$x,$y,$z];
									if(isset($sub[3]) && $world = $this->getLevelByName($sub[3])){
										$pos[] = $world;
									}else{
										$pos[] = $sender->getLevel();
									}
								}elseif($world = $this->getLevelByName($sub[0])){
									if(isset($sub[1]) && is_numeric($x = $sub[1]) && isset($sub[2]) && is_numeric($y = $sub[2]) && isset($sub[3]) && is_numeric($z = $sub[3])){
										$pos = [$x,$y,$z, $world];
									}else{
										$spawn = $world->getSafeSpawn();
										$pos = [$spawn->z, $spawn->y, $spawn->z, $world];
									}
								}
								if(isset($pos)){
									$sender->teleport(new Position(...$pos));
								}
							}
						break;
						case "jump":
						case "move":
							if($sender instanceof Player && isset($sub[2]) && is_numeric($x = $sub[0]) && is_numeric($y = $sub[1]) && is_numeric($z = $sub[2])){
								if(isset($sub[3]) && $sub[3] == "%"){
									$d = (isset($sub[4]) && is_numeric($sub[4]) && $sub[4] >= 0) ? $sub[4] : (max($x, $y, $z) > 0 ? max($x, $y, $z): -min($x, $y, $z));
									$this->move($sender, (new Vector3($x * 0.4, $y * 0.4 + 0.1, $z * 0.4))->multiply(1.11 / $d), $d, isset($sub[5]) && is_numeric($sub[5]) ? $sub[5]: 0.15, isset($sub[6]) && is_numeric($sub[6]) ? $sub[6]: 1);
								}else{
									$sender->setMotion((new Vector3($x, $y, $z))->multiply(0.4));
								}
							}
						break;
						case "givemoney":
						case "gm":
							if($sender instanceof Player && is_numeric($sub[0]) && is_numeric($sub[0]) && $sub[0] >= 0){
								$this->giveMoney($sender, $sub[0]);
							}
						break;
						case "takemoney":
						case "tm":
							if($sender instanceof Player && is_numeric($sub[0]) && is_numeric($sub[0]) && $sub[0] >= 0){
								$this->giveMoney($sender, -$sub[0]);
							}
						break;
						case "message":
						case "msg":
							$sender->sendMessage($subs);
						break;
						case "broadcast":
						case "say":
						case "broad":
							$this->getServer()->broadcastMessage($subs);
						break;
						case "effect":
						case "ef":
						case "potion":
						case "pot":
							if(isset($sub[0]) && strlen($sub[0]) > 0){
								if(!($effect = Effect::getEffect((int) $sub[0])) instanceof Effect && !($effect = Effect::getEffectByName($sub[0])) instanceof Effect){
									foreach($this->effectTable as $id => $nameArr){
										foreach($nameArr as $name){
											if(strtoupper($sub[0]) == $name){
												if(($effect = Effect::getEffect($id)) instanceof Effect){
													break 2;
												}
											}
										}
									}
								}
								if($effect instanceof Effect){
									if(!isset($sub[1]) || !is_numeric($sub[1])) $sub[1] = 10;
									if(!isset($sub[2]) || !is_numeric($sub[2])) $sub[2] = 1;
									if($sender->hasEffect($id = $effect->getID())){
										$oldEffect = $sender->getEffect($id);
										if($effect->getAmplifier() > $oldEffect->getAmplifier() || $effect->getDuration() < $oldEffect->getDuration()){
											$oldEffect->setAmplifier($sub[2] - 1)->setDuration($sub[1] * 20)->add($sender, true);
											$effects = $this->efffectProperty->getValue($sender);
											$effects[$oldEffect->getID()] = $oldEffect;
											$this->effectProperty->setValue($sender, $effects);
										}
									}else{
										$sender->addEffect($effect->setAmplifier($sub[2] - 1)->setDuration($sub[1] * 20));		
									}
								}else{
									break;
								}
							}
						break;
						case "particle":
						case "par":
							
						break;
						default:
							$change = $match;
						break;
					}
					foreach($scArr = explode(" ", $this->data[$key]) as $scKey => $value){
						if($match == $value){
							if($change == ""){
								unset($scArr[$scKey]);
							}else{
								$scArr[$scKey] = $change;
							}
							break;
						}
					}
					$this->data[$key] = implode(" ", $scArr);
				}
			}
			$explode = $explode2 = explode(" ", $command);
			if(preg_match_all("/@sub([0-9]+)/i", $this->data[$key], $matches)){
				foreach($matches[0] as $matchKey => $match){
					$this->data[$key] = str_replace($match, isset($explode2[$subKey = floor($matches[1][$matchKey])]) ? $explode2[$subKey] : "", $this->data[$key]);
					unset($explode[$matchKey + 1]);
				}
			}
			$command = $this->data[$key] . substr(implode(" ", $explode), strlen($key));
		}
		if(trim($command) == ""){
			return false;
		}else{
			return $command;
		}
	}

	public function replaceMessage(\pocketmine\event\Event $event, $mode){
		if($isPlayer = ($mode == self::MODE_PLAYER || $mode == self::MODE_CHAT)){
			$command = $mode == self::MODE_PLAYER ? substr($event->getMessage(), 1) : $event->getMessage();
			$sender = $event->getPlayer();
			if(!$sender->hasPermission("shortcut.use")){
				return false;
			}
		}elseif($mode == self::MODE_CONSOLE){
			$command = $event->getCommand();
			$sender = $event->getSender();
		}else{
			return false;
		}
		$players = $this->getServer()->getOnlinePlayers();
		if(preg_match_all("/[^.](@([a-zA-Z]+))/", $command = str_replace("%*%", "%&*&%", $command), $matches)){
			foreach($matches[2] as $matchKey => $match){
				switch(strtolower($match)){
					case "player":
					case "p":
						$change = $sender->getName();
					break;
					case "x":
						$change = $isPlayer ? $sender->x : 0;
					break;
					case "y":
						$change = $isPlayer ? $sender->y : 0;
					break;
					case "z":
						$change = $isPlayer ? $sender->z : 0;
					break;
					case "floorx":
					case "fx":
						$change = $isPlayer ? floor($sender->x) : 0;
					break;
					case "floory":
					case "fy":
						$change = $isPlayer ? floor($sender->y) : 0;
					break;
					case "floorz":
					case "fz":
						$change = $isPlayer ? floor($sender->z) : 0;
					break;
					case "ceilx":
					case "cx":
						$change = $isPlayer ? ceil($sender->x) : 0;
					break;
					case "ceily":
					case "cy":
						$change = $isPlayer ? ceil($sender->y) : 0;
					break;
					case "ceilz":
					case "cz":
						$change = $isPlayer ? ceil($sender->z) : 0;
					break;
					case "roundx":
					case "rx":
						$change = $isPlayer ? round($sender->x) : 0;
					break;
					case "roundy":
					case "ry":
						$change = $isPlayer ? round($sender->y) : 0;
					break;
					case "roundz":
					case "rz":
						$change = $isPlayer ? round($sender->z) : 0;
					break;
					case "health":
					case "hp":
						$change = $isPlayer ? $sender->getHelath() : 0;
					break;
					case "maxhealth":
					case "mhp":
						$change = $isPlayer ? $sender->getMaxHelath() : 0;
					break;
					case "getmoney":
					case "money":
					case "mo":
						$change = $isPlayer ? $this->getMoney($sender) : 0;
					break;
					case "worldname":
					case "world":
					case "w":
						$change = isPlayer ? $sender->getLevel()->getName() : $this->getServer()->getDefaultLevel()->getName();
					break;
					case "worldfoldername":
					case "worldfolder":
					case "wf":
						$change = isPlayer ? $sender->getLevel()->getFolderName() : $this->getServer()->getDefaultLevel()->getFolderName();
					break;
					case "all":
					case "a":
						if($sender->isOp() && count($players) > 0) $change = "%*%";
					break;
					case "random":
					case "rand":
					case "r":
						$change = count($players) > 0 ?  $players[array_rand($players)]->getName() : "";
					break;
					case "server":
					case "s":
						$change = $this->getServer()->getServerName();
					break;
					case "version":
					case "v":
						$change = $this->getServer()->getApiVersion();
					break;
				}
				if(isset($change)) $command = str_replace($matches[1][$matchKey], $change, $command);
			}
			if(strpos($command, "%*%") !== false){
		 		$event->setCancelled();
				foreach($players as $allPlayer){
					$allMessage = str_replace(["%*%", "%&*&%"], [$allPlayer->getName(), "%*%"], $command);
					$isPlayetCommand = false;
					if($event instanceof PlayerCommandPreprocessEvent){
						$ev = new PlayerCommandPreprocessEvent($sender, "/" . $allMessage);
						$isPlayerCommand = true;
					}elseif(!$isCommand){
						$this->getServer()->getPluginManager()->callEvent($ev = new PlayerChatEvent($sender, $allMessage));
						if(!$ev->isCancelled()) $this->getServer()->broadcastMessage(sprintf($ev->getFormat(), $ev->getPlayer()->getDisplayName(), $ev->getMessage()), $ev->getRscipients());
						return false;
					}else{
						$ev = new ServerCommandEvent($sender, $allMessage);
					}
					$this->getServer()->getPluginManager()->callEvent($ev);
					if(!$ev->isCancelled()) $this->getServer()->dispatchCommand($sender, $isPlayerCommand ? substr($ev->getMessage(), 1) : $ev->getCommand());
				}
				return false;
			}elseif($isPlayer){
				$event->setMessage($mode == self::MODE_PLAYER ? "/" . $command : $command);
			}elseif($mode == self::MODE_CONSOLE){
				$event->setCommand($command);
			}
		}
	}

	public function getMoney($player){
		if(!$this->money){
			return false;
		}else{
			if($player instanceof Player){
				$player = $player->getName();
			}elseif(!is_string($player)){
				return false;
			}
			switch($this->money->getName()){
				case "PocketMoney":
				case "MassiveEconomy":
				case "Money":
					return $this->money->getMoney($player);
				break;
				case "EconomyAPI":
					return $this->money->mymoney($player);
				break;
				default:
					return false;
				break;
			}
		}
	}

	public function giveMoney($player, $money){
		if(!$this->money){
			return false;
		}else{
			if($player instanceof Player){
				$player = $player->getName();
			}elseif(!is_string($player) || !is_numeric($money) || ($money = floor($money)) <= 0){
				return false;
			}
			switch($this->money->getName()){
				case "PocketMoney":
					$this->money->grantMoney($player, $money);
				break;
				case "EconomyAPI":
					$this->money->setMoney($player, $this->money->mymoney($player) + $money);
				break;
				case "MassiveEconomy":
				case "Money":
					$this->money->setMoney($player, $this->money->getMoney($player) + $money);
				break;
				default:
					return false;
				break;
			}
			return true;
		}
	}

	public function move(Player $player, Vector3 $move, $count, $cool, $countLog = 0){
		if($count - $countLog < 0 || $player->closed || !$player->spawned){
			return;
		}else{
			$countLog++;
			$player->setMotion($move);
			$player->onGround = true;
			if($count >= $countLog) $this->getServer()->getScheduler()->scheduleDelayedTask(new Task($this, [$this,"move"], [$player,$move,$count,$cool,$countLog]), $cool * 20);
		}
	}

	public function getLevelByName($name){
		$levels = $this->getServer()->getLevels();
		foreach($levels as $level){
			if(strtolower($level->getFolderName()) == strtolower($name)){
				return $level;
			}
		}
		foreach($levels as $level){
			if(strtolower($level->getName()) == strtolower($name)){
				return $level;
			}			
		}
		if($this->getServer()->loadLevel($name) !== false){
			return $this->getServer()->getLevelByName($name);
		}
		return false;
	}

	public function getEffectName($name, $amplifier = 1, $duration = 10){
		
	}

	public function loadYml(){
		@mkdir($this->getDataFolder());
		$this->data = (new Config($this->getDataFolder() . "ShortCut.yml", Config::YAML))->getAll();
	}

	public function saveYml(){
		ksort($this->data);
		$sc = new Config($this->getDataFolder() . "ShortCut.yml", Config::YAML);
		$sc->setAll($this->data);
		$sc->save();
	}

	public function isKorean(){
		return $this->getServer()->getLanguage()->getName() == "\"한국어\"";
	}
}