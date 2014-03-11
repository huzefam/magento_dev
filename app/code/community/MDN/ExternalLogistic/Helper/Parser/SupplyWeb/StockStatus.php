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
class MDN_ExternalLogistic_Helper_Parser_SupplyWeb_StockStatus extends MDN_ExternalLogistic_Helper_Parser_SupplyWeb_Abstract {

      const kMnemonique = "STK01";
    const kStockPath = "out/";

    const kOP_CODE = "OP_CODE";
    const kCODE_SOC = "CODE_SOC";
    const kCODE_ARTICLE = "CODE_ARTICLE";
    const kQUANTITE = "QUANTITE";
    const kLOT = "LOT";
    const kDATE_DLV = "DATE_DLV";

    /**
     * Process
     *
     * @return array $data
     */
    public function process()
    {

        // init
        $result = '';
        $error = false;

        //download every STK files
        $workingDirectory = $this->getWorkingDirectory();
        $ftp = $this->getFtpObject();
        $stockFiles = $ftp->downloadFilesMatchingPattern(self::kStockPath, array("/".self::kMnemonique."/i"), $workingDirectory);

        sort($stockFiles);

        if (count($stockFiles) > 0)
        {
            foreach($stockFiles as $stream){

                try{

                    if(preg_match("#\.dat$#i",$stream['localpath'])){
                        if($this->checkBalise($stream)){
                        $result .= $this->matchStock($stream);
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
            'logistic_stream_code' => $this->getStreamCode()
        );

        return $data;

    }

    /**
     * Check qty in stream and qty in magento
     * Log if diff
     *
     * @param array $stream
     * @return string $result
     */
    public function matchStock($stream){

        $result = '';
        $productIds = array();
        $lines = file($stream['localpath']);
        $helper = mage::helper('ExternalLogistic/StockStatus');

        for($i = 0; $i < count($lines) - 1; $i++){

            $data = $this->buildDataArray($lines[$i]);

            //process file

            //check stocks for sku
            $productId = mage::getModel('catalog/product')->getIdBySku($data[self::kCODE_ARTICLE]);
            $productIds[] = $productId;
            $helper->checkProductQty(
                    array(
                        'sku' => $data[self::kCODE_ARTICLE],
                        'qty' => $data[self::kQUANTITE]
                    )
            );

        }

        //check that all other product have stock level = 0
        $collection = mage::getModel('cataloginventory/stock_item')
                            ->getCollection()
                            ->addFieldToFilter('product_id', array('nin' => $productIds));

        foreach($collection as $item)
        {
            $productId = $item->getproduct_id();
            if ($item->getQty() <> 0)
                   $helper->addManualException($productId, 0, $item->getQty());
        }

        //send report
        $helper->transmitReport();
        $result .= 'File ' . basename($stream['localpath']) . ' processed, ';

        return $result;

    }

    protected function buildDataArray($line){

        $retour = array(
            self::kOP_CODE => $this->getValue(substr($line, 0, 10), self::kTypeAlpha),
            self::kCODE_SOC => $this->getValue(substr($line, 10, 20), self::kTypeAlpha),
            self::kCODE_ARTICLE => $this->getValue(substr($line, 30, 50), self::kTypeAlpha),
            self::kQUANTITE => $this->getValue(substr($line, 80, 10), self::kTypeNum),
            self::kLOT => $this->getValue(substr($line, 90, 50), self::kTypeAlpha),
            self::kDATE_DLV => $this->getValue(substr($line, 140, 10), self::kTypeNum)
        );

        return $retour;

    }

    protected function getMnemonique(){
        return self::kMnemonique;
    }


}