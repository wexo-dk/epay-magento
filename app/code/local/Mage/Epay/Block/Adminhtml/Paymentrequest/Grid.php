<?php
/**
 * Copyright ePay | Dit Online Betalingssystem, (c) 2016.
 *
 * This program is free software. You are allowed to use the software but NOT allowed to modify the software.
 * It is also not legal to do any changes to the software and distribute it in your own name / brand.
 */

class Mage_Epay_Block_Adminhtml_Paymentrequest_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
    public function __construct()
    {
        parent::__construct();
        $this->setId('paymentrequest_grid');
        $this->setDefaultSort('paymentrequestid');
        $this->setDefaultDir('desc');
        $this->setSaveParametersInSession(true);
    }

    protected function _prepareCollection()
    {
        $collection = Mage::getModel('epay/paymentrequest')->getCollection()->setOrder('id', 'desc');
		$collection->getSelect()->where('status = ?', '1');

        $this->setCollection($collection);
        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        $this->addColumn('paymentrequestid', array(
            'header'    => Mage::helper('epay')->__("ID"),
            'align'     => 'right',
            'width'     => '150px',
            'index'     => 'paymentrequestid',
        ));

		$this->addColumn('created', array(
            'header'    => Mage::helper('epay')->__("Date"),
            'align'     => 'right',
            'width'     => '150px',
            'index'     => 'created',
			'type' 		=> 'datetime',
        ));

        $this->addColumn('orderid', array(
            'header'    => Mage::helper('epay')->__("Order #"),
            'align'     => 'left',
            'index'     => 'orderid',
        ));

        $this->addColumn('amount', array(
            'header'    => Mage::helper('epay')->__("Amount"),
            'align'     => 'left',
            'index'     => 'amount',
			'renderer'	=> new Mage_Epay_Block_Adminhtml_Paymentrequest_Renderer_Amount//divide by 100
        ));

        $this->addColumn('receiver', array(
            'header'    => Mage::helper('epay')->__("Receiver"),
            'align'     => 'left',
            'index'     => 'receiver',
        ));

		$yesNoOptions = array('0' => Mage::helper('epay')->__("No"), '1' => Mage::helper('epay')->__("Yes"));

		$this->addColumn('ispaid', array(
            'header'    => Mage::helper('epay')->__("Is Paid"),
            'align'     => 'left',
            'index'     => 'ispaid',
			'type'      => 'options',
			'options'   => $yesNoOptions,
        ));

        return parent::_prepareColumns();
    }

    public function getRowUrl($row)
    {
        return $this->getUrl('*/*/view', array('id' => $row->getId()));
    }
}