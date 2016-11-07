<?php
class Mage_Epay_Block_Adminhtml_Paymentrequest_View extends Mage_Adminhtml_Block_Widget_View_Container
{
    public function __construct() {

		parent::__construct();

		$this->_removeButton('edit');

		$standard = Mage::getModel('epay/standard');

		$this->_headerText = Mage::helper('epay')->__('Payment request');

		$paymentrequest_id = $this->getRequest()->getParam('id');
		$paymentRequest = Mage::getModel('epay/paymentrequest')->load($paymentrequest_id)->getData();


        $order_id = $paymentRequest['orderid'];
        $order = Mage::getModel('sales/order')->loadByIncrementId($order_id);
        $store_id = $order->getStoreId();


		$soapClient = new SoapClient("https://paymentrequest.api.epay.eu/v1/PaymentRequestSOAP.svc?wsdl");

		$params = array();

		$params["authentication"] = array();
		$params["authentication"]["merchantnumber"] = $standard->getConfigData('merchantnumber', $store_id);
		$params["authentication"]["password"] = $standard->getConfigData('remoteinterfacepassword', $store_id);

		$params["paymentrequest"] = array();
		$params["paymentrequest"]["paymentrequestid"] = $paymentRequest["paymentrequestid"];

		$getPaymentRequest = $soapClient->getpaymentrequest(array('getpaymentrequestrequest' => $params));

		if($getPaymentRequest->getpaymentrequestResult->result)
		{
			$this->setPaymentrequestId($paymentRequest["paymentrequestid"]);
			$this->setPaymentrequest($getPaymentRequest->getpaymentrequestResult);
		}

		$this->setTemplate('epay/paymentrequest/view.phtml');
    }
}