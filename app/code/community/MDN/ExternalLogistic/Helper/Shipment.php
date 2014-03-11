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
class MDN_ExternalLogistic_Helper_Shipment extends Mage_Core_Helper_Abstract {

    /**
     * Create a new shipment
     *
     * @param unknown_type $orderIncrementId
     * @param unknown_type $date
     * @param unknown_type $products = associative array, 'qty', 'sku'
     */
    public function createShipment($orderIncrementId, $date, $products, $tracking = null) {
        $debug = '';

        //load order
        $order = $this->getOrder($orderIncrementId);
        if (!$order->getId())
            throw new Exception('Unable to retrieve order #' . $orderIncrementId);

        //if order already complete or canceled, leave
        if ($order->getState() == 'complete')
                return true;
        if ($order->getState() == 'canceled')
                return true;

        //init shipment
        $convertor = Mage::getModel('sales/convert_order');
        $shipment = $convertor->toShipment($order);

        //parcourt les �l�ments de la commande
        foreach ($order->getAllItems() as $orderItem) {
            $debug .= 'Parse order item with sku = ' . $orderItem->getSku() . " \n";

            //skip special cases
            if (!$orderItem->isDummy(true) && !$orderItem->getQtyToShip()) {
                $debug .= 'Order item for product ' . $orderItem->getSku() . ' is dummy' . "\n";
                continue;
            }
            if ($orderItem->getIsVirtual()) {
                $debug .= 'Order item for product ' . $orderItem->getSku() . ' is virtual' . "\n";
                continue;
            }

            //retrieve qty
            $productSku = $orderItem->getsku();

            if($products !== null){
                $qty = $this->getQtyForSku($productSku, $products);
            }else{
                $qty = $orderItem->getqty_ordered();
            }

            $debug .= 'Qty = ' . $qty . ' for sku = ' . $productSku . " \n";

            if ($qty > 0) {
                //ajout au shipment
                $ShipmentItem = $convertor->itemToShipmentItem($orderItem);
                $ShipmentItem->setQty($qty);
                $shipment->addItem($ShipmentItem);
            }
            
        }

        //save shipmeent
        $shipment->register();
        $shipment->getOrder()->setIsInProcess(true);
        $transactionSave = Mage::getModel('core/resource_transaction')
                        ->addObject($shipment)
                        ->addObject($shipment->getOrder())
                        ->save();

        //add tracking
        if ($tracking)
        {
            //todo : check si faut pas récupérer juste la partie avant le '_' dans shippingMethod
            $shippingMethod = $order->getShippingMethod();
            mage::helper('Orderpreparation/Tracking')->addTrackingToShipment($tracking, $shipment->getincrement_id(), $shippingMethod);
        }
        
        //notify customer
        $shipment->sendEmail();

        //return shipment
        return $shipment;
    }

    /**
     * Load order from incrementId
     *
     * @param unknown_type $orderIncrementId
     */
    protected function getOrder($orderIncrementId) {
        $order = mage::getModel('sales/order')->loadByIncrementId($orderIncrementId);
        return $order;
    }

    /**
     * Get shipped qty for sku
     *
     * @param unknown_type $sku
     * @param unknown_type $products
     * @return unknown
     */
    protected function getQtyForSku($sku, $products) {
        foreach ($products as $product) {
            if (trim(strtolower($product['sku'])) == trim(strtolower($sku)))
                return $product['qty'];
        }

        return -1;
    }

}