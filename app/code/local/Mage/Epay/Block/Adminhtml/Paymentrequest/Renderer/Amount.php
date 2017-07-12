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
class Mage_Epay_Block_Adminhtml_Paymentrequest_Renderer_Amount extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{
    public function render(Varien_Object $row)
    {
        $data = $row->getData();

        $currencyCode = $data["currency_code"];
        if(!isset($currencyCode)) {
            $order = Mage::getModel('sales/order')->loadByIncrementId($data["orderid"]);
            $currencyCode = $order->getBaseCurrencyCode();
        }
        $minorunits = Mage::helper('epay')->getCurrencyMinorunits($currencyCode);
        $amountInMinorunits = Mage::helper('epay')->convertPriceFromMinorunits($data["amount"], $minorunits);
        $formattedPrice = Mage::app()->getLocale()->currency($currencyCode)->toCurrency($amountInMinorunits);

        return $formattedPrice;
    }
}
