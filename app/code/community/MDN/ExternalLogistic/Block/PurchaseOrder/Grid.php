<?php

class MDN_ExternalLogistic_Block_PurchaseOrder_Grid extends Mage_Adminhtml_Block_Widget_Grid
{

    public function __construct()
    {
        parent::__construct();
        $this->setId('purchaseOrderGrid');

        $this->_parentTemplate = $this->getTemplate();
        $this->setEmptyText(Mage::helper('ExternalLogistic')->__('No items'));
    }

    protected function _prepareCollection()
    {
        //build collection depending of magento version
        $collection = Mage::getModel('Purchase/Order')
        	->getCollection()
        	->join('Purchase/Supplier',
			           'po_sup_num=sup_id');
        $this->setCollection($collection);
        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
      $this->addColumn('po_order_id', array(
            'header'=> Mage::helper('purchase')->__('Ref'),
            'index' => 'po_order_id',
        ));

        $this->addColumn('po_date', array(
            'header'=> Mage::helper('purchase')->__('Date'),
            'index' => 'po_date',
            'type'	=> 'date'
        ));

        $this->addColumn('sup_name', array(
            'header'=> Mage::helper('purchase')->__('Supplier'),
            'index' => 'sup_name',
        ));

        $this->addColumn('po_status', array(
            'header'=> Mage::helper('purchase')->__('Status'),
            'index' => 'po_status',
            'type' => 'options',
            'options' => mage::getModel('Purchase/Order')->getStatuses(),
            'align'	=> 'right'
        ));

        $this->addColumn('amount', array(
            'header'=> Mage::helper('purchase')->__('Amount'),
            'index' => 'amount',
            'renderer'  => 'MDN_Purchase_Block_Widget_Column_Renderer_OrderAmount',
            'align' => 'right',
            'filter'    => false,
            'sortable'  => false

        ));

        $this->addColumn('sent_to_logistic_company', array(
            'header'=> Mage::helper('ExternalLogistic')->__('Sent to Logistic'),
            'index' => 'sent_to_logistic_company',
            'align' => 'center',
            'type' => 'options',
            'options' => array(
                '1' => Mage::helper('ExternalLogistic')->__('Yes'),
                '0' => Mage::helper('ExternalLogistic')->__('No'),
				)
        ));



        return parent::_prepareColumns();
    }


    public function getGridParentHtml()
    {
        $templateName = Mage::getDesign()->getTemplateFilename($this->_parentTemplate, array('_relative'=>true));
        return $this->fetchView($templateName);
    }

    public function getRowUrl($row)
    {
    	return $this->getUrl('Purchase/Orders/Edit', array('po_num' => $row->getId()));
    }

}
