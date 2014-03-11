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
class MDN_ExternalLogistic_Helper_PurchaseOrder extends Mage_Core_Helper_Abstract
{
	/**
	 * Send orders to logistic
	 *
	 * @param unknown_type $orderId
	 */
	public function sendOrdersToLogistic($orderIds)
	{
		//retrieve default stream
		$stream = mage::helper('ExternalLogistic/Streams')->getDefaultStream(MDN_ExternalLogistic_Helper_Streams::kDefaultStreamPurchaseOrder);
		if (!$stream)
			throw new Exception($this->__('Default stream for purchase order is not set'));

		//force source
		$stream->forceSourceContent(MDN_ExternalLogistic_Model_Sources::kSourcePurchaseOrder, $orderIds);
			
		//execute stream
		$stream->process();

	}

        public function createDelivery($data, $warehouseId)
        {
            //load PO
            $po = mage::getModel('Purchase/Order')->load($data['increment_id'], 'po_order_id');
            if (!$po->getId())
                throw new Exception('Unable to load PO #'.$data['increment_id']);

            //parse products
            foreach($data['products'] as $product)
            {
                $productId = mage::getModel('catalog/product')->getIdBySku($product['sku']);
                if ($productId)
                {
                    $purchaseOrderItem = $po->getPurchaseOrderItem($productId);
                    $po->createDelivery($purchaseOrderItem,
                                        $product['qty'],
                                        $po['delivery_date'],
                                        'PO #'.$po->getpo_order_id(),
                                        $warehouseId,
                                        true);
                }
            }

            $po->computeDeliveryProgress();
        }

}