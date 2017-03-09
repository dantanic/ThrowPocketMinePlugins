<?php
/*
__PocketMine Plugin__
name=EconomyShop
version=1.2.2
author=onebone
apiversion=12,13
class=EconomyShop
*/

class EconomyShop implements Plugin {
	private $api, $path, $tap, $shop;
	public $config;
	
	public function __construct(ServerAPI $api, $server = false){
		$this->api = $api;
		$this->shop = array();
		$this->tap = array();
	}
	
	public function init(){
	 	$this->api->console->register("shop", "",array($this,"Commander"));
		$this->createConfig();
		$this->createMessageConfig();
		foreach($this->api->plugin->getList() as $p){
			if($p["name"] == "EconomyAPI" and $this->config["compatible-to-economyapi"] or $p["name"] == "PocketMoney" and !$this->config["compatible-to-economyapi"]){
				$exist = true;
			}
		}
		if(!isset($exist)){
			console("[ERROR] Cannot find ".($this->config["compatible-to-economyapi"] ? "EconomyAPI" : "PocketMoney"));
			$this->api->console->defaultCommands("stop", "", "plugin", false);
			return;
		}
		$this->path = $this->api->plugin->configPath($this);
		$this->loadItems();
		$this->api->addHandler("player.block.touch", array($this, "handler"));
		$this->api->event("tile.update", array($this, "handler"));
		$this->api->event("server.close", array($this, "handler"));
		$this->shopdata = new Config($this->path."Shops.yml", CONFIG_YAML);
		$shops = $this->api->plugin->readYAML($this->path."Shops.yml");
		if($this->config["check-data"]){
			$this->checkData($shops);
		}
		foreach($shops as $s){
			$this->shop[] = array(
				"x" => (int)$s["x"],
				"y" => (int)$s["y"],
				"z" => (int)$s["z"],
				"item" => (int)$s["item"],
				"amount" => (int)$s["amount"],
				"price" => (float)$s["price"],
				"level" => $s["level"],
				"meta" => (int)$s["meta"]
			);
		}
		$this->api->economy->EconomySRegister("EconomyShop");
		EconomyShopAPI::set($this);
	}
	
	public function __destruct(){}
	 	public function Commander($cmd,$params,$issuer){
			$this->shopdata->setAll($this->shop);
			$this->shopdata->save();
	}

	private function checkData(&$data){
		$err = 0;
		foreach($data as $key => $shop){
			if(!isset($shop["x"]) or !isset($shop["y"]) or !isset($shop["z"]) or !isset($shop["item"]) or !isset($shop["amount"]) or !isset($shop["price"]) or !isset($shop["level"]) or !isset($shop["meta"])){
				++$err;
				unset($data[$key]);
			}elseif($shop["x"] < 0 or $shop["y"] < 0 or $shop["z"] < 0 or $shop["item"] < 0 or $shop["amount"] < 0){
				++$err;
				unset($data[$key]);
			}
		}
		if($err > 0){
			$s = ($err > 1) ? "s" : "";
			console(FORMAT_DARK_RED."[EconomyShop] Has been found $err error{$s} in data file. Removed all invalid shops.");
		}
	}
	
	private function createConfig(){
		$this->config = $this->api->plugin->readYAML($this->api->plugin->createConfig($this, array(
			"compatible-to-economyapi" => true,
			"frequent-save" => false,
			"check-data" => true
		))."config.yml");
	}
	
	private function createMessageConfig(){
		$this->shopSign = (new Config(DATA_PATH."plugins/EconomyShop/ShopText.yml", CONFIG_YAML, array(
			"shop" => array(
				"[SHOP]",
				"$%1",
				"%2",
				"Amount : %3"
			)
		)))->getAll();
		
		$this->lang = new Config(DATA_PATH."plugins/EconomyShop/language.properties", CONFIG_PROPERTIES, array(
			"create-shop-no-permission" => "You don't have permission to create shop",
			"shop-data-incorrect" => "Shop data is incorrect",
			"failed-creating-shop" => "Failed creating shop due to unknown error",
			"item-not-support" => "Item %1 doesn't support at EconomyShop",
			"shop-created" => "Shop created",
			"error-creative-mode" => "You are in creative mode",
			"message-confirm" => "Are you sure to buy this? Tap again to confirm",
			"no-money" => "You don't have money to buy this",
			"bought-item" => "Has been bought %1 of %2 for $%3",
		));
	}
	
