<?php
/**
 * Copyright ePay | Dit Online Betalingssystem, (c) 2010.
 * This program is free software. You are allowed to use the software but NOT allowed to modify the software. 
 * It is also not legal to do any changes to the software and distribute it in your own name / brand. 
 */
 
include_once("Mage/Adminhtml/controllers/Sales/OrderController.php");
 
class Mage_Epay_Sales_OrderController extends Mage_Adminhtml_Sales_OrderController
{
	
	public function epayCaptureAction()
	{
		$orderIds = $this->getRequest()->getPost('order_ids', array());
		
		$remoteinterfaceError = false;
		
		$okIds = array();
		$ok_invoiceIds = array();
		$failIds = array();
		$notfoundIds = array();
		
		foreach ($orderIds as $orderId)
		{
			$order = Mage::getModel('sales/order')->load($orderId);
			$payment = $order->getPayment();
			
			if((int)Mage::getStoreConfig('payment/epay_standard/remoteinterface') != 1)
			{	
				if($remoteinterfaceError == false)
				{
					$this->_getSession()->addError($this->__('Remote interface must be activated.'));
					$remoteinterfaceError = true;
					//To avoid multiple errors
				}
			}
			else
			{
				try
				{
					if($this->_canDoCapture($order))
					{
						$read = Mage::getSingleton('core/resource')->getConnection('core_read');
						$row = $read->fetchRow("SELECT * FROM epay_order_status WHERE orderid = '" . $payment->getOrder()->getIncrementId() . "'");
						if($row["status"] == '1')
						{
							if($order->canInvoice())
							{
								$invoice = $order->prepareInvoice();
								$invoice->register();
								Mage::getModel('core/resource_transaction')->addObject($invoice)->addObject($invoice->getOrder())->save();
								
								if((int)Mage::getStoreConfig('payment/epay_standard/captureinvoicemail') == 1)
								{
									$invoice->setEmailSent(true);
								}
								
								$invoice->capture();
								$invoice->save();
								
								if((int)Mage::getStoreConfig('payment/epay_standard/captureinvoicemail') == 1)
								{
									$invoice->sendEmail();
								}

								$order->addStatusToHistory($order->getStatus(), "Transaction with id: " . $tid . " has been captured by amount: " . number_format($epayamount / 100, 2, ",", "."));
								$order->save();
								
								$ok_invoiceIds[] = $order->getIncrementId();
							}
							else
							{
								$failIds[] = $order->getIncrementId();
								
							}
						}
						else
						{
							$notfoundIds[] = $order->getIncrementId();
						}
					}
					else
					{
						$alreadyIds[] = $order->getIncrementId();
					}
				}
				catch (Exception $e)
				{
					$this->_getSession()->addException($e, $e->getMessage() . " - Go to the ePay administration to capture the payment manually.");
				}
			}
		}
			
		$ok = 'The following orders was captured: ' . implode(", ", $okIds);
		$ok_invoice = 'An invoice was created for the following orders: ' . implode(", ", $ok_invoiceIds);
		$fail = 'The following orders failed to be captured by ePay: ' . implode(", ", $failIds);
		$notfound = 'The following orders was not found to be processed by ePay: ' . implode(", ", $notfoundIds);
		$already = 'The following orders has already been captured by ePay: ' . implode(", ", $alreadyIds);
		
		if(count($okIds) > 0)
			$this->_getSession()->addSuccess($ok);
		
		if(count($ok_invoiceIds) > 0)
			$this->_getSession()->addSuccess($ok_invoice);
		
		if(count($failIds) > 0)
			$this->_getSession()->addError($fail);
		
		if(count($notfoundIds) > 0)
			$this->_getSession()->addError($notfound);
		
		if(count($alreadyIds) > 0)
			$this->_getSession()->addError($already);
		
		$this->_redirect('*/*/');
	}
	
	protected function _canDoCapture($order)
	{
		if((int)Mage::getStoreConfig('payment/epay_standard/remoteinterface') != 1)
		{
			return false;
		}
		
		// Read info directly from the database
		try
		{
			$read = Mage::getSingleton('core/resource')->getConnection('core_read');
			$row = $read->fetchRow("select * from epay_order_status where orderid = '" . $order->getIncrementId() . "'");
			if($row["status"] == '1')
			{
				$epayamount = ($amount * 100);
				$tid = $row["tid"];
				$param = array
				(
					'merchantnumber' => Mage::getStoreConfig('payment/epay_standard/merchantnumber'),
					'transactionid' => $tid,
					'epayresponse' => 0,
					'pwd' => Mage::getStoreConfig('payment/epay_standard/remoteinterfacepassword')
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
			$this->_getSession()->addException($e, $e->getMessage() . " - Go to the ePay administration to capture the payment manually.");
		}
			
		return true;
	}
}