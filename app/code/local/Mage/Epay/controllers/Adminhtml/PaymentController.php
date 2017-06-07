<?php
/**
 * Copyright (c) 2017. All rights reserved ePay A/S (a Bambora Company).
 *
 * This program is free software. You are allowed to use the software but NOT allowed to modify the software.
 * It is also not legal to do any changes to the software and distribute it in your own name / brand.
 *
 * All use of the payment modules happens at your own risk. We offer a free test account that you can use to test the module.
 *
 * @author    ePay A/S (a Bambora Company)
 * @copyright Bambora (http://bambora.com) (http://www.epay.dk)
 * @license   ePay A/S (a Bambora Company)
 *
 */
class Mage_Epay_Adminhtml_PaymentController extends Mage_Adminhtml_Controller_Action
{
    public function moveAction()
    {
        //Validate orderid
        $childOrderId = $this->getRequest()->getParam('orderid');
        $parentOrderId = $this->getRequest()->getParam('parentorderid');

        if (isset($childOrderId) && isset($parentOrderId)) {
            /** @var Mage_Sales_Model_Order */
            $childOrder = Mage::getModel('sales/order')->loadByIncrementId($childOrderId);
            /** @var Mage_Sales_Model_Order */
            $parentOrder = Mage::getModel('sales/order')->loadByIncrementId($parentOrderId);

            foreach ($parentOrder->getAllItems() as $item) {
                if ($item->getSku() === 'surcharge_fee') {

                    /** @var Mage_Sales_Model_Order_Item */
                    $feeItem = Mage::helper('epay')->createFeeItem($item->getBaseRowTotal(), $item->getRowTotal(), $childOrder->getStoreId(), $childOrder->getId(), $item->getName());

                    $childOrder->addItem($feeItem);

                    $childOrder->setBaseGrandTotal($childOrder->getBaseGrandTotal() + $item->getBaseRowTotal());
                    $childOrder->setBaseSubtotal($childOrder->getBaseSubtotal() + $item->getBaseRowTotal());
                    $childOrder->setGrandTotal($childOrder->getGrandTotal() + $item->getRowTotal());
                    $childOrder->setSubtotal($childOrder->getSubtotal() + $item->getRowTotal());

                    $feeMessage = $item->getName() . ' ' .__("added to order");
                    $childOrder->addStatusHistoryComment($feeMessage);

                    $childOrder->save();
                    break;
                }
            }

            if ($childOrder->getBaseGrandTotal() <= $parentOrder->getBaseGrandTotal()) {
                $write = Mage::getSingleton('core/resource')->getConnection('core_write');
                $write->query('update epay_order_status set orderid = "' . $childOrderId . '" WHERE orderid = "' . $parentOrderId . '"');

                $childPayment = $childOrder->getPayment();
                $parentPayment = $parentOrder->getPayment();


                $transactionId = $parentPayment->getAdditionalInformation(Mage_Epay_Model_Standard::PSP_REFERENCE);
                $isInstantCapture = $parentPayment->getAdditionalInformation('instantcapture');

                $childPayment->setAdditionalInformation(Mage_Epay_Model_Standard::PSP_REFERENCE, $transactionId);
                $childPayment->setAdditionalInformation('instantcapture', $isInstantCapture);
                $childPayment->setAdditionalInformation('movedfromparent', true);

                $childPayment->setTransactionId($transactionId)->setIsTransactionClosed(0);
                $transaction = $childPayment->addTransaction(Mage_Sales_Model_Order_Payment_Transaction::TYPE_AUTH);
                $transaction->setAdditionalInformation("Transaction ID", $transactionId);
                $transaction->save();
                $childPayment->save();

                /** @var Mage_Epay_Model_Standard */
                $epayStandard = Mage::getModel('epay/standard');
                $message = __("Payment moved from parent");

                $status = $epayStandard->getConfigData('order_status_after_payment', $parentOrder->getStoreId());
                $childOrder->setState(Mage_Sales_Model_Order::STATE_PROCESSING, $status, $message, false);
                $childOrder->save();

                $this->_getSession()->addSuccess($message);
            } else {
                $this->_getSession()->addError(__("The transaction could not be moved because the amount of the edited order exceeds the transaction amount - Go to the ePay administration to handle the payment manually."));
            }
        } else {
            $this->_getSession()->addInfo(__("Nothing changed"));
        }

        $this->_redirectReferer();
    }

    public function _isAllowed()
    {
        return parent::_isAllowed();
    }
}
