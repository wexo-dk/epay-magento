<?php
class Mage_Epay_Adminhtml_MassactionController extends Mage_Adminhtml_Controller_Action
{
	/**
	 * Mass Invoice and Capture Action
	 */
	public function epayMassCaptureAction()
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
                    $notInvoiced[] = $order->getIncrementId() . '('.Mage::helper('epay')->__("Invoice not available"). ')';
                    continue;
                }

                $pspReference = $order->getPayment()->getAdditionalInformation(Mage_Epay_Model_Standard::PSP_REFERENCE);
                if(empty($pspReference))
                {
                    $notInvoiced[] = $order->getIncrementId(). '('.Mage::helper('epay')->__("ePay transaction not found"). ')';
                    continue;
                }

                $invoice = $order->prepareInvoice();
                $invoice->setRequestedCaptureCase(Mage_Sales_Model_Order_Invoice::CAPTURE_ONLINE);
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
            $this->_getSession()->addSuccess(sprintf(Mage::helper('epay')->__("You Invoiced and Captured %s order(s)."), $countInvoicedOrder). ' (' .implode(" , ", $invoiced) . ')');
        }

        $this->_redirect('adminhtml/sales_order/index');
	}

    /**
     * Mass Creditmemo and Refund Action
     */
    public function epayMassRefundAction()
	{
		$invoiceIds = $this->getRequest()->getPost('invoice_ids', array());
        $countRefundedOrder = 0;
        $refunded = array();
        $notRefunded = array();

		foreach ($invoiceIds as $invoiceId)
		{
            /** @var Mage_Sales_Model_Order_Invoice $invoice */
            $invoice = Mage::getModel('sales/order_invoice');
            try
            {
                $invoice = $invoice->load($invoiceId);

                if(!$invoice->canRefund())
                {
                    $notRefunded[] = $invoice->getIncrementId(). '('.Mage::helper('epay')->__("Creditmemo not available"). ')';
                    continue;
                }

                $order = $invoice->getOrder();

                $pspReference = $order->getPayment()->getAdditionalInformation(Mage_Epay_Model_Standard::PSP_REFERENCE);
                if(empty($pspReference))
                {
                    $notRefunded[] = $invoice->getIncrementId(). '('.Mage::helper('epay')->__("ePay transaction not found"). ')';
                    continue;
                }

                $service = Mage::getModel('sales/service_order', $order);
                $creditmemo = $service->prepareInvoiceCreditmemo($invoice);
                $creditmemo->register();
                $creditmemo->save();

                Mage::getModel('core/resource_transaction')
                         ->addObject($creditmemo)
                         ->addObject($creditmemo->getOrder())
                         ->addObject($creditmemo->getInvoice())
                         ->save();

                $countRefundedOrder++;
                $refunded[] = $invoice->getIncrementId();
            }
            catch (Exception $e)
            {
                $notInvoiced[] = $invoice->getIncrementId();
                $this->_getSession()->addError(sprintf(Mage::helper('epay')->__("Invoice: %s returned with an error: %s"), $invoice->getIncrementId(), $e->getMessage()));
                continue;
            }
        }

        $countNonRefundedOrder = count($invoiceIds) - $countRefundedOrder;

        if ($countNonRefundedOrder && $countRefundedOrder)
        {
            $this->_getSession()->addError(sprintf(Mage::helper('epay')->__("%s invoice(s) cannot be Refunded."), $countNonRefundedOrder). ' (' .implode(" , ", $notRefunded) . ')');
        }
        elseif ($countNonRefundedOrder)
        {
            $this->_getSession()->addError(Mage::helper('epay')->__("You cannot Refund the invoice(s)."). ' (' .implode(" , ", $notRefunded) . ')');
        }

        if ($countRefundedOrder)
        {
            $this->_getSession()->addSuccess(sprintf(Mage::helper('epay')->__("You Refunded %s invoice(s)."), $countRefundedOrder). ' (' .implode(" , ", $refunded) . ')');
        }

        $this->_redirect('adminhtml/sales_invoice/index');
	}

    /**
     * Mass Delete Action
     */
    public function epayMassDeleteAction()
	{
		$ids = $this->getRequest()->getPost('order_ids', array());
        $countDeleted = 0;
        $deleted = array();
        $notDeleted = array();

		foreach ($ids as $id)
		{
            $order = Mage::getModel('sales/order');;
            try
            {
                $order = $order->load($id);
                $order->delete();

                $countDeleted++;
                $deleted[] = $order->getIncrementId();
            }
            catch (Exception $e)
            {
                $notDeleted[] = $order->getIncrementId();
                $this->_getSession()->addError(sprintf(Mage::helper('epay')->__("Delete: %s returned with an error: %s"), $order->getIncrementId(), $e->getMessage()));
                continue;
            }
        }

        $countNonDeleted = count($ids) - $countDeleted;

        if ($countNonDeleted && $countDeleted)
        {
            $this->_getSession()->addError(sprintf(Mage::helper('epay')->__("%s order(s) cannot be Deleted."), $countNonDeleted). ' (' .implode(" , ", $notDeleted) . ')');
        }
        elseif ($countNonDeleted)
        {
            $this->_getSession()->addError(Mage::helper('epay')->__("You cannot Delete the order(s)."). ' (' .implode(" , ", $notDeleted) . ')');
        }

        if ($countDeleted)
        {
            $this->_getSession()->addSuccess(sprintf(Mage::helper('epay')->__("You Deleted %s order(s)."), $countDeleted). ' (' .implode(" , ", $deleted) . ')');
        }

        $this->_redirect('adminhtml/sales_order/index');
	}
}