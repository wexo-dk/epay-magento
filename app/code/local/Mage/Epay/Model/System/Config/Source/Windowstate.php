<?php
/**
 * Copyright ePay | Dit Online Betalingssystem, (c) 2010.
 * 
 * This program is free software. You are allowed to use the software but NOT allowed to modify the software. 
 * It is also not legal to do any changes to the software and distribute it in your own name / brand. 
 */
class Mage_Epay_Model_System_Config_Source_Windowstate
{

    public function toOptionArray()
    {
        return array(
            array('value'=>1, 'label'=>Mage::helper('adminhtml')->__("Overlay")),
            array('value'=>3, 'label'=>Mage::helper('adminhtml')->__("Full Screen")),
        );
    }

}