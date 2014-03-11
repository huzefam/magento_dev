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
class MDN_ExternalLogistic_Helper_Parser_SupplyWeb_PurchaseOrderDelivery extends MDN_ExternalLogistic_Helper_Parser_SupplyWeb_Abstract
{

    const kMnemonique = "CRR01";
    const kRecepPath = "out/";

    const kOP_CODE = "OP_CODE";
    const kCODE_SOC = "CODE_SOC";
    const kN_PIECE = "N_PIECE";
    const kDATE_RECEP = "DATE_RECEP";
    const kCODE_ART = "CODE_ART";
    const kQTE = "QTE";
    const kNO_LOT = "NO_LOT";
    const kDATE_DLV = "DATE_DLV";
    const kCOMMT = "COMMT";

    public function process()
    {
        $result = '';

        //download every files
        $streamCode = $this->getStreamCode();
        $workingDirectory = $this->getWorkingDirectory($streamCode);
        $ftp = $this->getFtpObject();
        $recepFiles = $ftp->downloadFilesMatchingPattern(self::kRecepPath, array("/".self::kMnemonique."/i"), $workingDirectory);
        $error = false;
        $warehouseId = mage::getStoreConfig('externallogistic/supplyweb/warehouse');

        sort($recepFiles);
  
        if (count($recepFiles) > 0)
        {
            foreach($recepFiles as $stream){

                try{

                    if(preg_match("#\.dat$#i", $stream['localpath'])){
                        if($this->checkBalise($stream)){

                            $deliveries = $this->parseFile($stream);

                            foreach($deliveries as $id => $po){

                                $poIncrementId = $po['increment_id'];
                                $purchaseOrder = Mage::getModel('Purchase/Order')->load($poIncrementId, 'po_order_id');
                                if ($purchaseOrder->getId())
                                {
                                    //process PO
                                    mage::helper('ExternalLogistic/PurchaseOrder')->createDelivery($po, $warehouseId);
                                    $result .= 'PO  #'.$po['increment_id'].' processed, '."\n";
                                }
                                else
                                {
                                    //process "free" stock movement
                                    $result .= 'PO #'.$poIncrementId.' doesnt exists, process free stock movement';
                                    $description = 'From reception #'.$poIncrementId;
                                    foreach($po['products'] as $product)
                                    {
                                        $qty = $product['qty'];
                                        $sku = $product['sku'];
                                        $productId = mage::getModel('catalog/product')->getIdBySku($sku);
                                        Mage::helper('ExternalLogistic/StockMovement')->createStockMovement($sku, $qty, true, 'undefined', null, $description);
                                    }

                                }

                            }
                            $result .= $this->archive($stream, $ftp);

                        }else{
                            $result .= 'Balise for file '.$stream['remotepath'].' isn\'t available';
                        }
                    }
                        
                }catch(Exception $e){
                    $error = true;
                    $result .= $e->getMessage();
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
            'logistic_stream_code' => $streamCode
        );
        return $data;
    }

    protected function parseFile($stream){

        $retour = array();

        $lines = file($stream['localpath']);

        for($i = 0; $i < count($lines); $i++){

            $data = $this->buildDataArray($lines[$i]);

            if(!array_key_exists($data[self::kN_PIECE], $retour)){

                $retour[$data[self::kN_PIECE]] = array(
                    'increment_id' => $data[self::kN_PIECE],
                    'delivery_date' => $data[self::kDATE_RECEP],
                    'products' => array()
                );

            }

            if($data[self::kQTE] > 0){
                $retour[$data[self::kN_PIECE]]['products'][] = array(
                    'sku' => $data[self::kCODE_ART],
                    'qty' => $data[self::kQTE]
                );
            }

        }

        return $retour;

    }

    protected function buildDataArray($line){

        $retour = array(
            self::kOP_CODE => $this->getValue(substr($line, 0, 10), self::kTypeAlpha),
            self::kCODE_SOC => $this->getValue(substr($line, 10, 20), self::kTypeAlpha),
            self::kN_PIECE => $this->getValue(substr($line, 30, 20), self::kTypeAlpha),
            self::kDATE_RECEP => $this->getValue(substr($line, 50, 8), self::kTypeNum),
            self::kCODE_ART => $this->getValue(substr($line, 58, 50), self::kTypeAlpha),
            self::kQTE => $this->getValue(substr($line, 108, 10), self::kTypeNum),
            self::kNO_LOT => $this->getValue(substr($line, 118, 20), self::kTypeAlpha),
            self::kDATE_DLV => $this->getValue(substr($line, 138, 8), self::kTypeNum),
            self::kCOMMT => $this->getValue(substr($line, 146, 100), self::kTypeAlpha)
        );

        return $retour;

    }

    protected function getMnemonique(){
        return self::kMnemonique;
    }
    
}