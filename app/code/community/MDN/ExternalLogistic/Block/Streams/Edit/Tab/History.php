<?php

/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magentocommerce.com for more information.
 *
 * @category    Mage
 * @package     Mage_Adminhtml
 * @copyright   Copyright (c) 2009 Irubin Consulting Inc. DBA Varien (http://www.varien.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class MDN_ExternalLogistic_Block_Streams_Edit_Tab_History extends Mage_Adminhtml_Block_Widget_Grid {

    public function __construct() {
        parent::__construct();
        $this->setId('streamHistoryGrid');
        $this->setDefaultSort('elh_date');
        $this->setDefaultDir('desc');
        $this->setVarNameFilter('external_logistic_streams_history');
        $this->_parentTemplate = $this->getTemplate();
        $this->setEmptyText(Mage::helper('ExternalLogistic')->__('No items'));
    }

    protected function _prepareCollection() {
        $collection = $this->getStream()->getHistory();
        $this->setCollection($collection);
        return parent::_prepareCollection();
    }

    protected function _prepareColumns() {
        $this->addColumn('elh_date',
                array(
                    'header' => Mage::helper('ExternalLogistic')->__('Date'),
                    'index' => 'elh_date',
                    'type' => 'datetime',
                    'filter' => false,
                    'sortable' => false
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
                    'header' => Mage::helper('ExternalLogistic')->__('Result'),
                    'index' => 'elh_result'
        ));

        $this->addColumn('elh_entity_ids',
                array(
                    'header' => Mage::helper('ExternalLogistic')->__('Ids'),
                    'index' => 'elh_entity_ids'
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

    public function getGridParentHtml() {
        $templateName = Mage::getDesign()->getTemplateFilename($this->_parentTemplate, array('_relative' => true));
        return $this->fetchView($templateName);
    }

    /**
     * Enter description here...
     *
     * @return unknown
     */
    public function getStream() {
        return mage::registry('current_stream');
    }

}
