<?php
namespace Wattz\Entities;
use Wattz\Main;
use pocketmine\Server;

use pocketmine\command\CommandSender;
use pocketmine\entity\Human;
use pocketmine\entity\Effect;
use pocketmine\level\format\FullChunk;
use pocketmine\level\Location;
use pocketmine\level\Position;
use pocketmine\nbt\tag\Byte;
use pocketmine\nbt\tag\Compound;
use pocketmine\nbt\tag\Double;
use pocketmine\nbt\tag\Enum;
use pocketmine\nbt\tag\Float;
use pocketmine\nbt\tag\Short;
use pocketmine\nbt\tag\String;
use pocketmine\permission\PermissibleBase;
use pocketmine\permission\PermissionAttachment;
use pocketmine\plugin\Plugin;
use pocketmine\Player;

use pocketmine\math\Vector3;
use pocketmine\math\AxisAlignedBB;

use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\network\protocol\EntityEventPacket;

use pocketmine\entity\Entity;
use pocketmine\network\Network;
use pocketmine\network\protocol\AddEntityPacket;

use pocketmine\level\format\mcregion\Chunk;

use pocketmine\item\Item;

class UndeadPlayer extends Human implements CommandSender {
	const NETWORK_ID=0;
	
	public $targetPlayer;
	public $lastNearestPlayer;
	public $targetDist;
	public $plugin;
	public $thisname;
	
	public static $range = 32;
	public static $speed = 0.1;
	public static $jump = 2.5;
	public static $attack = 1.5;
	public $width = 0.6;
	public $length = 0.6;
	public $height = 1.8;
	public $stepHeight = 0.5;
	public $knockback = 0;
	
	public function __construct(FullChunk $chunk, Compound $nbt, $targetPlayer = null, $entityToCopyCoords = null, $plugin = null) {
		// if $targetPlayer is null pocketmine is probably trying to reload a saved
		// version of this entity which we do not want anymore - as it is not cancellable ( I think ? )
		// just let it create and the update function will despawn the entity immediatley when it finds
		// it to have no targetPlayer
		if( is_null ($targetPlayer) ) {
		    Server::getInstance()->getLogger()->info(Main::PREFIX  .  " and old Undead NPC was loaded - despawning");
		    parent::__construct($chunk, $nbt);
		    $this->close();
		    return;
		} 
		// if targetPlayer was set we can create chunk and nbt values outselves
		$this->plugin = $plugin;
		$this->thisname = "Undead " . $targetPlayer->getName();
		$name = $this->thisname;
		//$IsSlim = $targetPlayer->isSkinSlim();
		$playerX = $entityToCopyCoords->getX();
		$playerY = $entityToCopyCoords->getY();
		$playerZ = $entityToCopyCoords->getZ();
		$outX=round($playerX,1);
		$outY=round($playerY,1);
		$outZ=round($playerZ,1);
		$playerLevel = $entityToCopyCoords->getLevel()->getName();
		$playerYaw = $entityToCopyCoords->getYaw();
		$playerPitch = $entityToCopyCoords->getPitch();
		$humanInv = $targetPlayer->getInventory();
		$pHealth = 1;
		$pHeldItem=$targetPlayer->getInventory()->getItemInHand();
		
		$nbt = new Compound;
		$motion = new Vector3(0,0,0);
		$nbt->Pos = new Enum("Pos", [
		  new Double("", $outX),
		  new Double("", $outY),
		  new Double("", $outZ)
		]);
		$nbt->Motion = new Enum("Motion", [
		  new Double("", $motion->x),
		  new Double("", $motion->y),
		  new Double("", $motion->z)
		]);
		$nbt->Rotation = new Enum("Rotation", [
		    new Float("", $playerYaw),
		    new Float("", $playerPitch)
		]);
		$nbt->Health = new Short("Health", $pHealth);
		$nbt->Inventory = new Enum("Inventory", $humanInv);
		$nbt->NameTag = new String("name", $this->thisname);
		//$nbt->Invulnerable = new Byte("Invulnerable", 0);
		$nbt->CustomTestTag = new Byte("CustomTestTag", 1);
		
		// get skin and make scary
		$skindata = $targetPlayer->getSkinData();
		$skindata = $this->negaskin($skindata);
		
		$nbt->Skin = new Compound("Skin", [
		    "Data" => new String("Data", $skindata)
		    // "Slim" => new Byte("Slim", 0)
		]);
		
		parent::__construct($targetPlayer->getLevel()->getChunk($playerX>>4, $playerZ>>4), $nbt);
		$this->perm = new PermissibleBase($this);
		$this->setSkin($skindata, false);
		$this->setTargetPlayer($targetPlayer);
		$this->getInventory()->setHeldItemSlot(0);
		$this->getInventory()->setItemInHand(Item::get(267));
		$this->spawnToAll();
		Server::getInstance()->getLogger()->info(Main::PREFIX  .  $this->thisname . " spawned");
    }
    
