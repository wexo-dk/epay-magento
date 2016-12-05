<?php
/**
 * Copyright ePay | Dit Online Betalingssystem, (c) 2016.
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

        $write = Mage::getSingleton('core/resource')->getConnection('core_write');
        $write->insert('epay_order_status', array('orderid'=>$this->getMethod()->getCheckout()->getLastRealOrderId()));

        if(intval($this->getConfigData('windowstate')) === 3)
        {
            $url = $this->getPaymentWindowUrl();
            Mage::app()->getFrontController()->getResponse()->setRedirect($url);
            Mage::app()->getResponse()->sendResponse();
            return;
        }

        $this->setTemplate('epay/standard/redirect_standardwindow.phtml');

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

    public function getPaymentWindowUrl()
    {
        $baseUrl = 'https://ssl.ditonlinebetalingssystem.dk/integration/ewindow/Default.aspx';
        $paymentRequest = $this->getPaymentRequestString(true);
        $requestParams = "";
        $count = 0;
        foreach ($paymentRequest as $key => $value)
        {
            if($count === 0)
            {
                $requestParams .= '?' . $key . '=' .urlencode($value);
            }
            else
            {
                $requestParams .=  '&' . $key . '=' .urlencode($value);
            }
            $count++;
        }
        $url = $baseUrl . $requestParams;

        return $url;
    }

    public function getPaymentRequestString($paymentRequestOnly = false)
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

        if($paymentRequestOnly === true)
        {
            return $paymentRequest;
        }

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
        return '<input type="button" onclick="javascript: paymentwindow.open();" value="'. $this->__("Open the payment window").'"></input>';
    }

    public function getCancelAction()
    {
        return '<input name="paymentCancel" id="paymentCancel" type="button" class="form-button-alt" onclick="javascript:location=\''. $this->getCancelUrl().'\'" value="'.$this->__("Cancel payment").'"></input>';
    }
}