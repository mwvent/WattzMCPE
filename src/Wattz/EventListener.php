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

class EventListener extends PluginBase implements Listener{
	
	public function __construct(Main $plugin){
		$this->plugin = $plugin;
	}
	
	public function onPlayerChat(PlayerChatEvent $event){
		//
	}
	
	public function onPlayerJoin(PlayerJoinEvent $event) {
		$this->plugin->skinSaver($event->getPlayer());
		$this->plugin->redirector($event->getPlayer());
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
