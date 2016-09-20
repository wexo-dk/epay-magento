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
		
		ALTER TABLE epay_order_status ADD COLUMN `cardnopostfix` VARCHAR(45) NOT NULL;
		
    ");


$installer->endSetup();

