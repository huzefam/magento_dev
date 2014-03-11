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

/**
 * Order view tabs
 *
 * @category   Mage
 * @package    Mage_Adminhtml
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class MDN_ExternalLogistic_Block_Streams_Edit_Tabs extends Mage_Adminhtml_Block_Widget_Tabs
{

    public function __construct()
    {
        parent::__construct();
        $this->setId('externallogistic_streams_tabs');
        $this->setDestElementId('edit_form');
        $this->setTitle(Mage::helper('ExternalLogistic')->__('Edit Stream'));

    }

    protected function _beforeToHtml()
    {
    	$this->addTab('main', array(
            'label'     => Mage::helper('ExternalLogistic')->__('Information'),
            'content'   =>  $this->getLayout()->createBlock('ExternalLogistic/Streams_Edit_Tab_Information')->toHtml(),
    		'active'    => true
    	));
        
        //hide this tab depending of direction
        if ($this->getStream()->getels_direction() == MDN_ExternalLogistic_Model_Streams::kDirectionMagentoToLogistic)
        {
			$this->addTab('custominformation', array(
	            'label'     => Mage::helper('ExternalLogistic')->__('Custom information'),
	            'content'   =>  $this->getLayout()->createBlock('ExternalLogistic/Streams_Edit_Tab_CustomInformation')->toHtml(),
	        ));
        }
        
        $this->addTab('scheduling', array(
            'label'     => Mage::helper('ExternalLogistic')->__('Scheduling'),
            'content'   =>  $this->getLayout()->createBlock('ExternalLogistic/Streams_Edit_Tab_Scheduling')->toHtml(),
        ));

        $this->addTab('history', array(
            'label'     => Mage::helper('ExternalLogistic')->__('History'),
            'content'   =>  $this->getLayout()->createBlock('ExternalLogistic/Streams_Edit_Tab_History')->toHtml(),
        ));
                
        return parent::_beforeToHtml();
    }
    
    /**
     * Enter description here...
     *
     * @return unknown
     */
	public function getStream()
	{
		return mage::registry('current_stream');
	}
}
