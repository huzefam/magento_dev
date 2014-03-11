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
class MDN_ExternalLogistic_Helper_StockMovement extends Mage_Core_Helper_Abstract
{
	const kSourceTypeUndefined = 'undefined' ;
	const kSourceTypePurchaseOrder = 'purchase_order' ;
	const kSourceTypeProductReturn = 'product_return' ;
	
	/**
	 * Create a stock movement
	 *
	 * @param unknown_type $qty
	 * @param unknown_type $isPositive
	 * @param unknown_type $purchaseOrderId
	 * @param unknown_type $description
	 */
	public function createStockMovement($sku, $qty, $isPositive, $sourceType, $sourceId, $description, $warehouseId = null)
	{
		if ($warehouseId == null)
			$warehouseId = mage::getStoreConfig('purchase/purchase_order/default_warehouse_for_delivery');			
	
		//create stock movement depending of source type
		switch ($sourceType)
		{
			case self::kSourceTypeUndefined:		
				//create stock movement
				$productId = mage::getModel('catalog/product')->getIdBySku($sku);
				if ($productId)
				{
                    $additionalDatas = array('sm_type' => 'adjustment');
					$model = mage::getModel('AdvancedStock/StockMovement');
					if ($isPositive)
						$model->createStockMovement($productId, null, $warehouseId, $qty, $description, $additionalDatas);
					else 
						$model->createStockMovement($productId, $warehouseId, null, $qty, $description, $additionalDatas);
				}
                                else
        				throw new Exception('Unable to retrieve product with sku = '.$sku);
				break;
			case self::kSourceTypeProductReturn:
				throw new Exception('MDN_ExternalLogistic_Helper_StockMovement kSourceTypeProductReturn not implemented');				
				break;
			case self::kSourceTypePurchaseOrder:
				
				//retrieve infos
				$purchaseOrder = mage::getModel('Purchase/Order')->load($sourceId, 'po_order_id');
				$productId = mage::getModel('catalog/product')->getIdBySku($sku);
				$product = mage::getModel('catalog/product')->load($productId);
				
				//raise error if unable to find objects
				if (!$purchaseOrder->getId())
					throw new Exception('Unable to retrieve purchase order with id = '.$sourceId);
				if (!$product->getId())
					throw new Exception('Unable to retrieve product with sku = '.$sku);
				
				//retrieve order item
				$purchaseOrderProduct = $purchaseOrder->getOrderProductItem($productId);
				if ($purchaseOrderProduct)
				{
					//create delivery
					$purchaseOrder->createDelivery($purchaseOrderProduct, $qty, date('Y-m-d'), $description, $warehouseId, true);
				}
				
				break;
		}
	}
}