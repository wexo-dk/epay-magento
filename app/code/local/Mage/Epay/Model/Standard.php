<?php
/**
 * Copyright ePay | Dit Online Betalingssystem, (c) 2010.
 *
 * This program is free software. You are allowed to use the software but NOT allowed to modify the software.
 * It is also not legal to do any changes to the software and distribute it in your own name / brand.
 */

class Mage_Epay_Model_Standard extends Mage_Payment_Model_Method_Abstract
{
    const PAYMENT_TYPE_AUTH = 'AUTHORIZATION';
    const PAYMENT_TYPE_SALE = 'SALE';

    protected $_code  = 'epay_standard';
    protected $_formBlockType = 'epay/standard_form';
	protected $_infoBlockType = 'epay/standard_email';

	protected $_isGateway 				= true;
	protected $_canAuthorize 			= false; // NO! Authorization is not done by webservices! (PCI)
	protected $_canCapture 				= true;
	protected $_canCapturePartial 		= true;
	protected $_canRefund 				= true;
	protected $_canOrder 				= true;
	protected $_canRefundInvoicePartial = true;
	protected $_canVoid 				= true;
	protected $_canUseInternal 			= true;	// If an internal order is created (phone / mail order) payment must be done using webpay and not an internal checkout method!
	protected $_canUseCheckout 			= true;
	protected $_canUseForMultishipping 	= true;
	protected $_canSaveCc 				= false; // NO CC is never saved. (PCI)

    //
    // Allowed currency types
    //
    protected $_allowCurrencyCode = array(
		'ADP','AED','AFA','ALL','AMD','ANG','AOA','ARS','AUD','AWG','AZM','BAM','BBD','BDT','BGL','BGN','BHD','BIF','BMD','BND','BOB',
		'BOV','BRL','BSD','BTN','BWP','BYR','BZD','CAD','CDF','CHF','CLF','CLP','CNY','COP','CRC','CUP','CVE','CYP','CZK','DJF','DKK',
		'DOP','DZD','ECS','ECV','EEK','EGP','ERN','ETB','EUR','FJD','FKP','GBP','GEL','GHC','GIP','GMD','GNF','GTQ','GWP','GYD','HKD',
		'HNL','HRK','HTG','HUF','IDR','ILS','INR','IQD','IRR','ISK','JMD','JOD','JPY','KES','KGS','KHR','KMF','KPW','KRW','KWD','KYD',
		'KZT','LAK','LBP','LKR','LRD','LSL','LTL','LVL','LYD','MAD','MDL','MGF','MKD','MMK','MNT','MOP','MRO','MTL','MUR','MVR','MWK',
		'MXN','MXV','MYR','MZM','NAD','NGN','NIO','NOK','NPR','NZD','OMR','PAB','PEN','PGK','PHP','PKR','PLN','PYG','QAR','ROL','RUB',
		'RUR','RWF','SAR','SBD','SCR','SDD','SEK','SGD','SHP','SIT','SKK','SLL','SOS','SRG','STD','SVC','SYP','SZL','THB','TJS','TMM',
		'TND','TOP','TPE','TRL','TRY','TTD','TWD','TZS','UAH','UGX','USD','UYU','UZS','VEB','VND','VUV','XAF','XCD','XOF','XPF','YER',
		'YUM','ZAR','ZMK','ZWD'
    );

    //
    // Default constructor
    //
    public function __construct()
    {
		// Nothing to do
    }

	protected function _canDoCapture($order)
	{
		$session = Mage::getSingleton('adminhtml/session');

		if (((int)$this->getConfigData('remoteinterface', $order ? $order->getStoreId() : null)) != 1) {
    		return false;
    	}

		try
		{
			// Read info directly from the database
			$read = Mage::getSingleton('core/resource')->getConnection('core_read');
			$row = $read->fetchRow("select * from epay_order_status where orderid = '" . $order->getIncrementId() . "'");

			if($row["status"] == '1')
			{
				$tid = $row["tid"];
				$param = array
				(
					'merchantnumber' => $this->getConfigData('merchantnumber', $order ? $order->getStoreId() : null),
					'transactionid' => $tid,
					'epayresponse' => 0,
					'pwd' => $this->getConfigData('remoteinterfacepassword', $order ? $order->getStoreId() : null)
				);

				$client = new SoapClient('https://ssl.ditonlinebetalingssystem.dk/remote/payment.asmx?WSDL');
				$result = $client->gettransaction($param);

				if($result->gettransactionResult == 1)
				{
					return true;
				}
				else
				{
					return false;
				}
			}
			else
			{
				return false;
			}
		}
		catch (Exception $e)
		{
			$session->addException($e, $e->getMessage() . " - Go to the ePay administration to capture the payment manually.");
		}

		return true;
	}

