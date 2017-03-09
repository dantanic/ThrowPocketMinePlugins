<?php

namespace FakeServer;

use FakeServer\Packets\AddEntityPacket;
use FakeServer\Packets\AddItemEntityPacket;
use FakeServer\Packets\AddPaintingPacket;
use FakeServer\Packets\AddPlayerPacket;
use FakeServer\Packets\AdventureSettingsPacket;
use FakeServer\Packets\AnimatePacket;
use FakeServer\Packets\BatchPacket;
use FakeServer\Packets\ContainerClosePacket;
use FakeServer\Packets\ContainerOpenPacket;
use FakeServer\Packets\ContainerSetContentPacket;
use FakeServer\Packets\ContainerSetDataPacket;
use FakeServer\Packets\ContainerSetSlotPacket;
use FakeServer\Packets\DataPacket;
use FakeServer\Packets\DropItemPacket;
use FakeServer\Packets\FullChunkDataPacket;
use FakeServer\Packets\Info;
use FakeServer\Packets\SetEntityLinkPacket;
use FakeServer\Packets\TileEntityDataPacket;
use FakeServer\Packets\EntityEventPacket;
use FakeServer\Packets\ExplodePacket;
use FakeServer\Packets\HurtArmorPacket;
use FakeServer\Packets\Info as ProtocolInfo;
use FakeServer\Packets\InteractPacket;
use FakeServer\Packets\LevelEventPacket;
use FakeServer\Packets\DisconnectPacket;
use FakeServer\Packets\LoginPacket;
use FakeServer\Packets\PlayStatusPacket;
use FakeServer\Packets\TextPacket;
use FakeServer\Packets\MoveEntityPacket;
use FakeServer\Packets\MovePlayerPacket;
use FakeServer\Packets\PlayerActionPacket;
use FakeServer\Packets\PlayerArmorEquipmentPacket;
use FakeServer\Packets\PlayerEquipmentPacket;
use FakeServer\Packets\RemoveBlockPacket;
use FakeServer\Packets\RemoveEntityPacket;
use FakeServer\Packets\RemovePlayerPacket;
use FakeServer\Packets\RespawnPacket;
use FakeServer\Packets\SetDifficultyPacket;
use FakeServer\Packets\SetEntityDataPacket;
use FakeServer\Packets\SetEntityMotionPacket;
use FakeServer\Packets\SetHealthPacket;
use FakeServer\Packets\SetSpawnPositionPacket;
use FakeServer\Packets\SetTimePacket;
use FakeServer\Packets\StartGamePacket;
use FakeServer\Packets\TakeItemEntityPacket;
use FakeServer\Packets\TileEventPacket;
use FakeServer\Packets\UpdateBlockPacket;
use FakeServer\Packets\UseItemPacket;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\utils\MainLogger;

class FakeNetwork extends \pocketmine\network\Network{
	private $fakePacketPool;
	protected $plugin;

	public function __construct(Server $server, FakeServer $plugin){
		parent::__construct($server);
		$this->registerFakePackets();
		$this->plugin = $plugin;
	}

	public function registerFakePacket($id, $class){
		$this->fakePacketPool[$id] = new $class;
	}

