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

    public function getPaymentLogos()
    {
        $standard = Mage::getModel('epay/standard');
        $merchantNumber = $standard->getConfigData('merchantnumber', $standard->getStore()->getId());
        $res = '<iframe style="width:100%; height: 40px;" frameborder="0" src="https://relay.ditonlinebetalingssystem.dk/integration/paymentlogos/PaymentLogos.aspx?merchantnumber='.$merchantNumber.'&direction=2&padding=2&rows=1&logo=0&showdivs=0&iframe=1"></iframe>';
        return $res;
    }
}