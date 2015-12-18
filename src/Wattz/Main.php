<?php

namespace Wattz;
use Wattz\Entities\Herobrine;
use Wattz\Entities\HerobrineBat;
use Wattz\Entities\UndeadPlayer;
use Wattz\Tasks\HerobrineTask;
use Wattz\Tasks\SavePlayerPositionsTask;
use Wattz\Commands\WarpCommand;
use pocketmine\entity\Entity;

use pocketmine\command\CommandExecutor;
use pocketmine\command\CommandSender;
use pocketmine\permission\Permission;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\Server;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;
use pocketmine\network\RakLibInterface;

use Mysqli;

class Main extends PluginBase {
    const PRODUCER = "mwvent";
    const VERSION = "1.0";
    const MAIN_WEBSITE = "https://wattz.org.uk/mcpe";
    const PREFIX = "[WATTZ] ";
    
    public $cfg;
    
    public $tables;
    public $db_statements;
    
	public $warpaliases = array();
	
    public $herobrineTask;
    private $savePlayerPositionsTask;

    public function onEnable(){
        @mkdir($this->getDataFolder());
        $this->saveDefaultConfig();
        $this->cfg = $this->getConfig()->getAll();
        $this->getCommand("ping")->setExecutor(new Commands\Commands($this));
        $this->getCommand("hb")->setExecutor(new Commands\Commands($this));
		$this->getCommand("hb")->setExecutor(new Commands\Commands($this));
		$this->getServer()->getPluginManager()->registerEvents(new EventListener($this), $this);
		Entity::registerEntity(Herobrine::class);
		Entity::registerEntity(HerobrineBat::class);
		Entity::registerEntity(UndeadPlayer::class);
		$this->herobrineTask = new HerobrineTask($this);
		$this->getServer()->getScheduler()->scheduleRepeatingTask($this->herobrineTask,20);
        $this->savePlayerPositionsTask = new \Wattz\Tasks\SavePlayerPositionsTask($this);
		$this->getServer()->getScheduler()->scheduleRepeatingTask($this->savePlayerPositionsTask,300);
        
        $this->db = new \Wattz\Database($this, $this->cfg);
        
		// cache warp aliases
		foreach($this->cfg["warps"] as $currentwarpkey => $currentwarp) {
			if( isset($currentwarp["names"]) ) {
				foreach( $currentwarp["names"] as $currentwarpname) {
					$this->warpaliases[$currentwarpname] = $currentwarpkey;
				}
			}
		}
		$this->initWarpCommands();
		print_r($this->warpaliases);
		Server::getInstance()->getLogger()->info(Main::PREFIX  . "Ready");
    }

    public function removeHerobrine() {
    }
    
	public function initWarpCommands() {
		$commandMap = $this->getServer()->getCommandMap();
		foreach($this->warpaliases as $currentwarpalias => $currenttarget) {
			$commandToOverride = $commandMap->getCommand($currentwarpalias);
			if( ! $commandToOverride === null ) {
				//This prepares the command for the next step, setting up the Command->nextLabel
				$commandToOverride->setLabel($currentwarpalias."_disabled"); 
				//This changes the current label
				$commandToOverride->unregister($commandMap); 
			}
			//Now, we can register our command.
			$command = new WarpCommand($this, $currentwarpalias, $currenttarget);
			$commandMap->register($currentwarpalias, $command, $currentwarpalias);
		}
	}
	
    public function skinSaver($player) {
        if( ! $this->cfg["saveskins"] ) return;
		$this->db->db_saveSkin($player);
    }
    
	public function savePlayerPositions($players) {
		$this->db->db_setMultiUserLocation($players);
	}
	
	public function getWarpRealName($warpname) {
		$retval = false;
		foreach($this->warpaliases as $currentwarpalias => $currenttarget) {
			if(strtolower($currentwarpalias) == $warpname) {
				return trim($currenttarget);
			}
		}
	}
    
    public function redirector(Player $player) {
        $redirectTask = new \Wattz\Tasks\RedirectPlayerTask($this, $player);
    }
    
    public function forcePlayerDisconnect(Player $player) {
        // https://forums.pocketmine.net/threads/temporary-solution-for-transferring-players-in-0-12-1.11759/
        // find out the RakLib interface, which is the network interface that MCPE players connect with
		foreach($this->getServer()->getNetwork()->getInterfaces() as $interface){
			if($interface instanceof RakLibInterface ){
				$raklib = $interface;
				break;
			}
		}
		if(!isset($raklib)){
			Server::getInstance()->getLogger()->critical(Main::PREFIX  . "rakLib not found");
			return;
		}

		// calculate the identifier for the player used by RakLib
		$identifier = $player->getAddress() . ":" . $player->getPort();

		// this method call is the most important one - it sends some signal to RakLib that makes it think that the client has clicked the "Quit to Title" button (or timed out). Some RakLib internal stuff will then tell PocketMine that the player has quitted.
		$raklib->closeSession($identifier, "transfer");
    }
    
	public function redirect(Player $player, $warpname) {
		$redirectTask = new \Wattz\Tasks\RedirectPlayerTask($this, $player, $warpname);
	}

    public function redirect_run(Player $player, $warpname) {
		$warpname = strtolower($warpname);
		// chucked in to enable me to deal with an emergencey
        if( $this->cfg["autokickall"] ) {
            $player->kick($this->cfg["autokickall_msg"]);
            return;
        }
		// get server details from config
		$targetwarp = $this->getWarpRealName($warpname);
		if( $targetwarp === false ) {
			$player->sendMessage(TextFormat::RED . "[Error] Warp doesn't exist");
			return false;
		}
        if( $this->cfg["warps"]["this_server_name"] == $targetwarp ) {
			$player->sendMessage(TextFormat::RED . "[Error] You are already in this world");
            return false; 
        }

		// Get connection details
		$hostname = $this->cfg["warps"][$targetwarp]["hostname"];
		$port = $this->cfg["warps"][$targetwarp]["port"];
        
        // Update db
        $this->db->db_setUserLocation($player, $targetwarp);
        
        // skip past the actual transfer if disabled - just go to the part where
        // player is disconnected
        if( $this->cfg["warps"]["prevent-actual-transfer"] ) {
            $player->kick("To go to this world please connect to $hostname $port");
            return;
        }
		
		// TODO ping/query target to check online
		
		$ft_plugin = $this->getServer()->getPluginManager()->getPlugin("FastTransfer");
		if ($ft_plugin === null) {
			Server::getInstance()->getLogger()->critical(Main::PREFIX  . "Could not find FastTransfer plugin");
        }
        $ft_plugin->transferPlayer($player, $hostname, $port, "Connecting you to " . $warpname);
        
        $this->forcePlayerDisconnect($player);
    }
}

?>
