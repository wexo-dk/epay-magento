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
class Mage_Epay_Block_Adminhtml_Form_Paymentrequest_Tab_General extends Mage_Adminhtml_Block_Widget_Form
{
    protected function _prepareForm()
    {
        $order_id = $this->getRequest()->getParam('id');
        $order = Mage::getModel('sales/order')->loadByIncrementId($order_id);

        $form = new Varien_Data_Form();
        $this->setForm($form);
        /** @var Mage_Sales_Model_Order_Payment */
        $payment = $order->getPayment();

        $payment->setCcNumberEnc("");
        $payment->setCcType("");
        $payment->setAdditionalInformation(Mage_Epay_Model_Standard::PSP_REFERENCE, "");
        $payment->save();


        $formData = $this->getRequest()->getPost();
        $formData = $formData ? new Varien_Object($formData) : new Varien_Object();

        $fieldset = $form->addFieldset('paymentrequest_paymentrequest', array('legend' => Mage::helper('epay')->__("Payment request")));

        $fieldset->addField(
            'orderid', 'text', array(
            'label'     => Mage::helper('epay')->__("Order #"),
            'class'     => 'required-entry',
            'required'  => true,
            'name'      => 'orderid',
            'value'     => $this->getRequest()->getParam('id'),
            'readonly'  => true
            )
        );

        $fieldset->addField(
            'amount', 'text', array(
            'label'     => Mage::helper('epay')->__("Payment Amount"),
            'class'     => 'required-entry',
            'required'  => true,
            'name'      => 'amount',
            'value'        => $formData->getAmount() != null ? $formData->getAmount() : Mage::getModel('directory/currency')->format($order->getBaseTotalDue(), array('display' => Zend_Currency::NO_SYMBOL), false),
            'readonly'    => true
            )
        );

        $fieldset->addField(
            'currency', 'text', array(
            'label'     => Mage::helper('epay')->__("Currency"),
            'class'     => 'required-entry',
            'required'  => true,
            'name'      => 'currency',
            'value'     => $formData->getCurrency() != null ? $formData->getCurrency() : $order->getStore()->getBaseCurrencyCode(),
            'readonly'  => true
            )
        );

        return parent::_prepareForm();
    }
}
