<?php
/**
 * Copyright ePay | Dit Online Betalingssystem, (c) 2016.
 * 
 * This program is free software. You are allowed to use the software but NOT allowed to modify the software. 
 * It is also not legal to do any changes to the software and distribute it in your own name / brand. 
 */
class Mage_Epay_Model_Mysql4_Paymentrequest extends Mage_Core_Model_Mysql4_Abstract
{
     public function _construct()
     {
         $this->_init('epay/paymentrequest', 'id');
     }
}