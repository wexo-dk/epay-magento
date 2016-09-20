<?php
/**
 * Copyright ePay | Dit Online Betalingssystem, (c) 2010.
 * This program is free software. You are allowed to use the software but NOT allowed to modify the software. 
 * It is also not legal to do any changes to the software and distribute it in your own name / brand. 
 */
class Mage_Epay_Block_Adminhtml_Order_Grid extends Mage_Adminhtml_Block_Sales_Order_Grid
{
    protected function _prepareMassaction()
    {
		parent::_prepareMassaction();

        $this->getMassactionBlock()->addItem('epay_order', array(
             'label'=> Mage::helper('sales')->__('Capture with ePay'),
             'url'  => $this->getUrl('epay/adminhtml_massaction/epayCapture'),
        ));

        return $this;
    }
}
?>