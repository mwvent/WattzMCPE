<?php
namespace Wattz\Entities;
use Wattz\Main;
use pocketmine\Server;

use pocketmine\command\CommandSender;
use pocketmine\entity\Human;
use pocketmine\entity\Effect;
use pocketmine\level\format\FullChunk;
use pocketmine\nbt\tag\ByteTag;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\DoubleTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\nbt\tag\FloatTag;
use pocketmine\nbt\tag\ShortTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\permission\PermissibleBase;
use pocketmine\permission\PermissionAttachment;
use pocketmine\plugin\Plugin;

use pocketmine\math\Vector3;

use pocketmine\entity\Entity;
use pocketmine\entity\Snowball;
use pocketmine\entity\Arrow;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;


use pocketmine\level\format\mcregion\Chunk;
class dummyChunk extends Chunk
{
     public function __construct() {
	return true;
     }
}

class Herobrine extends Human implements CommandSender{
	const NETWORK_ID=0;
	
	public $targetPlayer;
	public $lastNearestPlayer;
	public $targetDist;
	public $plugin;
	public $knockback;
	public $timespawned;
	
	public function __construct(FullChunk $chunk, CompoundTag $nbt, $targetPlayer = null, $plugin = null) {
		// if $targetPlayer is null pocketmine is probably trying to refload a saved
		// version of this entity which we do not want anymore - as it is not cancellable ( I think ? )
		// just let it create and the update function will despawn the entity immediatley when it finds
		// it to have no targetPlayer
		if( is_null ($targetPlayer) ) {
		    parent::__construct($chunk, $nbt);
		    $this->close();
		    return;
		} 
		// if targetPlayer was set we can create chunk and nbt values outselves
		$this->plugin = $plugin;
		$name = "Herobrine";
		// $IsSlim = $targetPlayer->isSkinSlim();
		$playerX = $targetPlayer->getX();
		$playerY = $targetPlayer->getY();
		$playerZ = $targetPlayer->getZ();
		$outX=round($playerX,1);
		$outY=round($playerY,1);
		$outZ=round($playerZ,1);
		// echo "Playerpos $playerX, $playerY, $playerZ" . PHP_EOL;  // DEBUG
		$playerLevel = $targetPlayer->getLevel()->getName();
		$playerYaw = $targetPlayer->getYaw();
		$playerPitch = $targetPlayer->getPitch();
		$humanInv = $targetPlayer->getInventory();
		$pHealth = 99;
		
		// get a nice place to spawn
                if( rand(1,2) == 1 ) {
                    $provisional_spawnloc = new Vector3(
                        $playerX + rand(15, 20), 
                        $playerY, 
                        $playerZ + rand(15, 20)
                    );
                } else {
                    $provisional_spawnloc = new Vector3(
                        $playerX - rand(15, 20), 
                        $playerY, 
                        $playerZ - rand(15, 20)
                    );
                }
		$spawnloc = $targetPlayer->getLevel()->getSafeSpawn($provisional_spawnloc);
		$outX = $spawnloc->x;
		$outY = $spawnloc->y; // 0,5
		$outZ = $spawnloc->z;
		// echo "Spawnpos $outX, $outY, $outZ" . PHP_EOL; // DEBUG
		
		$nbt = new CompoundTag;
		$motion = new Vector3(0,0,0);
		$nbt->Pos = new ListTag("Pos", [
		  new DoubleTag("", $outX),
		  new DoubleTag("", $outY),
		  new DoubleTag("", $outZ)
		]);
		$nbt->Motion = new ListTag("Motion", [
		  new DoubleTag("", $motion->x),
		  new DoubleTag("", $motion->y),
		  new DoubleTag("", $motion->z)
		]);
		$nbt->Rotation = new ListTag("Rotation", [
		    new FloatTag("", $playerYaw),
		    new FloatTag("", $playerPitch)
		]);
		$nbt->Health = new ShortTag("Health", $pHealth);
		$nbt->Inventory = new ListTag("Inventory", $humanInv);
		$nbt->NameTag = new StringTag("name"," ");
		$nbt->Invulnerable = new ByteTag("Invulnerable", 1);
		$nbt->CustomTestTag = new ByteTag("CustomTestTag", 1);
		
		$nbt->Skin = new CompoundTag("Skin", [
		    "Data" => new StringTag("Data", file_get_contents($this->plugin->getDataFolder()."/herobrine.skin")),
		    "Slim" => new ByteTag("Slim", 0)
		]);
		
		parent::__construct($targetPlayer->getLevel()->getChunk($playerX>>4, $playerZ>>4), $nbt);
		$this->perm = new PermissibleBase($this);
		$this->setNameTagVisible(false);
		$this->setSkin(file_get_contents($this->plugin->getDataFolder()."/herobrine.skin"), false);
		$this->setTargetPlayer($targetPlayer);
		$this->spawnToAll();
		$this->timespawned = time();
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
		return "Herobrine";
	}
	
