<?php

namespace Wattz;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;
use pocketmine\event\entity\EntityTeleportEvent;
use pocketmine\event\player\PlayerKickEvent;
use TimeRanks\TimeRanks;

class EventListener extends PluginBase implements Listener{
        /**
         *
         * @var Wattz\Main
         */
        private $plugin;
	public function __construct(Main $plugin){
		$this->plugin = $plugin;
	}
        
        // allow players who have played a little to still join when full
        public function onPlayerKick(PlayerKickEvent $event){
            if($event->getReason() === "disconnectionScreen.serverFull") {
                $timeRanksPlugin = $this->plugin->getServer()->getPluginManager()->getPlugin("TimeRanks");
                if(is_null($timeRanksPlugin)) {
                    return;
                }
                $playerName = strtolower($event->getPlayer()->getName());
                $playTime = $timeRanksPlugin->getMinutes($playerName);
                if($playTime > 60) {
                    $msg = "Allowed $playerName to join when full ";
                    $msg .= "because they have $playTime mins of playtime.";
                    $this->plugin->getServer()->getLogger()->info($msg);
                    $event->setCancelled(true);
                }
            }
	}
	
	public function onPlayerChat(PlayerChatEvent $event){
		//
	}
	
	public function onPlayerJoin(PlayerJoinEvent $event) {
		$this->plugin->skinSaver($event->getPlayer());
		$this->plugin->redirector($event->getPlayer());
                $timeRanksPlugin = $this->plugin->getServer()->getPluginManager()->getPlugin("TimeRanks");
                if(is_null($timeRanksPlugin)) {
                    return;
                }
                $playerName = strtolower($event->getPlayer()->getName());
                $playTime = $timeRanksPlugin->getMinutes($playerName);
                echo "DEBUG: $playerName joined with $playTime playtime\n";
	}
	
	public function onPlayerQuit(PlayerQuitEvent $event){
		//
	}
    
    public function onPlayerTeleport(EntityTeleportEvent $event)  {
        return;
		$player = $event->getEntity();
		if (!($player instanceof Player)) return;
        
		if( is_null($event->getTo()->getLevel()) || is_null($event->getFrom()->getLevel()) ) {
            return;
        }
            
        $toworld = $event->getTo()->getLevel()->getName();
        $fromworld = $event->getFrom()->getLevel()->getName();
        
        if($toworld == $fromworld) {
            return;
        }
        
        echo "Player tried to go to world " . $toworld . PHP_EOL;
        $event->setCancelled(true);
	}
}
?>
