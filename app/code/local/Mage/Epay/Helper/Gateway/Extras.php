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
class Mage_Epay_Helper_Gateway_Extras extends Mage_Core_Helper_Abstract
{
    /**
     * @var  Mage_Varian_Order
     */
    protected $order;

    /**
     * @var Mage_Varian_Quote
     */
    private $_quote;

    /**
     * @var array
     */
    private $_extras;

    /**
     * Entry point.
     *
     * @param array  $data  data array
     * @param object $order paramname description
     *
     * @return void No return value
     */
    public function init($order)
    {
        $this->order = $order;
        $this->_quote = $order->getQuote();
        $this->_extras = array();

        return $this;
    }

    public function getTaxRate($taxClass)
    {
        // Load the customer so we can retrevice the correct tax class id
        $customer = Mage::getModel('customer/customer')
            ->load($this->order->getCustomerId());
        $calculation = Mage::getSingleton('tax/calculation');
        $request = $calculation->getRateRequest(
            $this->order->getShippingAddress(),
            $this->order->getBillingAddress(),
            $customer->getTaxClassId(),
            $this->order->getStore()
        );
        return $calculation->getRate($request->setProductClassId($taxClass));
    }

    public function assemble()
    {
        $this->_addShippingFee();

        $this->_addGiftCard();

        $this->_addCustomerBalance();

        $this->_addRewardCurrency();

        $this->_addDiscount();

        $this->_addGiftWrapPrice();

        $this->_addGiftWrapItemPrice();

        $this->_addGwPrintedCardPrice();

        return $this->_extras;
    }

    private function _addGiftWrapPrice()
    {
        if ($this->order->getGwPrice() <= 0) {
            return;
        }

        $price = $this->order->getGwPrice();
        $tax = $this->order->getGwTaxAmount();

        $name = Mage::helper("enterprise_giftwrapping")->__("Gift Wrapping for Order");
        $this->_extras[] = array(
            "quantity" => 1,
            "id" => "gw_order",
            "description" => $name,
            "price" => ($price + $tax) * 100,
        );
    }

    private function _addGiftWrapItemPrice()
    {
        if ($this->order->getGwItemsPrice() <= 0) {
            return;
        }

        $price = $this->order->getGwItemsPrice();
        $tax = $this->order->getGwItemsTaxAmount();

        $name = Mage::helper("enterprise_giftwrapping")
            ->__("Gift Wrapping for Items");

        $this->_extras[] = array(
            "quantity" => 1,
            "id" => "gw_items",
            "description" => $name,
            "price" => ($price + $tax) * 100
        );
    }

    private function _addGwPrintedCardPrice()
    {
        if ($this->order->getGwPrintedCardPrice() <= 0) {
            return;
        }

        $price = $this->order->getGwPrintedCardPrice();
        $tax = $this->order->getGwPrintedCardTaxAmount();

        $name = Mage::helper("enterprise_giftwrapping")->__("Printed Card");
        $this->_extras[] = array(
            "quantity" => 1,
            "id" => "gw_printed_card",
            "description" => $name,
            "price" => ($price + $tax) * 100
        );
    }

    private function _addGiftCard()
    {
        if ($this->order->getGiftCardsAmount() <= 0) {
            return;
        }

        $this->_extras[] = array(
            "quantity" => 1,
            "id" => "gift_card",
            "description" => "gift_card",
            "price" => ($this->order->getGiftCardsAmount() * -1) * 100
        );
    }

    private function _addCustomerBalance()
    {
        if ($this->order->getCustomerBalanceAmount() <= 0) {
            return;
        }

        $this->_extras[] = array(
            "quantity" => 1,
            "id" => "customer_balance",
            "description" => "customer_balance",
            "price" => ($this->order->getCustomerBalanceAmount() * -1) * 100
        );
    }

    private function _addRewardCurrency()
    {
        if ($this->order->getRewardCurrencyAmount() <= 0) {
            return;
        }

        $this->_extras[] = array(
            "quantity" => 1,
            "id" => "reward_currency",
            "description" => "reward_currency",
            "price" => ($this->order->getRewardCurrencyAmount() * -1) * 100
        );
    }

    private function _addShippingFee()
    {
        if ($this->order->getBaseShippingInclTax() <= 0) {
            return;
        }

        $taxClass = Mage::getStoreConfig('tax/classes/shipping_tax_class');

        $this->_extras[] = array(
            "quantity" => 1,
            "id" => $this->order->getShippingMethod(),
            "description" => $this->order->getShippingDescription(),
            "price" => $this->order->getShippingAmount() * 100,
            "vat" => $this->getTaxRate($taxClass)
        );
    }

    private function _addDiscount()
    {
        if ($this->order->getDiscountAmount() >= 0) {
            return;
        }

        $amount = $this->order->getDiscountAmount();
        $applyAfter = Mage::helper('tax')->applyTaxAfterDiscount(
            $this->order->getStoreId()
        );
        if ($applyAfter == true) {
            //With this setting active the discount will not have the correct
            //value. We need to take each respective products rate and calculate
            //a new value.
            $amount = 0;
            foreach ($this->order->getAllVisibleItems() as $product) {
                $rate = $product->getTaxPercent();
                $newAmount = $product->getBaseDiscountAmount() * (($rate / 100) + 1);
                $amount -= $newAmount;
            }

            //If the discount also extends to shipping
            $shippingDiscount = $this->order->getBaseShippingDiscountAmount();
            if ($shippingDiscount) {
                $taxClass = Mage::getStoreConfig('tax/classes/shipping_tax_class');
                $rate = $this->getTaxRate($taxClass);
                $newAmount = $shippingDiscount * (($rate / 100) + 1);
                $amount -= $newAmount;
            }
        }

        $desc = $this->order->getDiscountDescription();

        $this->_extras[] = array(
            "quantity" => 1,
            "id" => $desc,
            "description" => Mage::helper('sales')->__("Discount (%s)", $desc),
            "price" => $amount * 100
        );
    }
}