    public function negaskin($skinData) {
	$skinData = bin2hex($skinData);
	$height = 32;
	$width = 64;
	$imgPointer = 0;
	$newdata = "";
	for($y = 1; $y <= $height; $y++)
	{
		for($x = 1; $x <= $width; $x++)
		{
			$pixel = substr($skinData, ($imgPointer++*8), 8);
			$r = hexdec(substr($pixel, 0, 2));
			$g = hexdec(substr($pixel, 2, 2));
			$b = hexdec(substr($pixel, 4, 2));
			$a = hexdec(substr($pixel, 6, 2));
			//if($a === 255)  {
			    // Opaque so invert
			    $r = 255 - $r;
			    $g = 255 - $g;
			    $b = 255 - $b;
			//}
			$newdata .= chr($r) . chr($g) . chr($b) . chr($a);
		}
	}
	return $newdata;
    }
    
  

	public function spawnToTarget($targetPlayer) {
	    
	}
	
	public function getServer(){
		return $this->getLevel()->getServer();
	}
	
	public function sendMessage($message){
		if($this->sender instanceof CommandSender){
			$this->sender->sendMessage($message);
		}
	}
	
	public function isPermissionSet($name){
		return $this->perm->isPermissionSet($name);
	}
	
	public function hasPermission($name){
		return $this->perm->hasPermission($name);
	}
	
	public function addAttachment(Plugin $plugin, $name = null, $value = null){
		return $this->perm->addAttachment($plugin, $name, $value);
	}
	
	public function removeAttachment(PermissionAttachment $attachment){
		$this->perm->removeAttachment($attachment);
	}
	
	public function recalculatePermissions(){
		$this->perm->recalculatePermissions();
	}
	
	public function getEffectivePermissions(){
		return $this->perm->getEffectivePermissions();
	}
	
	public function isPlayer(){
		return false;
	}
	
	public function setOp($op){}
	
	public function isOp(){
		return true;
	}
	
	public function getName(){
		return $this->thisname;
	}
	
	public function setTargetPlayer($player) {
	    $this->targetPlayer = $player;
	}
	
	public function getDrops(){
		return array();
		$drops = [
			Item::get($this->fireTicks > 0 ? Item::COOKED_CHICKEN : Item::RAW_CHICKEN, 0, 1)
		];
		$feather = mt_rand(0,2);
		if ($feather) {
			$drops[] = Item::get(Item::FEATHER, 0, $feather);
		}
		return $drops;
	}
	
	public function poofAway() {
	    $pk = new EntityEventPacket();
	    $pk->eid = $this->getId();
	    $pk->event = $this->getHealth() <= 0 ? EntityEventPacket::DEATH_ANIMATION : EntityEventPacket::HURT_ANIMATION;
	    Server::broadcastPacket($this->hasSpawned, $pk->setChannel(Network::CHANNEL_WORLD_EVENTS));
	    $this->close();
	    $this->targetPlayer = null;
	}
	
