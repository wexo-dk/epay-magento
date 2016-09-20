<?php
/**
 * Copyright ePay | Dit Online Betalingssystem, (c) 2010.
 * This program is free software. You are allowed to use the software but NOT allowed to modify the software. 
 * It is also not legal to do any changes to the software and distribute it in your own name / brand. 
 */

class Mage_Epay_Block_Sales_Order_Print extends Mage_Sales_Block_Order_Print
{
    protected function _prepareLayout()
    {
        if ($headBlock = $this->getLayout()->getBlock('head')) {
            $headBlock->setTitle($this->__('Print Order # %s', $this->getOrder()->getRealOrderId()));
        }
        $this->setChild(
            'payment_info',
            $this->helper('payment')->getInfoBlock($this->getOrder()->getPayment())
        );
    }

    public function getPaymentInfoHtml()
    {
        //return $this->getChildHtml('payment_info');
        
        $res = $this->getChildHtml('payment_info');
        
        //
				// Read info directly from the database   	
    		$read = Mage::getSingleton('core/resource')->getConnection('core_read');
    		$row = $read->fetchRow("select * from epay_order_status where orderid = '" . $this->getOrder()->getIncrementId() . "'");
    		$standard = Mage::getModel('epay/standard');
    		
    		if ($row['status'] == '1') {
	    		//
	    		// Payment has been made to this order
	    		$res .= "<table border='0' width='100%'>";
	    		if ($row['tid'] != '0') {
	    			$res .= "<tr><td>" . Mage::helper('epay')->__('EPAY_LABEL_19') . "</td>";
	    			$res .= "<td>" . $row['tid'] . "</td></tr>";
	    		}
	    		if ($row['cardid'] != '0') {
	    			$res .= "<tr><td>" . Mage::helper('epay')->__('EPAY_LABEL_26') . "</td>";
	    			$res .= "<td>" . $this->printLogo($row['cardid']) . "</td></tr>";
	    		}
	    		if (strlen($row['cardnopostfix']) != 0) {
	    			$res .= "<tr><td>" . Mage::helper('epay')->__('EPAY_LABEL_101') . "</td>";
	    			$res .= "<td>XXXX XXXX XXXX " . $row['cardnopostfix'] . "</td></tr>";
	    		}
	    		if ($row['transfee'] != '0') {
	    			$res .= "<tr><td>" . Mage::helper('epay')->__('EPAY_LABEL_27') . "</td>";
	    			$res .= "<td>" . $this->getOrder()->getBaseCurrencyCode() . "&nbsp;" . number_format(((int)$row['transfee']) / 100, 2, ',', ' ') . "</td></tr>";
	    		}
	    		$res .= "</table><br>";
	    		
	    	} else {
	    		$res .= "<br>" . Mage::helper('epay')->__('EPAY_LABEL_28') . "<br>";
	    	}
    	
    	return $res;
    }
    
    public function printLogo($cardid) {
    	$res = '<img src="';
    	
    	switch($cardid) {
    		case '1': {
    			$res .= $this->getSkinUrl('images/epay/dankort.gif'); break;
    		}
    		case '2': {
    			$res .= $this->getSkinUrl('images/epay/dankort.gif'); break;
    		}
    		case '3': {
    			$res .= $this->getSkinUrl('images/epay/visaelectron.gif'); break;
    		}
    		case '4': {
    			$res .= $this->getSkinUrl('images/epay/mastercard.gif'); break;
    		}
    		case '5': {
    			$res .= $this->getSkinUrl('images/epay/mastercard.gif'); break;
    		}
    		case '6': {
    			$res .= $this->getSkinUrl('images/epay/visaelectron.gif'); break;
    		}
    		case '7': {
    			$res .= $this->getSkinUrl('images/epay/jcb.gif'); break;
    		}
    		case '8': {
    			$res .= $this->getSkinUrl('images/epay/diners.gif'); break;
    		}
    		case '9': {
    			$res .= $this->getSkinUrl('images/epay/maestro.gif'); break;
    		}
    		case '10': {
    			$res .= $this->getSkinUrl('images/epay/amex.gif'); break;
    		}
    		case '12': {
    			$res .= $this->getSkinUrl('images/epay/edankort.gif'); break;
    		}
    		case '13': {
    			$res .= $this->getSkinUrl('images/epay/diners.gif'); break;
    		}
    		case '14': {
    			$res .= $this->getSkinUrl('images/epay/amex.gif'); break;
    		}
    		case '15': {
    			$res .= $this->getSkinUrl('images/epay/maestro.gif'); break;
    		}
    		case '16': {
    			$res .= $this->getSkinUrl('images/epay/forbrugsforeningen.gif'); break;
    		}
    		case '17': {
    			$res .= $this->getSkinUrl('images/epay/ewire.gif'); break;
    		}
    		case '18': {
    			$res .= $this->getSkinUrl('images/epay/visa.gif'); break;
    		}
    		case '24': {
    			$res .= $this->getSkinUrl('images/epay/mastercard.gif'); break;
    		}
    		case '25': {
    			$res .= $this->getSkinUrl('images/epay/mastercard.gif'); break;
    		}
    	}
    	$res .= '" border="0" />';
    	return $res;
    }

    public function getOrder()
    {
        return Mage::registry('current_order');
    }

    protected function _prepareItem(Mage_Core_Block_Abstract $renderer)
    {
        $renderer->setPrintStatus(true);

        return parent::_prepareItem($renderer);
    }

}

