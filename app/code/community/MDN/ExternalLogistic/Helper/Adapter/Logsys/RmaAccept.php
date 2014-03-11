<?php
/* 
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

class MDN_ExternalLogistic_Helper_Adapter_Logsys_RmaAccept extends MDN_ExternalLogistic_Helper_Adapter_Logsys_Abstract {

    const kRmaAccept = "RMA_ACCEPT";
    const kRmaPath = "in/rma/";

    protected function getRemoteDirectory(){
        return self::kRmaPath;
    }

    protected function getStreamPrefix(){
        return self::kRmaAccept;
    }

    public function process(){

        $this->checkTraitment();

        $rmaIds = array();
        $streamCode = $this->formatFilename($this->getStreamPrefix());

        $collection = mage::getModel('ProductReturn/Rma')
                            ->getCollection()
                            ->addFieldToFilter('rma_status',MDN_ProductReturn_Model_Rma::kStatusProductReturnAccepted)
                            ->addFieldToFilter('sent_to_logistic_company', '0');

        foreach($collection as $item)
        {
            $rmaIds[] = $item->getId();
        }

        //send csv to server only if there is 1+ RMA
        if (count($rmaIds) > 0)
        {
            //generate csv
            $csv = $this->getRmaCsv($rmaIds);

            //process file
            $this->saveAndUploadFile($streamCode, $csv, $this->getRemoteDirectory());
        }


        //return result
        $data = array(
            'error' => false,
            'entity_ids' => implode(',', $rmaIds),
            'result' => 'Stream code '.$streamCode.', '.count($rmaIds).' RMA sent',
            'logistic_stream_code' => $streamCode
        );

        return $data;
    }

    protected function getRmaCsv($rmaIds){

        $retour = '';

        // header
        $retour = "CMD_ID\tDEST_NOM\tRMA_ID\tRMA_DATE_ACCEPT\tRMA_DATE_VALID\tDEST_EMAIL\tDEST_TEL1\tRMA_DESCRIPTION\tART_ID\tART_LIBELLE\tART_QTE_INIT\tART_QTE_RETOUR\tART_RAISON\tART_DESCRIPTION\tART_NUM_SERIE\n";

        // content
        foreach($rmaIds as $id){

            $rma = Mage::getModel('ProductReturn/Rma')->load($id);
            $rmaProductCollection = Mage::getModel('ProductReturn/RmaProducts')->getCollection()
                    ->addFieldToFilter('rp_rma_id',$rma->getrma_id());
            $order = Mage::getModel('sales/order')->load($rma->getrma_order_id());

            foreach($rmaProductCollection as $rmaProduct){

                $product = Mage::getModel('sales/order_item')->getCollection()
                                    ->addFieldToFilter('order_id', $rma->getrma_order_id())
                                    ->addFieldToFilter('product_id', $rmaProduct->getrp_product_id())
                                    ->getFirstItem();

                $retour .= "\"".$this->formatContent($order->getincrement_id())."\"\t"; // CMD_ID*
                $retour .= "\""."\"\t"; // DEST_NOM
                $retour .= "\"".$this->formatContent($rma->getrma_id())."\"\t"; // RMA_ID*
                $retour .= "\"".$this->formatContent($rma->getrma_updated_at())."\"\t"; // RMA_DATE_ACCEPT*
                $retour .= "\"".$this->formatContent($rma->getrma_expire_date())."\"\t"; // RMA_DATE_VALID*
                $retour .= "\""."\"\t"; // DEST_EMAIL
                $retour .= "\""."\"\t"; // DEST_TEL1
                $retour .= "\"".$this->formatContent($rma->getrma_public_description())."\"\t"; // RMA_DESCRIPTION*
                $retour .= "\"".$this->formatContent($product->getsku())."\"\t"; // ART_ID*
                $retour .= "\"".$this->formatContent($rmaProduct->getrp_product_name())."\"\t"; // ART_LIBELLE*
                $retour .= "\"".(int)$product->getqty_ordered()."\"\t"; // TODO : ART_QTE_INIT*
                $retour .= "\"".$this->formatContent($rmaProduct->getrp_qty())."\"\t"; // ART_QTE_RETOUR*
                $retour .= "\"".$this->formatContent($rma->getrma_reason())."\"\t"; // ART_RAISON*
                $retour .= "\"".$this->formatContent($rmaProduct->getrp_description())."\"\t"; // ART_DESCRIPTION*
                $retour .= "\""."\"\n"; // ART_NUM_SERIE

            }


        }

        return $retour;

    }

}