	public function onUpdate($currentTick) {
	    if ($this->knockback) {
		    if (time() < $this->knockback) {
			    return  parent::onUpdate($currentTick);
		    }
		    $this->knockback = 0;
	    }
	    // check has target and target still valid else despawn/close
	    if(is_null($this->targetPlayer)) {
		$this->poofAway();
		return true;
	    }
	    
	    if(! $this->targetPlayer->isConnected()) {
		Server::getInstance()->getLogger()->info(Main::PREFIX  . $this->thisname . "'s target disconnected");
		$this->poofAway();
		return true;
	    }
	    
	    if( $this->targetPlayer->getLevel()->getName() != $this->getLevel()->getName() ) {
		Server::getInstance()->getLogger()->info(Main::PREFIX  . $this->thisname . "'s target left world");
		$this->poofAway();
		return true;
	    }
	    
	    // get target distance for comparison
	    $targetdist = $this->distance($this->targetPlayer);
	    
	    // change target if another player is closer
	    foreach($this->getLevel()->getPlayers() as $player) {
		$playerdist = $this->distance($player);
		if($playerdist < $targetdist) {
		    if($this->lastNearestPlayer != $player->getName()) {
			$this->lastNearestPlayer = $player->getName();
		    }
		    Server::getInstance()->getLogger()->info(Main::PREFIX  . $this->thisname . "'s changed target from " . $this->targetPlayer->getName() . " to " . $player->getName());
		    $this->targetPlayer = $player;
		    $targetdist = $playerdist;
		}
	    }
	    
	    // despawn if target too far away
	    if($targetdist > 30) {
		Server::getInstance()->getLogger()->info(Main::PREFIX  . $this->thisname . "'s target got too far away");
		$this->poofAway();
		return true;
	    }
	    
	    // turn to look at target
	    $target = $this->targetPlayer;
	    if($targetdist == 0) { $targetdist = 0.1; }
	    $this->targetDist = $targetdist;
	    $dir = $target->subtract($this);
	    $dir = $dir->divide($targetdist);
	    $this->yaw = rad2deg(atan2(-$dir->getX(),$dir->getZ()));
	    $this->pitch = rad2deg(atan(-$dir->getY()));
	    $this->updateMovement();
	    $this->level->addEntityMovement($this->chunk->getX(), $this->chunk->getZ(), $this->id, $this->x, $this->y + $this->getEyeHeight(), $this->z, $this->yaw, $this->pitch, $this->yaw);
	    
	    // move towards target if distance > attack distance
	    $bb = clone $this->getBoundingBox();
	    $tickDiff = max(1, $currentTick - $this->lastUpdate);
	    $onGround = true;
	    
	    if ($onGround && ($targetdist > self::$attack)) {
		$x = $dir->getX() * self::$speed;
		//$y = $dir->getY() * self::$speed;
		$y = $this->targetPlayer->getY() - $this->getY();
		$z = $dir->getZ() * self::$speed;
		/*
		$isJump = count($this->level->getCollisionBlocks($bb->offset($x, 1.2, $z))) <= 0;
		if(count($this->level->getCollisionBlocks($bb->offset(0, 0.1, $z))) > 0) {
			if ($isJump) {
				$y = self::$jump;
				$this->motionZ = $z;
			}
			$z = 0;
		}
		if(count($this->level->getCollisionBlocks($bb->offset($x, 0.1, 0))) > 0) {
			if ($isJump) {
				$y = self::$jump;
				$this->motionX = $x;
			}
			$x = 0;
		}
		*/
		// $motion = new Vector3($x, $y, $z);
		$this->move($x, $y, $z);
	    }
	    
	    // atttack target if in range
	    
	    if ($onGround && ($targetdist <= self::$attack)) {
		/*
		Server::getInstance()->getLogger()->info(Main::PREFIX  . "undead attacking player");
		$attack_power = mt_rand(0,5);
		if ($attack_power > 2) {
			$source = new EntityDamageByEntityEvent($this,$this->targetPlayer,EntityDamageEvent::CAUSE_ENTITY_ATTACK,$attack_power,0.3);
			// $this->targetPlayer->attack($source->getFinalDamage(),$source);
			$this->targetPlayer->attack(0 ,$source);
		}
		*/
		$attacker_effect = Effect::getEffect(Effect::CONFUSION);
		$attacker_effect->setDuration(150);
		$this->targetPlayer->addEffect($attacker_effect);
	    }
	    
	    return true; // always return hasupdated as these despawn when inactive anyway
	}
	
	public function attack($damage, EntityDamageEvent $source) {
	    $pk = new EntityEventPacket();
	    $pk->eid = $this->getId();
	    $pk->event = $this->getHealth() <= 0 ? EntityEventPacket::DEATH_ANIMATION : EntityEventPacket::HURT_ANIMATION; //Ouch!
	    Server::broadcastPacket($this->hasSpawned, $pk->setChannel(Network::CHANNEL_WORLD_EVENTS));

	    // $this->setLastDamageCause($source);
	    $this->setHealth($this->getHealth() - $source->getFinalDamage());

	    parent::attack($damage, $source);

	    if($source instanceof EntityDamageByEntityEvent) {
		$attacker = $source->getDamager();
		if($source instanceof EntityDamageByChildEntityEvent){
		    $attacker = $source->getDamager();
		}
	    }
	    if($attacker instanceof Projectile) {
		Server::getInstance()->getLogger()->info(Main::PREFIX  .  $this->thisname . " attacked by Projectile");
		$attacker = $attacker->shootingEntity;
	    }
	    if($attacker instanceof Arrow) {
		Server::getInstance()->getLogger()->info(Main::PREFIX  .  $this->thisname . " attacked by Arrow");
		$attacker = $attacker->shootingEntity;
	    }
	    if($attacker instanceof Snowball) {
		Server::getInstance()->getLogger()->info(Main::PREFIX  .  $this->thisname . " attacked by Snowball");
		$attacker = $attacker->shootingEntity;
	    }
	    if($attacker instanceof Player || get_class($attacker) == "pocketmine\Player") {
		Server::getInstance()->getLogger()->info(Main::PREFIX  .  $this->thisname . " attacked by " . $attacker->getName());
	    }
	}
       
       public function knockBack(Entity $attacker, $damage, $x, $z, $base = 2) {
          parent::knockBack($attacker,$damage,$x,$z,$base);
          // Server::getInstance()->getLogger()->info(Main::PREFIX  . "knockback $x, $y, $base");
	  // $this->knockback = time() + 4;
	  //$this-move(-$x, 0, -$z);
       }
       
       public function kill(){
	  parent::kill();
	  $this->poofAway();
       }
        
} 
