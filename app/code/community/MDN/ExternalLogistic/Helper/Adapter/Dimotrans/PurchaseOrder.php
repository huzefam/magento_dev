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
class MDN_ExternalLogistic_Helper_Adapter_Dimotrans_PurchaseOrder extends MDN_ExternalLogistic_Helper_Adapter_Dimotrans_Abstract
{
    public function process()
    {
        //collect order ids
		$warehouseId = mage::getStoreConfig('externallogistic/dimotrans/warehouse');
        $purchaseOrderIds = array();
        $collection = mage::getModel('Purchase/Order')
                            ->getCollection()
                            ->addFieldToFilter('po_status', 'waiting_for_delivery')
							->addFieldToFilter('po_target_warehouse', $warehouseId)
                            ->addFieldToFilter('sent_to_logistic_company', '0');
        foreach($collection as $item)
        {
            $purchaseOrderIds[] = $item->getId();
        }

        //send xml to server only if there is 1+ PO
		$streamCode = 'RECEP_'.date('YmdHis');
        if (count($purchaseOrderIds) > 0)
        {
            //generate xml
            $xml = $this->getPurchaseOrderXml($purchaseOrderIds);
			
            //process file
            $this->saveAndUploadFile($streamCode, $xml, 'Send/');
        }
        
        //return result
        $data = array(
            'error' => false,
            'entity_ids' => implode(',', $purchaseOrderIds),
            'result' => 'Stream code '.$streamCode.', '.count($purchaseOrderIds).' PO sent',
            'logistic_stream_code' => $streamCode,
			'auto_confirm' => true
        );

        return $data;

    }
	
	
	public function getPurchaseOrderXml($purchaseOrderIds)
    {
        $xmlWriter = mage::helper('ExternalLogistic/XmlWriter');
        $xmlWriter->init();

        //root element
        $xmlWriter->push('WINDEV_TABLE');

        foreach($purchaseOrderIds as $purchaseOrderId)
        {
            $purchaseOrder = mage::getModel('Purchase/Order')->load($purchaseOrderId);

            //products
            foreach($purchaseOrder->getProducts() as $product)
            {
				$xmlWriter->push('Table');
			
				//PO information
                $xmlWriter->element('ACT_CODE', mage::getStoreConfig('externallogistic/dimotrans/activity'));
				$xmlWriter->element('REE_NOFO', $purchaseOrder->getpo_order_id());
				$xmlWriter->element('TIE_CODE', $this->getSupplierCode($purchaseOrder->getSupplier()));
				$xmlWriter->element('REE_LIFO', $purchaseOrder->getSupplier()->getsup_name());
				$xmlWriter->element('REE_DARE', $this->formatDate($purchaseOrder->getpo_supply_date()));
				
				//product
                $xmlWriter->element('ART_CODE', $product->getsku());
                $xmlWriter->element('REL_QTRE', $product->getpop_qty());
                $xmlWriter->element('REL_COM', $product->getpop_supplier_ref());

                $xmlWriter->pop();
            }
        }

        //end root element
        $xmlWriter->pop();

        return $xmlWriter->getXml();
    }
	
	private function getSupplierCode($supplier)
	{
		$code = $supplier->getsupplier_code();
		if ($code == '')
			$code = $supplier->getsup_name();
		return $code;
	}
	
}