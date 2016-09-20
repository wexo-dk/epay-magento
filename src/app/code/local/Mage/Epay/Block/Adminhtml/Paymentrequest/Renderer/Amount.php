<?php
class Mage_Epay_Block_Adminhtml_Paymentrequest_Renderer_Amount extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{
	public function render(Varien_Object $row)
	{
		$data = $row->getData();
		
		$formattedPrice = Mage::app()->getLocale()->currency($data["currency_code"])->toCurrency(($row->getData($this->getColumn()->getIndex()) / 100));
		
		//$formattedPrice = Mage::helper('core')->currency(($row->getData($this->getColumn()->getIndex()) / 100), true, false);
		
		$value = $formattedPrice;
		return $value;
	}
}
?>