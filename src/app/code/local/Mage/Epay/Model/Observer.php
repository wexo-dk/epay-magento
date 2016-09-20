<?php
/**
 * Copyright ePay | Dit Online Betalingssystem, (c) 2010.
 * This program is free software. You are allowed to use the software but NOT allowed to modify the software. 
 * It is also not legal to do any changes to the software and distribute it in your own name / brand. 
 */
 
class Mage_Epay_Model_Observer
{
    public function autocancelPendingOrders()
    {
		$payment = Mage::getModel('epay/standard');
		$date = Mage::getSingleton('core/date');
		
		echo $date->gmtDate();
		
		if($payment->getConfigData('use_auto_cancel', null))
		{
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
				$orderModel = Mage::getModel('sales/order');
				$orderModel->load($order["entity_id"]);
				
				try
				{
					$read = Mage::getSingleton('core/resource')->getConnection('core_read');
					$row = $read->fetchRow("select * from epay_order_status where orderid = '" . $orderModel->getIncrementId() . "'");
					
					if($row["status"] == '0')
					{
						if(!$orderModel->canCancel())
							continue;
						
						$orderModel->cancel();
						$orderModel->save();
						
						$orderModel->addStatusToHistory($orderModel->getStatus(), "Order was auto canceled because no payment has been made.");
						$orderModel->save();
					}
				}
				catch(Exception $e)
				{
					echo "Could not be canceled: " . $e->getMessage();
				}
			}
		}
    }
}