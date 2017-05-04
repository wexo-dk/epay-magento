<?php
class Mage_Epay_Model_Objectmodel_Api extends Mage_Api_Model_Resource_Abstract
{
     /**
     * method Name
     *
     * @param string $orderIncrementId
     * @return string
     */
    public function GetPaymentInfo($orderid)
    {
		$read = Mage::getSingleton('core/resource')->getConnection('core_read');
		$row = $read->fetchRow("select * from epay_order_status where orderid = '" . $orderid . "' LIMIT 1");
        return $row;
    }
}