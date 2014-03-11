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
class MDN_ExternalLogistic_Helper_Source extends Mage_Core_Helper_Abstract
{
	
	/**
	 * return sources array
	 *
	 * @return unknown
	 */
	public function getSources()
	{
		$values = array();
		
		$values[MDN_ExternalLogistic_Model_Sources::kSourceProducts] = $this->__(MDN_ExternalLogistic_Model_Sources::kSourceProducts);
		$values[MDN_ExternalLogistic_Model_Sources::kSourceSalesOrder] = $this->__(MDN_ExternalLogistic_Model_Sources::kSourceSalesOrder);
		$values[MDN_ExternalLogistic_Model_Sources::kSourceSalesOrderItem] = $this->__(MDN_ExternalLogistic_Model_Sources::kSourceSalesOrderItem);
		$values[MDN_ExternalLogistic_Model_Sources::kSourcePurchaseOrder] = $this->__(MDN_ExternalLogistic_Model_Sources::kSourcePurchaseOrder);
		$values[MDN_ExternalLogistic_Model_Sources::kSourcePurchaseOrderItem] = $this->__(MDN_ExternalLogistic_Model_Sources::kSourcePurchaseOrderItem);
		
		return $values;
	}
	
	/**
	 * Return a source from its name
	 *
	 */
	public function getSource($sourceName, $forceSourceContent)
	{
		$collection = null;
		
		switch ($sourceName)
		{
			//*************************************************************************************************************
			case MDN_ExternalLogistic_Model_Sources::kSourceProducts:
				$max = mage::getStoreConfig('externallogistic/products/max');
				
				$collection = mage::getModel('catalog/product')
								->getCollection()
								->addAttributeToSelect('*')
								->addFieldToFilter('sent_to_logistic_company', 0);
				if (is_numeric($max))				
					$collection->getSelect()->limit($max);
				
				break;
				
			//*************************************************************************************************************
			case MDN_ExternalLogistic_Model_Sources::kSourceSalesOrder:
				
				//init collection
				$collection = mage::getModel('sales/order')
								->getCollection()
								->addAttributeToSelect('*')
								->addAttributeToSelect('external_logistic_custom_information');
				$orderAddressAttributes = Mage::getSingleton('eav/config')->getEntityAttributeCodes('order_address');
				foreach($orderAddressAttributes as $attributeCode)
				{
					//add addresses
			        $collection->joinAttribute('billing_'.$attributeCode, 'order_address/'.$attributeCode, 'billing_address_id', null, 'left');
			        $collection->joinAttribute('shipping_'.$attributeCode, 'order_address/'.$attributeCode, 'shipping_address_id', null, 'left');
				}
				
				//add billing & shipping name
				$collection->addExpressionAttributeToSelect('billing_name',
                											'CONCAT({{billing_company}}, " ", {{billing_firstname}}, " ", {{billing_lastname}})',
											                array('billing_firstname', 'billing_lastname', 'billing_company'))
            				->addExpressionAttributeToSelect('shipping_name',
                											'CONCAT({{shipping_firstname}},  IFNULL(CONCAT(\' \', {{shipping_lastname}}), \'\'), " ", {{shipping_company}})',
                											array('shipping_firstname', 'shipping_lastname', 'shipping_company'));
				
                //add payment method
                $collection->joinAttribute('payment_method', 'order_payment/method', 'entity_id', null, 'left');
                											
				//if force source is set, add condition with order ids
				if (($forceSourceContent != null) && (isset($forceSourceContent['src_sales_order'])))
					$collection->addFieldToFilter('entity_id', array('in' => $forceSourceContent['src_sales_order']));

				break;
				
			//*************************************************************************************************************
			case MDN_ExternalLogistic_Model_Sources::kSourceSalesOrderItem:
				
				//init collection
				$collection = mage::getModel('sales/order_item')
								->getCollection();
				$collection->join('sales/order', 'entity_id=order_id', array('increment_id'));
								
				//if force source is set, add condition with order ids
				if (($forceSourceContent != null) && (isset($forceSourceContent['src_sales_order'])))
					$collection->addFieldToFilter('entity_id', array('in' => $forceSourceContent['src_sales_order']));
					
				break;

			//*************************************************************************************************************
			case MDN_ExternalLogistic_Model_Sources::kSourcePurchaseOrder:
		        $collection = Mage::getModel('Purchase/Order')
		        	->getCollection()
		        	->join('Purchase/Supplier',
					           'po_sup_num=sup_id');
					           
				//if force source is set, add condition with order ids
				if (($forceSourceContent != null) && (isset($forceSourceContent['src_purchase_order'])))
				{
					$collection->addFieldToFilter('po_num', array('in' => $forceSourceContent['src_purchase_order']));
				}
					
				break;				

			//*************************************************************************************************************
			case MDN_ExternalLogistic_Model_Sources::kSourcePurchaseOrderItem:
				
		        $collection = Mage::getModel('Purchase/OrderProduct')
		        	->getCollection()
		        	->join('Purchase/Order',
					           'po_num=pop_order_num')
					->join('catalog/product', 'pop_product_id = entity_id');
				
				//if force source is set, add condition with order ids
				if (($forceSourceContent != null) && (isset($forceSourceContent['src_purchase_order'])))
				{
					$collection->addFieldToFilter('po_num', array('in' => $forceSourceContent['src_purchase_order']));
				}
									
				break;				
				
			default:
				throw new Exception($this->__('Unable to find source : %s', $sourceName));
				break;
		}
		
		return $collection;
	}
}