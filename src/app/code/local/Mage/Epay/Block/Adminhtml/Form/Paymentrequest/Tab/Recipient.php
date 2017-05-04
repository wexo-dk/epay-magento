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
class Mage_Epay_Block_Adminhtml_Form_Paymentrequest_Tab_Recipient extends Mage_Adminhtml_Block_Widget_Form
{
    protected function _prepareForm()
    {
        $order_id = $this->getRequest()->getParam('id');
        $order = Mage::getModel('sales/order')->loadByIncrementId($order_id);

        $store_id = $order->getStoreId();

        $form = new Varien_Data_Form();
        $this->setForm($form);

        $formData = $this->getRequest()->getPost();
        $formData = $formData ? new Varien_Object($formData) : new Varien_Object();

        $fieldset = $form->addFieldset('paymentrequest_requester', array('legend' => Mage::helper('epay')->__("E-mail")));

        $fieldset->addField('email_requester', 'text', array(
            'label'     => Mage::helper('epay')->__("Requester"),
            'class'     => 'required-entry',
            'required'  => true,
            'name'      => 'requester',
            'value'        => $formData->getRequester() != null ? $formData->getRequester() : $order->getStore()->getWebsite()->getName()
        ));

        $fieldset->addField('email_comment', 'textarea', array(
            'label'     => Mage::helper('epay')->__("Comment"),
            //'class'     => 'required-entry',
            'required'  => false,
            'name'      => 'comment',
            'value'        => $formData->getComment()
        ));

        $fieldset = $form->addFieldset('paymentrequest_recipient', array('legend' => Mage::helper('epay')->__("Recipient")));

        $fieldset->addField('recipient_name', 'text', array(
            'label'     => Mage::helper('epay')->__("Name"),
            'class'     => 'required-entry',
            'required'  => true,
            'name'      => 'recipient_name',
            'value'        => $formData->getRecipientName() != null ? $formData->getRecipientName() : $order->getCustomerName()
        ));

        $fieldset->addField('recipient_email', 'text', array(
            'label'     => Mage::helper('epay')->__("E-mail"),
            'class'     => 'required-entry',
            'required'  => true,
            'name'      => 'recipient_email',
            'value'        => $formData->getRecipientEmail() != null ? $formData->getRecipientEmail() : $order->getCustomerEmail()
        ));

        $fieldset = $form->addFieldset('paymentrequest_replyto', array('legend' => Mage::helper('epay')->__("Reply to")));

        $fieldset->addField('replyto_name', 'text', array(
            'label'     => Mage::helper('epay')->__("Name"),
            'class'     => 'required-entry',
            'required'  => true,
            'name'      => 'replyto_name',
            'value'        => $formData->getReplytoName() != null ? $formData->getReplytoName() : Mage::getStoreConfig('trans_email/ident_sales/name', $store_id)
        ));

        $fieldset->addField('replyto_email', 'text', array(
            'label'     => Mage::helper('epay')->__("E-mail"),
            'class'     => 'required-entry',
            'required'  => true,
            'name'      => 'replyto_email',
            'value'        => $formData->getReplytoEmail() != null ? $formData->getReplytoEmail() : Mage::getStoreConfig('trans_email/ident_sales/email', $store_id)
        ));

        return parent::_prepareForm();
    }
}
