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
            /*
             * Kick players with bad words in name
             */
            $name = strtolower($event->getPlayer()->getName());
            $regex = "((f.{0,3}u.{0,3}k)|(s(e|3){1,5}x)|bit*ch|penis|boob|dick|tits)";
            if(preg_match($regex, $name) > 0) {
                $event->getPlayer()->kick("Name not allowed.");
                return;
            }
            
            $this->plugin->skinSaver($event->getPlayer());
            $this->plugin->redirector($event->getPlayer());
            
            // See if player has a rank in ranks file
            $pRank = $this->plugin->readFromRanksFile($event->getPlayer()->getName());
            
            // If nothing in ranks file tell timeranks to update the rank
            if($pRank == "") {
                // tell pureperms to use basic group
                $ppPlugin = $this->plugin->getServer()->getPluginManager()->getPlugin("PurePerms");
                $pureGroup = $ppPlugin->getDefaultGroup();
                $ppPlugin->getUserDataMgr()->setGroup($event->getPlayer(), $pureGroup, null);
                // do timeranks rankup (sets buddyChannels)
                $timeRanksPlugin = $this->plugin->getServer()->getPluginManager()->getPlugin("TimeRanks");
                $playerName = strtolower($event->getPlayer()->getName());
                $playTime = $timeRanksPlugin->getMinutes($playerName);
                $timeRank = $timeRanksPlugin->getRankFromMinutes($playTime);
                $timeRanksPlugin->checkRankUp($playerName, 0, $playTime);
                $pRank = $timeRank->getRankName();
                // and set BuddyChannels direct
                $buddyChannelsPlugin = $this->plugin->getServer()->getPluginManager()->getPlugin("BuddyChannels");
                $buddyChannelsPlugin->setBaseRank($event->getPlayer()->getName(), $pRank);
                // debug
                $msg = $event->getPlayer()->getName() . " loaded time-based rank " . $pRank ;
                $this->plugin->getServer()->getLogger()->debug($msg);
            } else {
                $msg = $event->getPlayer()->getName() . " loaded manual rank " . $pRank ;
                $this->plugin->getServer()->getLogger()->debug($msg);
                // otherwise tell pureperms to use rank found in ranks file
                $ppPlugin = $this->plugin->getServer()->getPluginManager()->getPlugin("PurePerms");
                $pureGroup = $ppPlugin->getGroup($pRank);
                $ppPlugin->getUserDataMgr()->setGroup($event->getPlayer(), $pureGroup, null);
                $levels =  $this->plugin->getServer()->getLevels();
                foreach($levels as $level){
                    $ppPlugin->getUserDataMgr()->setGroup($event->getPlayer(), $pureGroup, $level->getName());
                }
                // and set BuddyChannels direct
                $buddyChannelsPlugin = $this->plugin->getServer()->getPluginManager()->getPlugin("BuddyChannels");
                $buddyChannelsPlugin->setBaseRank($event->getPlayer()->getName(), $pRank);
            }
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
