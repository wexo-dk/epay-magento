<?php
class Mage_Epay_Model_Mysql4_Paymentrequest_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract
 {
     public function _construct()
     {
         parent::_construct();
         $this->_init('epay/paymentrequest');
     }
}