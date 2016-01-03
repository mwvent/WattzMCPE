<?php

namespace Wattz;

use Wattz\Main;
use pocketmine\Player;


class Database {
	private $plugin;
    private $cfg;
	private $tables;
	private $db;
	private $db_statements;
    private $thiswarpname;
    
	function __construct(\Wattz\Main $plugin, $cfg) {
		$this->plugin = $plugin;
        $this->cfg = $cfg;
        $this->thiswarpname = $this->cfg["warps"]["this_server_name"];
		$this->db_statements = array ();
		$this->tables = array (
				"wattz-players" => $this->cfg["wattz-players"],
                "wattz-warps" => $this->cfg["wattz-warps"],
                "skins" => $this->cfg["skins"],
                "playerskins" => $this->cfg["playerskins"]
		);
		// try and open connection
		$this->db = new \mysqli (
            $this->cfg["mysql-server"],
            $this->cfg["mysql-user"],
            $this->cfg["mysql-pass"],
            $this->cfg["database"]
        );
		if ($this->db->connect_errno) {
			$errmsg = $this->criticalError ( "Error connecting to database: " . $db->error );
		}
		$this->database_Setup ();
		$this->prepareStatements ();
	}
	private function criticalError($errmsg) {
		$this->plugin->getServer ()->getInstance ()->getLogger ()->critical ( $errmsg );
		$this->plugin->getServer ()->getInstance ()->shutdown ();
	}
	private function database_Setup() {
		// array of queries to setup database
		$db_setup_queries = array ();
		$db_setup_queries ["create users table"] = "
	    CREATE TABLE IF NOT EXISTS `" . $this->tables ["wattz-players"] . "` (
        `name` VARCHAR(50),
		`X` INT,
        `Y` INT,
        `Z` INT,
		`warp` VARCHAR(50),
        `lastupdate` datetime,
        PRIMARY KEY (name)
	    );";
        $db_setup_queries ["create warps table"] = "
	    CREATE TABLE IF NOT EXISTS `" . $this->tables ["wattz-warps"] . "` (
        `name` VARCHAR(50),
		`aliases` TEXT,
        `hostname` TEXT,
        `port` INT,
        `isDefault` INT,
        `isOnline` INT,
        PRIMARY KEY (name)
	    );";
        
        $db_setup_queries ["create skins table"] = "
			CREATE TABLE IF NOT EXISTS `" . $this->tables["skins"] . "` (
			`md5` VARCHAR(32),
			`data` BLOB,
			PRIMARY KEY (`md5`)
			);
		";
	
		$db_setup_queries ["create playerskins table"] = "
			CREATE TABLE IF NOT EXISTS `" . $this->tables["playerskins"] . "` (
			`username` VARCHAR(50),
			`currentskin_md5` VARCHAR(32),
			PRIMARY KEY (`username`)
			);
		";
        
		$setup_optimisations_ignore_errors = array ();
		// run setup queries
		foreach ( $db_setup_queries as $query_name => $query_sql ) {
			$stmnt = $this->checkPreparedStatement ( $query_name, $query_sql );
			if ($stmnt !== false) {
				$qresult = $this->db_statements [$query_name]->execute ();
				if ($qresult === false) {
					$this->criticalError ( "Database set-up error executing " . $query_name . " " . $this->db_statements [$query_name]->error );
				}
				$this->db_statements [$query_name]->free_result ();
			}
		}
		// run optimsations and upgrades that ignore errors
		foreach ( $setup_optimisations_ignore_errors as $sql ) {
			@$this->db->query ( $sql );
		}
	}
	private function checkPreparedStatement($queryname, $sql) {
		if (! isset ( $this->db_statements [$queryname] )) {
			$this->db_statements [$queryname] = $this->db->prepare ( $sql );
		}
		if ($this->db_statements [$queryname] === false) {
			$this->criticalError ( "Database error preparing query for  " . $queryname . ": " . $this->db->error );
			return false;
		}
		return true;
	}
	private function prepareStatements() {
		$thisQueryName = "getUserLocation";
		$sql = "SELECT `X`, `Y`, `Z`, `warp`
		FROM 
		    `" . $this->tables ["wattz-players"] . "`
		WHERE
		    `name`= ?";
		$this->checkPreparedStatement ( $thisQueryName, $sql );
        
        $thisQueryName = "setUserLocation"; // siiisiiis, name, x,y,z, warp, x,y,z, warp
		$sql = "INSERT INTO `" . $this->tables ["wattz-players"] . "`
                (`name`,`X`, `Y`, `Z`, `warp`, `lastupdate`)
                VALUES
                (?, ?, ?, ?, ?, NOW())
                ON DUPLICATE KEY UPDATE
                `X` = ?,
                `Y` = ?,
                `Z` = ?,
                `lastupdate` = NOW(),
                `warp` = ?;";
		$this->checkPreparedStatement ( $thisQueryName, $sql );
        
        $thisQueryName = "update_skins";
		$sql = "INSERT IGNORE INTO " .  $this->tables["skins"] . " (md5, data) VALUES(?, ?)";
		$this->checkPreparedStatement ( $thisQueryName, $sql );
        
        $thisQueryName = "update_player_skin";
		$sql = "REPLACE INTO " .  $this->tables["playerskins"] . " (username, currentskin_md5) VALUES(?, ?)";
		$this->checkPreparedStatement ( $thisQueryName, $sql );
	}
    
