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

class MDN_ExternalLogistic_Helper_Adapter_Logsys_RmaProductStock extends MDN_ExternalLogistic_Helper_Adapter_Logsys_Abstract {

    const kRmaProductStock = "RMA_PRODUCT_STOCK";
    const kRmaPath = "in/rma/";
    const kBackToStock = "produit_remis_enstock";
    const kDestroy = "produit_detruit";

    protected function getRemoteDirectory(){
        return self::kRmaPath;
    }

    protected function getStreamPrefix(){
        return self::kRmaProductStock;
    }

    public function process(){

        $this->checkTraitment();

        $rmaIds = array();
        $rmaProductIds = array();
        $streamCode = $this->formatFilename($this->getStreamPrefix());

        $collection = mage::getModel('ProductReturn/RmaProducts')
                            ->getCollection()
                            ->addFieldToFilter('rp_destination',array('in', array(MDN_ProductReturn_Model_RmaProducts::kDestinationStock, MDN_ProductReturn_Model_RmaProducts::kDestinationDestroy)))
                            ->addFieldToFilter('sent_to_logistic_company', '0');

        foreach($collection as $item)
        {
            $rmaProductIds[] = $item->getId();
        }

        //send csv to server only if there is 1+ RMA
        if (count($rmaProductIds) > 0)
        {
            //generate csv
            $csv = $this->getRmaProductCsv($rmaProductIds);

            //process file
            $this->saveAndUploadFile($streamCode, $csv, $this->getRemoteDirectory());
        }


        //return result
        $data = array(
            'error' => false,
            'entity_ids' => implode(',', $rmaProductIds),
            'result' => count($rmaProductIds).' RMA Product sent',
            'logistic_stream_code' => $streamCode
        );

        return $data;
    }

    protected function getRmaProductCsv($rmaProductIds){

        $retour = '';

        // header
        $retour = "RMA_ID\tART_ID\tART_QTE\tART_ACTION\n";

        // content
        foreach($rmaProductIds as $id){

            $rmaProduct = Mage::getModel('ProductReturn/RmaProducts')->load($id);

            $product = Mage::getModel('catalog/product')->load($rmaProduct->getrp_product_id());
            $action = $this->getAction($rmaProduct->getrp_destination());

            $retour .= "\"".$rmaProduct->getrp_rma_id()."\"\t"; // RMA_ID
            $retour .= "\"".$product->getsku()."\"\t"; //ART_ID*
            $retour .= "\"".$rmaProduct->getrp_qty()."\"\t"; //ART_QTE*
            $retour .= "\"".$action."\"\n";// ART_ACTION

        }

        return $retour;
    }

    protected function getAction($action){

        $retour = '';

        switch($action){
            case MDN_ProductReturn_Model_RmaProducts::kDestinationStock:
                $retour = self::kBackToStock;
                break;

            case MDN_ProductReturn_Model_RmaProducts::kDestinationDestroy:
                $retour = self::kDestroy;
                break;

        }

        return $retour;

    }
    

}
