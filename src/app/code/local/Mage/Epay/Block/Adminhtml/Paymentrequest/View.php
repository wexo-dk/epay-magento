<?php
class Mage_Epay_Block_Adminhtml_Paymentrequest_View extends Mage_Adminhtml_Block_Widget_View_Container
{
    public function __construct() {

		parent::__construct();

		$this->_removeButton('edit');

		$standard = Mage::getModel('epay/standard');

		$this->_headerText = Mage::helper('epay')->__("Payment request");

		$paymentrequest_id = $this->getRequest()->getParam('id');
		$paymentRequest = Mage::getModel('epay/paymentrequest')->load($paymentrequest_id)->getData();


        $orderId = $paymentRequest['orderid'];
        $order = Mage::getModel('sales/order')->loadByIncrementId($orderId);
        $storeId = $order->getStoreId();


		$soapClient = new SoapClient("https://paymentrequest.api.epay.eu/v1/PaymentRequestSOAP.svc?wsdl");

		$params = array();

		$params["authentication"] = array();
		$params["authentication"]["merchantnumber"] = $standard->getConfigData('merchantnumber', $storeId);
        $params["authentication"]["password"] = $standard->getRemotePassword($storeId);

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