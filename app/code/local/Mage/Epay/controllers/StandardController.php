<?php
/**
 * Copyright ePay | Dit Online Betalingssystem, (c) 2010.
 * This program is free software. You are allowed to use the software but NOT allowed to modify the software.
 * It is also not legal to do any changes to the software and distribute it in your own name / brand.
 */

class Mage_Epay_StandardController extends Mage_Core_Controller_Front_Action
{
    //
    // Flag only used for callback
    protected $_callbackAction = false;
    protected $_orderObj = null;

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
    public function getStandard()
    {
        return Mage::getSingleton('epay/standard');
    }

    /**
     * When a customer chooses Epay on Checkout/Payment page
     *
     */
    public function redirectAction()
    {
		//
		// Load layout
		//
		$this->loadLayout();
		$this->getLayout()->getBlock('content')->append($this->getLayout()->createBlock('epay/standard_redirect'));
		$this->renderLayout();

		//
		// Load the session object
		//
		$session = Mage::getSingleton('checkout/session');
		$session->setEpayStandardQuoteId($session->getQuoteId());

		//
		// Save order comment
		//
		$this->_orderObj = Mage::getModel('sales/order');
		$this->_orderObj->loadByIncrementId($session->getLastRealOrderId());
		$this->_orderObj->addStatusToHistory($this->_orderObj->getStatus(), $this->__('EPAY_LABEL_31'));
		$this->_orderObj->save();
    }

	public function checkoutAction()
    {
		//
		// Load layout
		//
		$quote = Mage::getModel('checkout/cart')->getQuote();

		$quote->reserveOrderId();

		$this->loadLayout();
		$this->getLayout()->getBlock('content')->append($this->getLayout()->createBlock('epay/standard_checkout'));
		$this->renderLayout();
    }

