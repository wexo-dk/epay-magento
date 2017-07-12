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

use Mage_Epay_Helper_EpayConstant as EpayConstant;

class Mage_Epay_Model_System_Config_Source_Surchargemode
{
    public function toOptionArray()
    {
        return array(
            array('value'=>EpayConstant::SURCHARGE_ORDER_LINE, 'label'=>"Create order line"),
            array('value'=>EpayConstant::SURCHARGE_SHIPMENT, 'label'=>"Add to shipment & handling")
        );
    }
}
