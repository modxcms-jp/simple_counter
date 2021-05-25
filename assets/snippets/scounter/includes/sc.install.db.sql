CREATE TABLE `{PREFIX}scounter_daycount` (
`date` date NOT NULL COMMENT 'Das Datum des Tages',
`count` INT( 11 ) NOT NULL DEFAULT '0' COMMENT 'Anzahl der Besucher',
PRIMARY KEY ( `date` )
) ENGINE = MYISAM ;

INSERT INTO `{PREFIX}scounter_daycount` (`date`, `count`) VALUES 
(CURDATE( )-1, 0),
(CURDATE( )-2, 0);


CREATE TABLE `{PREFIX}scounter_useronline` (
`ip` VARCHAR( 15 ) NOT NULL COMMENT 'Die IP des Besuchers',
`time` DATETIME NOT NULL COMMENT 'Die genaue Zeit seines Besuches',
PRIMARY KEY ( `IP` )
) ENGINE = MYISAM ;