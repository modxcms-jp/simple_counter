<?php
class SCounterDataDb {
	var $ip;	//the users ip
	var $time;	//the time now
	var $tbl = array();
	var $total;
	var $today;
	var $yesterday;
	var $useronline;
	
	function SCounterDataDb() {
		global $modx;
		$this->tbl["check"]		 = $GLOBALS['table_prefix']."scounter_useronline";
		$this->tbl["useronline"] = $modx->getFullTableName("scounter_useronline");
		$this->tbl["daycount"]	 =   $modx->getFullTableName('scounter_daycount');
		$this->ip	= $_SERVER['REMOTE_ADDR'];
	}
	
	function FirstRun($path) {
		global $modx;
		$rs = $modx->db->query("SHOW TABLES LIKE '".$this->tbl["check"]."'");
		$count = $modx->db->getRecordCount($rs);
		
		if ($count==0) {
			$fh = fopen($path."includes/sc.install.db.sql", 'r');
			$idata = '';
			while (!feof($fh)) {
				$idata .= fread($fh, 1024);
			}
			fclose($fh);
			$idata = str_replace("\r", '', $idata);
			$idata = str_replace('{PREFIX}',$GLOBALS['table_prefix'], $idata);
			$sql_array = split("\n\n", $idata);
			foreach($sql_array as $sql_entry) {
				$sql_do = trim($sql_entry, "\r\n; ");
				$modx->db->query($sql_do);	
			}
		}
	}
	
	function Read($time) {
		global $modx;
		$this->Delete($time);
		$rs = $modx->db->select(" * ", $this->tbl["useronline"], '`ip` = "'.$this->ip.'"');
		$count = $modx->db->getRecordCount($rs);
		if ($count == 1)	$return = $this->Update();
		else				$return = $this->Writenew();
		
		$this->ReadDate();
		return array($this->total, $this->today, $this->yesterday, $this->useronline);
	}
	
	function Writenew() {
		global $modx;
		//create row for new user-online
		$modx->db->query('INSERT INTO '.$this->tbl["useronline"].' VALUES ("'.$this->ip.'", NOW())');
		
		// write into <today> one new user
		$rs = $modx->db->select(" * ", $this->tbl["daycount"], '`date` = CURDATE( )');
		if($modx->db->getRecordCount($rs) == 1) {
			while($row = $modx->db->getRow($rs)) {
				$count = $row['count'];
			}
			$count++;
			$modx->db->update("`count` = '".$count."'", $this->tbl["daycount"], "`date` = CURDATE()");
		}
	}
	
	function Update() {
		global $modx;
		//update the time in the database, if user is still online
		$modx->db->update("`time` = NOW()", $this->tbl["useronline"], '`ip` = "'. $this->ip .'"');
	}
	
	function Delete($time) {
		global $modx;
		//create all rows which are older than $time minutes
		$modx->db->query('DELETE FROM '.$this->tbl["useronline"].' WHERE DATE_SUB(NOW(), INTERVAL '.$time.' MINUTE) > time');
	}
	
	function ReadDate() {
		global $modx;
		// Read Users from today - if row not exists - create it.
		$rs = $modx->db->select(" * ", $this->tbl["daycount"], '`date` = CURDATE( )');
		if($modx->db->getRecordCount($rs) == 1) {
			while($row = $modx->db->getRow($rs)) {
				$this->today = $row['count'];
			}
		}
		else {
			$modx->db->query("INSERT INTO ".$this->tbl["daycount"]." VALUES (CURDATE(), '1')");
			$this->today = 1;
		}
		// Read Users from yesterday
		$rs = $modx->db->select(" * ", $this->tbl["daycount"], '`date` = DATE_SUB(CURDATE( ), INTERVAL 1 DAY)');
		while($row = $modx->db->getRow($rs)) {
			$this->yesterday = $row['count'];
		}
		if ($this->yesterday == "") $this->yesterday =0;
		// Read Users from totals
		$rs = $modx->db->select(" SUM(count) ", $this->tbl["daycount"]);
		while($row = $modx->db->getRow($rs)) {
			$this->total = $row['SUM(count)'];
		}
		$rs = $modx->db->select(" * ", $this->tbl["useronline"]);
		$this->useronline = $modx->db->getRecordCount($rs);
	}
}
?>
