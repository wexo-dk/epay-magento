<?php
/**
 * Copyright ePay | Dit Online Betalingssystem, (c) 2010.
 * This program is free software. You are allowed to use the software but NOT allowed to modify the software.
 * It is also not legal to do any changes to the software and distribute it in your own name / brand.
 */

class Mage_Epay_StandardController extends Mage_Core_Controller_Front_Action
{
    const PSP_REFERENCE = 'epayReference';

    /**
     * Check if the session is expired
     */
    protected function _expireAjax()
    {
        if (!Mage::getSingleton('checkout/session')->getQuote()->hasItems()) {
            $this->getResponse()->setHeader('HTTP/1.1', '403 Session Expired');
            exit;
        }
    }

    /**
     * Get singleton with epay strandard order transaction information
     *
     * @return Mage_Epay_Model_Standard
     */
    public function getMethod()
    {
        return Mage::getSingleton('epay/standard');
    }

    /**
     * Redirect Action
     */
    public function redirectAction()
    {
		$this->loadLayout();
		$this->getLayout()->getBlock('content')->append($this->getLayout()->createBlock('epay/standard_redirect'));
		$this->renderLayout();

        $session = Mage::getSingleton('checkout/session');
		$session->setEpayStandardQuoteId($session->getQuoteId());

		$this->_orderObj = Mage::getModel('sales/order');
		$this->_orderObj->loadByIncrementId($session->getLastRealOrderId());
		$this->_orderObj->addStatusToHistory($this->_orderObj->getStatus(), $this->__('The order is now placed and payment must now be made by ePay online payment system (www.epay.eu)'));
		$this->_orderObj->save();
    }

	/**
	 * Checkout Action
	 */
	public function checkoutAction()
    {
		$quote = Mage::getModel('checkout/cart')->getQuote();
        $quote->reserveOrderId();

		$this->loadLayout();
		$this->getLayout()->getBlock('content')->append($this->getLayout()->createBlock('epay/standard_checkout'));
		$this->renderLayout();
    }

    /**
     * Cancel Action
     */
    public function cancelAction()
    {
		$session = Mage::getSingleton('checkout/session');
    	$cart = Mage::getSingleton('checkout/cart');
        $larstOrderId = Mage::getModel("sales/order")->getCollection()->getLastItem()->getIncrementId();
		$order = Mage::getModel('sales/order')->loadByIncrementId($larstOrderId);
		if ($order->getId())
		{
			$session->getQuote()->setIsActive(false)->save();
	        $session->clear();
    	    try
			{
				$order->setActionFlag(Mage_Sales_Model_Order::ACTION_FLAG_CANCEL, true);
            	$order->cancel()->save();
        	}
			catch (Mage_Core_Exception $e)
			{
            	Mage::logException($e);
        	}
			$items = $order->getItemsCollection();
        	foreach ($items as $item)
			{
				try
				{
					$cart->addOrderItem($item);
            	}
				catch (Mage_Core_Exception $e)
				{
					$session->addError($this->__($e->getMessage()));
                	Mage::logException($e);
                	continue;
            	}
        	}
        	$cart->save();
		}
		$this->_redirect('checkout/cart');
    }

    /**
     * Success Action
     */
    public function successAction()
    {
        $session = Mage::getSingleton('checkout/session');
        $session->setQuoteId($session->getEpayStandardQuoteId(true));
        Mage::getSingleton('checkout/session')->getQuote()->setIsActive(false)->save();

        $this->_redirect('checkout/onepage/success');
    }

    /**
     * Callback action
     *
     * @return Zend_Controller_Response_Abstract
     */
    public function callbackAction()
    {
        $message ='';
        $responseCode = '400';
        if($this->validateCallback($message))
        {
            $message = $this->processCallback($responseCode);
        }

        $this->getResponse()->setHeader('HTTP/1.0', $responseCode, true)
            ->setHeader('Content-type', 'application/json', true)
            ->setHeader('X-EPay-System', $this->getMethod()->getCmsInfo())
            ->setBody($message);

        return $this->_response;
    }

