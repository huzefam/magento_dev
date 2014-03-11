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
class MDN_ExternalLogistic_Helper_Adapter_Dimotrans_SalesOrder extends MDN_ExternalLogistic_Helper_Adapter_Dimotrans_Abstract
{
    public function process()
    {
		$debug = '';
	
		//collect order ids
        $salesOrderIds = array();
        $collection = mage::getModel('Orderpreparation/ordertopreparepending')
                            ->getCollection()
                            ->addFieldToFilter('opp_type', 'fullstock');
        foreach($collection as $item)
        {
            $order = mage::getModel('sales/order')->load($item->getopp_order_id());
			
			//if already sent, do not process order
            if ($order->getsent_to_logistic_company() == 1)
			{
					$debug .= '<p>Order #'.$order->getincrement_id().' : already sent to logistic ';
                    continue;
			}
					
			//check if shipping method match
			if (!$this->shippingMethodAccepted($order))
			{
				$debug .= '<p>Order #'.$order->getincrement_id().' : shipping method doesnt match ('.$order->getshipping_method().')';
				continue;
			}

			//check if specific condition matches
			$conditionAttribute = mage::getStoreConfig('externallogistic/dimotrans/where_attribute');
			$conditionValue = mage::getStoreConfig('externallogistic/dimotrans/where_value');
			if ($conditionAttribute != '')
			{
				if ($order->getData($conditionAttribute) != $conditionValue)
				{
					$debug .= '<p>Order #'.$order->getincrement_id().' : condition not ok ';
					continue;
				}
			}
				
			$debug .= '<p>Order #'.$order->getincrement_id().' : OK !!!! ';
            $salesOrderIds[] = $item->getopp_order_id();
        }
		
		//create xml file
        $streamCode = 'COMMANDE_'.date('YmdHis');
        if (count($salesOrderIds) > 0)
        {
            //generate xml
            $xml = $this->getSalesOrderXml($salesOrderIds);
			
            //create working directory
            $this->saveAndUploadFile($streamCode, $xml, 'Send/');
        }
		
		//create shipments
        foreach($salesOrderIds as $orderId)
        {
            $order = mage::getModel('sales/order')->load($orderId);
			if ($order->canShip())
			{
				$shipment = mage::helper('ExternalLogistic/Shipment')->createShipment($order->getincrement_id(),
																		  date('Y-m-d'),
																		  $this->getProductsArray($order),
																		  null);
																		  
				
			}
		}
		
		//raise event to allow other extension to perform custom actions
		$workingDirectory = mage::helper('ExternalLogistic')->getWorkingDirectoryForCompany('Dimotrans').$streamCode.'/';
		Mage::dispatchEvent('externallogistic_dimotrans_send_order', array('parser' => $this, 'order_ids' => $salesOrderIds, 'working_directory' => $workingDirectory));
        
        //return result
        $data = array(
            'error' => false,
            'entity_ids' => implode(',', $salesOrderIds),
            'result' => 'Stream code '.$streamCode.', '.count($salesOrderIds).' orders sent',
            'logistic_stream_code' => $streamCode,
			'auto_confirm' => true
        );

        return $data;
		
	}
	
    /**
     * Generate XML
     * @param <type> $collection
     */
    protected function getSalesOrderXml($orderIds)
    {
        $xmlWriter = mage::helper('ExternalLogistic/XmlWriter');
        $xmlWriter->init();

        //root element
        $xmlWriter->push('WINDEV_TABLE');

        //parse
        foreach($orderIds as $orderId)
        {
            $order = mage::getModel('sales/order')->load($orderId);
			
            $xmlWriter->push('CDEN');

			$xmlWriter->element('ACT_CODE', mage::getStoreConfig('externallogistic/dimotrans/activity'));
			$xmlWriter->element('OPE_REDO', $order->getincrement_id());
			$xmlWriter->element('TIE_CODE', $order->getCustomerName());
			$xmlWriter->element('OPE_RTIE', $order->getincrement_id());
			
			$shippingAddress = $order->getShippingAddress();
			$xmlWriter->element('TIE_NOM', $shippingAddress->getlastname().' '.$shippingAddress->getfirstname());
			$xmlWriter->element('OPE_ADR1', $shippingAddress->getStreet(1));
			$xmlWriter->element('OPE_ADR2', $shippingAddress->getStreet(2));
			$xmlWriter->element('OPE_ADCP', $shippingAddress->getpostcode());
			$xmlWriter->element('OPE_ADVL', $shippingAddress->getcity());
			$xmlWriter->element('OPE_CPAY', $shippingAddress->getcountry_id());
			
			$xmlWriter->push('CDLG');
			foreach ($order->getAllItems() as $orderItem)
			{
				$xmlWriter->element('ART_CODE', $orderItem->getSku());
				$xmlWriter->element('OPL_QTOC', ((int)$orderItem->getqty_ordered()));
			}
			$xmlWriter->pop();
			
			$xmlWriter->pop();
		}
		
        //end root element
        $xmlWriter->pop();

        return $xmlWriter->getXml();
		
	}
	
	
    /**
     * return sales order products list as array
     * @param <type> $order
     * @return <type>
     */
    protected function getProductsArray($order)
    {
        $temp = array();
        foreach($order->getAllItems() as $item)
        {
            if (!isset($temp[$item->getsku()]))
                    $temp[$item->getsku()] = 0;
            $temp[$item->getsku()] += $item->getqty_ordered();
        }

        //convert table
        $products = array();
        foreach($temp as $sku => $qty)
        {
            $products[] = array('sku' => $sku, 'qty' => $qty);
        }

        return $products;
    }

	/**
	 * Check if order shipping method is accepted
	 */
	protected function shippingMethodAccepted($order)
	{
		if (mage::getStoreConfig('externallogistic/dimotrans/enable_shipping_method_condition') != '1')
			return true;
			
		$shippingMethod = $order->getshipping_method();
		$acceptedShippingMethods = explode(',', mage::getStoreConfig('externallogistic/dimotrans/shipping_method'));
		if (in_array($shippingMethod, $acceptedShippingMethods))
		{
			return true;
		}
		else
		{
			return false;
		}
		
	}
}