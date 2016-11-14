<?php
/**
 * Copyright ePay | Dit Online Betalingssystem, (c) 2010.
 * This program is free software. You are allowed to use the software but NOT allowed to modify the software.
 * It is also not legal to do any changes to the software and distribute it in your own name / brand.
 */
class Mage_Epay_Block_Adminhtml_Sales_Order_View_Tab_Info extends Mage_Adminhtml_Block_Sales_Order_View_Tab_Info implements Mage_Adminhtml_Block_Widget_Tab_Interface
{
    /**
     * Retrieve order model instance
     *
     * @return Mage_Sales_Model_Order
     */
    public function getOrder()
    {
		return parent::getOrder();
    }

    /**
     * Retrieve source model instance
     *
     * @return Mage_Sales_Model_Order
     */
    public function getSource()
    {
        return parent::getOrder();
    }

    /**
     * Retrieve order totals block settings
     *
     * @return array
     */
    public function getOrderTotalData()
    {
        return parent::getOrderTotalData();
    }

    public function getOrderInfoData()
    {
        return parent::getOrderInfoData();
    }

    public function getTrackingHtml()
    {
        return parent::getTrackingHtml();
    }

    public function getItemsHtml()
    {
        return $this->getChildHtml('order_items');
    }

    /**
     * Retrive giftmessage block html
     *
     * @return string
     */
    public function getGiftmessageHtml()
    {
        return parent::getGiftmessageHtml();
    }

    public function getPaymentHtml()
    {
    	$res = $this->getChildHtml('order_payment');

    	$read = Mage::getSingleton('core/resource')->getConnection('core_read');
    	$row = $read->fetchRow("select * from epay_order_status where orderid = '" . $this->getOrder()->getIncrementId() . "'");


    	if ($row['status'] == '1')
        {
            $order = $this->getOrder();

    		$res .= "<table border='0' width='100%'>";
    		$res .= "<tr><td colspan='2'><b>" . Mage::helper('epay')->__('EPAY_LABEL_18') . "</b></td></tr>";
    		if ($row['tid'] != '0')
            {
    			$res .= "<tr><td width='150'>" . Mage::helper('epay')->__('EPAY_LABEL_19') . "</td>";
    			$res .= "<td>" . $row['tid'] . "</td></tr>";
    		}
    		if ($row['amount'] != '0')
            {
    			$res .= "<tr><td>" . Mage::helper('epay')->__('EPAY_LABEL_20') . "</td>";
    			$res .= "<td>" . $order->getBaseCurrencyCode() . "&nbsp;" . number_format(((int)$row['amount']) / 100, 2, ',', ' ') . "</td></tr>";
    		}
    		if ($row['cur'] != '0')
            {
    			$res .= "<tr><td>" . Mage::helper('epay')->__('EPAY_LABEL_21') . "</td>";
    			$res .= "<td>" . $row['cur'] . "</td></tr>";
    		}
    		if ($row['date'] != '0')
            {
    			$res .= "<tr><td>" . Mage::helper('epay')->__('EPAY_LABEL_22') . "</td>";
    			$res .= "<td>" . $row['date'] . "</td></tr>";
    		}
    		if ($row['eKey'] != '0')
            {
    			$res .= "<tr><td>" . Mage::helper('epay')->__('EPAY_LABEL_23') . "</td>";
    			$res .= "<td>" . $row['eKey'] . "</td></tr>";
    		}
    		if ($row['fraud'] != '0')
            {
    			$res .= "<tr><td>" . Mage::helper('epay')->__('EPAY_LABEL_24') . "</td>";
    			$res .= "<td>" . sprintf(Mage::helper('epay')->__('EPAY_LABEL_30'), $row['fraud']) . "</td></tr>";
    		}
    		if ($row['subscriptionid'] != '0')
            {
    			$res .= "<tr><td>" . Mage::helper('epay')->__('EPAY_LABEL_25') . "</td>";
    			$res .= "<td>" . $row['subscriptionid'] . "</td></tr>";
    		}
    		if ($row['cardid'] != '0')
            {
    			$res .= "<tr><td>" . Mage::helper('epay')->__('EPAY_LABEL_26') . "</td>";
    			$res .= "<td>" . $this->printLogo($row['cardid']) . "</td></tr>";
    		}
    		if (strlen($row['cardnopostfix']) != 0)
            {
    			$res .= "<tr><td>" . Mage::helper('epay')->__('EPAY_LABEL_101') . "</td>";
    			$res .= "<td>" . $row['cardnopostfix'] . "</td></tr>";
    		}
    		if ($row['transfee'] != '0')
            {
    			$res .= "<tr><td>" . Mage::helper('epay')->__('EPAY_LABEL_27') . "</td>";
    			$res .= "<td>" . $order->getBaseCurrencyCode() . "&nbsp;" . number_format(((int)$row['transfee']) / 100, 2, ',', ' ') . "</td></tr>";
    		}

    		$method = Mage::getModel('epay/standard');

    		if ($method->getConfigData('remoteinterface', $order ? $order->getStoreId() : null) == 1)
            {
    			$res .= $this->getTransactionStatus($row['tid'], $method);
    		}

    		$res .= "</table><br>";

    		$res .= "<a href='https://admin.ditonlinebetalingssystem.dk/admin' target='_blank'>" . Mage::helper('epay')->__('EPAY_LABEL_29') . "</a>";
    		$res .= "<br><br>";

    	}
		else
		{
			$res .= "<br>" . Mage::helper('epay')->__('EPAY_LABEL_28') . "<br>";
		}

		return $res;
    }

