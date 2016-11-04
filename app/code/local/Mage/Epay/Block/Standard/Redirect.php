<?php
/**
 * Copyright ePay | Dit Online Betalingssystem, (c) 2010.
 * This program is free software. You are allowed to use the software but NOT allowed to modify the software.
 * It is also not legal to do any changes to the software and distribute it in your own name / brand.
 */

class Mage_Epay_Block_Standard_Redirect extends Mage_Core_Block_Template
{
    private $_method;
    private $_order;

    public function __construct()
    {
        parent::__construct();

        if(!$this->isOrderCompleated())
        {
            $this->setTemplate('epay/standard/redirect_standardwindow.phtml');

            //
            // Save the order into the epay_order_status table
            //
            $write = Mage::getSingleton('core/resource')->getConnection('core_write');
            $write->insert('epay_order_status', array('orderid'=>$this->getMethod()->getCheckout()->getLastRealOrderId()));
        }
        else
        {
            //If the order is already compleated goto onepage success
            Mage::app()->getFrontController()->getResponse()->setRedirect(Mage::getUrl('checkout/onepage/success'));
            Mage::app()->getResponse()->sendResponse();
        }
    }

    private function isOrderCompleated()
    {
        $currentOrderStatus = $this->getOrder()->getStatus();
        $compleatedStatus = $this->getMethod()->getConfigData('order_status_after_payment', $this->getOrder() ? $this->getOrder()->getStoreId() : null);

        return $currentOrderStatus === $compleatedStatus;
    }

    private function getMethod()
    {
        if(!isset($this->_method))
        {
            $this->_method = Mage::getModel('epay/standard');
        }

        return $this->_method;
    }

    private function getOrder()
    {
        if(!isset($this->_order))
        {
            $order = Mage::getModel('sales/order');
            $this->_order = $order->loadByIncrementId($this->getMethod()->getCheckout()->getLastRealOrderId());
        }

        return $this->_order;
    }

    private function getCms()
    {
        return $this->getMethod()->getCmsInfo();
    }

    private function getConfigData($config)
    {
        $storeId = $this->getOrder()->getStoreId() ? $this->getOrder()->getStoreId() : null;

        return $this->getMethod()->getConfigData($config, $storeId);
    }

    private function getHashValue($paymentRequest)
    {
        return $this->getMethod()->generateMD5Key($paymentRequest, $this->getConfigData('md5key'));
    }

    private function getAcceptUrl()
    {
        return $this->getMethod()->getAcceptUrl();
    }

    private function getCancelUrl()
    {
        return $this->getMethod()->getCancelUrl();
    }

    private function getCallbackUrl()
    {
        return $this->getMethod()->getCallbackUrl();
    }

    private function getAmount($inMinorUnits)
    {
        return $inMinorUnits ? ((float)$this->getOrder()->getBaseTotalDue()) * 100 : (float)$this->getOrder()->getBaseTotalDue();
    }

    public function getFormattedAmount()
    {
        return number_format($this->getAmount(false), 2, ',', ' ');
    }

    public function getLanguage()
    {
        $localCode = Mage::app()->getLocale()->getLocaleCode();
        return $this->getMethod()->calcLanguage($localCode);
    }

    public function getCurrencyCode()
    {
        $baseCurrency =  $this->getOrder()->getBaseCurrency();
        return $baseCurrency->getCode();
    }

    public function getOrderId()
    {
        $checkout = $this->getMethod()->getCheckout();
        return $checkout->getLastRealOrderId();
    }



    public function getPaymentRequestString()
    {

        $paymentRequest = array(
                            'encoding' => "UTF-8",
                            'cms' => $this->getCms(),
                            'windowstate' => $this->getConfigData('windowstate'),
                            'merchantnumber' => $this->getConfigData('merchantnumber'),
                            'windowid' => $this->getConfigData('windowid'),
                            'amount' => $this->getAmount(true),
                            'currency' => $this->getCurrencyCode(),
                            'orderid' => $this->getOrderId(),
                            'accepturl' => $this->getAcceptUrl(),
                            'cancelurl' => $this->getCancelUrl(),
                            'callbackurl' => $this->getCallbackUrl(),
                            'mailreceipt' => $this->getConfigData('authmail'),
                            'smsreceipt' => $this->getConfigData('authsms'),
                            'instantcapture' => $this->getConfigData('instantcapture'),
                            'group' => $this->getConfigData('epaygroup'),
                            'language' => $this->getLanguage(),
                            'ownreceipt' => $this->getConfigData('ownreceipt'),
                            'timeout' => "60"
                            );

        if($this->getConfigData('enableinvoicedata') == '1')
        {
            $paymentRequest['invoice'] = $this->getMethod()->getOrderInJson($this->getOrder());
        }

        $paymentRequest['splitpayment'] = intval($this->getConfigData('splitpayment'));
        $paymentRequest['hash']  = $this->getHashValue($paymentRequest);

        $keyValueArray = array();

        foreach ($paymentRequest as $key => $value)
        {
            $keyValueArray[] = "'" . $key . "':'" . $value . "'";
        }

        $paymentRequestString = implode(",\n",$keyValueArray);

        return $paymentRequestString;
    }

    public function getLoadAction()
    {
        return '<input type="button" onclick="javascript: paymentwindow.open();" value="'. $this->__('EPAY_LABEL_35').'"></input>';
    }

    public function getCancelAction()
    {
        return '<input name="paymentCancel" id="paymentCancel" type="button" class="form-button-alt" onclick="javascript:location=\''. $this->getCancelUrl().'\'" value="'.$this->__('EPAY_LABEL_100').'"></input>';
    }
}