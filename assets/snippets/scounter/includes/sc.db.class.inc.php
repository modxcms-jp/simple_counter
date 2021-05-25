<?php
class SCounterDataDb
{
	private $ip;	//the users ip
	private $tbl = array();
	private $total;
	private $today;
	private $yesterday;
	private $useronline;

	function __construct()
	{
		global $modx;
		$this->tbl['check']		 = $GLOBALS['table_prefix'] . "scounter_useronline";
		$this->tbl['useronline'] = $modx->getFullTableName("scounter_useronline");
		$this->tbl['daycount']	 =   $modx->getFullTableName('scounter_daycount');
		$this->ip	= $_SERVER['REMOTE_ADDR'];
	}

	function FirstRun($path)
	{
		$rs = db()->query("SHOW TABLES LIKE '" . $this->tbl["check"] . "'");
		$count = db()->getRecordCount($rs);

		if ($count != 0) {
			return;
		}

		$fh = fopen($path . "includes/sc.install.db.sql", 'rb');
		$idata = '';
		while (!feof($fh)) {
			$idata .= fread($fh, 1024);
		}
		fclose($fh);
		$idata = str_replace("\r", '', $idata);
		$idata = str_replace('{PREFIX}', $GLOBALS['table_prefix'], $idata);
		$sql_array = explode("\n\n", $idata);
		foreach ($sql_array as $sql_entry) {
			$sql_do = trim($sql_entry, "\r\n; ");
			db()->query($sql_do);
		}
	}

	function Read($time)
	{
		$this->Delete($time);
		$rs = db()->select(" * ", $this->tbl["useronline"], '`ip` = "' . $this->ip . '"');
		$count = db()->getRecordCount($rs);
		if ($count == 1) {
			$this->Update();
		} else {
			$this->Writenew();
		}

		$this->ReadDate();
		return array($this->total, $this->today, $this->yesterday, $this->useronline);
	}

	function Writenew()
	{
		//create row for new user-online
		db()->query(
			'INSERT INTO ' . $this->tbl["useronline"] . ' VALUES ("' . $this->ip . '", NOW())'
		);

		// write into <today> one new user
		$rs = db()->select(" * ", $this->tbl["daycount"], '`date` = CURDATE( )');
		if (db()->getRecordCount($rs) == 1) {
			while ($row = db()->getRow($rs)) {
				$count = $row['count'];
			}
			$count++;
			db()->update("`count` = '" . $count . "'", $this->tbl["daycount"], "`date` = CURDATE()");
		}
	}

	function Update()
	{
		//update the time in the database, if user is still online
		db()->update("`time` = NOW()", $this->tbl["useronline"], '`ip` = "' . $this->ip . '"');
	}

	function Delete($time)
	{
		//create all rows which are older than $time minutes
		db()->query(
            sprintf(
                'DELETE FROM %s WHERE DATE_SUB(NOW(), INTERVAL %s MINUTE) > time',
                $this->tbl["useronline"],
                $time
            )
		);
	}

	function ReadDate()
	{
		// Read Users from today - if row not exists - create it.
		$rs = db()->select(" * ", $this->tbl["daycount"], '`date` = CURDATE( )');
		if (db()->getRecordCount($rs) == 1) {
			while ($row = db()->getRow($rs)) {
				$this->today = $row['count'];
			}
		} else {
			db()->query("INSERT INTO " . $this->tbl["daycount"] . " VALUES (CURDATE(), '1')");
			$this->today = 1;
		}
		// Read Users from yesterday
		$rs = db()->select(" * ", $this->tbl["daycount"], '`date` = DATE_SUB(CURDATE( ), INTERVAL 1 DAY)');
		while ($row = db()->getRow($rs)) {
			$this->yesterday = $row['count'];
		}
		if ($this->yesterday == "") $this->yesterday = 0;
		// Read Users from totals
		$rs = db()->select(" SUM(count) ", $this->tbl["daycount"]);
		while ($row = db()->getRow($rs)) {
			$this->total = $row['SUM(count)'];
		}
		$rs = db()->select(" * ", $this->tbl["useronline"]);
		$this->useronline = db()->getRecordCount($rs);
	}
}