	public function getMessage($key, $value = array("%1", "%2", "%3")){
		if($this->lang->exists($key)){
			return str_replace(array("%1", "%2", "%3"), array($value[0], $value[1], $value[2]), $this->lang->get($key));
		}
		return "Undefined message \"$key\"";
	}
	
	public function convertDataToSign(Tile $tile, $line3){
		foreach($this->shopSign as $line1 => $line){
			if($tile->data["Text1"] == $line1){
				$tile->data["Text1"] = $line[0];
				if(is_numeric($tile->data["Text2"]) or is_numeric($tile->data["Text3"]) or is_numeric($tile->data["Text4"])){
					$tile->data["Text2"] = str_replace("%1", $tile->data["Text2"], $line[1]);
					$tile->data["Text3"] = str_replace("%2", $line3, $line[2]);
					$tile->data["Text4"] = str_replace("%3", $tile->data["Text4"], $line[3]);
					$this->api->tile->spawnToAll($tile);
					return 1;
				}else{
					return -1;
				}
			}
		}
		return 0;
	}
	
	public function tagExists($tag){
		foreach($this->shopSign as $line){
			if($tag == $line[0]){
				return true;
			}
		}
		return false;
	}
	
	public function topExists($top){
		foreach($this->shopSign as $line1 => $line){
			if($top == $line1){
				return true;
			}
		}
		return false;
	}
	
	public function editShop($x, $y, $z, $level, $price, $item, $amount, $meta){
		if(is_array($this->shop)){
			foreach($this->shop as $k => $s){
				if($s["x"] == $x and $s["y"] == $y and $s["z"] == $z and $s["level"] == $level){
					$this->shop[$k] = array("level" => $level, "x" => $x, "y" => $y, "z" => $z, "price" => $price, "item" => $item, "amount" => $amount, "meta" => $meta);
					if($this->config["frequent-save"]){
						$this->shopdata->setAll($this->shop);
						$this->shopdata->save();
					}
					return true;
				}
			}
		}
		return false;
	}
	
	public function getShops(){
		return is_array($this->shop) ? $this->shop : array();
	}
	
