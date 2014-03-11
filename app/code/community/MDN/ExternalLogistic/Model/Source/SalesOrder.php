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
class MDN_ExternalLogistic_Model_Source_SalesOrder extends MDN_ExternalLogistic_Model_Source_Abstract
{
	public function getFieldset()
	{
        $form = new Varien_Data_Form();
        $fieldset = $form->addFieldset('settings', array(
            'legend' => Mage::helper('ExternalLogistic')->__('Settings')
        ));
        
        //sales order statuses
        $fieldset->addField('status', 'multiselect', array(
            'label' => Mage::helper('ExternalLogistic')->__('Allowed Statuses'),
            'name'  => 'status',
            'values' => mage::getModel('adminhtml/System_Config_Source_Order_Status')->toOptionArray(),
            'value' => 1
        ));
        
        //shipping method
        $fieldset->addField('shipping_method', 'multiselect', array(
            'label' => Mage::helper('ExternalLogistic')->__('Allowed Shipping methods'),
            'name'  => 'shipping_method',
            'values' => mage::getModel('adminhtml/System_Config_Source_Shipping_Allmethods')->toOptionArray(),
            'value' => 1
        ));
        
        //payment_validated
        $fieldset->addField('require_payment_validated', 'select', array(
            'label' => Mage::helper('ExternalLogistic')->__('Require Payment validated'),
            'name'  => 'require_payment_validated',
            'values' => mage::getModel('adminhtml/system_config_source_yesno')->toOptionArray(),
            'value' => 1
        ));
        
        //is fullstock
        $fieldset->addField('require_is_fullstock', 'select', array(
            'label' => Mage::helper('ExternalLogistic')->__('Require is Fullstock'),
            'name'  => 'require_is_fullstock',
            'values' => mage::getModel('adminhtml/system_config_source_yesno')->toOptionArray(),
            'value' => 1
        ));        
        
        return $form;
	}
}