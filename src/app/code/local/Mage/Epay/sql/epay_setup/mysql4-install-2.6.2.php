<?php
/**
 * Copyright ePay | Dit Online Betalingssystem, (c) 2010.
 * 
 * This program is free software. You are allowed to use the software but NOT allowed to modify the software. 
 * It is also not legal to do any changes to the software and distribute it in your own name / brand. 
 */


$installer = $this;

$installer->startSetup();

$installer->run("	
	CREATE TABLE if not exists `paymentrequest` (
	  `id` int(11) NOT NULL AUTO_INCREMENT,
	  `orderid` varchar(20) DEFAULT NULL,
	  `currency_code` char(3) DEFAULT NULL,
	  `amount` int(11) DEFAULT NULL,
	  `receiver` varchar(255) DEFAULT NULL,
	  `ispaid` tinyint(4) NOT NULL DEFAULT '0',
	  `status` int(11) NOT NULL DEFAULT '0',
	  `paymentrequestid` bigint(20) DEFAULT NULL,
	  `created` timestamp NULL DEFAULT NULL,
	  PRIMARY KEY (`id`)
	);
");


$installer->endSetup();