    public function getSession()
    {
        return Mage::getSingleton('epay/session');
    }

    public function getCheckout()
    {
        return Mage::getSingleton('checkout/session');
    }

    public function getQuote()
    {
        return $this->getCheckout()->getQuote();
    }

	public function canEdit()
	{
		return true;
	}

	public function canCapture()
	{
		$captureOrder = $this->_data["info_instance"]->getOrder();

		$read = Mage::getSingleton('core/resource')->getConnection('core_read');
	    $row = $read->fetchRow("select * from epay_order_status where orderid = '" . $captureOrder->getIncrementId() . "'");
		if($row["status"] == '1')
		{
			return true;
		}

		return false;
	}

	public function canVoid(Varien_Object $payment)
	{
		$voidOrder = $this->_data["info_instance"]->getOrder();

		if (((int)$this->getConfigData('remoteinterface', $voidOrder ? $voidOrder->getStoreId() : null)) != 1) {
    		return false;
    	}

		// Read info directly from the database
    	$read = Mage::getSingleton('core/resource')->getConnection('core_read');
    	$row = $read->fetchRow("select * from epay_order_status where orderid = '" . $voidOrder->getIncrementId() . "'");
		if ($row['status'] == '1')
		{
			return $this->_canVoid;
		}
		else
		{
			return false;
		}

		return $this->_canVoid;
	}

	public function canRefund()
    {
		$creditOrder = $this->_data["info_instance"]->getOrder();

		if (((int)$this->getConfigData('remoteinterface', $creditOrder ? $creditOrder->getStoreId() : null)) != 1) {
    		return false;
    	}

		// Read info directly from the database
    	$read = Mage::getSingleton('core/resource')->getConnection('core_read');
    	$row = $read->fetchRow("select * from epay_order_status where orderid = '" . $creditOrder->getIncrementId() . "'");
		if ($row['status'] == '1')
		{
			return $this->_canRefund;
		}
		else
		{
			return false;
		}

		return $this->_canRefund;
    }

    public function createFormBlock($name)
    {
        $block = $this->getLayout()->createBlock('epay/standard_form', $name)->setMethod('epay_standard')->setPayment($this->getPayment())->setTemplate('epay/standard/form.phtml');

        return $block;
    }

    public function validate()
    {
        parent::validate();

        $currencyCode = $this->getQuote()->getBaseCurrencyCode();
		if(isset($currencyCode))
		{
			if(!in_array($currencyCode, $this->_allowCurrencyCode))
			{
				Mage::throwException(Mage::helper('epay')->__('Selected currency code (' . $currencyCode . ') is not compatabile with ePay'));
			}
		}
        return $this;
    }

    public function onOrderValidate(Mage_Sales_Model_Order_Payment $payment)
    {

    }

    public function onInvoiceCreate(Mage_Sales_Model_Invoice_Payment $payment)
    {

    }

    public function getInfoBlockType()
    {
        return $this->_infoBlockType;
    }

    public function processCreditmemo($creditmemo, $payment)
    {
        return $this;
    }

    public function processBeforeRefund($invoice, $payment)
    {
        return $this;
    }

	public function getOrderPlaceRedirectUrl()
    {
    	return Mage::getUrl('epay/standard/redirect');
    }

