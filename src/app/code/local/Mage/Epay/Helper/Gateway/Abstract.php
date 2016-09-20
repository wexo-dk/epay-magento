<?php
/**
 * Copyright ePay | Dit Online Betalingssystem, (c) 2010.
 * This program is free software. You are allowed to use the software but NOT allowed to modify the software. 
 * It is also not legal to do any changes to the software and distribute it in your own name / brand. 
 */
 
abstract class Mage_Epay_Helper_Gateway_Abstract extends Mage_Core_Helper_Abstract
{
    protected $order;

	protected $lines = array();
	
    public function init($order)
    {
        $this->order = $order;
    }

    public function getGoodsList($items = null)
    {
        if ($items === null) {
            $items = $this->order->getAllVisibleItems();
        }
		
        foreach ($items as $item) {
            //For handling the different activation
            $qty = $item->getQtyOrdered(); //Standard
            if (!isset($qty)) {
                $qty = $item->getQty(); //Advanced
            }
            $id = $item->getProductId();
            $product = Mage::getModel('catalog/product')->load($id);

            $extras = Mage::helper('epay/gateway_extras')
                ->init($this->order);

            $taxRate = $extras->getTaxRate($product->getTaxClassId());

            $lines[] = array
			(
				"quantity" => $qty,
				"id" => $item->getSku(),
				"description" => $item->getName(),
				"price" => $item->getBasePrice()*100,
				"vat" => $taxRate,
				"discount" => 0
			);
        }

        //Only add discounts and etc for unactivated orders
        if ($this->order->hasInvoices() <= 1) {
            $extraFees = Mage::helper('epay/gateway_extras')
            	->init($this->order);

	        foreach ($extraFees->assemble() as $fee) {
	            $lines[] = $fee;
	        }
        }
		
		return $lines;
    }
}