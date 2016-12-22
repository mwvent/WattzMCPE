<?php

namespace Wattz\Commands;

use pocketmine\command\Command;
use pocketmine\command\CommandExecutor;
use pocketmine\command\CommandSender;
use pocketmine\permission\Permission;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\TextFormat;
use pocketmine\command\PluginCommand;

use Wattz\Main;

class Commands extends PluginBase implements CommandExecutor{
	public function __construct(Main $plugin) {
        $this->plugin = $plugin;
    }
    
    public function onCommand(CommandSender $sender, Command $cmd, $label, array $args) {
	switch(strtolower($cmd->getName())) {
	    case 'ping':
		$sender->sendMessage("pong");
		return true;
		break;
	    case 'hb':
		if($sender instanceof Player){
		    $sender->sendMessage("This is a console only command.");
		} else {
		    switch($args[0]) {
			case "uon":
			    $this->plugin->herobrineTask->herobrine_spawnundead = true;
			    return true;
			    break;
			case "uoff":
			    $this->plugin->herobrineTask->herobrine_spawnundead = false;
			    return true;
			    break;
			case "on":
			    $this->plugin->herobrineTask->herobrines_main_target = null;
			    $this->plugin->herobrineTask->herobrine_active = true;
			    return true;
			    break;
			case "off":
			    $this->plugin->herobrineTask->herobrines_main_target = null;
			    $this->plugin->herobrineTask->herobrine_active = false;
			    return true;
			    break;
			case "target":
			    $onlinePlayers = $this->plugin->getServer()->getOnlinePlayers();
			    foreach($onlinePlayers as $player) {
				if(strtolower($player->getName()) == strtolower($args[1])) {
				    $this->plugin->herobrineTask->herobrines_main_target = $player;
				    $this->plugin->herobrineTask->herobrine_active = true;
				    return true;
				}
			    }
			    $sender->sendMessage("Could not find player.");
			    return false;
			    break;
		    }
		}
		break;
	}
    }
}
?>
