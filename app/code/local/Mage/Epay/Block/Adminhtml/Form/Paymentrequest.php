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

        $this->_addButton(
            'saveandcontinue', array(
            'label'     => Mage::helper('adminhtml')->__("Send payment request"),
            'onclick'   => 'paymentrequest_form.submit();',
            'class'     => 'save',
            ), -100
        );
    }

    public function getHeaderText()
    {
        return Mage::helper('epay')->__("Payment request");
    }
}
