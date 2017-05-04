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
class Mage_Epay_Block_Adminhtml_Sales_Order_View_Tab_Info extends Mage_Adminhtml_Block_Template implements Mage_Adminhtml_Block_Widget_Tab_Interface
{
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('epay/order/view/tab/info.phtml');
    }

    private function getOrder()
    {
        return Mage::registry('current_order');
    }

    private function isRemoteInterfaceEnabled()
    {
        $order = $this->getOrder();
        return intval($order->getPayment()->getMethodInstance()->getConfigData('remoteinterface', $order->getStoreId())) === 1;
    }

    public function getPaymentInformationHtml()
    {
        if (!$this->isRemoteInterfaceEnabled()) {
            return Mage::helper('epay')->__("Please enable remote payment processing from the module configuration");
        }

        $order = $this->getOrder();
        $read = Mage::getSingleton('core/resource')->getConnection('core_read');
        $row = $read->fetchRow("select * from epay_order_status where orderid = '" . $order->getIncrementId() . "'");
        if ($row['status'] == '1') {
            $method = Mage::getModel('epay/standard');

            $res = "<table border='0' width='100%'>";
            $res .= "<tr><td colspan='2'><b>" . Mage::helper('epay')->__("Payment system transaction information") . "</b></td></tr>";
            if ($row['tid'] != '0') {
                $res .= "<tr><td width='150'>" . Mage::helper('epay')->__("Transaction ID") . ":</td>";
                $res .= "<td>" . $row['tid'] . "</td></tr>";
            }
            if ($row['amount'] != '0') {
                $res .= "<tr><td>" . Mage::helper('epay')->__("Amount") . ":</td>";
                $res .= "<td>" . $order->getBaseCurrencyCode() . "&nbsp;" . number_format(((int)$row['amount']) / 100, 2, ',', ' ') . "</td></tr>";
            }
            if ($row['cur'] != '0') {
                $res .= "<tr><td>" . Mage::helper('epay')->__("Currency code") . ":</td>";
                $res .= "<td>" . $row['cur'] . "</td></tr>";
            }
            if ($row['date'] != '0') {
                $res .= "<tr><td>" . Mage::helper('epay')->__("Transaction date") . ":</td>";
                $res .= "<td>" . $row['date'] . "</td></tr>";
            }
            if ($row['eKey'] != '0') {
                $res .= "<tr><td>" . Mage::helper('epay')->__("MD5 key") . ":</td>";
                $res .= "<td>" . $row['eKey'] . "</td></tr>";
            }
            if ($row['fraud'] != '0') {
                $res .= "<tr><td>" . Mage::helper('epay')->__("Fraud control") . ":</td>";
                $res .= "<td>" . sprintf(Mage::helper('epay')->__("This creditcard has been used %s time(s) the past 24 hours"), $row['fraud']) . "</td></tr>";
            }
            if ($row['subscriptionid'] != '0') {
                $res .= "<tr><td>" . Mage::helper('epay')->__("Subscription ID") . ":</td>";
                $res .= "<td>" . $row['subscriptionid'] . "</td></tr>";
            }
            if ($row['cardid'] != '0') {
                $res .= "<tr><td>" . Mage::helper('epay')->__("Card type") . ":</td>";
                $res .= "<td>". $method->calcCardtype($row['cardid']) . $this->getPaymentLogoUrl($row['cardid']) . "</td></tr>";
            }
            if (strlen($row['cardnopostfix']) != 0) {
                $res .= "<tr><td>" . Mage::helper('epay')->__("Card number") . ":</td>";
                $res .= "<td>" . $row['cardnopostfix'] . "</td></tr>";
            }
            if ($row['transfee'] != '0') {
                $res .= "<tr><td>" . Mage::helper('epay')->__("Surcharge fee") . ":</td>";
                $res .= "<td>" . $order->getBaseCurrencyCode() . "&nbsp;" . number_format(((int)$row['transfee']) / 100, 2, ',', ' ') . "</td></tr>";
            }

            if ($method->getConfigData('remoteinterface', $order ? $order->getStoreId() : null) == 1) {
                $res .= $this->getTransactionStatus($row['tid'], $row['fraud'], $method, $order);
            }

            $res .= "</table><br>";

            $res .= "<a href='https://admin.ditonlinebetalingssystem.dk/admin' target='_blank'>" . Mage::helper('epay')->__("Go to payment system administration and process the transaction") . "</a>";
            $res .= "<br><br>";
        } else {
            $res = $this->getChildHtml('order_payment');
            $res .= "<br>" . Mage::helper('epay')->__("There is not registered any payment for this order yet!") . "<br>";
        }

        return $res;
    }

    //
    // Translate the current ePay transaction status
    //
    public function translatePaymentStatus($status)
    {
        if (strcmp($status, "PAYMENT_NEW") == 0) {
            return Mage::helper('epay')->__("New");
        } elseif (strcmp($status, "PAYMENT_CAPTURED") == 0 || strcmp($status, "PAYMENT_EUROLINE_WAIT_CAPTURE") == 0 || strcmp($status, "PAYMENT_EUROLINE_WAIT_CREDIT") == 0) {
            return Mage::helper('epay')->__("Captured");
        } elseif (strcmp($status, "PAYMENT_DELETED") == 0) {
            return Mage::helper('epay')->__("Deleted");
        } else {
            return Mage::helper('epay')->__("Unknown");
        }
    }

    //
    // Retrieves the transaction status from ePay
    //
    public function getTransactionStatus($tid, $fraud, $paymentobj, $order)
    {
        $res = "<tr><td colspan='2'><br><b>" . Mage::helper('epay')->__("Current payment system transaction status") . "</b></td></tr>";
        try {
            $storeId = $order->getStoreId();

            $param = array(
                'merchantnumber' => $paymentobj->getConfigData('merchantnumber', $storeId),
                'transactionid' => $tid,
                'epayresponse' => 0,
                'pwd' =>  $paymentobj->getRemotePassword($storeId)
            );

            $client = new SoapClient('https://ssl.ditonlinebetalingssystem.dk/remote/payment.asmx?WSDL');
            $result = $client->gettransaction($param);

            if ($result->gettransactionResult === true) {
                if ($fraud > 0) {
                    $res .= "<tr><td>" . __("Fraud status") . ":</td>";
                    $res .= "<td>" .$result->transactionInformation->fraudStatus. "</td></tr>";

                    $res .= "<tr><td>" . __("Payer country code") . ":</td>";
                    $res .= "<td>" .$result->transactionInformation->payerCountryCode. "</td></tr>";

                    $res .= "<tr><td>" . __("Issued country code") . ":</td>";
                    $res .= "<td>" .$result->transactionInformation->issuedCountryCode. "</td></tr>";
                    if (isset($result->transactionInformation->FraudMessage)) {
                        $res .= "<tr><td>" . __("Fraud message") . ":</td>";
                        $res .= "<td>" .$result->transactionInformation->FraudMessage. "</td></tr>";
                    }
                }

                $res .= "<tr><td>" . Mage::helper('epay')->__("Transaction status") . ":</td>";
                $res .= "<td>" . $this->translatePaymentStatus($result->transactionInformation->status) . "</td></tr>";

                if (strcmp($result->transactionInformation->status, "PAYMENT_DELETED") == 0) {
                    $res .= "<tr><td>" . Mage::helper('epay')->__("Deleted date") . ":</td>";
                    $res .= "<td>" . str_replace("T", " ", $result->transactionInformation->deleteddate) . "</td></tr>";
                }

                $res .= "<tr><td>" . Mage::helper('epay')->__("Order number") . ":</td>";
                $res .= "<td>" . $result->transactionInformation->orderid . "</td></tr>";

                $res .= "<tr><td>" . Mage::helper('epay')->__("Acquirer") . ":</td>";
                $res .= "<td>" . $result->transactionInformation->acquirer . "</td></tr>";

                $res .= "<tr><td>" . Mage::helper('epay')->__("Currency code") . ":</td>";
                $res .= "<td>" . $result->transactionInformation->currency . "</td></tr>";

                $res .= "<tr><td>" . Mage::helper('epay')->__("Splitpayment") . ":</td>";
                $res .= "<td>" . ($result->transactionInformation->splitpayment ? Mage::helper('epay')->__("Yes") : Mage::helper('epay')->__("No")) . "</td></tr>";

                $res .= "<tr><td>" . Mage::helper('epay')->__("3D Secure") . ":</td>";
                $res .= "<td>" . ($result->transactionInformation->msc ? Mage::helper('epay')->__("Yes") : Mage::helper('epay')->__("No")) . "</td></tr>";

                $res .= "<tr><td>" . Mage::helper('epay')->__("Description") . ":</td>";
                $res .= "<td>" . $result->transactionInformation->description . "</td></tr>";

                $res .= "<tr><td>" . Mage::helper('epay')->__("Cardholder") . ":</td>";
                $res .= "<td>" . $result->transactionInformation->cardholder . "</td></tr>";

                $res .= "<tr><td>" . Mage::helper('epay')->__("Auth amount") . ":</td>";
                $res .= "<td>" . $order->getBaseCurrencyCode() . "&nbsp;" . number_format(((int)$result->transactionInformation->authamount) / 100, 2, ',', ' ') . "&nbsp;&nbsp;&nbsp;" . (((int)$result->transactionInformation->authamount) > 0 ? str_replace("T", " ", $result->transactionInformation->authdate) : "") . "</td></tr>";

                $res .= "<tr><td>" . Mage::helper('epay')->__("Captured amount") . ":</td>";
                $res .= "<td>" . $order->getBaseCurrencyCode() . "&nbsp;" . number_format(((int)$result->transactionInformation->capturedamount) / 100, 2, ',', ' ') . "&nbsp;&nbsp;&nbsp;" . (((int)$result->transactionInformation->capturedamount) > 0 ? str_replace("T", " ", $result->transactionInformation->captureddate) : "") . "</td></tr>";

                $res .= "<tr><td>" . Mage::helper('epay')->__("Credited amount") . ":</td>";
                $res .= "<td>" . $order->getBaseCurrencyCode() . "&nbsp;" . number_format(((int)$result->transactionInformation->creditedamount) / 100, 2, ',', ' ') . "&nbsp;&nbsp;&nbsp;" . (((int)$result->transactionInformation->creditedamount) > 0 ? str_replace("T", " ", $result->transactionInformation->crediteddate) : "") . "</td></tr>";

                $res .= "<tr><td>" . Mage::helper('epay')->__("Surcharge fee") . ":</td>";
                $res .= "<td>" . $order->getBaseCurrencyCode() . "&nbsp;" . number_format(((int)$result->transactionInformation->fee) / 100, 2, ',', ' ') . "</td></tr>";

                if (isset($result->transactionInformation->history) && isset($result->transactionInformation->history->TransactionHistoryInfo) && count($result->transactionInformation->history->TransactionHistoryInfo) > 0) {
                    //
                    // Important to convert this item to array. If only one item is to be found in the array of history items
                    // the object will be handled as non-array but object only.
                    $historyArray = $result->transactionInformation->history->TransactionHistoryInfo;
                    if (count($result->transactionInformation->history->TransactionHistoryInfo) == 1) {
                        $historyArray = array($result->transactionInformation->history->TransactionHistoryInfo);
                        // convert to array
                    }
                    $res .= "<tr><td colspan='2'><br><br><b>" . Mage::helper('epay')->__("History") . "</b></td></tr>";
                    for ($i = 0; $i < count($historyArray); $i++) {
                        $res .= "<tr><td>" . str_replace("T", " ", $historyArray[$i]->created) . "</td>";
                        $res .= "<td>";
                        if (strlen($historyArray[$i]->username) > 0) {
                            $res .= ($historyArray[$i]->username . ": ");
                        }
                        $res .= $historyArray[$i]->eventMsg . "</td></tr>";
                    }
                }
            } else {
                if ($result->epayresponse != -1) {
                    if ($result->epayresponse == -1019) {
                        $res .= "<tr><td colspan='2'>" . Mage::helper('epay')->__("Invalid password used for webservice access!"). "</td>";
                    } else {
                        $res .= "<tr><td colspan='2'>" . $paymentobj->getEpayErrorText($result->epayresponse) . "</td>";
                    }
                } else {
                    $res .= "<tr><td colspan='2'>" . Mage::helper('epay')->__("Unknown error!") . "</td>";
                }
            }
        } catch (Exception $e) {
            $res .= "<tr><td colspan='2'>" . Mage::helper('epay')->__("An error occured in the communication to the payment system") ." - ". $e->getMessage(). "</td>";
        }

        return $res;
    }

    /**
     * Create html for paymentLogoUrl
     *
     * @param mixed $paymentId
     * @return string
     */
    private function getPaymentLogoUrl($paymentId)
    {
        return '<img class="epay_paymentcard" src="https://d25dqh6gpkyuw6.cloudfront.net/paymentlogos/external/'.$paymentId . '.png"';
    }

    /**
     * ######################## TAB settings #################################
     */
    public function getTabLabel()
    {
        return 'ePay';
    }
    public function getTabTitle()
    {
        return Mage::helper('epay')->__('ePay Payment Information');
    }
    public function canShowTab()
    {
        /** @var Mage_Sales_Model_Order_Payment $payment */
        $payment = $this->getOrder()->getPayment();
        if ($payment->getMethod() === 'epay_standard' && $this->isRemoteInterfaceEnabled()) {
            return true;
        }
        return false;
    }
    public function isHidden()
    {
        return false;
    }
}
