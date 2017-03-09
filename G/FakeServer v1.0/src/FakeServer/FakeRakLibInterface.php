<?php
namespace FakeServer;

use pocketmine\event\player\PlayerCreationEvent;
use pocketmine\network\protocol\DataPacket;
use pocketmine\network\protocol\Info as ProtocolInfo;
use pocketmine\network\protocol\Info;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\utils\MainLogger;
use raklib\protocol\EncapsulatedPacket;
use raklib\RakLib;
use raklib\server\RakLibServer;
use raklib\server\ServerHandler;
use raklib\server\ServerInstance;

class FakeRakLibInterface extends \pocketmine\network\RakLibInterFace{
	protected $plugin;

	public function __construct(Server $server, FakeServer $plugin){
		parent::__construct($server);
 		$this->plugin = $plugin;
	}

	public function process(){
		$work = false;
		if($this->interface->handlePacket()){
			$work = true;
			while($this->interface->handlePacket()){
			}
		}
		if($this->rakLib->isTerminated()){
			$info = $this->rakLib->getTerminationInfo();
			$this->network->unregisterInterface($this);
			\ExceptionHandler::handler(E_ERROR, "RakLib Thread crashed [".$info["scope"]."]: " . (isset($info["message"]) ? $info["message"] : ""), $info["file"], $info["line"]);
		}
		return $work;
	}

	public function handleEncapsulated($identifier, EncapsulatedPacket $packet, $flags){
		if(isset($this->players[$identifier])){
			try{
				if($packet->buffer !== ""){
					$pk = $this->getPacket($packet->buffer);
					if($pk !== null){
						$pk->decode();
						$this->players[$identifier]->handleDataPacket($pk);
					}
				}
			}catch(\Exception $e){
				if(\pocketmine\DEBUG > 1 and isset($pk)){
					$logger = $this->server->getLogger();
					if($logger instanceof MainLogger){
						$logger->debug("Packet " . get_class($pk) . " 0x" . bin2hex($packet->buffer));
						$logger->logException($e);
					}
				}

				if(isset($this->players[$identifier])){
					$this->interface->blockAddress($this->players[$identifier]->getAddress(), 5);
				}
			}
		}
	}

	public function putPacket(Player $player, DataPacket $packet, $needACK = false, $immediate = false){
		if(isset($this->identifiers[$h = spl_object_hash($player)])){
			$identifier = $this->identifiers[$h];
			$pk = null;
			if(!$packet->isEncoded){
				$packet->encode();
			}elseif(!$needACK){
				if(!isset($packet->__encapsulatedPacket)){
					$packet->__encapsulatedPacket = new CachedEncapsulatedPacket;
					$packet->__encapsulatedPacket->identifierACK = null;
					$packet->__encapsulatedPacket->buffer = $packet->buffer;
					if($packet->getChannel() !== 0){
						$packet->__encapsulatedPacket->reliability = 3;
						$packet->__encapsulatedPacket->orderChannel = $packet->getChannel();
						$packet->__encapsulatedPacket->orderIndex = 0;
					}else{
						$packet->__encapsulatedPacket->reliability = 2;
					}
				}
				$pk = $packet->__encapsulatedPacket;
			}

			if(!$immediate and !$needACK and $packet::NETWORK_ID !== ProtocolInfo::BATCH_PACKET
				and Network::$BATCH_THRESHOLD >= 0
				and strlen($packet->buffer) >= Network::$BATCH_THRESHOLD){
				$this->server->batchPackets([$player], [$packet], true, $packet->getChannel());
				return null;
			}

			if($pk === null){
				$pk = new EncapsulatedPacket();
				$pk->buffer = $packet->buffer;
				if($packet->getChannel() !== 0){
					$packet->reliability = 3;
					$packet->orderChannel = $packet->getChannel();
					$packet->orderIndex = 0;
				}else{
					$packet->reliability = 2;
				}

				if($needACK === true){
					$pk->identifierACK = $this->identifiersACK[$identifier]++;
				}
			}

			$this->interface->sendEncapsulated($identifier, $pk, ($needACK === true ? RakLib::FLAG_NEED_ACK : 0) | ($immediate === true ? RakLib::PRIORITY_IMMEDIATE : RakLib::PRIORITY_NORMAL));

			return $pk->identifierACK;
		}

		return null;
	}

	private function getPacket($buffer){
		$pid = ord($buffer{0});
		if(($data = $this->network->getPacket($pid)) === null){
			return null;
		}
		$data->setBuffer($buffer, 1);
		return $data;
	}
}