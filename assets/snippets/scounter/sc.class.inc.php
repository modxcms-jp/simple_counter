<?php
/*####
#
#	Name: SCounter
#	Version: 1.3
#	Author: SimonSimCity
#
# Latest Version: http://modxcms.com/SimpleCounter-1937.html
# Documentation: http://wiki.modxcms.com/index.php/SCounter (wiki)
#
####*/

class SCounter {
	var $name;
	var $version;
	var $config = array();
	var $output = array();
	var $_ctime;
	
	
	function SCounter() {
		global $modx;
		$path = strtr(realpath(dirname(__FILE__)), '\\', '/');
		include_once($path . '/includes/sc.db.class.inc.php');
		$this->database = new SCounterDataDb;
		$this->name = $this->config["snippet"]["name"] = "SCounter";
		$this->version = $this->config["snippet"]["version"] = "1.3";
		$this->config["snippet"]["versioncheck"] = "Unknown";
		$this->client = $modx->getUserData();
		$this->_check = 0;
	}
	
	function Set($field, $value) {
		$this->parameters[$field] = $value;
	}
	
	function VersionCheck($version) {	
		if ($version == $this->version) $this->_check = 1;
		$this->config["snippet"]["versioncheck"] = $version;
	}
	
	function Run() {
		global $modx;
		if ($this->_check == 0)
			return "Falsche Version";
		$this->config["path"] = $this->parameters["path"];
		$this->database->FirstRun($this->config["path"]);
		
		$return = $this->database->Read($this->parameters["minuten"]);
		list($this->output["total"], $this->output["today"], $this->output["yesterday"], $this->output["useronline"]) = $return;
		$this->setPlaceholders($this->output,"SCounter");
	}
	
	function setPlaceholders($value = '', $key = '', $path = '') {
		global $modx;
		$keypath = !empty($path) ? $path . "." . $key : $key;
	    $output = array();
		if (is_array($value)) { 
			foreach ($value as $subkey => $subval) {
				$this->setPlaceholders($subval, $subkey, $keypath);
            }
		} else {
			if (strlen($this->config["tagid"]) > 0) {$keypath .= ".".$this->config["tagid"]; }
			$modx->setPlaceholder($keypath,$value);	
		}
	}
	
}
?>