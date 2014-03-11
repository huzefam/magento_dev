<?php

class MDN_ExternalLogistic_Block_Sources_Grid extends Mage_Adminhtml_Block_Widget_Grid
{

    public function __construct()
    {
        parent::__construct();
        $this->setId('externalLogisticSourcesGrid');
        $this->setDefaultSort('elsrc_name');
        $this->setDefaultDir('desc');
        $this->setVarNameFilter('external_logistic_sources');
        $this->_parentTemplate = $this->getTemplate();
        $this->setEmptyText(Mage::helper('ExternalLogistic')->__('No sources'));
    }

    protected function _prepareCollection()
    {
        $collection = Mage::getModel('ExternalLogistic/Sources')
        		->getCollection();
        		
        $this->setCollection($collection);
        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        $this->addColumn('elsrc_id',
            array(
                'header'=> Mage::helper('ExternalLogistic')->__('Id'),
                'index' => 'elsrc_id',
        ));
        
        $this->addColumn('elsrc_name',
            array(
                'header'=> Mage::helper('ExternalLogistic')->__('Name'),
                'index' => 'elsrc_name',
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
    	return $this->getUrl('ExternalLogistic/Sources/Edit', array('elsrc_id' => $row->getId()));
    }

    
}
