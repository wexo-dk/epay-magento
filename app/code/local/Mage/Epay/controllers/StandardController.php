<?php
/**
 * Copyright ePay | Dit Online Betalingssystem, (c) 2016.
 * This program is free software. You are allowed to use the software but NOT allowed to modify the software.
 * It is also not legal to do any changes to the software and distribute it in your own name / brand.
 */
class Mage_Epay_StandardController extends Mage_Core_Controller_Front_Action
{
    const EPAY_SURCHARGE = 'surcharge_fee';

    /**
     * Get singleton with epay strandard order transaction information
     *
     * @return Mage_Epay_Model_Standard
     */
    private function getMethod()
    {
        return Mage::getSingleton('epay/standard');
    }

    /**
     * Redirect Action
     */
    public function redirectAction()
    {
        $session = Mage::getSingleton('checkout/session');
		$session->setEpayStandardQuoteId($session->getQuoteId());

        $orderModel = Mage::getModel('sales/order');
		/** @var Mage_Sales_Model_Order */
        $order = $orderModel->loadByIncrementId($session->getLastRealOrderId());

        $payment = $order->getPayment();
        $pspReference = $payment->getAdditionalInformation(Mage_Epay_Model_Standard::PSP_REFERENCE);
	$lastSuccessQuoteId = $session->getLastSuccessQuoteId();

        if(!empty($pspReference) || empty($lastSuccessQuoteId))
        {
            $this->_redirect('checkout/onepage/success');
            return;
        }

        $write = Mage::getSingleton('core/resource')->getConnection('core_write');
        $write->insert('epay_order_status', array('orderid'=> $order->getIncrementId()));

        $paymentMethod = $this->getMethod();
        $isOverlay = intval($paymentMethod->getConfigData('windowstate', $order->getStoreId())) === 1 ? true : false;
        $paymentData = array("paymentRequest"=> $paymentMethod->getPaymentRequestAsString($order),
                             "cancelUrl"=> $paymentMethod->getCancelUrl(),
                             "headerText"=> Mage::helper('epay')->__("Thank you for using ePay | Payment solutions"),
                             "headerText2"=> Mage::helper('epay')->__("Please wait..."),
                             "isOverlay"=> $isOverlay);

        $this->loadLayout();
        $block = $this->getLayout()->createBlock('epay/standard_redirect', 'epayredirect', $paymentData);
        $this->getLayout()->getBlock('content')->append($block);
        $this->renderLayout();
    }

