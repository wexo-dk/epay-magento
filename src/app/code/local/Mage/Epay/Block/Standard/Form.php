<?php
/**
 * Copyright ePay | Dit Online Betalingssystem, (c) 2010.
 * This program is free software. You are allowed to use the software but NOT allowed to modify the software. 
 * It is also not legal to do any changes to the software and distribute it in your own name / brand. 
 */
class Mage_Epay_Block_Standard_Form extends Mage_Payment_Block_Form
{
    protected function _construct()
    {
        $this->setTemplate('epay/standard/form.phtml');
        parent::_construct();
    }
}