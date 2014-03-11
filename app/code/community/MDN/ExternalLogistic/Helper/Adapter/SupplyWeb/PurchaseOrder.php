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
class MDN_ExternalLogistic_Helper_Adapter_SupplyWeb_PurchaseOrder extends MDN_ExternalLogistic_Helper_Adapter_SupplyWeb_Abstract {
    
    const kMnemonique = "REC01";

    private $_fields = null;

    /**
     * Process
     *
     * @return array $data
     */
    public function process() {

        $this->checkTraitment();

        $purchaseOrderIds = array();
        $streamCode = $this->getStreamCode();
        $filename = $this->formatFilename($streamCode);

        $collection = mage::getModel('Purchase/Order')
                        ->getCollection()
                        ->addFieldToFilter('po_status', 'waiting_for_delivery')
                        ->addFieldToFilter('sent_to_logistic_company', '0');

        foreach ($collection as $item) {
            $purchaseOrderIds[] = $item->getId();
        }

        //send csv to server only if there is 1+ PO
        if (count($purchaseOrderIds) > 0) {
            //generate csv
            $csv = $this->getPurchaseOrderCsv($purchaseOrderIds);

            //process file
            $this->saveAndUploadFile($filename, $csv, $this->getRemoteDirectory());
        }

        //return result
        $data = array(
            'error' => false,
            'entity_ids' => implode(',', $purchaseOrderIds),
            'result' => 'Stream code ' . $streamCode . ', ' . count($purchaseOrderIds) . ' PO sent',
            'logistic_stream_code' => $streamCode
        );

        return $data;
    }

    protected function getPurchaseOrderCsv($purchaseOrderIds) {

        $retour = '';

        foreach($purchaseOrderIds as $id){

            $order = Mage::getModel('Purchase/Order')->load($id);

            foreach($order->getProducts() as $product){

                foreach($this->getFields() as $field){

                    $retour .= $this->formatField($field, $order, $product);

                }

                $retour .= "\n";

            }

        }

        return $retour;

    }

    protected function formatField($field, $order, $product){

        $retour = '';

        switch($field['name']){

            case 'OP_CODE':
                $retour = $this->addSeparateur($field, "REC01");
                break;

            case 'CODE_SOC':
                $retour = $this->addSeparateur($field, self::kCODE_SOC);
                break;

            case 'N_PIECE':
                $retour = $this->addSeparateur($field, $order->getpo_order_id());
                break;

            case 'DATE_ATTENDUE':
                $retour = $this->addSeparateur($field, $this->getShippingDate($order));
                break;

            case 'CODE_ART':
                $retour = $this->addSeparateur($field, $product->getsku());
                break;

            case 'QTE':
                $retour = $this->addSeparateur($field, $product->getpop_qty());
                break;

            case 'COMMENTAIRE':
                $retour = $this->addSeparateur($field, '');
                break;

        }

        return $retour;

    }

    protected function getShippingDate($order){

        $retour = "";

        $date = $order->getpo_supply_date();

        $retour = str_replace("-", "",$date);

        $retour = ($retour == "") ? date('Ymd', Mage::getModel('core/date')->timestamp()) : $retour;

        return $retour;

    }

    protected function getStreamPrefix() {
        return self::kMnemonique;
    }

    protected function getStreamSuffix() {
        return self::kSuffix;
    }

    protected function getFields() {

        if ($this->_fields == null) {

            $this->_fields = array(
                'OP_CODE' => array(
                    'name' => 'OP_CODE',
                    'start' => 1,
                    'length' => 10,
                    'type' => self::kTypeAlpha
                ),
                'CODE_SOC' => array(
                    'name' => 'CODE_SOC',
                    'start' => 11,
                    'length' => 20,
                    'type' => self::kTypeAlpha
                ),
                'N_PIECE' => array(
                    'name' => 'N_PIECE',
                    'start' => 31,
                    'length' => 20,
                    'type' => self::kTypeAlpha
                ),
                'DATE_ATTENDUE' => array(
                    'name' => 'DATE_ATTENDUE',
                    'start' => 51,
                    'length' => 8,
                    'type' => self::kTypeNum
                ),
                'CODE_ART' => array(
                    'name' => 'CODE_ART',
                    'start' => 59,
                    'length' => 50,
                    'type' => self::kTypeAlpha
                ),
                'QTE' => array(
                    'name' => 'QTE',
                    'start' => 109,
                    'length' => 10,
                    'type' => self::kTypeNum
                ),
                'COMMENTAIRE' => array(
                    'name' => 'COMMENTAIRE',
                    'start' => 119,
                    'length' => 250,
                    'type' => self::kTypeAlpha
                )
            );
        }

        return $this->_fields;
    }

}