	public function setTargetPlayer($player) {
	    $this->targetPlayer = $player;
	}
	
	public function poofAway() {
	    // for($i=1;$i<4;$i++) {
		// $this->plugin->herobrineTask->herobrines_bats[] = new HerobrineBat(new dummyChunk, new Compound, $this);
	    // }
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
		Server::getInstance()->getLogger()->info(Main::PREFIX  . "Herobrine entity has no target - despawning");
		$this->poofAway();
		return true;
	    }
	    
	    if(! $this->targetPlayer->isConnected()) {
		$this->poofAway();
		return true;
	    }
	    
	    if( $this->targetPlayer->getLevel()->getName() != $this->getLevel()->getName() ) {
		$this->poofAway();
		return true;
	    }
	    
	    // get target distance for comparison
	    $targetdist = $this->distance($this->targetPlayer);
	    
	    // despawn if any player <5 block distance OR change target if another player is closer
	    foreach($this->getLevel()->getPlayers() as $player) {
		$playerdist = $this->distance($player);
		if($playerdist < 15) {
		    Server::getInstance()->getLogger()->info(Main::PREFIX  . "Herobrine " . $player->getName() . " got close - despawning");
		    if( $this->plugin->herobrineTask->herobrine_spawnundead ) {
		    	$newundeadplayer = new UndeadPlayer(new dummyChunk, new CompoundTag, $player, $this, $this->plugin);
		    }
		    $this->poofAway();
		    return true;
		}
		if($playerdist < $targetdist) {
		    if($this->lastNearestPlayer != $player->getName()) {
			$this->lastNearestPlayer = $player->getName();
			Server::getInstance()->getLogger()->info(Main::PREFIX  . "Herobrine turn to look @" . $this->lastNearestPlayer . " who is closer than " . $this->targetPlayer->getName());
		    }
		    $this->targetPlayer = $player;
		    $targetdist = $playerdist;
		}
	    }
	    
	    // despawn if target too far away
	    if($targetdist > 30) {
		Server::getInstance()->getLogger()->info(Main::PREFIX  . "Herobrine target too far away - despawning");
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
	    
	    
	    // bored timer
	    if( (time() - $this->timespawned) > 120 ) {
		Server::getInstance()->getLogger()->info(Main::PREFIX  . "Herobrine got bored (120s no activity)");
		$this->poofAway();
		return true;
	    }
	    
	    return true;
	}
	
	public function attack($damage, EntityDamageEvent $source) {
	    parent::attack($damage, $source);
	    if($source instanceof EntityDamageByEntityEvent) {
		$attacker = $source->getDamager();
		if($source instanceof EntityDamageByChildEntityEvent){
		    $attacker = $source->getDamager();
		}
	    }
	    if($attacker instanceof Projectile) {
		Server::getInstance()->getLogger()->info(Main::PREFIX  . "Herobrine attacked by Projectile");
		$attacker = $attacker->shootingEntity;
	    }
	    if($attacker instanceof Arrow) {
		Server::getInstance()->getLogger()->info(Main::PREFIX  . "Herobrine attacked by Arrow");
		$attacker = $attacker->shootingEntity;
	    }
	    if($attacker instanceof Snowball) {
		Server::getInstance()->getLogger()->info(Main::PREFIX  . "Herobrine attacked by Snowball");
		$attacker = $attacker->shootingEntity;
	    }
	    if($attacker instanceof Player || get_class($attacker) == "pocketmine\Player") {
		Server::getInstance()->getLogger()->info(Main::PREFIX  . "Herobrine attacked by " . $attacker->getName());
		// $attacker->setOnFire(3);
		$attacker_effect = Effect::getEffect(Effect::CONFUSION);
		$attacker_effect->setDuration(15 * 20);
		$attacker->addEffect($attacker_effect);
		//  $newundeadplayer = new UndeadPlayer(new dummyChunk, new Compound, $attacker, $this, $this->plugin);
		$this->poofAway();
	    } else {
		Server::getInstance()->getLogger()->info(Main::PREFIX  . "Herobrine attacked by " . get_class($attacker));
		$this->poofAway();
	    }
	}
	
	public function knockBack(Entity $attacker, $damage, $x, $z, $base = 0.4) {
		$this->knockback = time() + 1;// Stunned for 1 second...
		parent::knockBack($attacker,$damage,0.1,0.1,0.1);
	}
} 
