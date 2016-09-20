<?php

class Mage_Epay_Block_Adminhtml_Form_Paymentrequest_Tab_Recipient extends Mage_Adminhtml_Block_Widget_Form
{
    protected function _prepareForm()
    {
		$order_id = $this->getRequest()->getParam('id');
		$order = Mage::getModel('sales/order')->loadByIncrementId($order_id);
		
        $form = new Varien_Data_Form();
        $this->setForm($form);
		
		$formData = $this->getRequest()->getPost();
		$formData = $formData ? new Varien_Object($formData) : new Varien_Object();

		$fieldset = $form->addFieldset('paymentrequest_requester', array('legend' => Mage::helper('epay')->__('E-mail')));
          
		$fieldset->addField('email_requester', 'text', array(
			'label'     => Mage::helper('epay')->__('Requester'),
			'class'     => 'required-entry',
			'required'  => true,
			'name'      => 'requester',
			'value'		=> $formData->getRequester() != null ? $formData->getRequester() : $order->getStore()->getWebsite()->getName()
        ));
		
		$fieldset->addField('email_comment', 'textarea', array(
			'label'     => Mage::helper('epay')->__('Comment'),
			//'class'     => 'required-entry',
			'required'  => false,
			'name'      => 'comment',
			'value'		=> $formData->getComment()
        ));
		
        $fieldset = $form->addFieldset('paymentrequest_recipient', array('legend' => Mage::helper('epay')->__('Recipient')));
          
		$fieldset->addField('recipient_name', 'text', array(
			'label'     => Mage::helper('epay')->__('Name'),
			'class'     => 'required-entry',
			'required'  => true,
			'name'      => 'recipient_name',
			'value'		=> $formData->getRecipientName() != null ? $formData->getRecipientName() : $order->getCustomerName()
        ));
		  
        $fieldset->addField('recipient_email', 'text', array(
			'label'     => Mage::helper('epay')->__('E-mail'),
			'class'     => 'required-entry',
			'required'  => true,
			'name'      => 'recipient_email',
			'value'		=> $formData->getRecipientEmail() != null ? $formData->getRecipientEmail() : $order->getCustomerEmail()
        ));
		
		$fieldset = $form->addFieldset('paymentrequest_replyto', array('legend' => Mage::helper('epay')->__('Reply to')));
          
		$fieldset->addField('replyto_name', 'text', array(
			'label'     => Mage::helper('epay')->__('Name'),
			'class'     => 'required-entry',
			'required'  => true,
			'name'      => 'replyto_name',
			'value'		=> $formData->getReplytoName() != null ? $formData->getReplytoName() : Mage::getStoreConfig('trans_email/ident_sales/name')
        ));
		
		$fieldset->addField('replyto_email', 'text', array(
			'label'     => Mage::helper('epay')->__('E-mail'),
			'class'     => 'required-entry',
			'required'  => true,
			'name'      => 'replyto_email',
			'value'		=> $formData->getReplytoEmail() != null ? $formData->getReplytoEmail() : Mage::getStoreConfig('trans_email/ident_sales/email')
        ));

        return parent::_prepareForm();
    }
}