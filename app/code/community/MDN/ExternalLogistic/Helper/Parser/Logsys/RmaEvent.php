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

class MDN_ExternalLogistic_Helper_Parser_Logsys_RmaEvent extends MDN_ExternalLogistic_Helper_Parser_Logsys_Abstract {

    const kRma = "RMA_EVENT";
    const kRmaPath = "out/rma/";

    const kRMA_ID = 0;
    const kRMA_COMMENT_LOG = 1;
    const kART_ID = 2;
    const kART_QTE_RETOUR = 3;
    const kART_COMMENTAIRES = 4;
    const kART_ACTION = 5;
    const kCMD_ID = 6;

    const kPRODUIT_RECU = "produit_recu";
    const kPRODUIT_REMIS_EN_STOCK = "produit_remis_enstock";
    const kPRODUIT_DETRUIT = "produit_detruit";
    const kRENVOYER_AU_CLIENT = "renvoyer_au_client";
    const kRETOUR_NPAI = "retour_npai";

    public function process(){

        $result = '';
        $error = false;

        //download every RMA files
        $workingDirectory = $this->getWorkingDirectory(self::kRma);
        $ftp = $this->getFtpObject();
        $shipmentFiles = $ftp->downloadFilesMatchingPattern(self::kRmaPath, array("/" . self::kRma . "_/i"), $workingDirectory);

        sort($shipmentFiles);

        if (count($shipmentFiles) > 0) {
            foreach ($shipmentFiles as $stream) {

                try{
                    if($this->checkFile($stream)){

                        // processing
                        $result .= $this->updateRma($stream);

                        // archive
                        $result .= $this->archive($stream, $ftp);

                    }else{
                        $result .= $this->echec($stream, $ftp);
                    }
                }catch(Exception $e){
                    $error = true;
                    $result = $e->getMessage();
                    $result .= $this->echec($stream, $ftp);
                }

                $result .= ', ';

            }
        }else{
            $result .= 'No file to process';
        }
        
        //return result
        $data = array(
            'error' => $error,
            'entity_ids' => '',
            'result' => $result,
            'logistic_stream_code' => $this->getStreamCode()
        );

        return $data;

    }

    protected function checkFile($stream){

        $retour = false;

        $lines = file($stream['localpath']);

        if(preg_match("#".$this->getFileHeader()."#", $lines[0]))
            $retour = true;

        return $retour;

    }

    protected function updateRma($stream){

        $result = '';
        $error = false;

        $tab = $this->readFile($stream);

        foreach($tab['rma'] as $k => $elt){

            try{

                $rma = Mage::getModel('ProductReturn/Rma')->load($k);

                foreach($elt['products'] as $rmaProduct){

                    switch($rmaProduct['action']){

                        case self::kPRODUIT_RECU:
                            // TODO : idem product received
                            $rma->productsReceived();
                            break;

                        case self::kPRODUIT_REMIS_EN_STOCK:
                            // nothing to do
                            break;

                        case self::kPRODUIT_DETRUIT:
                            // nothing to do
                            break;

                        case self::kRENVOYER_AU_CLIENT:
                            // nothing to do
                            break;

                        case self::kRETOUR_NPAI:
                            // TODO : generation nouveau RMA associe au num de commande magento avec comme statut de RMA => NPAI
                            /*$increment_id = $rma->getrma_order_id();
                            $rma = Mage::getModel('ProductReturn/Rma');
                            $rma->setrma_order_id($increment_id)
                                    ->setrma_status(MDN_ProductReturn_Model_RMA::kStatusNpai) // TODO : add this status in RMA model and check fields !!!!
                                    ->save();*/
                            break;

                        default:
                            break;
                    }

                }

                $result .= 'RMA #' . $k . ' updated, ' . "\n";
                
            }catch(Exception $e){
                $error = true;
                $result .= $e->getMessage() . ", \n";
            }

        }

        foreach($tab['npai'] as $order_id => $npai){

            try{

                $order = Mage::getModel('sales/order')->loadByIncrementId($order_id);

                $rma = Mage::getModel('ProductReturn/Rma')
                            ->setrma_order_id($order->getentity_id())
                            ->setrma_created_at(date('Y-m-d H:i:s', Mage::getModel('core/date')->timestamp()))
                            ->setrma_status(MDN_ProductReturn_Model_RMA::kStatusNpai)
                            ->setrma_ref(mage::helper('ProductReturn')->createRmaReference($rma)) // set sent_to_external_company = 1 afin de verouiller l'envoi de ce rma ?
                            ->save();

                foreach($npai as $product){

                    $_product = Mage::getModel('catalog/product')->load(Mage::getModel('catalog/product')->getIdBySku($product['sku']));

                    $order_item = Mage::getModel('sales/order_item')->getCollection()
                            ->addFieldToFilter('order_id', $order->getentity_id())
                            ->addFieldToFilter('product_id', $_product->getentity_id())
                            ->getFirstItem();

                    $rmaProduct = Mage::getModel('ProductReturn/RmaProducts')
                        ->setrp_rma_id($rma->getrma_id())
                        ->setrp_product_id($_product->getentity_id())
                        ->setrp_orderitem_id($order_item->getitem_id())
                        ->setrp_qty($product['qty'])
                        ->setrp_product_name($_product->getname())
                        ->save();
                }

                $result .= "NPAI : order n° $order_id, \n";

            }catch(Exception $e){
                $error = true;
                $result .= $e->getMessage().", \n";
             }

        }

        //return result (or raise error)
        if (!$error)
            return $result;
        else
            throw new Exception($result);

    }

