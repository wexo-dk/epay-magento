<?php
/**
 * Copyright ePay | Dit Online Betalingssystem, (c) 2010.
 * This program is free software. You are allowed to use the software but NOT allowed to modify the software. 
 * It is also not legal to do any changes to the software and distribute it in your own name / brand. 
 */
 
class Mage_Epay_Adminhtml_PaymentrequestController extends Mage_Adminhtml_Controller_Action
{
	public function indexAction()
    {
        $this->loadLayout();
        $this->renderLayout();
    }
	
	public function createAction()
	{	
		//Validate orderid
		$order_id = $this->getRequest()->getParam('id');
		if(!Mage::helper('epay')->isValidOrder($order_id))
		{
			$this->_getSession()->addError("Invalid order");
			session_write_close();
			$this->_redirectReferer();
		}
		
		$this->loadLayout();
		$this->_addContent($this->getLayout()->createBlock('epay/adminhtml_form_paymentrequest'))->_addLeft($this->getLayout()->createBlock('epay/adminhtml_form_paymentrequest_tabs'));
		$this->renderLayout();
	}
	
	public function viewAction()
	{		
        $this->loadLayout();
        $this->renderLayout();	
	}
	
	public function saveAction()
	{	
		try
		{			
			if($data = $this->getRequest()->getPost())
			{
				$helper = Mage::helper('epay');
			
				if (!Zend_Validate::is($data['recipient_name'], 'NotEmpty')) {
			        $errors[] = $helper->__('Recipient name can\'t be empty');
			    }
			
				if (!Zend_Validate::is($data['recipient_email'], 'NotEmpty')) {
			        $errors[] = $helper->__('Recipient e-mail can\'t be empty');
			    }
				
				if (!Zend_Validate::is($data['replyto_name'], 'NotEmpty')) {
			        $errors[] = $helper->__('Reply to name can\'t be empty');
			    }
			
				if (!Zend_Validate::is($data['replyto_email'], 'NotEmpty')) {
			        $errors[] = $helper->__('Reply to e-mail can\'t be empty');
			    }	
				
				if (!Zend_Validate::is($data['orderid'], 'NotEmpty')) {
			        $errors[] = $helper->__('Amount can\'t be empty');
			    }	
				
				if (!Zend_Validate::is($data['amount'], 'NotEmpty')) {
			        $errors[] = $helper->__('Amount can\'t be empty');
			    }	
				
				if (!Zend_Validate::is($data['currency'], 'NotEmpty')) {
			        $errors[] = $helper->__('Currency can\'t be empty');
			    }	
				
			    if (!empty($errors)) {
			        throw new Exception (implode('<br>', $errors));
			    }
				
				$standard = Mage::getModel('epay/standard');
				
				//Validate order id
				$order_id = $this->getRequest()->getParam('id');
				
				$order = Mage::getModel('sales/order')->loadByIncrementId($order_id);
				if($order->hasData())
				{					
					$paymentRequest = Mage::getModel('epay/paymentrequest');
					$paymentRequest->setData('orderid', $data['orderid']);
					$paymentRequest->setData('currency', $data['currency']);
					$paymentRequest->setData('amount', $data['amount'] * 100);
					$paymentRequest->setData('receiver', $data['recipient_email']);
					$paymentRequest->setData('created', date('Y-m-d H:i:s', strtotime(Mage::getSingleton('core/date')->gmtDate())));
					$paymentRequest->save();
					
					$paymentRequestId = $paymentRequest->id;
					
					$params = array();
					
					$params["authentication"] = array();
					$params["authentication"]["merchantnumber"] = $standard->getConfigData('merchantnumber', $order ? $order->getStoreId() : null);
					$params["authentication"]["password"] = $standard->getConfigData('remoteinterfacepassword', $order ? $order->getStoreId() : null);
					
					$params["paymentrequest"] = array();
					$params["paymentrequest"]["reference"] = $data['orderid'];
					$params["paymentrequest"]["closeafterxpayments"] = 1;
					
					$params["paymentrequest"]["parameters"] = array();
					$params["paymentrequest"]["parameters"]["amount"] = $data['amount'] * 100;
					$params["paymentrequest"]["parameters"]["callbackurl"] = Mage::getUrl('epay/standard/callback', array('_nosid' => true, '_query' => array('paymentrequest' => $paymentRequest->id)));
					$params["paymentrequest"]["parameters"]["currency"] = $data['currency'];
					$params["paymentrequest"]["parameters"]["group"] = $standard->getConfigData('group', $order ? $order->getStoreId() : null);
					$params["paymentrequest"]["parameters"]["instantcapture"] = ($standard->getConfigData('instantcapture', $order ? $order->getStoreId() : null) == "1" ? "automatic" : "manual");
					$params["paymentrequest"]["parameters"]["orderid"] = $data['orderid'];
					$params["paymentrequest"]["parameters"]["windowid"] = $standard->getConfigData('windowid', $order ? $order->getStoreId() : null);
					
					if($standard->getConfigData('enableinvoicedata', $order ? $order->getStoreId() : null))
					{
						$params["paymentrequest"]["parameters"]["invoice"] = $standard->getOrderInJson($order);
					}

					$soapClient = new SoapClient("https://paymentrequest.api.epay.eu/v1/PaymentRequestSOAP.svc?wsdl");
					$createPaymentRequest = $soapClient->createpaymentrequest(array('createpaymentrequestrequest' => $params));
					
					if($createPaymentRequest->createpaymentrequestResult->result)
					{
						$sendParams = array();
						
						$sendParams["authentication"] = $params["authentication"];
						
						$sendParams["email"] = array();
						$sendParams["email"]["comment"] = $data['comment'];
						$sendParams["email"]["requester"] = $data['requester'];
						
						$sendParams["email"]["recipient"] = array();
						$sendParams["email"]["recipient"]["emailaddress"] = $data['recipient_email'];
						$sendParams["email"]["recipient"]["name"] = $data['recipient_name'];
						
						$sendParams["email"]["replyto"] = array();
						$sendParams["email"]["replyto"]["emailaddress"] = $data['replyto_email'];
						$sendParams["email"]["replyto"]["name"] = $data['replyto_name'];
						
						$sendParams["paymentrequest"] = array();
						$sendParams["paymentrequest"]["paymentrequestid"] = $createPaymentRequest->createpaymentrequestResult->paymentrequest->paymentrequestid;
						
						$sendPaymentRequest = $soapClient->sendpaymentrequest(array('sendpaymentrequestrequest' => $sendParams));
						
						if($sendPaymentRequest->sendpaymentrequestResult->result)
						{	
							$paymentRequestUpdate = Mage::getModel('epay/paymentrequest')->load($paymentRequestId)->setData('status', "1")->setData("paymentrequestid", $createPaymentRequest->createpaymentrequestResult->paymentrequest->paymentrequestid);
							$paymentRequestUpdate->setId($paymentRequestId)->save($paymentRequestUpdate);
							
							$this->_getSession()->addSuccess("Payment request sent");
							session_write_close();
							Mage::app()->getResponse()->setRedirect(Mage::helper('adminhtml')->getUrl("adminhtml/sales_order/view", array('order_id' => $order->getId())));
						}
						else
						{
							throw new Exception ($sendPaymentRequest->sendpaymentrequestResult->message);
						}
					}
					else
					{
						throw new Exception ($createPaymentRequest->createpaymentrequestResult->message);	
					}
				}
				else
				{
					throw new Exception ("Invalid order");
				}
			}
		}
		catch (Exception $e)
		{
			$this->_getSession()->addException($e, $e->getMessage());
		}
		
		$this->createAction();
	}
}