	public function handler($data, $event){
		$output = "";
		switch ($event){
		case "tile.update":
			if($data->class === TILE_SIGN){
				$issuer = $this->api->player->get($data->data["creator"], false);
				if(!$issuer instanceof Player)
					return;
				if($this->topExists($data->data["Text1"])){
					if($this->api->ban->isOp($issuer->username) == false){
						$output .= $this->getMessage("create-shop-no-permission");
						break;
					}
					if($data->data["Text2"] == "" or $data->data["Text2"] == "" or $data->data["Text3"] == "" or $data->data["Text4"] == ""){
						$issuer->sendChat($this->getMessage("shop-data-incorrect"));
					}else{
						$data->data["Text3"] = strtolower($data->data["Text3"]);
						$e = explode(":", $data->data["Text3"]);
						if(count($e) == 1){
							$e[1] = 0;
						}
						if(strpos($data->data["Text3"], ":") !== false){
							$e = explode(":", $data->data["Text3"]);
						}else{
							$e = explode(":", $data->data["Text3"]);
							$e[1] = isset($e[1]) ? $e[1] : 0;
							if(is_numeric($e[0]) and is_numeric($e[1])){
								$e[0] = $data->data["Text3"];
								$e[1] = 0;
							}else{
								$e = explode(":", $data->data["Text3"]);
								$e[1] = isset($e[1]) ? $e[1] : 0;
							}
						}
						if(is_numeric($e[0]) and is_numeric($e[1])){
							$name = $this->getItem($e[0].":".$e[1]);
							if($name == false){
								$issuer->sendChat($this->getMessage("item-not-support", array($data->data["Text3"], "", "")));
								break;
							}
						}else{
							$id = $this->getItem($data->data["Text3"]);
							if($id == false){
								$issuer->sendChat($this->getMessage("item-not-support", array($data->data["Text3"], "", "")));
								break;
							}
							$e = explode(":", $id);
							$e[1] = isset($e[1]) ? $e[1] : 0;
						}
						if($this->api->dhandle("economyshop.shop.create", array("player" => $issuer, "x" => $data->x, "y" => $data->y, "z" => $data->z, "level" => $data->level->getName(), "item" => $e[0], "meta" => $e[1], "price" => str_replace("\$", "", $data->data["Text2"]), "amount" => str_replace(array("Amount", "수량"), "", $data->data["Text4"]))) !== false){
							$this->convertDataToSign($data, isset($name) ? $name : $data->data["Text3"]);
							$this->createShop(array("x" => $data->data["x"], "y" => $data->data["y"], "z" => $data->data["z"], "item" => $e[0], "price" => str_replace("\$", "", $data->data["Text2"]), "amount" => str_replace("Amount : ", "", $data->data["Text4"]), "level" => $data->level->getName(), "meta" => $e[1]));
							$output .= $this->getMessage("shop-created");
						}else{
							$output .= $this->getMessage("failed-creating-shop");
						}
					}
				}
				$issuer->sendChat($output);
			}
			break;
		case "player.block.touch":
			$target = $data["target"]->getID();
			if($data["type"] == "break"){
			if($target == 323 or $target == 63 or $target == 68){
					if(!is_array($this->shop)){
						break;
					}
					foreach($this->shop as $key => $s){
						if($s["x"] == $data["target"]->x and $s["y"] == $data["target"]->y and $s["z"] == $data["target"]->z and $data["target"]->level->getName() == $s["level"]){
							if($this->api->ban->isOp($data["player"]->username) == false){
								$data["player"]->close("tried to destroy shop");
								return false;
							}
							unset($this->shop[$key]);
							if($this->config["frequent-save"]){
								$this->shopdata->setAll($this->shop);
								$this->shopdata->save();
							}
						}
					}
				}
				break;
			} /// here ///
			$target = $data["target"]->getID();
			//	$issuer = $data["player"];
				if($data["target"]->getID() !== 0){
				if(!is_array($this->shop)){
					break;
				}
				foreach($this->shop as $k => $s){
					if($s["x"] == $data["target"]->x and $s["y"] == $data["target"]->y and $data["target"]->z == $s["z"] and $data["target"]->level->getName() == $s["level"]){
						if($data["player"]->gamemode == CREATIVE){
							$data["player"]->sendChat($this->getMessage("error-creative-mode"));
							return false;
						}
						$level = $this->api->level->get($s["level"]);
						if($level !== false){
							$t = $this->api->tile->get(new Position($s["x"], $s["y"], $s["z"], $level));
							if($t == false or !$this->tagExists($t->data["Text1"])){
								unset($this->shop[$k]);
								break 2;
							}
						}else{
							unset($this->shop[$k]);
							break 2;
						}
						if(!isset($this->tap[$data["player"]->username])){
							$this->tap[$data["player"]->username] = array("x" => $s["x"], "y" => $s["y"], "z" => $s["z"]);
							$this->api->schedule(20, array($this, "removeTap"), $data["player"]->username);
							$data["player"]->sendChat($this->getMessage("message-confirm"));
							break;
						}
						if(!($s["x"] == $this->tap[$data["player"]->username]["x"] and $s["y"] == $this->tap[$data["player"]->username]["y"] and $s["z"] == $this->tap[$data["player"]->username]["z"])){
							$data["player"]->sendChat($this->getMessage("message-confirm"));
							$this->tap[$data["player"]->username] = array("x" => $s["x"], "y" => $s["y"], "z" => $s["z"]);
							$this->api->schedule(20, array($this, "removeTap"), $data["player"]->username);
							$this->cancel[$data["player"]->username] = true;
							break;
						}
						$can = false;
						if($this->config["compatible-to-economyapi"]){
							$can = $this->api->economy->useMoney($data["player"], $s["price"]);
						}else{
							$can = $this->api->dhandle("money.handle", array(
								"username" => $data["player"]->username,
								"method" => "grant",
								"amount" => -$s["price"],
							));
						}
						if($can == false){
							$data["player"]->sendChat($this->getMessage("no-money"));
							return false;
						}
						$data["player"]->addItem((int)$s["item"], (int)$s["meta"], (int)$s["amount"]);
						$output .= $this->getMessage("bought-item", array($s["amount"], $s["item"].":".$s["meta"], $s["price"]));
						if(isset($this->tap[$data["player"]->username])){
							unset($this->tap[$data["player"]->username]);
						}
						$data["player"]->sendChat($output);
						return false;
					}
				}
			}
			break;
		case "server.close":
			$this->shopdata->setAll($this->shop);
			$this->shopdata->save();
			break;
		}
	}
	