    /**
     * Validate the callback
     *
     * @param string &$message
     * @return boolean
     */
    private function validateCallback(&$message)
    {
        $method = $this->getMethod();

        if(!isset($_GET["txnid"]))
        {
            $message = "No GET(txnid) was supplied to the system!";
		    return false;
        }

        if (!isset($_GET["orderid"]))
	    {
            $message = "No GET(orderid) was supplied to the system!";
		    return false;
		}

        if (!isset($_GET["amount"])) {
            $message = "No GET(amount) supplied to the system!";
            return false;
        }

        if (!isset($_GET["currency"])) {
            $message = "No GET(currency) supplied to the system!";
            return false;
        }
        $order = Mage::getModel('sales/order')->loadByIncrementId($_GET["orderid"]);
        if(!isset($order) || !$order->getId())
        {
			$message = "The order object could not be loaded";
			return false;
        }
        if($order->getIncrementId() != $_GET["orderid"])
        {
            $message = "The loaded order id does not match the callback GET(orderId)";
			return false;
        }
        $storeId = $order->getStoreId();
        if ((strlen($method->getConfigData('md5key', $storeId))) > 0)
		{
			$accept_params = $_GET;
			$var = "";
			foreach ($accept_params as $key => $value)
			{
				if($key != "hash")
					$var .= $value;
			}

            $storeMd5 = $method->getConfigData('md5key', $storeId);
            $storeHash = md5($var . $storeMd5);
            if ($storeHash != $_GET["hash"])
			{
				$message = "Hash validation falied";
				return false;
            }
        }

        return true;
    }

    /**
     * Process the callback
     *
     * @param string &$responseCode
     */
    private function processCallback(&$responseCode)
    {
        $method = $this->getMethod();
        $order = Mage::getModel('sales/order')->loadByIncrementId($_GET["orderid"]);
        $storeId = $order->getStoreId();
        try
        {
            $message = '';
            $payment = $order->getPayment();
            $pspReference = $payment->getAdditionalInformation($this::PSP_REFERENCE);
            if(empty($pspReference))
            {
                $this->updatePaymentData($order, $method->getConfigData('order_status_after_payment', $storeId));

                $this->persistDataInEpayDBTable();

                if (intval($method->getConfigData('addfeetoshipping', $storeId)) == 1 && isset($_GET['txnfee']) && strlen($_GET['txnfee']) > 0)
                {
                    $this->addSurchargeToOrderShipment($order);
                }

                if (intval($method->getConfigData('sendmailorderconfirmation', $storeId) == 1))
                {
                    $this->sendOrderEmail($order);
                }

                if(intval($method->getConfigData('instantinvoice')) == 1)
                {
                    $this->createInvoice($order);
                }
                $message = "Callback Success - Order created";
            }
            else
            {
                $message = "Callback Success - Order already created";
            }
            $responseCode = '200';
            return $message;
        }
        catch(Exception $e)
        {
            $responseCode = '500';
            return "Callback Failed: " .$e->getMessage();
        }
    }

    /**
     * Persist the callback data into the epay_prder_status table
     */
    private function persistDataInEpayDBTable()
    {
        $read = Mage::getSingleton('core/resource')->getConnection('core_read');
		$row = $read->fetchRow("select * from epay_order_status where orderid = '" . $_GET['orderid'] . "'");

		if(!$row && isset($_GET['paymentrequest']) && strlen($_GET['paymentrequest']) > 0)
		{
			$write = Mage::getSingleton('core/resource')->getConnection('core_write');
			$write->insert('epay_order_status', array('orderid'=>$_GET['orderid']));

			$read = Mage::getSingleton('core/resource')->getConnection('core_read');
			$row = $read->fetchRow("select * from epay_order_status where orderid = '" . $_GET['orderid'] . "'");
		}

		if ($row['status'] == '0')
		{
    		$write = Mage::getSingleton('core/resource')->getConnection('core_write');
			$write->query('update epay_order_status set tid = "' . ((isset($_GET['txnid'])) ? $_GET['txnid'] : '0') . '", status = 1, ' .
									'amount = "' . ((isset($_GET['amount'])) ? $_GET['amount'] : '0') . '", '.
									'cur = "' . ((isset($_GET['currency'])) ? $_GET['currency'] : '0') . '", '.
									'date = "' . ((isset($_GET['date'])) ? $_GET['date'] : '0') . '", '.
									'eKey = "' . ((isset($_GET['hash'])) ? $_GET['hash'] : '0') . '", '.
									'fraud = "' . ((isset($_GET['fraud'])) ? $_GET['fraud'] : '0') . '", '.
									'subscriptionid = "' . ((isset($_GET['subscriptionid'])) ? $_GET['subscriptionid'] : '0') . '", '.
									'cardid = "' . ((isset($_GET['paymenttype'])) ? $_GET['paymenttype'] : '0') . '", '.
									'cardnopostfix = "' . ((isset($_GET['cardno'])) ? $_GET['cardno'] : '') . '", '.
									'transfee = "' . ((isset($_GET['txnfee'])) ? $_GET['txnfee'] : '0') . '" where orderid = "' . $_GET['orderid'] . '"');

        }
    }

