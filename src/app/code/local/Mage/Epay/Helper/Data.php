<?php
/**
 * Copyright ePay | Dit Online Betalingssystem, (c) 2010.
 * This program is free software. You are allowed to use the software but NOT allowed to modify the software. 
 * It is also not legal to do any changes to the software and distribute it in your own name / brand. 
 */
class Mage_Epay_Helper_Data extends Mage_Core_Helper_Abstract
{
    public function isValidOrder($incrementId)
	{
		//Validate order id
		$order = Mage::getModel('sales/order')->loadByIncrementId($incrementId);
		if($order->hasData())
		{
			return true;
		}
		
		return false;
	}
}