 	public function getOrderInJson($order)
	{
		if($this->getConfigData('enableinvoicedata', $order ? $order->getStoreId() : null))
		{
			$invoice["customer"]["emailaddress"] = $order->getCustomerEmail();
		    $invoice["customer"]["firstname"] = $this->removeSpecialCharacters($order->getBillingAddress()->getFirstname());
			$invoice["customer"]["lastname"] = $this->removeSpecialCharacters($order->getBillingAddress()->getLastname());
		    $invoice["customer"]["address"] = $this->removeSpecialCharacters($order->getBillingAddress()->getStreetFull());
		    $invoice["customer"]["zip"] = $this->removeSpecialCharacters($order->getBillingAddress()->getPostcode());
		    $invoice["customer"]["city"] = $this->removeSpecialCharacters($order->getBillingAddress()->getCity());
		    $invoice["customer"]["country"] = $this->removeSpecialCharacters($order->getBillingAddress()->getCountryId());

		    $invoice["shippingaddress"]["firstname"] = $this->removeSpecialCharacters($order->getShippingAddress()->getFirstname());
			$invoice["shippingaddress"]["lastname"] = $this->removeSpecialCharacters($order->getShippingAddress()->getLastname());
		    $invoice["shippingaddress"]["address"] = $this->removeSpecialCharacters($order->getShippingAddress()->getStreetFull());
		    $invoice["shippingaddress"]["zip"] = $this->removeSpecialCharacters($order->getShippingAddress()->getPostcode());
		    $invoice["shippingaddress"]["city"] = $this->removeSpecialCharacters($order->getShippingAddress()->getCity());
		    $invoice["shippingaddress"]["country"] = $this->removeSpecialCharacters($order->getShippingAddress()->getCountryId());

		    $invoice["lines"] = array();

			$invoiceData = Mage::helper('epay/gateway_advanced');
			$invoiceData->init($order);

	        $items = $invoiceData->getGoodsList();

			foreach ($items as $item)
	        {
                $item["description"] = $this->removeSpecialCharacters($item["description"]);
                $invoice["lines"][] = $item;
	        }

			return json_encode($invoice,JSON_UNESCAPED_UNICODE);

		}
		else
		{
			return "";
		}
	}
    /**
     * Removes all special charactors from a string and replace them with a spacing
     *
     * @param string $value
     * @return string
     */
    private function removeSpecialCharacters($value)
    {

        return preg_replace('/[^\p{Latin}\d]/u', ' ', $value);
    }

	/**
     * Convert card id to name
     *
     * @param int $cardid
     * @return string
     */
    public function calcCardtype($cardid)
	{
        $cardIdArray = array(
            '1' => 'Dankort / VISA/Dankort',
            '2' => 'eDankort',
            '3' => 'VISA / VISA Electron',
            '4' => 'MasterCard',
            '6' => 'JCB',
            '7' => 'Maestro',
            '8' => 'Diners Club',
            '9' => 'American Express',
            '10' => 'ewire',
            '12' => 'Nordea e-betaling',
            '13' => 'Danske Netbetalinger',
            '14' => 'PayPal',
            '16' => 'MobilPenge',
            '17' => 'Klarna',
            '18' => 'Svea',
            '19' => 'SEB Direktbetalning',
            '20' => 'Nordea E-payment',
            '21' => 'Handelsbanken Direktbetalningar',
            '22' => 'Swedbank Direktbetalningar',
            '23' => 'ViaBill',
            '24' => 'NemPay',
            '25' => 'iDeal');

        return key_exists($cardid, $cardIdArray) ? $cardIdArray[$cardid] : '';
	}

    /**
     * Returns information about magento and module version
     *
     * @return string
     */
    public function getCmsInfo()
    {
        $bamboraVersion = (string) Mage::getConfig()->getNode()->modules->Mage_Epay->version;
        $magentoVersion = Mage::getVersion();
        $result = 'Magento/' . $magentoVersion . ' Module/' . $bamboraVersion;

        return $result;
    }

     public function generateMD5Key($paymentRequest, $md5Key)
     {
         $valueString = implode($paymentRequest).$md5Key;
         return md5($valueString);
     }

