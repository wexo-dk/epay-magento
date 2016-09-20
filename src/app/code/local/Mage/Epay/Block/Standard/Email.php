<?php
/**
 * Copyright ePay | Dit Online Betalingssystem, (c) 2010.
 * This program is free software. You are allowed to use the software but NOT allowed to modify the software. 
 * It is also not legal to do any changes to the software and distribute it in your own name / brand. 
 */
class Mage_Epay_Block_Standard_Email extends Mage_Core_Block_Template
{
	
	protected $_toHtml;
	
    protected function _construct()
    {
        $this->setTemplate('epay/standard/email.phtml');	
		parent::_construct();
    }
	
	public function _toHtml()
	{
        $html = $this->renderView();
        return $html;
	}
	
	public function toPdf()
	{
        $this->setTemplate('payment/info/pdf/default.phtml');
      	return $this->toHtml();
	}
	
	public function getMethod()
    {
        return Mage::getModel('epay/standard');
    }	
	
}