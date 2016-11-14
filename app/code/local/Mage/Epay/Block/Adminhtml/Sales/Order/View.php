<?php
class Mage_Epay_Block_Adminhtml_Sales_Order_View extends Mage_Adminhtml_Block_Sales_Order_View {
    public function  __construct() {

		$read = Mage::getSingleton('core/resource')->getConnection('core_read');
    	$row = $read->fetchRow("select * from epay_order_status where orderid = '" . $this->getOrder()->getIncrementId() . "'");

    	if (!$row || $row['status'] == '0')
        {
    		$this->_addButton('button_sendpaymentrequest', array('label' => Mage::helper('epay')->__("Create payment request"), 'onclick' => 'setLocation(\'' . Mage::helper("adminhtml")->getUrl('adminhtml/paymentrequest/create/', array('id' => $this->getOrder()->getRealOrderId())) . '\')', 'class' => 'scalable go'), 0, 100, 'header', 'header');
		}

		parent::__construct();
    }
}