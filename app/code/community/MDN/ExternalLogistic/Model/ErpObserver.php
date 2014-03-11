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
class MDN_ExternalLogistic_Model_ErpObserver 
{
	/**
	 * Add external logistic tab to purchase order sheet
	 *
	 * @param Varien_Event_Observer $observer
	 */
	public function purchase_order_edit_create_tabs(Varien_Event_Observer $observer)
	{
		//init vars
		$tab = $observer->getEvent()->gettab();   
		$purchaseOrder = $observer->getEvent()->getpurchase_order();   
		$layout = $observer->getEvent()->getlayout();   
		
		//add custom tabs
		$tab->addTab('tab_external_logistic', array(
            'label'     => Mage::helper('ExternalLogistic')->__('External Logistic'),
            'content'   => $layout->createBlock('ExternalLogistic/Adminhtml_Purchase_Order_Edit_Tabs_ExternalLogistic')
            						->toHtml()
        ));

	}
	
	/**
	 * Add sent to logistic column in sales order grid
	 *
	 * @param Varien_Event_Observer $observer
	 */
	public function salesorder_grid_preparecolumns(Varien_Event_Observer $observer)
	{

	}
	
	/**
	 * Add send to logistic mass action in sales order grid
	 *
	 * @param Varien_Event_Observer $observer
	 */
	public function salesorder_grid_preparemassaction(Varien_Event_Observer $observer)
	{

		
	}
	
/**
	 * Add custom tabs to erp product sheet
	 *
	 */
	public function advancedstock_product_edit_create_tabs(Varien_Event_Observer $observer)
	{
		//init vars
		$tab = $observer->getEvent()->gettab();   
		$product= $observer->getEvent()->getproduct();   
		$layout = $observer->getEvent()->getlayout();   
		
		//add custom tabs
		$tab->addTab('tab_external_logistic', array(
            'label'     => Mage::helper('ExternalLogistic')->__('External Logistic'),
            'content'   => $layout->createBlock('ExternalLogistic/Product_Edit_Tabs_ExternalLogistic')
            						->setTemplate('ExternalLogistic/Product/Edit/Tab/ExternalLogistic.phtml')
            						->setProduct($product)
            						->toHtml(),
        ));
	}
	
	/**
	 * handler called when purchase order status change
	 *
	 * @param Varien_Event_Observer $observer
	 */
	public function purchase_order_status_changed(Varien_Event_Observer $observer)
	{

	}
}
