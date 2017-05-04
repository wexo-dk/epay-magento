<?php
/**
 * Copyright (c) 2017. All rights reserved ePay A/S (a Bambora Company).
 *
 * This program is free software. You are allowed to use the software but NOT allowed to modify the software.
 * It is also not legal to do any changes to the software and distribute it in your own name / brand.
 *
 * All use of the payment modules happens at your own risk. We offer a free test account that you can use to test the module.
 *
 * @author    ePay A/S (a Bambora Company)
 * @copyright Bambora (http://bambora.com) (http://www.epay.dk)
 * @license   ePay A/S (a Bambora Company)
 *
 */
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