    /**
     * Convert country code to a number
     *
     * @param mixed $lan
     * @return string
     */
    public function calcLanguage($lan)
	{
        $languageArray = array(
            'da_DK' => '1',
            'de_CH' => '7',
            'de_DE' => '7',
            'en_AU' => '2',
            'en_GB' => '2',
            'en_NZ' => '2',
            'en_US' => '2',
            'sv_SE' => '3',
            'nn_NO' => '4',
            );

        return key_exists($lan, $languageArray) ? $languageArray[$lan] : '0';
	}

    function getEpayErrorText($errorcode)
    {
		$res = "Unable to lookup errorcode";

		try
		{
			$client = new SoapClient('https://ssl.ditonlinebetalingssystem.dk/remote/payment.asmx?WSDL');

			$param = array
			(
				'merchantnumber' => $this->getConfigData('merchantnumber', $this->getOrder() ? $this->getOrder()->getStoreId() : null),
				'language' => $this->calcLanguage(Mage::app()->getLocale()->getLocaleCode()),
				'epayresponsecode' => $errorcode,
				'epayresponsestring' => 0,
				'epayresponse' => 0,
				'pwd' => $this->getConfigData('remoteinterfacepassword', $this->getOrder() ? $this->getOrder()->getStoreId() : null)
			);

			$client = new SoapClient('https://ssl.ditonlinebetalingssystem.dk/remote/payment.asmx?WSDL');
			$result = $client->getEpayError($param);

			if($result->getEpayErrorResult == 1)
			{
				$res = $result->epayresponsestring;
			}
		}
		catch (Exception $e)
		{
			return $res;
		}

	    return $res;
    }

    function getPbsErrorText($errorcode)
    {
    	$res = "Unable to lookup errorcode";

		try
		{
			$client = new SoapClient('https://ssl.ditonlinebetalingssystem.dk/remote/payment.asmx?WSDL');
			$param = array
			(
				'merchantnumber' => $this->getConfigData('merchantnumber', $this->getOrder() ? $this->getOrder()->getStoreId() : null),
				'language' => $this->calcLanguage(Mage::app()->getLocale()->getLocaleCode()),
				'pbsresponsecode' => $errorcode,
				'epayresponsestring' => 0,
				'epayresponse' => 0,
				'pwd' => $this->getConfigData('remoteinterfacepassword', $this->getOrder() ? $this->getOrder()->getStoreId() : null)
			);
			$client = new SoapClient('https://ssl.ditonlinebetalingssystem.dk/remote/payment.asmx?WSDL');
			$result = $client->getPbsError($param);
			if($result->getPbsErrorResult == 1)
			{
				$res = $result->pbsresponsestring;
			}
		}
		catch (Exception $e)
		{
			return $res;
		}

	    return $res;
    }

