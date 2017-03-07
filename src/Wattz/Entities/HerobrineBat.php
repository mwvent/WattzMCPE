<?php 
namespace Wattz\Entities;
use Wattz\Main;
use pocketmine\Server;

use pocketmine\entity\Effect;
use pocketmine\level\format\FullChunk;
use pocketmine\nbt\tag\ByteTag;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\DoubleTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\nbt\tag\FloatTag;
use pocketmine\nbt\tag\ShortTag;
use pocketmine\nbt\tag\StringTag;

use pocketmine\level\sound\BatSound;

use pocketmine\entity\Entity;
use pocketmine\entity\Creature;
use pocketmine\network\Network;
use pocketmine\network\protocol\AddEntityPacket;
use pocketmine\Player;


class HerobrineBat extends Creature {
	//const NETWORK_ID=19; // 19 bat
	const DATA_BAT_FLAGS = 2;
	public $batflags = 0;
	private $currentDirection;
	public $lifeTime = 40;
	public $NETWORK_ID;
	public function __construct(FullChunk $chunk, CompoundTag $nbt, $sourceEntity = null) {
	    if( is_null($sourceEntity) ) { // not spawned by plugin - may have been left over from a crash - just close
		parent::__construct($chunk, $nbt); // need to allow construction to finish or crash
		$this->close();
		return;
	    }
	    $possibleTypes = array (
		//38, // enderman
		//41, // ghast
		42, // magma cube
		//43, // blaze
		//44, // zombie villager
		//10, // chicken
		85 // fireball
	    );
	    $this->NETWORK_ID = $possibleTypes[array_rand($possibleTypes)];
	    $this->batflags = 0;
	    $chunk=$sourceEntity->getLevel()->getChunk($sourceEntity->getX()>>4, $sourceEntity->getZ()>>4);
	    $nbt = new CompoundTag;
	    $nbt->Pos = new ListTag("Pos", [
	      new DoubleTag("", $sourceEntity->getX()),
	      new DoubleTag("", $sourceEntity->getY()),
	      new DoubleTag("",  $sourceEntity->getZ())
	    ]);
	    $nbt->Rotation = new ListTag("Rotation", [
		new FloatTag("", $sourceEntity->getYaw()),
		new FloatTag("", $sourceEntity->getPitch())
		    ]);
	    $nbt->Health = new ShortTag("Health", 2);
	    $nbt->NameTag = new StringTag("name","HerobrineBat");
	    $nbt->Invulnerable = new ByteTag("Invulnerable", 1);
	    $nbt->BatFlags = new ByteTag("BatFlags", $this->batflags);
	    $nbt->BatFlags = new ByteTag("ED1", $this->batflags);
	    
	    parent::__construct($chunk, $nbt);
	    $this->setNameTagVisible(false);
	    $this->spawnToAll();
	    $this->setNameTagVisible(false);
	    $this->currentDirection = rand(1,8);
	    $this->getLevel()->addSound(new BatSound($this), null);
	}
	/*
	public function spawnTo(Player $player){
		$pk = new AddEntityPacket();
		$pk->eid = $this->getId();
		$pk->type = self::NETWORK_ID;
		$pk->x = $this->x;
		$pk->y = $this->y;
		$pk->z = $this->z;
		$pk->speedX = $this->motionX;
		$pk->speedY = $this->motionY;
		$pk->speedZ = $this->motionZ;
		$pk->yaw = $this->yaw;
		$pk->pitch = $this->pitch;
		$pk->metadata = [
				Entity::DATA_NAMETAG => [Entity::DATA_TYPE_STRING, $this->getDataProperty(2)],
				Entity::DATA_SHOW_NAMETAG => [Entity::DATA_TYPE_BYTE, 1],
				Entity::DATA_NO_AI => [Entity::DATA_TYPE_BYTE, 1],
				$this::DATA_BAT_FLAGS => [Entity::DATA_TYPE_BYTE, 2],
        ];
		$player->dataPacket($pk->setChannel(Network::CHANNEL_ENTITY_SPAWNING));
		parent::spawnTo($player);
	}
	*/
	
	public function spawnTo(Player $player){
		$pk = new AddEntityPacket();
		$pk->eid = $this->getId();
		$pk->type = $this->NETWORK_ID;
		$pk->x = $this->x;
		$pk->y = $this->y;
		$pk->z = $this->z;
		$pk->speedX = $this->motionX;
		$pk->speedY = $this->motionY;
		$pk->speedZ = $this->motionZ;
		$pk->yaw = $this->yaw;
		$pk->pitch = $this->pitch;
		// 
		 $this->dataProperties = [
				Entity::DATA_NAMETAG => [Entity::DATA_TYPE_STRING, $this->getDataProperty(2)],
				Entity::DATA_SHOW_NAMETAG => [Entity::DATA_TYPE_BYTE, 1],
				Entity::DATA_NO_AI => [Entity::DATA_TYPE_BYTE, 1],
				$this::DATA_BAT_FLAGS => [Entity::DATA_TYPE_BYTE, $this->batflags],
		];
		$pk->metadata = $this->dataProperties;
		$player->dataPacket($pk);
		parent::spawnTo($player);
	}
	
	public function getName(){
		return "Herobrines Bat";
	}

	public function poofAway() {
	    $this->close();
	}
	
	public function onUpdate($currentTick) {
	    $ms = 0.5;
	    $y = 0.2;
	    switch($this->currentDirection) {
		case 1 : 
		    $x = $ms; $z = 0;
		    break;
		case 2 :
		    $x = -$ms; $z = 0;
		    break;
		case 3 :
		    $z = $ms; $x = 0;
		    break;
		case 4 :
		    $z = -$ms; $x = 0;
		    break;
		case 5 : 
		    $x = $ms; $z = -$ms;
		    break;
		case 6 :
		    $x = -$ms; $z = $ms;
		    break;
		case 7 :
		    $z = $ms; $x = -$ms;
		    break;
		case 8 :
		    $z = -$ms; $x = $ms;
		    break;
	    }
	    if(rand(1,5) == 1) {
		$this->currentDirection = rand(1,8);
	    }
	    if($y > 127) {
		$this->poofAway();
	    }
	    $this->lifeTime--;
	    if($this->lifeTime < 1) {
		$this->poofAway();
	    }
	    $this->updateFallState(100,false);
	    $this->move($x, $y, $z);
	    return parent::onUpdate($currentTick);
	}
	
	public function knockBack(Entity $attacker, $damage, $x, $z, $base = 0.4) {
	    Server::getInstance()->getLogger()->info(Main::PREFIX  . "Herobrines bat attacked by " . $attacker->getName());
	    $attacker->knockBack($this,$damage * 2,-$x * 300,-$z * 300, $base);
	    $attacker_effect = Effect::getEffect(Effect::CONFUSION);
	    $attacker_effect->setDuration(15 * 20);
	    $attacker->addEffect($attacker_effect);
	    parent::knockBack($attacker,0,0,0,0);
	    $this->close();
	}
}
