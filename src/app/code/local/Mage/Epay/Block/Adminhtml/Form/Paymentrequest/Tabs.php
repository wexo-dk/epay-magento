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