    //
    // Translate the current ePay transaction status
    //
    public function translatePaymentStatus($status)
    {
		if(strcmp($status, "PAYMENT_NEW") == 0)
		{
			return Mage::helper('epay')->__('EPAY_LABEL_42');
		}
		elseif (strcmp($status, "PAYMENT_CAPTURED") == 0 || strcmp($status, "PAYMENT_EUROLINE_WAIT_CAPTURE") == 0 || strcmp($status, "PAYMENT_EUROLINE_WAIT_CREDIT") == 0)
		{
			return Mage::helper('epay')->__('EPAY_LABEL_41');
		}
		elseif (strcmp($status, "PAYMENT_DELETED") == 0)
		{
			return Mage::helper('epay')->__('EPAY_LABEL_40');
		}
		else
		{
			return Mage::helper('epay')->__('EPAY_LABEL_43');
		}
    }

    //
    // Retrieves the transaction status from ePay
    //
    public function getTransactionStatus($tid, $paymentobj)
    {
        $res = "<tr><td colspan='2'><br><b>" . Mage::helper('epay')->__('EPAY_LABEL_44') . "</b></td></tr>";
        try
		{
            $param = array
            (
                'merchantnumber' => $paymentobj->getConfigData('merchantnumber', $this->getOrder() ? $this->getOrder()->getStoreId() : null),
                'transactionid' => $tid,
                'epayresponse' => 0,
                'pwd' => $paymentobj->getConfigData('remoteinterfacepassword', $this->getOrder() ? $this->getOrder()->getStoreId() : null)
            );

			$client = new SoapClient('https://ssl.ditonlinebetalingssystem.dk/remote/payment.asmx?WSDL');

            $result = $client->gettransaction($param);

            if ($result->gettransactionResult == 1)
            {
                $res .= "<tr><td>" . Mage::helper('epay')->__('EPAY_LABEL_45') . ":</td>";
                $res .= "<td>" . $this->translatePaymentStatus($result->transactionInformation->status) . "</td></tr>";

                if(strcmp($result->transactionInformation->status, "PAYMENT_DELETED") == 0)
                {
                    $res .= "<tr><td>" . Mage::helper('epay')->__('EPAY_LABEL_46') . ":</td>";
                    $res .= "<td>" . str_replace("T", " ", $result->transactionInformation->deleteddate) . "</td></tr>";
                }

                $res .= "<tr><td>" . Mage::helper('epay')->__('EPAY_LABEL_7') . ":</td>";
                $res .= "<td>" . $result->transactionInformation->orderid . "</td></tr>";

                $res .= "<tr><td>" . Mage::helper('epay')->__('EPAY_LABEL_47') . ":</td>";
                $res .= "<td>" . $result->transactionInformation->acquirer . "</td></tr>";

                $res .= "<tr><td>" . Mage::helper('epay')->__('EPAY_LABEL_21') . ":</td>";
                $res .= "<td>" . $result->transactionInformation->currency . "</td></tr>";

                $res .= "<tr><td>" . Mage::helper('epay')->__('EPAY_LABEL_48') . ":</td>";
                $res .= "<td>" . ($result->transactionInformation->splitpayment ? Mage::helper('epay')->__('EPAY_LABEL_50') : Mage::helper('epay')->__('EPAY_LABEL_51')) . "</td></tr>";

                $res .= "<tr><td>" . Mage::helper('epay')->__('EPAY_LABEL_49') . ":</td>";
                $res .= "<td>" . ($result->transactionInformation->msc ? Mage::helper('epay')->__('EPAY_LABEL_50') : Mage::helper('epay')->__('EPAY_LABEL_51')) . "</td></tr>";

                $res .= "<tr><td>" . Mage::helper('epay')->__('EPAY_LABEL_52') . ":</td>";
                $res .= "<td>" . $result->transactionInformation->description . "</td></tr>";

                $res .= "<tr><td>" . Mage::helper('epay')->__('EPAY_LABEL_53') . ":</td>";
                $res .= "<td>" . $result->transactionInformation->cardholder . "</td></tr>";

                $res .= "<tr><td>" . Mage::helper('epay')->__('EPAY_LABEL_54') . ":</td>";
                $res .= "<td>" . $this->getOrder()->getBaseCurrencyCode() . "&nbsp;" . number_format(((int)$result->transactionInformation->authamount) / 100, 2, ',', ' ') . "&nbsp;&nbsp;&nbsp;" . (((int)$result->transactionInformation->authamount) > 0 ? str_replace("T", " ", $result->transactionInformation->authdate) : "") . "</td></tr>";

                $res .= "<tr><td>" . Mage::helper('epay')->__('EPAY_LABEL_55') . ":</td>";
                $res .= "<td>" . $this->getOrder()->getBaseCurrencyCode() . "&nbsp;" . number_format(((int)$result->transactionInformation->capturedamount) / 100, 2, ',', ' ') . "&nbsp;&nbsp;&nbsp;" . (((int)$result->transactionInformation->capturedamount) > 0 ? str_replace("T", " ", $result->transactionInformation->captureddate) : "") . "</td></tr>";

                $res .= "<tr><td>" . Mage::helper('epay')->__('EPAY_LABEL_56') . ":</td>";
                $res .= "<td>" . $this->getOrder()->getBaseCurrencyCode() . "&nbsp;" . number_format(((int)$result->transactionInformation->creditedamount) / 100, 2, ',', ' ') . "&nbsp;&nbsp;&nbsp;" . (((int)$result->transactionInformation->creditedamount) > 0 ? str_replace("T", " ", $result->transactionInformation->crediteddate) : "") . "</td></tr>";

                $res .= "<tr><td>" . Mage::helper('epay')->__('EPAY_LABEL_39') . ":</td>";
                $res .= "<td>" . $this->getOrder()->getBaseCurrencyCode() . "&nbsp;" . number_format(((int)$result->transactionInformation->fee) / 100, 2, ',', ' ') . "</td></tr>";

                if(isset($result->transactionInformation->history) && isset($result->transactionInformation->history->TransactionHistoryInfo) && count($result->transactionInformation->history->TransactionHistoryInfo) > 0)
                {
                    //
                    // Important to convert this item to array. If only one item is to be found in the array of history items
                    // the object will be handled as non-array but object only.
                    $historyArray = $result->transactionInformation->history->TransactionHistoryInfo;
                    if(count($result->transactionInformation->history->TransactionHistoryInfo) == 1)
                    {
                        $historyArray = array($result->transactionInformation->history->TransactionHistoryInfo);
                        // convert to array
                    }
                    $res .= "<tr><td colspan='2'><br><br><b>" . Mage::helper('epay')->__('EPAY_LABEL_76') . "</b></td></tr>";
                    for($i = 0; $i < count($historyArray); $i++)
                    {
                        $res .= "<tr><td>" . str_replace("T", " ", $historyArray[$i]->created) . "</td>";
                        $res .= "<td>";
                        if(strlen($historyArray[$i]->username) > 0)
                        {
                            $res .= ($historyArray[$i]->username . ": ");
                        }
                        $res .= $historyArray[$i]->eventMsg . "</td></tr>";
                    }
                }
            }
            else
            {
                if($result->epayresponse != -1)
                {
                    if($result->epayresponse == -1019)
                    {
                        $res .= "<tr><td colspan='2'>" . Mage::helper('epay')->__('Invalid password used for webservice access!'). "</td>";
                    }
                    else
                    {
                        $res .= "<tr><td colspan='2'>" . $paymentobj->getEpayErrorText($result->epayresponse) . "</td>";
                    }
                }
                else
                {
                    $res .= "<tr><td colspan='2'>" . Mage::helper('epay')->__('Unknown error!') . "</td>";
                }
            }
        }
		catch (Exception $e)
		{
            $res .= "<tr><td colspan='2'>" . Mage::helper('epay')->__('EPAY_LABEL_57') ." - ". $e->getMessage(). "</td>";
		}

		return $res;
    }

    public function printLogo($cardid) {
    	$res = '<img src="';
		$res .= $this->getSkinUrl('images/epay/'. $cardid .'.png');
    	$res .= '" border="0" />';
    	return $res;
    }

    public function getViewUrl($orderId)
    {
		return $orderId;
    }

    /**
     * ######################## TAB settings #################################
     */
    public function getTabLabel()
    {
        return Mage::helper('sales')->__('Information');
    }

    public function getTabTitle()
    {
        return Mage::helper('sales')->__('Order Information');
    }

    public function canShowTab()
    {
        return true;
    }

    public function isHidden()
    {
        return false;
    }
}