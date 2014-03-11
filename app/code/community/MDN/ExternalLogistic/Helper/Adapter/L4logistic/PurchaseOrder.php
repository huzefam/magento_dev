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
class MDN_ExternalLogistic_Helper_Adapter_L4logistic_PurchaseOrder extends MDN_ExternalLogistic_Helper_Adapter_L4logistic_Abstract
{
    public function process()
    {
        //collect order ids (todo : add filter on "send_to_logistic_company")
        $purchaseOrderIds = array();
        $collection = mage::getModel('Purchase/Order')
                            ->getCollection()
                            ->addFieldToFilter('po_status', 'waiting_for_delivery')
                            ->addFieldToFilter('sent_to_logistic_company', '0')
							;
        foreach($collection as $item)
        {
            $purchaseOrderIds[] = $item->getId();
        }

        //send xml to server only if there is 1+ PO
        if (count($purchaseOrderIds) > 0)
        {
            //generate xml
            $xml = $this->getPurchaseOrderXml($purchaseOrderIds);

            //process file
            $streamCode = 'ANAPRO'.date('YmdHis');
            $this->saveAndUploadFile($streamCode, $xml, 'IN/');
        }
        
        //return result
        $data = array(
            'error' => false,
            'entity_ids' => implode(',', $purchaseOrderIds),
            'result' => 'Stream code '.$streamCode.', '.count($purchaseOrderIds).' PO sent',
            'logistic_stream_code' => $streamCode
        );

        return $data;

    }

    public function getPurchaseOrderXml($purchaseOrderIds)
    {
        $xmlWriter = mage::helper('ExternalLogistic/XmlWriter');
        $xmlWriter->init();

        $prefix = mage::getStoreConfig('externallogistic/l4logistic/prefix');

        //root element
        $xmlWriter->push('AttenduApprovisionnement');

        foreach($purchaseOrderIds as $purchaseOrderId)
        {
            $purchaseOrder = mage::getModel('Purchase/Order')->load($purchaseOrderId);
            $xmlWriter->push('Appro');

            //main information for order
            $xmlWriter->element('CodeActivite', mage::getStoreConfig('externallogistic/l4logistic/activity'));
            $xmlWriter->element('Activite', mage::getModel('ExternalLogistic/System_Config_Source_L4logistic_Activity')->getLabelFromCode(mage::getStoreConfig('externallogistic/l4logistic/activity')));
            $xmlWriter->element('Reference', $purchaseOrder->getpo_order_id());
            $xmlWriter->element('CodeTypeApprovisionnement', 'STD');
            $xmlWriter->element('GroupeOrdresApprovisionnement', $prefix);
            $xmlWriter->element('PreparationGroupeOrdresPreparationDescription', mage::getStoreConfig('externallogistic/l4logistic/customer_name'));
            $xmlWriter->element('CodeFournisseur', $this->cleanStringValue($purchaseOrder->getSupplier()->getsup_name(), 14));
            $xmlWriter->element('DateDeReceptionPrevue', $this->formatDate($purchaseOrder->getpo_supply_date()));
            $xmlWriter->element('Reservation', '2');

            //products
            $i = 1;
            foreach($purchaseOrder->getProducts() as $product)
            {
                $xmlWriter->push('Ligne');

                $xmlWriter->element('ReferenceLigne', $i);
                $xmlWriter->element('CodeArticle', $prefix.$product->getsku());
                $xmlWriter->element('QuantiteAttendue', $product->getpop_qty());

                $xmlWriter->pop();
                $i++;
            }

            $xmlWriter->pop();
        }

        //end root element
        $xmlWriter->pop();

        return $xmlWriter->getXml();
    }
}
