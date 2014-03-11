<?php

class MDN_ExternalLogistic_Block_History_Grid extends Mage_Adminhtml_Block_Widget_Grid
{

    public function __construct()
    {
        parent::__construct();
        $this->setId('externalLogisticHistoryGrid');
        $this->setDefaultSort('elh_date');
        $this->setDefaultDir('desc');
        $this->setVarNameFilter('external_logistic_history');
        $this->_parentTemplate = $this->getTemplate();
        $this->setEmptyText(Mage::helper('ExternalLogistic')->__('History is empty'));
    }

    protected function _prepareCollection()
    {
        $collection = Mage::getModel('ExternalLogistic/History')
        		->getCollection();
        		
        $this->setCollection($collection);
        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        $this->addColumn('elh_date',
            array(
                'header'=> Mage::helper('ExternalLogistic')->__('Date'),
                'type'  => 'datetime',
                'index' => 'elh_date',
        ));
        
        $this->addColumn('elh_description',
            array(
                'header'=> Mage::helper('ExternalLogistic')->__('Description'),
                'index' => 'elh_description',
        ));

        $this->addColumn('elh_company_code',
                array(
                    'header' => Mage::helper('ExternalLogistic')->__('Company'),
                    'index' => 'elh_company_code'
        ));

        $this->addColumn('elh_logistic_stream_code',
                array(
                    'header' => Mage::helper('ExternalLogistic')->__('Logistic stream code'),
                    'index' => 'elh_logistic_stream_code'
        ));

        $this->addColumn('elh_result',
            array(
                'header'=> Mage::helper('ExternalLogistic')->__('Result'),
                'index' => 'elh_result',
                'renderer' => 'MDN_ExternalLogistic_Block_Widget_Grid_Column_Renderer_HistoryResult'
        ));

        $this->addColumn('elh_has_error', array(
            'header' => Mage::helper('AdvancedStock')->__('Error'),
            'index' => 'elh_has_error',
            'type' => 'options',
            'options' => array(
                '1' => Mage::helper('catalog')->__('Yes'),
                '0' => Mage::helper('catalog')->__('No'),
            )
        ));

        $this->addColumn('elh_confirmed',
                array(
                    'header' => Mage::helper('ExternalLogistic')->__('Confirmed'),
                    'index' => 'elh_confirmed',
                    'type' => 'options',
                    'options' => array(
                        '1' => Mage::helper('catalog')->__('Yes'),
                        '0' => Mage::helper('catalog')->__('No'),
                    )
        ));

        $this->addColumn('download_history',
            array(
                'header'=> Mage::helper('ExternalLogistic')->__('Download'),
                'renderer' => 'MDN_ExternalLogistic_Block_Widget_Grid_Column_Renderer_DownloadHistory',
                'filter' => false,
                'sortable' => false
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
    	return $this->getUrl('ExternalLogistic/History/View', array('elh_id' => $row->getId()));
    }
    
}
