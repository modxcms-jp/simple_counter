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

class SCounter
{
	private $version;
	private $config = array();
	private $output = array();

	function __construct()
	{
		include_once(__DIR__ . '/includes/sc.db.class.inc.php');
		$this->database = new SCounterDataDb;
		$this->name = $this->config['snippet']['name'] = "SCounter";
		$this->version = $this->config['snippet']['version'] = '1.3';
		$this->config['snippet']['versioncheck'] = "Unknown";
		$this->client = evo()->getUserData();
		$this->_check = 0;
	}

	function Set($field, $value)
	{
		$this->parameters[$field] = $value;
	}

	function VersionCheck($version)
	{
		if ($version == $this->version) {
			$this->_check = 1;
		}
		$this->config["snippet"]["versioncheck"] = $version;
	}

	function Run()
	{
		if ($this->_check == 0) {
			return "Falsche Version";
		}
		$this->config["path"] = $this->parameters["path"];
		$this->database->FirstRun($this->config["path"]);

        list(
            $this->output["total"],
            $this->output["today"],
            $this->output["yesterday"],
            $this->output["useronline"]
            ) = $this->database->Read($this->parameters["minuten"]);
		$this->setPlaceholders($this->output, "SCounter");
	}

	function setPlaceholders($value = '', $key = '', $path = '')
	{
		$keypath = !empty($path) ? $path . "." . $key : $key;
		if (is_array($value)) {
			foreach ($value as $subkey => $subval) {
				$this->setPlaceholders($subval, $subkey, $keypath);
			}
		} else {
			if (strlen($this->config["tagid"]) > 0) {
				$keypath .= "." . $this->config["tagid"];
			}
			evo()->setPlaceholder($keypath, $value);
		}
	}
}
