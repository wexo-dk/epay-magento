<?php

class Mage_Epay_Block_Adminhtml_Form_Paymentrequest extends Mage_Adminhtml_Block_Widget_Form_Container
{
    public function __construct()
    {
        parent::__construct();

        $this->_objectId = 'id';
        $this->_blockGroup = 'epay';
        $this->_controller = 'adminhtml_form';
        $this->_mode = 'paymentrequest';
		
		$this->_removeButton('save'); 
		$this->_removeButton('delete'); 
				
        $this->_addButton('saveandcontinue', array(
            'label'     => Mage::helper('adminhtml')->__('Send payment request'),
            'onclick'   => 'paymentrequest_form.submit();',
            'class'     => 'save',
        ), -100);
    }
 
    public function getHeaderText()
    {
        return Mage::helper('epay')->__('Payment request');
    }
}