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

    /**
     * Write exception to log
     *
     * @param string $message
     * @param int $level
     * @return void
     */
    public function log($id, $message, $level = null)
    {
        $errorMessage = sprintf("(ID: %s) - %s ", $id, $message);
        Mage::log($errorMessage, $level, 'bambora.log');
    }

    /**
     * Write exception to log
     *
     * @param Exception $exception
     * @return void
     */
    public function logException($exception)
    {
        Mage::log($exception->__toString(), 2, 'bamboraException.log');
    }


    /**
     * Convert an amount to minorunits
     *
     * @param $amount
     * @param $minorUnits
     * @param $roundingMode
     * @return int
     */
    public function convertPriceToMinorunits($amount, $minorunits, $roundingMode)
    {
        if ($amount == "" || $amount == null) {
            return 0;
        }

        switch ($roundingMode) {
            case EpayConstant::ROUND_UP:
                $amount = ceil($amount * pow(10, $minorunits));
                break;
            case EpayConstant::ROUND_DOWN:
                $amount = floor($amount * pow(10, $minorunits));
                break;
            default:
                $amount = round($amount * pow(10, $minorunits));
                break;
        }

        return $amount;
    }

    /**
     * Convert an amount from minorunits
     *
     * @param $amount
     * @param $minorunits
     * @return float
     */
    public function convertPriceFromMinorunits($amount, $minorunits)
    {
        if ($amount == "" || $amount == null) {
            return 0;
        }

        return ($amount / pow(10, $minorunits));
    }

    /**
     * Return minorunits based on Currency Code
     *
     * @param $currencyCode
     * @return int
     */
    public function getCurrencyMinorunits($currencyCode)
    {
        $currencyArray = array(
        'TTD' => 0, 'KMF' => 0, 'ADP' => 0, 'TPE' => 0, 'BIF' => 0,
        'DJF' => 0, 'MGF' => 0, 'XPF' => 0, 'GNF' => 0, 'BYR' => 0,
        'PYG' => 0, 'JPY' => 0, 'CLP' => 0, 'XAF' => 0, 'TRL' => 0,
        'VUV' => 0, 'CLF' => 0, 'KRW' => 0, 'XOF' => 0, 'RWF' => 0,
        'IQD' => 3, 'TND' => 3, 'BHD' => 3, 'JOD' => 3, 'OMR' => 3,
        'KWD' => 3, 'LYD' => 3);

        return key_exists($currencyCode, $currencyArray) ? $currencyArray[$currencyCode] : 2;
    }
}
