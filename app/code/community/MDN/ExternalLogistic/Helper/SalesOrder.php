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
class MDN_ExternalLogistic_Helper_SalesOrder extends Mage_Core_Helper_Abstract
{
	/**
	 * Send orders to logistic
	 *
	 * @param unknown_type $orderId
	 */
	public function sendOrdersToLogistic($orderIds)
	{
		//retrieve default stream
		$stream = mage::helper('ExternalLogistic/Streams')->getDefaultStream(MDN_ExternalLogistic_Helper_Streams::kDefaultStreamSalesOrder);
		if (!$stream)
			throw new Exception($this->__('Default stream for sales order is not set'));
		
		//force source
		$stream->forceSourceContent(MDN_ExternalLogistic_Model_Sources::kSourceSalesOrder, $orderIds);
			
		//execute stream
		return $stream->process();
	}

        /**
         *Add conditions from system.xml to collection
         * 
         * @param <type> $collection
         */
        public function addConditionsToCollection($collection)
        {
            //todo : implement
            $statuts = mage::getStoreConfig('externallogistic/sales_order/allowed_statuses');
            $shippingmethods = mage::getStoreConfig('externallogistic/sales_order/allowed_shipping_methods');
            $invoiced = mage::getStoreConfig('externallogistic/sales_order/require_invoiced');

            $collection->addFieldToFilter('status', array('in' => $statuts ));

            $collection->addFieldToFilter('shipping_method', array('in' => $shippingmethods ));

            if($invoiced==1){
                $collection->addFieldToFilter('payment_validated', $invoiced);
            }
            return $collection;
        }
		
		/**
		 * Add comment to order
		 */
		public function addComment($order, $comment)
		{
			//add organizer
			$Task = Mage::getModel('Organizer/Task')
					->setot_author_user(1)
					->setot_created_at(date('Y-m-d H:i'))
					->setot_caption($comment)
					->setot_description('')
					->setot_entity_type('order')
					->setot_entity_id($order->getId())
					->setot_entity_description('Order #'.$order->getincrement_id())
					->save();
		}
}