    public function capture(Varien_Object $payment, $amount)
    {
		$session = Mage::getSingleton('adminhtml/session');

		//
		// Verify if remote interface is enabled
		//
		if(!$this->_canDoCapture($payment->getOrder()))
		{
			return $this;
		}

		if(((int)$this->getConfigData('remoteinterface', $payment->getOrder() ? $payment->getOrder()->getStoreId() : null)) != 1)
		{
			$this->addOrderComment($payment->getOrder(), Mage::helper('epay')->__('EPAY_LABEL_73'));
			return $this;
		}

		try
		{
			//
			// Read info directly from the database
			$read = Mage::getSingleton('core/resource')->getConnection('core_read');
			$row = $read->fetchRow("select * from epay_order_status where orderid = '" . $payment->getOrder()->getIncrementId() . "'");
			if($row["status"] == '1')
			{
				$epayamount = ((string)($amount * 100));

				$tid = $row["tid"];
				$param = array
				(
					'merchantnumber' => $this->getConfigData('merchantnumber', $payment->getOrder() ? $payment->getOrder()->getStoreId() : null),
					'transactionid' => $tid,
					'amount' => $epayamount,
					'group' => '',
					'pbsResponse' => 0,
					'epayresponse' => 0,
					'pwd' => $this->getConfigData('remoteinterfacepassword', $payment->getOrder() ? $payment->getOrder()->getStoreId() : null)
				);

				$client = new SoapClient('https://ssl.ditonlinebetalingssystem.dk/remote/payment.asmx?WSDL');
				$result = $client->capture($param);

				if($result->captureResult == 1)
				{
					//
					// Success - transaction captured!
					//
					$this->addOrderComment($payment->getOrder(), "Transaction with id: " . $tid . " has been captured by amount: " . number_format($amount, 2, ",", "."));

					if(!$payment->getParentTransactionId() || $tid != $payment->getParentTransactionId())
					{
						$payment->setTransactionId($tid);
					}

					$payment->setIsTransactionClosed(0);
				}
				else
				{
					if($result->epayresponse != -1)
					{
						if($result->epayresponse ==  -1002)
						{
							$this->addOrderComment($payment->getOrder(), "Transaction could not be deleted by ePay: " . $result->epayresponse . ". Forretningsnummeret findes ikke.");
							throw new Exception("Transaction could not be captured by ePay: " . $result->epayresponse . ". Forretningsnummeret findes ikke.");
						}
						else
						{
							if($result->epayresponse ==  -1003 || $result->epayresponse ==  -1006)
							{
								$this->addOrderComment($payment->getOrder(), "Transaction could not be captured by ePay: " . $result->epayresponse . ". Der er ikke adgang til denne funktion (API / Remote Interface).");
								throw new Exception("Transaction could not be captured by ePay: " . $result->epayresponse . ". Der er ikke adgang til denne funktion (API / Remote Interface).");
							}
							else
							{
								$this->addOrderComment($payment->getOrder(), 'Transaction could not be captured by ePay: ' . $result->epayresponse . '. ' . $this->getEpayErrorText($result->epayresponse));
								throw new Exception('Transaction could not be captured by ePay: ' . $result->epayresponse . '. ' . $this->getEpayErrorText($result->epayresponse));
							}
						}
					}
					else
					{
						throw new Exception("Transaction could not be captured by ePay: " . $result->pbsResponse . '. ' . $this->getPbsErrorText($result->pbsResponse));
					}
				}
			}
			else
			{
				//
				// Somehow the order was not found - this must be an error!
				//
				throw new Exception("Order not found - please check the epay_order_status table!");
			}
		}
		catch (Exception $e)
		{
			$session->addException($e, $e->getMessage() . " - Go to the ePay administration to capture the payment manually.");
		}

		return $this;
    }