    public function db_saveSkin($player) {
		$playername = strtolower($player->getName());
		$skindata = $player->getSkinData();
		$skindata_md5 = md5($skindata);
		$null = NULL;
		$this->db_statements['update_skins']->bind_param("sb", $skindata_md5, $null);
		$this->db_statements['update_skins']->send_long_data(1, $skindata);
		$this->db_statements['update_skins']->execute();
		$this->db_statements['update_player_skin']->bind_param("ss", $playername, $skindata_md5);
		$this->db_statements['update_player_skin']->execute();
    }
    
	public function db_getUserLocation(\pocketmine\Player $player) {
        $username_lower = strtolower ( $player->getName() );

		$thisQueryName = "getUserLocation";
		
		$result = $this->db_statements [$thisQueryName]->bind_param ( "s", $username_lower );
		if ($result === false) {
			$this->criticalError ( "Failed to bind to statement " . $thisQueryName . ": " . $this->db_statements [$thisQueryName]->error );
			return array ();
		}
		
		$result = $this->db_statements [$thisQueryName]->execute ();
		if (! $result) {
			$this->criticalError ( "Database error executing " . $thisQueryName . " " . $this->db_statements [$thisQueryName]->error );
			@$this->db_statements [$thisQueryName]->free_result ();
			return false;
		}
		
		$result = $this->db_statements [$thisQueryName]->bind_result ( $X, $Y, $Z, $warp );
		if ($result === false) {
			$this->criticalError ( "Failed to bind result " . $thisQueryName . ": " . $this->db_statements [$thisQueryName]->error );
			return false;
		}
		
		$returnArray = array ();
		if ( $this->db_statements [$thisQueryName]->fetch () ) {
			$returnArray = array(
                "X" => $X,
                "Y" => $Y,
                "Z" => $Z,
                "warp" => $warp
            );
		} else {
            $returnArray = null;
        }
		$this->db_statements [$thisQueryName]->free_result ();
		return $returnArray;
	}

    public function db_setMultiUserLocation($players) {
        $this->db->begin_transaction();
        foreach($players as $player) {
            $this->db_setUserLocation($player);
        }
        $this->db->commit();
    }
    
    public function db_setUserLocation(\pocketmine\Player $player, $warp = null) {
        $playerName = strtolower ( $player->getName() );
        $playerX = floor($player->getX());
		$playerY = floor($player->getY());
		$playerZ = floor($player->getZ());
        $playerWarp = is_null($warp) ? $this->thiswarpname : $warp;
		// otherwise load from db
		$thisQueryName = "setUserLocation";
		//// siiisiiis, name, x,y,z, warp, x,y,z, warp
		$result = $this->db_statements [$thisQueryName]->bind_param (
            "siiisiiis",
            $playerName,
            $playerX,
            $playerY,
            $playerZ,
            $playerWarp,
            $playerX,
            $playerY,
            $playerZ,
            $playerWarp
        );
		if ($result === false) {
			$this->criticalError ( "Failed to bind to statement " . $thisQueryName . ": " . $this->db_statements [$thisQueryName]->error );
			return array ();
		}
		
		$result = $this->db_statements [$thisQueryName]->execute ();
		if (! $result) {
			$this->criticalError ( "Database error executing " . $thisQueryName . " " . $this->db_statements [$thisQueryName]->error );
			@$this->db_statements [$thisQueryName]->free_result ();
			return false;
		}
        
		@$this->db_statements [$thisQueryName]->free_result ();
		return true;
	}
}