<?php

class MDN_ExternalLogistic_Block_Streams_Grid extends Mage_Adminhtml_Block_Widget_Grid
{

    public function __construct()
    {
        parent::__construct();
        $this->setId('externalLogisticStrealsGrid');
        $this->setDefaultSort('els_name');
        $this->setDefaultDir('desc');
        $this->setVarNameFilter('external_logistic_streams');
        $this->_parentTemplate = $this->getTemplate();
        $this->setEmptyText(Mage::helper('ExternalLogistic')->__('No streams'));
    }

    protected function _prepareCollection()
    {
        $collection = Mage::getModel('ExternalLogistic/Streams')
        		->getCollection();
        		
        $this->setCollection($collection);
        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {        
        $this->addColumn('els_name',
            array(
                'header'=> Mage::helper('ExternalLogistic')->__('Name'),
                'index' => 'els_name',
        ));

        $this->addColumn('els_description',
            array(
                'header'=> Mage::helper('ExternalLogistic')->__('Description'),
                'index' => 'els_description',
        ));
        
        $this->addColumn('els_direction',
            array(
                'header'=> Mage::helper('ExternalLogistic')->__('Direction'),
                'index' => 'els_direction',
                'type'	=> 'options',
                'options'	=> array('0' => mage::helper('ExternalLogistic')->__('Magento to Logistic'), '1' => mage::helper('ExternalLogistic')->__('Logistic to Magento'))
        ));

        $this->addColumn('elh_last_execution',
            array(
                'header'=> Mage::helper('ExternalLogistic')->__('Last execution'),
                'index' => 'elh_last_execution',
                'type'	=> 'datetime'
        ));

        $this->addColumn('els_schedule_values',
            array(
                'header'=> Mage::helper('ExternalLogistic')->__('Schedule'),
                'index' => 'els_schedule_values',
                'renderer' => 'MDN_ExternalLogistic_Block_Widget_Grid_Column_Renderer_Schedule'
        ));

        $this->addColumn('els_is_active',
            array(
                'header'=> Mage::helper('ExternalLogistic')->__('Active'),
                'index' => 'els_is_active',
                'align' => 'center',
                'type' => 'options',
                'options' => array(
                    '1' => Mage::helper('purchase')->__('Yes'),
                    '0' => Mage::helper('purchase')->__('No'))
        ));
                
        /*
        $this->addColumn('els_is_default_stream_for',
            array(
                'header'=> Mage::helper('ExternalLogistic')->__('Is default stream for'),
                'index' => 'els_is_default_stream_for',
                'type' => 'option',
                'options' => mage::helper('ExternalLogistic/Streams')->getDefaultStreams()
        ));
         */

        return parent::_prepareColumns();
    }

    public function getGridParentHtml()
    {
        $templateName = Mage::getDesign()->getTemplateFilename($this->_parentTemplate, array('_relative'=>true));
        return $this->fetchView($templateName);
    }

    public function getRowUrl($row)
    {
    	return $this->getUrl('ExternalLogistic/Streams/Edit', array('els_id' => $row->getId()));
    }

    
}