    /**
     * Cancel Action
     */
    public function cancelAction()
    {
        /** @var Mage_Checkout_Model_Session */
		$session = Mage::getSingleton('checkout/session');
    	$cart = Mage::getSingleton('checkout/cart');
        $larstOrderId = $session->getLastRealOrderId();
		$order = Mage::getModel('sales/order')->loadByIncrementId($larstOrderId);
		if ($order->getId())
		{
			$session->getQuote()->setIsActive(0)->save();
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
        $order = null;
        if($this->validateCallback($message, $order))
        {
            $message = $this->processCallback($responseCode);
        }
        else
        {
            if(isset($order))
            {
                $order->addStatusHistoryComment("Callback from ePay returned with an error: ". $message);
                $order->save();
            }
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
    private function validateCallback(&$message, &$order)
    {
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

        $method = $this->getMethod();
        $storeId = $order->getStoreId();
        $storeMd5Enc = $method->getConfigData('md5key', $storeId);
        $storeMd5 = Mage::helper('core')->decrypt($storeMd5Enc);
        if (!empty($storeMd5))
		{
			$accept_params = $_GET;
			$var = "";
			foreach ($accept_params as $key => $value)
			{
				if($key != "hash")
					$var .= $value;
			}

            $storeHash = md5($var . $storeMd5);
            if ($storeHash != $_GET["hash"])
			{
				$message = "Hash validation failed - Please check your MD5 key";
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
        $message = '';
        /** @var Mage_Sales_Model_Order */
        $order = Mage::getModel('sales/order')->loadByIncrementId($_GET["orderid"]);
        $payment = $order->getPayment();
        try
        {
            $pspReference = $payment->getAdditionalInformation(Mage_Epay_Model_Standard::PSP_REFERENCE);
            if(empty($pspReference) && !$order->isCanceled())
            {
                $method = $this->getMethod();
                $storeId = $order->getStoreId();
                $this->updatePaymentData($order, $method->getConfigData('order_status_after_payment', $storeId));

                $this->persistDataInEpayDBTable();

                if (intval($method->getConfigData('addfeetoshipping', $storeId)) == 1 && isset($_GET['txnfee']) && floatval($_GET['txnfee']) > 0)
                {
                    $this->addSurchargeItemToOrder($order);
                }

                if (intval($method->getConfigData('sendmailorderconfirmation', $storeId) == 1))
                {
                    $this->sendOrderEmail($order);
                }

                if(intval($method->getConfigData('instantinvoice', $storeId)) == 1)
                {
                    if(intval($method->getConfigData('remoteinterface', $storeId)) == 1 || intval($method->getConfigData('instantcapture', $storeId)) === 1)
                    {
                        $this->createInvoice($order);
                    }
                    else
                    {
                        $order->addStatusHistoryComment(Mage::helper('epay')->__("Could not use instant invoice."). ' - '. Mage::helper('epay')->__("Please enable remote payment processing from the module configuration"));
                        $order->save();
                    }
                }

                $message = "Callback Success - Order created";
            }
            else
            {
                if($order->isCanceled())
                {
                    $message = "Callback Success - Order was canceled by Magento";
                }
                else
                {
                    $message = "Callback Success - Order already created";
                }
            }
            $responseCode = '200';

        }
        catch(Exception $e)
        {
            $payment->setAdditionalInformation(Mage_Epay_Model_Standard::PSP_REFERENCE, "");
            $payment->save();
            $responseCode = '500';
            $message = "Callback Failed: " .$e->getMessage();
        }

        return $message;
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
        if(isset($_GET['paymentrequest']) && strlen($_GET['paymentrequest']) > 0)
        {
            //Mark as paid
            $paymentRequestUpdate = Mage::getModel('epay/paymentrequest')->load($_GET["paymentrequest"])->setData('ispaid', "1");
            $paymentRequestUpdate->setId($_GET["paymentrequest"])->save($paymentRequestUpdate);
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
        $methodInstance = $this->getMethod();
        $txnId = $_GET["txnid"];
        $payment = $order->getPayment();
        $payment->setTransactionId($txnId);
        $payment->setIsTransactionClosed(false);
        $payment->setAdditionalInformation(Mage_Epay_Model_Standard::PSP_REFERENCE, $txnId);
        $payment->addTransaction(Mage_Sales_Model_Order_Payment_Transaction::TYPE_AUTH);
        $payment->setCcNumberEnc($_GET['cardno']);
        $payment->setCcType($methodInstance->calcCardtype($_GET['paymenttype']));

        $isInstantCapture = intval($methodInstance->getConfigData('instantcapture', $order->getStoreId())) === 1 ? true : false;
        $payment->setAdditionalInformation('instantcapture', $isInstantCapture);

        $payment->save();

        $message = Mage::helper('epay')->__("Payment authorization was a success.") . ' ' . Mage::helper('epay')->__("Transaction ID").': '.$txnId;
        $order->setState(Mage_Sales_Model_Order::STATE_PROCESSING, $orderStatusAfterPayment, $message, false);
        $order->save();
    }

    /**
     * Add Surcharge item to the order as a order line
     *
     * @param Mage_Sales_Model_Order $order
     * @return void
     */
    private function addSurchargeItemToOrder($order)
    {
        $baseFeeAmount = ((int)$_GET['txnfee']) / 100;
        $feeAmount = Mage::helper('directory')->currencyConvert($baseFeeAmount, $order->getBaseCurrencyCode(), $order->getOrderCurrencyCode());

        foreach($order->getAllItems() as $item)
        {
            if($item->getSku() === $this::EPAY_SURCHARGE)
            {
                return;
            }
        }

        /** @var Mage_Sales_Model_Order_Item */
        $feeItem = Mage::getModel('sales/order_item');

        $feeItem->setSku($this::EPAY_SURCHARGE);
        $text = $this->getMethod()->calcCardtype($_GET['paymenttype']) . ' - ' . Mage::helper('epay')->__('Surcharge fee');
        $feeItem->setName($text);
        $feeItem->setBaseCost($baseFeeAmount);
        $feeItem->setBasePrice($baseFeeAmount);
        $feeItem->setBasePriceInclTax($baseFeeAmount);
        $feeItem->setBaseOriginalPrice($baseFeeAmount);
        $feeItem->setBaseRowTotal($baseFeeAmount);
        $feeItem->setBaseRowTotalInclTax($baseFeeAmount);

        $feeItem->setCost($feeAmount);
        $feeItem->setPrice($feeAmount);
        $feeItem->setPriceInclTax($feeAmount);
        $feeItem->setOriginalPrice($feeAmount);
        $feeItem->setRowTotal($feeAmount);
        $feeItem->setRowTotalInclTax($feeAmount);

        $feeItem->setProductType(Mage_Catalog_Model_Product_Type::TYPE_VIRTUAL);
        $feeItem->setIsVirtual(1);
        $feeItem->setQtyOrdered(1);
        $feeItem->setStoreId($order->getStoreId());
        $feeItem->setOrderId($order->getId());

        $order->addItem($feeItem);

        $order->setBaseGrandTotal($order->getBaseGrandTotal() + $baseFeeAmount);
        $order->setBaseSubtotal($order->getBaseSubtotal() + $baseFeeAmount);
        $order->setGrandTotal($order->getGrandTotal() + $feeAmount);
        $order->setSubtotal($order->getSubtotal() + $feeAmount);

        $feeMessage = $text . ' ' .__("added to order");
        $order->addStatusHistoryComment($feeMessage);
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
        //$order->setIsCustomerNotified(1);
        $order->addStatusHistoryComment(sprintf(Mage::helper('epay')->__("Notified customer about order #%s"), $order->getIncrementId()))
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
            $invoice->setRequestedCaptureCase(Mage_Sales_Model_Order_Invoice::CAPTURE_ONLINE);
            $invoice->register();
            $invoice->save();

            $transactionSave = Mage::getModel('core/resource_transaction')
              ->addObject($invoice)
              ->addObject($invoice->getOrder());
            $transactionSave->save();

            if(intval($this->getMethod()->getConfigData('instantinvoicemail', $order->getStoreId())) == 1)
            {
                $invoice->sendEmail();
                $order->addStatusHistoryComment(sprintf(Mage::helper('epay')->__("Notified customer about invoice #%s"), $invoice->getId()))
                    ->setIsCustomerNotified(true);
                $order->save();
            }
        }
    }
}
