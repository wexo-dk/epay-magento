<?php

class Mage_Epay_Block_Adminhtml_Form_Paymentrequest_Tabs extends Mage_Adminhtml_Block_Widget_Tabs
{
	public function __construct()
	{
		parent::__construct();
		$this->setId('paymentrequest_tabs');
		$this->setDestElementId('paymentrequest_form');
		$this->setTitle(Mage::helper('epay')->__("Payment request"));
	}

	protected function _beforeToHtml()
	{
		$this->addTab('general', array(
			'label' => Mage::helper('epay')->__("General"),
			'title' => Mage::helper('epay')->__("General"),
			'content' => $this->getLayout()->createBlock('epay/adminhtml_form_paymentrequest_tab_general')->toHtml(),
		));

		$this->addTab('recipient', array(
			'label' => Mage::helper('epay')->__("E-mail"),
			'title' => Mage::helper('epay')->__("E-mail"),
			'content' => $this->getLayout()->createBlock('epay/adminhtml_form_paymentrequest_tab_recipient')->toHtml(),
		));

		return parent::_beforeToHtml();
	}
}