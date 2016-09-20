<?php
/**
 * Copyright ePay | Dit Online Betalingssystem, (c) 2010.
 * This program is free software. You are allowed to use the software but NOT allowed to modify the software. 
 * It is also not legal to do any changes to the software and distribute it in your own name / brand. 
 */

class Mage_Epay_Block_Standard_Redirect extends Mage_Core_Block_Template
{
    
    public function __construct()
    {
        parent::__construct();
        $standard = Mage::getModel('epay/standard');

        $this->setTemplate('epay/standard/redirect_standardwindow.phtml');

        //
    	// Save the order into the epay_order_status table
    	//
    	$write = Mage::getSingleton('core/resource')->getConnection('core_write');
		$write->insert('epay_order_status', array('orderid'=>$standard->getCheckout()->getLastRealOrderId()));
    }
}