	public function removeTap($username){
		if(isset($this->cancel[$username])){
			unset($this->cancel[$username]);
			return false;
		}
		if(isset($this->tap[$username])) 
			unset($this->tap[$username]);
	}
	
	public function createShop($shopdata){
		$this->shop[] = array(
			"x" => (int)$shopdata["x"],
			"y" => (int)$shopdata["y"],
			"z" => 	(int)$shopdata["z"],
			"item" => (int)$shopdata["item"],
			"amount" => (int)$shopdata["amount"],
			"price" => (float)$shopdata["price"],
			"level" => $shopdata["level"],
			"meta" => (int)$shopdata["meta"]
		);
		if($this->config["frequent-save"]){
			$this->shopdata->setAll($this->shop);
			$this->shopdata->save();
		}
	}
	
	public function getItem($item){ // gets ItemID and ItemName
		$e = explode(":", $item);
		if(count($e) > 1){
			if(is_numeric($e[0])){
				foreach($this->items as $k => $i){
					$item = explode(":", $i);
					$e[1] = isset($e[1]) ? $e[1] : 0;
					$item[1] = isset($item[1]) ? $item[1] : 0;
					if($e[0] == $item[0] and $e[1] == $item[1]){
						return $k;
					}
				}
				return false;
			}
		}else{
			$item = strtolower($item);
			if(isset($this->items[$item])){
				return $this->items[$item];
			}else{
				return false;
			}
		}
	}
  public function loadItems(){
	$items = array();
	if(!is_file($this->path."items.properties")){
	$items = new Config($this->path."items.properties", CONFIG_PROPERTIES, array(
		"air" => 0,
		"stone" => 1,
		"grassblock" => 2,
		"dirt" => 3,
		"cobblestone" => 4,
		"woodenplank" => 5,
		"treesapling" => 6,
		"firsapling" => "6:1",
		"birchsapling" => "6:2",
		"bedrock" => 7,
		"water" => 8,
		"stationarywater" => 9,
		"lava" => 10,
		"stationarylava" => 11,
		"sand" => 12,
		"gravel" => 13,
		"goldore" => 14,
		"ironore" => 15,
		"coalore" => 16,
		"tree" => 17,
		"oakwood" => "17:1",
		"birchwood" => "17:2",
		"treeleaf" => "18",
		"oaktreeleaf" => "18:1",
		"birchtreeleaf" => "18:2",
		"sponge" => 19,
		"glass" => 20,
		"lapisore" => 21,
		"lapisblock" => 22,
		"sandstone" => 24,
		"sandstone2" => "24:1",
		"sandstone3" => "24:2",
		"bed" => 26,
		"poweredrail" => 27,
		"cobweb" => 30,
		"bush" => 31,
		"whitewool" => 35,
		"orangewool" => "35:1",
		"magentawool" => "35:2",
		"skywool" => "35:3",
		"yellowwool" => "35:4",
		"greenwool" => "35:5",
		"pinkwool" => "35:6",
		"greywool" => "35:7",
		"greywool2" => "35:8",
		"bluishwool" => "35:9",
		"purplewool" => "35:10",
		"bluewool" => "35:11",
		"brownwool" => "35:12",
		"greenwool2" => "35:13",
		"redwool" => "35:14",
		"blackwool" => "35:15",
		"yellowflower" => 37,
		"blueflower" => 38,
		"brownmushroom" => 39,
		"redmushroom" => 40,
		"goldblock" => 41,
		"ironblock" => 42,
		"stonefoothold" => 43,
		"sandfoothold" => "43:1",
		"woodfoothold" => "43:2",
		"cobblefoothold" => "43:3",
		"brickfoothold" => "43:4",
		"stonefoothold2" => "43:6",
		"halfstone" => 44,
		"halfsand" => "44:1",
		"halfwood" => "44:2",
		"halfcobble" => "44:3",
		"halfbrick" => "44:4",
		"halfstone2" => "44:6",
		"brick" => 45,
		"tnt" => 46,
		"bookshelf" => 47,
		"mossstone" => 48,
		"obsidian" => 49,
		"torch" => 50,
		"fire" => 51,
		"woodstair" => 53,
		"chest" => 54,
		"diamondore" => 56,
		"diamondblock" => 57,
		"craftingtable" => 58,
		"crop" => 59,
		"farmland" => 60,
		"furnace" => 61,
		"signblock" => 63,
		"burningfurnace" => 62,
		"woodendoor" => 64,
		"ladder" => 65,
		"cobblestair" => 67,
		"wallsign" => 68,
		"irondoor" => 71,
		"redstoneore" => 73,
		"glowredstone" => 74,
		"snow" => 78,
		"ice" => 79,
		"snowblock" => 80,
		"cactus" => 81,
		"clayblock" => 82,
		"sugarcane" => 83,
		"fence" => 85,
		"pumpkin" => 86,
		"netherrack" => 87,
		"glowingstone" => 89,
		"jack-o-lanton" => 91,
		"cake" => 92,
		"invisiblebedrock" => 95,
		"trapdoor" => 96,
		"stonebrick" => 98,
		"mossbrick" => "98:1",
		"crackedbrick" => "98:2",
		"ironbars" => 101,
		"flatglass" => 102,
		"watermelon" => 103,
		"fencegate" => 107,
		"brickstair" => 108,
		"stonestair" => 109,
		"netherbrick" => 112,
		"netherbrickstair" => 114,
		"sandstair" => 128,
		"growingcarrot" => 141,
		"growingpotato" => 142,
		"quartzblock" => 155,
		"softquartz" => "155:1",
		"pilliarquartz" => "155:2",
		"quartzstair" => 156,
		"haybale" => 170,
		"carpet" => 171,
		"coalblock" => 173,
		"beetroot" => 244,
		"stonecutter" => 245,
		"glowingobsidian" => 246,
		"nethercore" => 247,
		"updateblock1" => 248,
		"updateblock2" => 249,
		"errorgrass" => 253,
		"errorleaves" => 254,
		"errorstone" => 255,
		"ironshovel" => 256,
		"ironpickaxe" => 257,
		"ironaxe" => 258,
		"flintandsteel" => 259,
		"apple" => 260,
		"bow" => 261,
		"arrow" => 262,
		"coal" => 263,
		"charcoal" => "263:1",
		"diamond" => 264,
		"ironingot" => 265,
		"goldingot"=> 266,
		"ironsword" => 267,
		"woodsword" => 268,
		"woodshovel" => 269,
		"woodpickaxe" => 270,
		"woodaxe" => 271,
		"stonesword" => 272,
		"stoneshovel" => 273,
		"stonepickaxe" => 274,
		"stoneaxe" => 275,
		"diamondsword" => 276,
		"diamondshovel" => 277,
		"diamondpickaxe" => 278,
		"diamondaxe" => 279,
		"stick" => 280,
		"bowl" => 281,
		"mushroomstew" => 282,
		"goldsword" => 283,
		"goldshovel" => 284,
		"goldpickaxe" => 285,
		"goldaxe" => 286,
		"web" => 287,
		"feather" => 288,
		"gunpowder" => 289,
		"woodhoe" => 290,
		"stonehoe" => 291,
		"ironhoe" => 292,
		"diamondhoe" => 293,
		"goldhoe" => 294,
		"seed" => 295,
		"wheat" => 296,
		"bread" => 297,
		"leatherhat" => 298,
		"leatherarmor" => 299,
		"leatherpants" => 300,
		"leatherboots" => 301,
		"chairhat" => 302,
		"chainchestplate" => 303,
		"chainlegging" => 304,
		"chainboots" => 305,
		"ironhelmet" => 306,
		"ironchestplate" => 307,
		"ironlegging"=> 308,
		"ironboots" => 309,
		"diamondhelmet" => 310,
		"diamondchestplate" => 311,
		"diamondlegging" => 312,
		"diamondboots" => 313,
		"goldhelmet" => 314,
		"goldchestplate" => 315,
		"goldlegging" => 316,
		"goldboots" => 317,
		"flint" => 318,
		"rawpork" => 319,
		"pork" => 320,
		"paint" => 321,
		"sign" => 323,
		"door" => 324,
		"bucket" => 325,
		"waterbucket" => 326,
		"irondoor" => 330,
		"redstone" => 331,
		"snowball" => 332,
		"leather" => 334,
		"claybrick" => 336,
		"clay" => 337,
		"sugarcane" => 338,
		"paper" => 339,
		"book" => 340,
		"slimeball" => 341,
		"egg" => 344,
		"compass" => 345,
		"clock" => 347,
		"glowstone" => 348,
		"ink" => 351,
		"redrose" => "351:1",
		"greencactus" => "351:2",
		"cocoabean" => "351:3",
		"lapislazuli" => "351:4",
		"cotton" => "351:5",
		"bluish" => "351:6",
		"lightgrey" => "351:7",
		"grey" => "351:8",
		"pink" => "351:9",
		"lightgreen" => "351:10",
		"yellow" => "351:11",
		"sky" => "351:12",
		"magenta"=> "351:13",
		"orange" => "351:14",
		"bonemeal" => "351:15",
		"bone" => 352,
		"sugar" => 353,
		"cake" => 354,
		"bed" => 355,
		"scissors" => 259,
		"melon" => 360,
		"pumpkinseed" => 361,
		"melonseed" => 362,
		"rawbeef" => 363,
		"stake" => 364,
		"rawchicken" => 365,
		"chicken" => 366,
		"carrot" => 391,
		"potato" => 392,
		"cookedpotato" => 393,
		"pumpkinpie" => 400,
		"netherbrick" => 405,
		"hellquartz" => 406,
		"camera" => 456,
		"beetroot" => 457,
		"beetrootseed" => 458,
		"beetrootsoup" => 459
		));
	}else{
		$items = new Config($this->path."items.properties", CONFIG_PROPERTIES);
	}
	$this->items = $items->getAll();
	}
}

class EconomyShopAPI { // Use this for EconomyShop!
	public static $p;
	public static function set(EconomyShop $e){
		if(EconomyShopAPI::$p instanceof EconomyShop){
			return false;
		}
		EconomyShopAPI::$p = $e;
		return true;
	}
	public static function getShops(){
		return EconomyShopAPI::$p->getShops();
	}
	public static function editShop($data){
		return EconomyShopAPI::$p->editShop($data["x"], $data["y"], $data["z"], $data["level"], $data["price"], $data["item"], $data["amount"], $data["meta"]);
	}
}