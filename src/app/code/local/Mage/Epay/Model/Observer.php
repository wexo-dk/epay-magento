<?php
/**
 * Copyright ePay | Dit Online Betalingssystem, (c) 2016.
 * This program is free software. You are allowed to use the software but NOT allowed to modify the software.
 * It is also not legal to do any changes to the software and distribute it in your own name / brand.
 */
class Mage_Epay_Model_Observer
{
    public function adminhtmlWidgetContainerHtmlBefore($event)
    {
        $block = $event->getBlock();
        if ($block instanceof Mage_Adminhtml_Block_Sales_Order_View)
        {
            $order = $this->getOrder();
            if(!$this->validatePaymentMethod() || $order->isCanceled())
            {
                return;
            }

            $read = Mage::getSingleton('core/resource')->getConnection('core_read');
            $row = $read->fetchRow("select * from epay_order_status where orderid = '" . $order->getIncrementId() . "'");

            if (!$row || $row['status'] == '0')
            {
                $block->addButton('button_sendpaymentrequest', array('label' => Mage::helper('epay')->__("Create payment request"), 'onclick' => 'setLocation(\'' . Mage::helper("adminhtml")->getUrl('adminhtml/paymentrequest/create/', array('id' => $order->getRealOrderId())) . '\')', 'class' => 'scalable go'), 0, 100, 'header', 'header');
            }
        }
    }

    public function addMassOrderAction($event)
    {
        $block = $event->getBlock();
        if ($block instanceof Mage_Adminhtml_Block_Widget_Grid_Massaction && $block->getRequest()->getControllerName() == 'sales_order')
        {
            $block->addItem('epay_capture', array(
             'label'=> Mage::helper('epay')->__("ePay - Mass Invoice and Capture"),
             'url'  => $block->getUrl('adminhtml/massaction/epaymasscapture'),
             'confirm' => Mage::helper('epay')->__("Are you sure you want to invoice and capture selected items?")
             ));

            $block->addItem('epay_delete', array(
             'label'=> Mage::helper('epay')->__("ePay - Mass Delete"),
             'url'  => $block->getUrl('adminhtml/massaction/epaymassdelete'),
             'confirm' => Mage::helper('epay')->__("Are you sure you want to delete selected items? This can not be undone! If there have been authorized a payment on the order it will not get voided by this action.")
             ));
        }
    }

    public function addMassInvoiceAction($event)
    {
        $block = $event->getBlock();
        if ($block instanceof Mage_Adminhtml_Block_Widget_Grid_Massaction && $block->getRequest()->getControllerName() == 'sales_invoice')
        {
            $block->addItem('epay_invoice', array(
             'label'=> Mage::helper('epay')->__("ePay - Mass Creditmemo and Refund"),
             'url'  => $block->getUrl('adminhtml/massaction/epaymassrefund'),
             'confirm' => Mage::helper('epay')->__("Are you sure you want to refund selected items?")
             ));
        }
    }


    /**
     * Auto Cancel orders that are from 1 day until 1 hour ago and with custom pending status
     */
    public function autocancelPendingOrders()
    {
        $payment = Mage::getModel('epay/standard');

        $storeId = $payment->getStore()->getId();

        if(intval($payment->getConfigData('use_auto_cancel', $storeId)) === 1)
        {
            $date = Mage::getSingleton('core/date');

            $orderCollection = Mage::getResourceModel('sales/order_collection');

            $orderCollection
                ->addFieldToFilter('status', array('eq' => $payment->getConfigData('order_status', null)))
                ->addFieldToFilter('created_at', array(
                    'to' => strtotime('-1 hour', strtotime($date->gmtDate())),
                    'from' => strtotime('-1 day', strtotime($date->gmtDate())),
                    'datetime' => true))
                ->setOrder('created_at', 'ASC')
                ->getSelect();

            foreach ($orderCollection->getItems() as $order)
            {
                /** @var Mage_Sales_Model_Order */
                $orderModel = Mage::getModel('sales/order');
                $orderModel->load($order["entity_id"]);

                try
                {
                    if(!$orderModel->canCancel())
                    {
                        continue;
                    }

                    $read = Mage::getSingleton('core/resource')->getConnection('core_read');
                    $row = $read->fetchRow("select * from epay_order_status where orderid = '" . $orderModel->getIncrementId() . "'");

                    if($row["status"] == '0')
                    {
                        $orderModel->cancel();
                        $message = Mage::helper('epay')->__("Order was auto canceled because no payment has been made.");
                        $orderModel->addStatusToHistory($orderModel->getStatus(), $message);
                        $orderModel->save();
                    }
                }
                catch(Exception $e)
                {
                    echo "Could not be canceled: " . $e->getMessage();
                    Mage::logException($e);
                }
            }
        }
    }

    public function orderPlacedAfter($observer)
    {
        /** @var Mage_Sales_Model_Order */
        $order = $observer->getOrder();
        $order->addStatusHistoryComment(Mage::helper('epay')->__("The Order is placed using ePay Online payment system and is now awaiting payment."))
            ->setIsCustomerNotified(false);
        $order->save();
    }


    /**
     * Get order
     *
     * @return mixed
     */
    private function getOrder()
    {
        return Mage::registry('current_order');
    }

    private function validatePaymentMethod()
    {
        $currentMethod = $this->getOrder()->getPayment()->getMethod();
        return $currentMethod === 'epay_standard';
    }
}