    public function refund(Varien_Object $payment, $amount)
    {
    	$session = Mage::getSingleton('adminhtml/session');
		//
    	// Verify if remote interface is enabled
    	//
		try
		{
	    	if (((int)$this->getConfigData('remoteinterface', $payment->getOrder() ? $payment->getOrder()->getStoreId() : null)) != 1)
			{
				$this->addOrderComment($payment->getOrder(), Mage::helper('epay')->__('EPAY_LABEL_74'));
				throw new Exception(Mage::helper('epay')->__('EPAY_LABEL_74'));
			}

	    	//
			// Read info directly from the database

			$read = Mage::getSingleton('core/resource')->getConnection('core_read');
			$row = $read->fetchRow("select * from epay_order_status where orderid = '" . $payment->getOrder()->getIncrementId() . "'");
			if($row["status"] == '1')
			{
				$epayamount = ((string)($amount * 100));
				$tid = $row["tid"];
				$param = array
				(
					'merchantnumber' => $this->getConfigData('merchantnumber', $payment->getOrder() ? $payment->getOrder()->getStoreId() : null),
					'transactionid' => $tid,
					'amount' => $epayamount,
					'group' => '',
					'pbsresponse' => 0,
					'epayresponse' => 0,
					'pwd' => $this->getConfigData('remoteinterfacepassword', $payment->getOrder() ? $payment->getOrder()->getStoreId() : null)
				);
				$client = new SoapClient('https://ssl.ditonlinebetalingssystem.dk/remote/payment.asmx?WSDL');
				$result = $client->credit($param);

				if($result->creditResult == 1)
				{
					//
					// Success - transaction credited!
					//
					$this->addOrderComment($payment->getOrder(), "Transaction with id: " . $tid . " has been credited by amount: " . number_format($amount, 2, ",", "."));
				}
				else
				{
		    		if ($result->epayresponse == -1002)
					{
		    			$this->addOrderComment($payment->getOrder(), "An error (" . $result->epayresponse . ") occured in the communication to ePay: The merchantnumber you are using does not exists or is disabled. Please log into your ePay account to verify your merchantnumber. This can be done from the menu: SETTINGS -> PAYMENT SYSTEM.");
		    			throw new Exception("An error (" . $result->epayresponse . ") occured in the communication to ePay: The merchantnumber you are using does not exists or is disabled. Please log into your ePay account to verify your merchantnumber. This can be done from the menu: SETTINGS -> PAYMENT SYSTEM.");
					}
					elseif ($result->epayresponse == -1003)
					{
		    			$this->addOrderComment($payment->getOrder(), "An error (" . $result->epayresponse . ") occured in the communication to ePay: The IP address your system calls ePay from is UNKNOWN. Please log into your ePay account to verify enter the IP address your system calls ePay from. This can be done from the menu: API / WEBSERVICES -> ACCESS.");
		    			throw new Exception("An error (" . $result->epayresponse . ") occured in the communication to ePay: The IP address your system calls ePay from is UNKNOWN. Please log into your ePay account to verify enter the IP address your system calls ePay from. This can be done from the menu: API / WEBSERVICES -> ACCESS.");
		    		}
					elseif($result->epayresponse ==  -1006)
					{
						$this->addOrderComment($payment->getOrder(), "An error (" . $result->epayresponse . ") occured in the communication to ePay: Your ePay account has not access to API / Remote Interface. This is only for ePay BUSINESS accounts. Please contact ePay to upgrade your ePay account.");
						throw new Exception("An error (" . $result->epayresponse . ") occured in the communication to ePay: Your ePay account has not access to API / Remote Interface. This is only for ePay BUSINESS accounts. Please contact ePay to upgrade your ePay account.");
					}
					elseif($result->epayresponse == -1021)
					{
						$this->addOrderComment($payment->getOrder(), "An error (" . $result->epayresponse . ") occured in the communication to ePay: An operation every 15 minutes can be performed on a transaction. Please wait 15 minutes and try again.");
						throw new Exception("An error (" . $result->epayresponse . ") An operation every 15 minutes can be performed on a transaction. Please wait 15 minutes and try again.");
					}
					else
					{
		    			$this->addOrderComment($payment->getOrder(), "An error (" . $result->epayresponse . ") occured in the communication to ePay: " . $this->getEpayErrorText($result->epayresponse));
		    			throw new Exception("An error (" . $result->epayresponse . ") occured in the communication to ePay: " . $this->getEpayErrorText($result->epayresponse));
		    		}

				}
			}
			else
			{
				//
				// Somehow the order was not found - this must be an error!
				//
				throw new Exception("Order not found - please check the epay_order_status table!");
			}
		}
		catch (Exception $e)
		{
			$session->addException($e, $e->getMessage() . " - Go to the ePay administration to credit the payment manually.");
		}

        return $this;
    }

    public function void (Varien_Object $payment)
	{
		$this->cancel($payment);
		return $this;
	}

