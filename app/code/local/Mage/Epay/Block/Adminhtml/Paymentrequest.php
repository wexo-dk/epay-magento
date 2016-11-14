<?php
/**
 * Copyright ePay | Dit Online Betalingssystem, (c) 2010.
 * 
 * This program is free software. You are allowed to use the software but NOT allowed to modify the software. 
 * It is also not legal to do any changes to the software and distribute it in your own name / brand. 
 */
 
class Mage_Epay_Block_Adminhtml_Paymentrequest extends Mage_Adminhtml_Block_Widget_Grid_Container
{
	public function __construct()
	{
		parent::__construct();
		$this->removeButton('add');
		$this->_controller = 'adminhtml_paymentrequest';
		$this->_blockGroup = 'epay';
		$this->_headerText = Mage::helper('epay')->__("Payment requests");
	}
	
	protected function _prepareLayout()
	{
		$this->setChild('grid', $this->getLayout()->createBlock($this->_blockGroup . '/' . $this->_controller . '_grid', $this->_controller . '.grid')->setSaveParametersInSession(true));
		return parent::_prepareLayout();
	}
}