	public function processBatch(BatchPacket $packet, Player $player){
		$str = zlib_decode($packet->payload, 1024 * 1024 * 64);
		$len = strlen($str);
		$offset = 0;
		try{
			while($offset < $len){
				$pid = ord($str{$offset++};
				if($pid == 0x8f){
					
				}
				if(($pk = $this->getPacket($pid))) !== null){
					if($pk::NETWORK_ID === ($this->plugin->isNewMCPE($player) ? FakeInfo::BATCH_PACKET : Info::BATCH_PACKET)){
						throw new \InvalidStateException("Invalid BatchPacket inside BatchPacket");
					}else{
						$pk->setBuffer($str, $offset);
						$pk->decode();
						if($this->plugin->isNewMCPE($player){
							$this->plugin->handleDataPacket($player, $pk);
						}else{
							$plyer->handleDataPacket($pk);
						}
						$offset += $pk->getOffset();
						if($pk->getOffset() <= 0){
							return;
						}
					}
				}
			}
		}catch(\Exception $e){
			if(\pocketmine\DEBUG > 1){
				$logger = $this->server->getLogger();
				if($logger instanceof MainLogger){
					$logger->debug("BatchPacket " . " 0x" . bin2hex($packet->payload));
					$logger->logException($e);
				}
			}
		}
	}

	public function getFakePacket($id){
		$class = $this->fakePacketPool[$id];
		if($class !== null){
			return clone $class;
		}
		return null;
	}


	private function registerPackets(){
		$this->fakePacketPool = new \SplFixedArray(256);
		$this->registerFakePacket(FakeInfo::LOGIN_PACKET, LoginPacket::class);
		$this->registerFakePacket(FakeInfo::PLAY_STATUS_PACKET, PlayStatusPacket::class);
		$this->registerFakePacket(FakeInfo::DISCONNECT_PACKET, DisconnectPacket::class);
		$this->registerFakePacket(FakeInfo::TEXT_PACKET, TextPacket::class);
		$this->registerFakePacket(FakeInfo::SET_TIME_PACKET, SetTimePacket::class);
		$this->registerFakePacket(FakeInfo::START_GAME_PACKET, StartGamePacket::class);
		$this->registerFakePacket(FakeInfo::ADD_PLAYER_PACKET, AddPlayerPacket::class);
		$this->registerFakePacket(FakeInfo::REMOVE_PLAYER_PACKET, RemovePlayerPacket::class);
		$this->registerFakePacket(FakeInfo::ADD_ENTITY_PACKET, AddEntityPacket::class);
		$this->registerFakePacket(FakeInfo::REMOVE_ENTITY_PACKET, RemoveEntityPacket::class);
		$this->registerFakePacket(FakeInfo::ADD_ITEM_ENTITY_PACKET, AddItemEntityPacket::class);
		$this->registerFakePacket(FakeInfo::TAKE_ITEM_ENTITY_PACKET, TakeItemEntityPacket::class);
		$this->registerFakePacket(FakeInfo::MOVE_ENTITY_PACKET, MoveEntityPacket::class);
		$this->registerFakePacket(FakeInfo::MOVE_PLAYER_PACKET, MovePlayerPacket::class);
		$this->registerFakePacket(FakeInfo::REMOVE_BLOCK_PACKET, RemoveBlockPacket::class);
		$this->registerFakePacket(FakeInfo::UPDATE_BLOCK_PACKET, UpdateBlockPacket::class);
		$this->registerFakePacket(FakeInfo::ADD_PAINTING_PACKET, AddPaintingPacket::class);
		$this->registerFakePacket(FakeInfo::EXPLODE_PACKET, ExplodePacket::class);
		$this->registerFakePacket(FakeInfo::LEVEL_EVENT_PACKET, LevelEventPacket::class);
		$this->registerFakePacket(FakeInfo::TILE_EVENT_PACKET, TileEventPacket::class);
		$this->registerFakePacket(FakeInfo::ENTITY_EVENT_PACKET, EntityEventPacket::class);
		$this->registerFakePacket(FakeInfo::PLAYER_EQUIPMENT_PACKET, PlayerEquipmentPacket::class);
		$this->registerFakePacket(FakeInfo::PLAYER_ARMOR_EQUIPMENT_PACKET, PlayerArmorEquipmentPacket::class);
		$this->registerFakePacket(FakeInfo::INTERACT_PACKET, InteractPacket::class);
		$this->registerFakePacket(FakeInfo::USE_ITEM_PACKET, UseItemPacket::class);
		$this->registerFakePacket(FakeInfo::PLAYER_ACTION_PACKET, PlayerActionPacket::class);
		$this->registerFakePacket(FakeInfo::HURT_ARMOR_PACKET, HurtArmorPacket::class);
		$this->registerFakePacket(FakeInfo::SET_ENTITY_DATA_PACKET, SetEntityDataPacket::class);
		$this->registerFakePacket(FakeInfo::SET_ENTITY_MOTION_PACKET, SetEntityMotionPacket::class);
		$this->registerFakePacket(FakeInfo::SET_ENTITY_LINK_PACKET, SetEntityLinkPacket::class);
		$this->registerFakePacket(FakeInfo::SET_HEALTH_PACKET, SetHealthPacket::class);
		$this->registerFakePacket(FakeInfo::SET_SPAWN_POSITION_PACKET, SetSpawnPositionPacket::class);
		$this->registerFakePacket(FakeInfo::ANIMATE_PACKET, AnimatePacket::class);
		$this->registerFakePacket(FakeInfo::RESPAWN_PACKET, RespawnPacket::class);
		$this->registerFakePacket(FakeInfo::DROP_ITEM_PACKET, DropItemPacket::class);
		$this->registerFakePacket(FakeInfo::CONTAINER_OPEN_PACKET, ContainerOpenPacket::class);
		$this->registerFakePacket(FakeInfo::CONTAINER_CLOSE_PACKET, ContainerClosePacket::class);
		$this->registerFakePacket(FakeInfo::CONTAINER_SET_SLOT_PACKET, ContainerSetSlotPacket::class);
		$this->registerFakePacket(FakeInfo::CONTAINER_SET_DATA_PACKET, ContainerSetDataPacket::class);
		$this->registerFakePacket(FakeInfo::CONTAINER_SET_CONTENT_PACKET, ContainerSetContentPacket::class);
		$this->registerFakePacket(FakeInfo::ADVENTURE_SETTINGS_PACKET, AdventureSettingsPacket::class);
		$this->registerFakePacket(FakeInfo::TILE_ENTITY_DATA_PACKET, TileEntityDataPacket::class);
		$this->registerFakePacket(FakeInfo::FULL_CHUNK_DATA_PACKET, FullChunkDataPacket::class);
		$this->registerFakePacket(FakeInfo::SET_DIFFICULTY_PACKET, SetDifficultyPacket::class);
		$this->registerFakePacket(FakeInfo::BATCH_PACKET, BatchPacket::class);
	}
}