    protected function readFile($stream){

        $retour = array(
            'npai' => array(),
            'rma' => array()
        );

        $lines = file($stream['localpath']);

        for($i = 0; $i < count($lines); $i++){

            if($i == 0)
                continue;

            $data = explode("\t", $lines[$i]);

            if(count($data) < 5)
                continue;

            $data = $this->cleanValues($data);

            if($data[self::kRMA_ID] == ""){

                if(!array_key_exists($data[self::kCMD_ID], $retour['npai'])){
                    $retour['npai'][$data[self::kCMD_ID]] = array();
                }
                $retour['npai'][$data[self::kCMD_ID]][] = array(
                    'sku' => $data[self::kART_ID],
                    'qty' => $data[self::kART_QTE_RETOUR]
                );

            }else{

                if(!array_key_exists($data[self::kRMA_ID], $retour['rma'])){

                    $retour['rma'][$data[self::kRMA_ID]] = array(
                        'comment' => $data[self::kRMA_COMMENT_LOG],
                    );
                }

                $retour['rma'][$data[self::kRMA_ID]]['products'][] = array(
                    'sku' => $data[self::kART_ID],
                    'qty' => $data[self::kART_QTE_RETOUR],
                    'comment' => $data[self::kART_COMMENTAIRES],
                    'action' => $data[self::kART_ACTION],
                    'commande' => $data[self::kCMD_ID]
                );

            }

        }

        return $retour;

    }

    protected function getActionType(){

        $retour = array(
            self::kPRODUIT_RECU => self::kPRODUIT_RECU,
            self::kPRODUIT_REMIS_EN_STOCK => self::kPRODUIT_REMIS_EN_STOCK,
            self::kPRODUIT_DETRUIT => self::kPRODUIT_DETRUIT,
            self::kRENVOYER_AU_CLIENT => self::kRENVOYER_AU_CLIENT,
            self::kRETOUR_NPAI => self::kRETOUR_NPAI
        );

        return $retour;

    }

    protected function getFileHeader(){
        return "RMA_ID\tRMA_COMMENT_LOG\tART_ID\tART_QTE_RETOUR\tART_COMMENTAIRES\tART_ACTION\tCMD_ID";
    }

    protected function getRemoteDirectory(){
        return self::kRmaPath;
    }

    protected function getStreamCode(){
        return $this->getStreamPrefix().'_'.date('YmdHis', Mage::getModel('core/date')->timestamp());
    }

    protected function getStreamPrefix(){
        return self::kRma;
    }

}
