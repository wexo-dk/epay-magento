<?php
class Mage_Epay_Adminhtml_MassactionController extends Mage_Adminhtml_Controller_Action
{
	public function epayCaptureAction()
	{
		$orderIds = $this->getRequest()->getPost('order_ids', array());
        $method = Mage::getModel('epay/standard');
        $countInvoicedOrder = 0;
        $invoiced = array();
        $notInvoiced = array();

		foreach ($orderIds as $orderId)
		{
            $order = Mage::getModel('sales/order')->load($orderId);
            try
            {
                if(!$order->canInvoice())
                {
                    $notInvoiced[] = $order->getIncrementId();
                    continue;
                }

                $invoice = $order->prepareInvoice();

                if(intval($method->getConfigData('instantcapture', $order->getStoreId())) == 1)
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

                if(intval($method->getConfigData('captureinvoicemail', $order->getStoreId())) == 1)
                {
                    $invoice->sendEmail();
                    $order->addStatusHistoryComment(sprintf(Mage::helper('epay')->__("Notified customer about invoice #%s", $invoice->getId())))
                        ->setIsCustomerNotified(true);
                    $order->save();
                }

                $countInvoicedOrder++;
                $invoiced[] = $order->getIncrementId();
            }
            catch (Exception $e)
            {
                $notInvoiced[] = $order->getIncrementId();
                $this->_getSession()->addError(sprintf(Mage::helper('epay')->__("Order: %s returned with an error: %s"), $orderId, $e->getMessage()));
                continue;
            }
        }

        $countNonInvoicedOrder = count($orderIds) - $countInvoicedOrder;

        if ($countNonInvoicedOrder && $countInvoicedOrder)
        {
            $this->_getSession()->addError(sprintf(Mage::helper('epay')->__("%s order(s) cannot be Invoiced and Captured."), $countNonInvoicedOrder). ' (' .implode(" , ", $notInvoiced) . ')');
        }
        elseif ($countNonInvoicedOrder)
        {
            $this->_getSession()->addError(Mage::helper('epay')->__("You cannot Invoice and Capture the order(s)."). ' (' .implode(" , ", $notInvoiced) . ')');
        }

        if ($countInvoicedOrder)
        {
            $this->_getSession()->addSuccess(sprintf(Mage::helper('epay')->__("We Invoiced and Captured %s order(s)."), $countInvoicedOrder). ' (' .implode(" , ", $invoiced) . ')');
        }

        $this->_redirect('adminhtml/sales_order/index');
	}
}