    /**
     * Update the payment data
     *
     * @param Mage_Sales_Model_Order $order
     * @param string $orderStatusAfterPayment
     */
    private function updatePaymentData($order, $orderStatusAfterPayment)
    {

        $payment = $order->getPayment();
        $txnId = $_GET["txnid"];
        $payment->setTransactionId($txnId);
        $payment->setIsTransactionClosed(false);
        $payment->setAdditionalInformation($this::PSP_REFERENCE, $txnId);
        $payment->addTransaction(Mage_Sales_Model_Order_Payment_Transaction::TYPE_AUTH);
        $payment->save();

        $message = Mage::helper('epay')->__("Payment authorization was a success.") . ' ' . sprintf(Mage::helper('sales')->__('Transaction ID: "%s".'), $txnId);
        $order->setState(Mage_Sales_Model_Order::STATE_PROCESSING, $orderStatusAfterPayment, $message, false);
        $order->save();
    }

    /**
     * Add surcharge fee to the shipment amount
     *
     * @param Mage_Sales_Model_Order $order
     */
    private function addSurchargeToOrderShipment($order)
    {
        $order->setBaseShippingAmount($order->getBaseShippingAmount() + (((int)$_GET['txnfee']) / 100));
        $order->setBaseGrandTotal($order->getBaseGrandTotal() + (((int)$_GET['txnfee']) / 100));

        $storefee = Mage::helper('directory')->currencyConvert((intval($_GET['txnfee']) / 100), $order->getBaseCurrencyCode(), $order->getOrderCurrencyCode());

        $order->setShippingAmount($order->getShippingAmount() + $storefee);
        $order->setGrandTotal($order->getGrandTotal() + $storefee);

        $order->save();
    }

    /**
     * Send an order confirmation to the customer
     *
     * @param Mage_Sales_Model_Order $order
     */
    private function sendOrderEmail($order)
    {
        $order->sendNewOrderEmail();
        $order->setIsCustomerNotified(1);
        $order->addStatusHistoryComment(sprintf(Mage::helper('epay')->__('Notified customer about order #%s'), $order->getIncrementId()))
            ->setIsCustomerNotified(true);
        $order->save();
    }

    /**
     * Create an invoice
     *
     * @param Mage_Sales_Model_Order $order
     */
    private function createInvoice($order)
    {
        if($order->canInvoice())
        {
            $invoice = $order->prepareInvoice();

            if(intval($this->getMethod()->getConfigData('instantcapture', $order->getStoreId())) == 1)
            {
                $invoice->setRequestedCaptureCase(Mage_Sales_Model_Order_Invoice::CAPTURE_OFFLINE);
            }
            else
            {
                $invoice->setRequestedCaptureCase(Mage_Sales_Model_Order_Invoice::CAPTURE_ONLINE);
            }

            $invoice->register();
            $invoice->save();

            $transactionSave = Mage::getModel('core/resource_transaction')
              ->addObject($invoice)
              ->addObject($invoice->getOrder());
            $transactionSave->save();

            if(intval($this->getMethod()->getConfigData('instantinvoicemail', $order->getStoreId())) == 1)
            {
                $invoice->sendEmail();
                $order->addStatusHistoryComment(sprintf(Mage::helper('epay')->__('Notified customer about invoice #%s', $invoice->getId())))
                    ->setIsCustomerNotified(true);
                $order->save();
            }
        }
    }
}