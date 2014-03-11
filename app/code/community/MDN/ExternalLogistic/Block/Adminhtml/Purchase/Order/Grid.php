<?php
/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * @copyright  Copyright (c) 2009 Maison du Logiciel (http://www.maisondulogiciel.com)
 * @author : Olivier ZIMMERMANN
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class MDN_ExternalLogistic_Block_Adminhtml_Purchase_Order_Grid extends MDN_Purchase_Block_Order_Grid
{
	/**
     * Add custom column
     *
     * @return unknown
     */
    protected function _prepareColumns()
    {
    	parent::_prepareColumns();
    	
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
    	
        return $this;
    }

}
