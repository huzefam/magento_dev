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
class MDN_ExternalLogistic_Helper_Parser_Logsys_DownloadStockLevel extends MDN_ExternalLogistic_Helper_Parser_Logsys_Abstract
{

    const kStock = "STK";
    const kStockPath = "out/stock/";

    const kINDEX_ART_ID = 0;
    const kINDEX_ART_QTE = 1;

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
        $workingDirectory = $this->getWorkingDirectory(self::kStock);
        $ftp = $this->getFtpObject();
        $stockFiles = $ftp->downloadFilesMatchingPattern(self::kStockPath, array("/".self::kStock."_/i"), $workingDirectory);

        sort($stockFiles);

        if (count($stockFiles) > 0)
        {
            foreach($stockFiles as $stream){

                try{
                    // processing
                    if($this->checkFile($stream)){

                        $result .= $this->matchStock($stream);
                        $result .= $this->archive($stream, $ftp);

                    }else{
                        // bad format
                        $result .= $this->echec($stream, $ftp);

                    }
                }catch(Exception $e){
                    $error = true;
                    $result .= $e->getMessage();
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

    /**
     * Check stock file
     *
     * @param array $stream
     * @return boolean
     */
    protected function checkFile($stream){

        $retour = false;

        $lines = file($stream['localpath']);

        if(preg_match("#".$this->getFileHeader()."#", $lines[0]))
            $retour = true;

        return $retour;
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

            if($i == 0)
                continue;

            $data = explode("\t",$lines[$i]);

            $data = $this->cleanValues($data);

            if(count($data) < 2)
                continue;

            //process file

            //check stocks for sku
            $productId = mage::getModel('catalog/product')->getIdBySku($data[self::kINDEX_ART_ID]);
            $productIds[] = $productId;
            $helper->checkProductQty(
                    array(
                        'sku' => $data[self::kINDEX_ART_ID],
                        'qty' => $data[self::kINDEX_ART_QTE]
                    )
            );

        }
        
        //send report
        $helper->transmitReport();
        $result .= 'File ' . basename($stream['localpath']) . ' processed, ';

        return $result;

    }

    protected function getFileHeader(){
        return "ART_ID\tART_QTE";
    }

    protected function getStreamCode(){
        return $this->getStreamPrefix().'_'.date('YmdHis', Mage::getModel('core/date')->timestamp());
    }

    protected function getStreamPrefix(){
        return self::kStock;
    }

    protected function getRemoteDirectory(){
        return self::kStockPath;
    }
    
}