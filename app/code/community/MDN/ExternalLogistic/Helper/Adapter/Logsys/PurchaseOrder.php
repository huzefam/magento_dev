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
 * @author : Nicolas MUGNIER
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class MDN_ExternalLogistic_Helper_Adapter_Logsys_PurchaseOrder extends MDN_ExternalLogistic_Helper_Adapter_Logsys_Abstract
{
    const kCommandeFournisseur = "CDMF";
    const kCommandeFournisseurPath = "in/comf/";

    protected function getRemoteDirectory(){
        return self::kCommandeFournisseurPath;
    }

    protected function getStreamPrefix(){
        return self::kCommandeFournisseur;
    }

    /**
     * Process
     *
     * @return array $data
     */
    public function process()
    {

        $this->checkTraitment();

        $purchaseOrderIds = array();
        $streamCode = $this->formatFilename($this->getStreamPrefix());

        $collection = mage::getModel('Purchase/Order')
                            ->getCollection()
                            ->addFieldToFilter('po_status', 'waiting_for_delivery')
                            ->addFieldToFilter('sent_to_logistic_company', '0');

        foreach($collection as $item)
        {
            $purchaseOrderIds[] = $item->getId();
        }

        //send csv to server only if there is 1+ PO
        if (count($purchaseOrderIds) > 0)
        {
            //generate csv
            $csv = $this->getPurchaseOrderCsv($purchaseOrderIds);

            //process file
            $this->saveAndUploadFile($streamCode, $csv, $this->getRemoteDirectory());
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

    /**
     * Build csv
     *
     * @param array $purchaseOrderIds
     * @return string $retour
     */
    public function getPurchaseOrderCsv($purchaseOrderIds)
    {
        // header
        $retour = "ART_ID\tART_QTE\tCMDF_FOURNISSEUR\tCMDF_ID\n";

        // content
        foreach($purchaseOrderIds as $id){

            $order = Mage::getModel('Purchase/Order')->load($id);

            foreach($order->getProducts() as $product){

                $retour .= "\"".$product->getsku()."\"\t";
                $retour .= "\"".$product->getpop_qty()."\"\t";
                $retour .= "\"".Mage::getModel('Purchase/Supplier')->load($order->getpo_sup_num())->getsup_name()."\"\t";
                $retour .= "\"".$order->getpo_order_id()."\"\n";

            }

        }

        return $retour;
    }
}
