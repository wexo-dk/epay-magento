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
class Mage_Epay_Helper_Data extends Mage_Core_Helper_Abstract
{
    const EPAY_SURCHARGE = 'surcharge_fee';

    public function isValidOrder($incrementId)
    {
        //Validate order id
        $order = Mage::getModel('sales/order')->loadByIncrementId($incrementId);
        if ($order->hasData()) {
            return true;
        }

        return false;
    }

    /**
     * Create Fee Item
     *
     * @param mixed $baseFeeAmount
     * @param mixed $feeAmount
     * @param mixed $storeId
     * @param mixed $orderId
     * @return Mage_Sales_Model_Order_Item
     */
    public function createFeeItem($baseFeeAmount, $feeAmount, $storeId, $orderId, $text)
    {
        /** @var Mage_Sales_Model_Order_Item */
        $feeItem = Mage::getModel('sales/order_item');

        $feeItem->setSku($this::EPAY_SURCHARGE);
        $feeItem->setName($text);
        $feeItem->setBaseCost($baseFeeAmount);
        $feeItem->setBasePrice($baseFeeAmount);
        $feeItem->setBasePriceInclTax($baseFeeAmount);
        $feeItem->setBaseOriginalPrice($baseFeeAmount);
        $feeItem->setBaseRowTotal($baseFeeAmount);
        $feeItem->setBaseRowTotalInclTax($baseFeeAmount);

        $feeItem->setCost($feeAmount);
        $feeItem->setPrice($feeAmount);
        $feeItem->setPriceInclTax($feeAmount);
        $feeItem->setOriginalPrice($feeAmount);
        $feeItem->setRowTotal($feeAmount);
        $feeItem->setRowTotalInclTax($feeAmount);

        $feeItem->setProductType(Mage_Catalog_Model_Product_Type::TYPE_VIRTUAL);
        $feeItem->setIsVirtual(1);
        $feeItem->setQtyOrdered(1);
        $feeItem->setStoreId($storeId);
        $feeItem->setOrderId($orderId);

        return $feeItem;
    }
}
