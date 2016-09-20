<?php
/**
 * Copyright ePay | Dit Online Betalingssystem, (c) 2009.
 * 
 * This program is free software. You are allowed to use the software but NOT allowed to modify the software. 
 * It is also not legal to do any changes to the software and distribute it in your own name / brand. 
 */


$installer = $this;

$installer->startSetup();

$installer->run("

		delete from {$installer->getTable('core_resource')} where code = 'epay_setup';
		
		CREATE TABLE if not exists `epay_order_status` (
  	`orderid` VARCHAR(45) NOT NULL,
  	`tid` VARCHAR(45) NOT NULL,
  	`status` INTEGER UNSIGNED NOT NULL DEFAULT 0 COMMENT '0 = unpaid, 1 = paid',
  	`amount` VARCHAR(45) NOT NULL,
  	`cur` VARCHAR(45) NOT NULL,
  	`date` VARCHAR(45) NOT NULL,
  	`eKey` VARCHAR(45) NOT NULL,
  	`fraud` VARCHAR(45) NOT NULL,
  	`subscriptionid` VARCHAR(45) NOT NULL,
  	`cardid` VARCHAR(45) NOT NULL,
  	`transfee` VARCHAR(45) NOT NULL
		);
		
    ");


$installer->endSetup();