    /**
     * When a customer cancel payment from epay.
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

	public function getOrderUpdatedWithEpayData($orderid)
	{
		// Read info directly from the database
		$read = Mage::getSingleton('core/resource')->getConnection('core_read');
		$row = $read->fetchRow("select * from epay_order_status where orderid = '" . $orderid . "'");

		return ($row['status'] == '1');
	}

    protected function _fillPaymentByResponse(Varien_Object $payment)
    {
        $payment->setTransactionId($_GET["txnid"])
            ->setParentTransactionId(null)
            ->setIsTransactionClosed(0)
            ->setTransactionAdditionalInfo("Transaction ID", $_GET["txnid"]);
    }

    protected function _authOrder(Mage_Sales_Model_Order $order)
    {
        $payment = $order->getPayment();
        $this->_fillPaymentByResponse($payment);

        $payment->addTransaction(Mage_Sales_Model_Order_Payment_Transaction::TYPE_AUTH);

        $order->save();
    }

    /**
     * when epay returns
     * The order information at this point is in POST
     * variables.  However, you don't want to "process" the order until you
     * get validation from the IPN.
     */
    public function successAction()
    {
        $session = Mage::getSingleton('checkout/session');
        $session->setQuoteId($session->getEpayStandardQuoteId(true));
        Mage::getSingleton('checkout/session')->getQuote()->setIsActive(false)->save();

        $this->_orderObj = Mage::getModel('sales/order');
        $payment = Mage::getModel('epay/standard');

        //
        // Load the order number
        if (Mage::getSingleton('checkout/session')->getLastOrderId())
		{
			$this->_orderObj->load(Mage::getSingleton('checkout/session')->getLastOrderId());
		}
		else
		{
			if (isset($_GET["orderid"]))
			{
				$this->_orderObj->loadByIncrementId($_GET["orderid"]);
			}
			else
			{
				echo "<h1>An error occured!</h1>";
				echo "No orderid was supplied to the system!";
				exit();
			}
        }

        //
        // Validate the order and send email confirmation if enabled
        if(!$this->_orderObj->getId()) {
			echo "<h1>An error occured!</h1>";
			echo "The order id was not known to the system";
			exit();
        }

        if (!isset($_GET["amount"])) {
            echo "<h1>An error occured!</h1>";
            echo "No amount supplied to the system!";
            exit();
        }

        if (!isset($_GET["currency"])) {
            echo "<h1>An error occured!</h1>";
            echo "No currency supplied to the system!";
            exit();
        }

        //
        // validate md5 if enabled
        if ((strlen($payment->getConfigData('md5key', $payment->getOrder() ? $payment->getOrder()->getStoreId() : null))) > 0)
		{
			$accept_params = $_GET;
			$var = "";
			foreach ($accept_params as $key => $value)
			{
				if($key != "hash")
					$var .= $value;
			}

            if (md5($var . $payment->getConfigData('md5key', $payment->getOrder() ? $payment->getOrder()->getStoreId() : null)) != $_GET["hash"])
			{
				echo "<h1>An error occured!</h1>";
				echo "The MD5 key does not match!<br />Please be sure that the correct MD5 key has been set in the ePay administration and the payment method settings.";
				exit();
            }
        }

		$this->_authOrder($this->_orderObj);

		$read = Mage::getSingleton('core/resource')->getConnection('core_read');
		$row = $read->fetchRow("select * from epay_order_status where orderid = '" . $_GET['orderid'] . "'");

		//
		// Create if no rows found and payment request
		//
		if(!$row && isset($_GET['paymentrequest']) && strlen($_GET['paymentrequest']) > 0)
		{
			//
			// Save the order into the epay_order_status table
			//
			$write = Mage::getSingleton('core/resource')->getConnection('core_write');
			$write->insert('epay_order_status', array('orderid'=>$_GET['orderid']));

			$read = Mage::getSingleton('core/resource')->getConnection('core_read');
			$row = $read->fetchRow("select * from epay_order_status where orderid = '" . $_GET['orderid'] . "'");
		}

		if ($row['status'] == '0')
		{
	    	//
	        // Save the order into the epay_order_status table
	        //
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

			$this->_orderObj->addStatusToHistory($payment->getConfigData('order_status_after_payment', $payment->getOrder() ? $payment->getOrder()->getStoreId() : null));

			//$this->_orderObj->setState('processing', $payment->getConfigData('order_status_after_payment', $payment->getOrder() ? $payment->getOrder()->getStoreId() : null), "", false);

			$this->_orderObj->save();

			//
			// Add the transaction fee to the shipping and handling amount
			//
			if (isset($_GET['txnfee']) && strlen($_GET['txnfee']) > 0)
			{
				if (((int)$payment->getConfigData('addfeetoshipping', $payment->getOrder() ? $payment->getOrder()->getStoreId() : null)) == 1)
				{
                    $this->_orderObj->setBaseShippingAmount($this->_orderObj->getBaseShippingAmount() + (((int)$_GET['txnfee']) / 100));
                    $this->_orderObj->setBaseGrandTotal($this->_orderObj->getBaseGrandTotal() + (((int)$_GET['txnfee']) / 100));

					$storefee = Mage::helper('directory')->currencyConvert((((int)$_GET['txnfee']) / 100), $this->_orderObj->getBaseCurrencyCode(), $this->_orderObj->getOrderCurrencyCode());

					$this->_orderObj->setShippingAmount($this->_orderObj->getShippingAmount() + $storefee);
					$this->_orderObj->setGrandTotal($this->_orderObj->getGrandTotal() + $storefee);

					$this->_orderObj->save();
				}
			}

			//
			// See if a payment request
			//
			if(isset($_GET['paymentrequest']) && strlen($_GET['paymentrequest']) > 0)
			{
				//Mark as paid
				$paymentRequestUpdate = Mage::getModel('epay/paymentrequest')->load($_GET["paymentrequest"])->setData('ispaid', "1");
				$paymentRequestUpdate->setId($_GET["paymentrequest"])->save($paymentRequestUpdate);
			}

			//
			// Send email order confirmation (if enabled). May be done only once!
			//
			if (((int)$payment->getConfigData('sendmailorderconfirmation', $payment->getOrder() ? $payment->getOrder()->getStoreId() : null)) == 1)
			{
				//$this->_orderObj->setEmailSent(true);
			    $this->_orderObj->sendNewOrderEmail();
			    $this->_orderObj->save();
			}

			//
			// Create an invoice if the the setting instantinvoice is set to Yes
			//
			if((int)$payment->getConfigData('instantinvoice') == 1)
			{
				if($this->_orderObj->canInvoice())
				{
					$invoice = $this->_orderObj->prepareInvoice();

					//Already captured by instantcapture
					$invoice->setRequestedCaptureCase(Mage_Sales_Model_Order_Invoice::CAPTURE_OFFLINE);
					$invoice->register();
					Mage::getModel('core/resource_transaction')->addObject($invoice)->addObject($invoice->getOrder())->save();

					if((int)$payment->getConfigData('instantinvoicemail') == 1)
					{
						$invoice->setEmailSent(true);
						$invoice->save();
						$invoice->sendEmail();
					}
				}
			}
    	}

    	//
        // If not callback - redirect the user to the success page
		//
        if (!$this->_callbackAction)
		{
            $this->_redirect('checkout/onepage/success');
        }
		else
		{
			//
			// Callback from ePay - just respond ok
			//
			echo "OK";
			exit();
        }
    }

    //
    // When callback is called from epay
    // just reflect to the success action
    //
    public function callbackAction()
    {
		$this->_callbackAction = true;
		$this->successAction();
    }
}