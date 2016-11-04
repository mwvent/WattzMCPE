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

class WarpCommand extends PluginCommand {
	private $plugin;
	private $warpname;
	private $name;

	public function __construct(Main $plugin, $aliasname, $warpname) {
		$this->plugin = $plugin;
		$this->warpname = $warpname;
		$this->name = $warpname;
		parent::__construct($warpname, $plugin);	
	}

	public function getName(): string {
		return $this->name;
	}
	
	public function execute(CommandSender $sender, $alias, array $args) {
		if(!$sender instanceof Player) {
			$sender->sendMessage("This command is only availible to players.");
			return;
		}
		$this->plugin->redirect($sender, $this->warpname);
	}
}