    public function cancel(Varien_Object $payment)
    {
		$session = Mage::getSingleton('adminhtml/session');

		if(Mage::app()->getRequest()->getActionName() == 'save')
		{
			$order = $payment->getOrder();
			$_totalDatas = $order->getData();

			$childOrder = Mage::getModel('sales/order')->loadByIncrementId($_totalDatas['relation_child_real_id']);

			if($childOrder->getBaseGrandTotal() <= $order->getBaseGrandTotal())
			{
				$childPayment = $childOrder->getPayment();

				$write = Mage::getSingleton('core/resource')->getConnection('core_write');
				$write->query('update epay_order_status set orderid = "' . $_totalDatas['relation_child_real_id'] . '" WHERE orderid = "' . $order->getIncrementId() . '"');

				$transactionId = $payment->getParentTransactionId();

				$childPayment->setTransactionId($transactionId)->setIsTransactionClosed(0);
				$transaction = $childPayment->addTransaction(Mage_Sales_Model_Order_Payment_Transaction::TYPE_AUTH);
				$transaction->setAdditionalInformation("Transaction ID", $transactionId);
				$transaction->save();
			}
			else
			{
				$session->addError("The transaction could not be moved because the amount of the edited order exceeds the transaction amount - Go to the ePay administration to handle the payment manually.");
			}

			return;
		}

    	//
    	// Verify if remote interface is enabled
    	//
    	if (((int)$this->getConfigData('remoteinterface', $payment->getOrder() ? $payment->getOrder()->getStoreId() : null)) != 1) {
    		$this->addOrderComment($payment->getOrder(), Mage::helper('epay')->__('EPAY_LABEL_75'));
    		return;
    	}

		try
		{
			//
			// Read info directly from the database
			//
			$read = Mage::getSingleton('core/resource')->getConnection('core_read');
			$row = $read->fetchRow("select * from epay_order_status where orderid = '" . $payment->getOrder()->getIncrementId() . "'");

			if($row["status"] == '1')
			{
				$tid = $row["tid"];
				$param = array
				(
					'merchantnumber' => $this->getConfigData('merchantnumber', $payment->getOrder() ? $payment->getOrder()->getStoreId() : null),
					'transactionid' => $tid,
					'group' => '',
					'epayresponse' => 0,
					'pwd' => $this->getConfigData('remoteinterfacepassword', $payment->getOrder() ? $payment->getOrder()->getStoreId() : null)
				);

				$client = new SoapClient('https://ssl.ditonlinebetalingssystem.dk/remote/payment.asmx?WSDL');
				$result = $client->delete($param);
				if($result->deleteResult == 1)
				{
					//
					// Success - transaction deleted!
					//
					$this->addOrderComment($payment->getOrder(), "Transaction deleted with transaction id: " . $tid);
					$payment->getOrder()->save();
				}
				else
				{
					if($result->epayresponse != -1)
					{
						if($result->epayresponse ==  -1002)
						{
							$this->addOrderComment($payment->getOrder(), "Transaction could not be deleted by ePay: " . $result->epayresponse . ". Forretningsnummeret findes ikke.");
							throw new Exception("Transaction could not be deleted by ePay: " . $result->epayresponse . ". Forretningsnummeret findes ikke.");
						}
						elseif($result->epayresponse ==  -1003 || $result->epayresponse ==  -1006)
						{
							$this->addOrderComment($payment->getOrder(), "Transaction could not be captured by ePay: " . $result->epayresponse . ". Der er ikke adgang til denne funktion (API / Remote Interface).");
							throw new Exception("Transaction could not be deleted by ePay: " . $result->epayresponse . ". Der er ikke adgang til denne funktion (API / Remote Interface).");
						}
						else
						{
							$this->addOrderComment($payment->getOrder(), 'Transaction could not be deleted by ePay: ' . $result->epayresponse . '. ' . $this->getEpayErrorText($result->epayresponse));
							throw new Exception('Transaction could not be deleted by ePay: ' . $result->epayresponse . '. ' . $this->getEpayErrorText($result->epayresponse));
						}
					}
					else
					{
						throw new Exception('Unknown response from ePay: ' . $result->epayresponse);
					}
				}
			}
			elseif($row["status"] == '0')
			{
				//
				// Do nothing - the order is to be canceled without any communication to ePay
				//
			}
			else
			{
				//
				// Somehow the order was not found - this must be an error!
				//
				throw new Exception("Order not found - please check the epay_order_status table!");
			}
		}
		catch (Exception $e)
		{
			$session->addException($e, $e->getMessage() . " - Go to the ePay administration to credit the payment manually.");
		}
    }

    public function addOrderComment($order, $comment)
    {
    	$order->addStatusToHistory($order->getStatus(), $comment);
		$order->save();
    }

    public function getStore()
    {
        return Mage::app()->getStore();
    }

    public function getAcceptUrl()
    {
        return Mage::getUrl('epay/standard/success', array('_nosid' => true));
    }

    public function getCancelUrl()
    {
        return Mage::getUrl('epay/standard/cancel', array('_nosid' => true));
    }

    public function getCallbackUrl()
    {
        return Mage::getUrl('epay/standard/callback', array('_nosid' => true));
    }
}