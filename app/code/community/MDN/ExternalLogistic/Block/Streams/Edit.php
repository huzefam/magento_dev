<?php

/**
 * Customer edit block
 *
 * @category   Mage
 * @package    Mage_Adminhtml
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class MDN_ExternalLogistic_Block_Streams_Edit extends Mage_Adminhtml_Block_Widget_Form_Container {

    /**
     * Define buttons
     *
     */
    public function __construct() {

        $this->_objectId = 'id';
        $this->_controller = 'Streams';
        $this->_blockGroup = 'ExternalLogistic';

        parent::__construct();

        if ($this->getStream()->getels_direction() == MDN_ExternalLogistic_Model_Streams::kDirectionMagentoToLogistic) {
            $this->_addButton(
                    'addcustominformation',
                    array(
                        'label' => Mage::helper('ExternalLogistic')->__('Add Custom Information'),
                        'onclick' => "window.location.href='" . $this->getAddCustomInformationUrl() . "'",
                        'level' => -1
                    )
            );
        }

        $this->_addButton(
                'run',
                array(
                    'label' => Mage::helper('ExternalLogistic')->__('Run'),
                    'onclick' => "window.location.href='" . $this->getUrl('ExternalLogistic/Streams/Run', array('els_id' => $this->getStream()->getId())) . "'",
                    'level' => -1
                )
        );
    }

    /**
     * main title
     *
     * @return unknown
     */
    public function getHeaderText() {
        return Mage::helper('ExternalLogistic')->__('Edit Stream - %s', $this->getStream()->getels_name());
    }

    public function GetBackUrl() {
        return $this->getUrl('ExternalLogistic/Streams/Grid', array());
    }

    public function getStream() {
        return mage::registry('current_stream');
    }

    /**
     *
     *
     */
    public function getAddNewFileUrl() {
        return $this->getUrl('ExternalLogistic/Streams/AddNewFile', array('els_id' => $this->getStream()->getId()));
    }

    /**
     * Enter description here...
     *
     * @return unknown
     */
    public function getAddCustomInformationUrl() {
        return $this->getUrl('ExternalLogistic/Streams/AddNewCustomInformation', array('els_id' => $this->getStream()->getId()));
    }

}
