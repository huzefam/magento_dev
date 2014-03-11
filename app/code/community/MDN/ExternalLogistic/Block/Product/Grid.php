<?php

class MDN_ExternalLogistic_Block_Product_Grid extends Mage_Adminhtml_Block_Widget_Grid
{

    public function __construct()
    {
        parent::__construct();
        $this->setId('catalogProductGrid');

        $this->_parentTemplate = $this->getTemplate();
        $this->setEmptyText(Mage::helper('ExternalLogistic')->__('No items'));
    }

    protected function _prepareCollection()
    {
        //build collection depending of magento version
        $collection = mage::getModel('catalog/product')
                            ->getCollection()
                            ->addAttributeToSelect('name');
        $this->setCollection($collection);
        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        $this->addColumn('entity_id',
            array(
                'header'=> Mage::helper('catalog')->__('ID'),
                'width' => '50px',
                'type'  => 'number',
                'index' => 'entity_id',
        ));
        $this->addColumn('name',
            array(
                'header'=> Mage::helper('catalog')->__('Name'),
                'index' => 'name',
        ));

        $this->addColumn('type',
            array(
                'header'=> Mage::helper('catalog')->__('Type'),
                'width' => '60px',
                'index' => 'type_id',
                'type'  => 'options',
                'options' => Mage::getSingleton('catalog/product_type')->getOptionArray(),
        ));

        $sets = Mage::getResourceModel('eav/entity_attribute_set_collection')
            ->setEntityTypeFilter(Mage::getModel('catalog/product')->getResource()->getTypeId())
            ->load()
            ->toOptionHash();

        $this->addColumn('set_name',
            array(
                'header'=> Mage::helper('catalog')->__('Attrib. Set Name'),
                'width' => '100px',
                'index' => 'attribute_set_id',
                'type'  => 'options',
                'options' => $sets,
        ));

        $this->addColumn('sku',
            array(
                'header'=> Mage::helper('catalog')->__('SKU'),
                'width' => '80px',
                'index' => 'sku',
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
    	return $this->getUrl('adminhtml/catalog_product/edit', array('id' => $row->getId()));
    }

}
