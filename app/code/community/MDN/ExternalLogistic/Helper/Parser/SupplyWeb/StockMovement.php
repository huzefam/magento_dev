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
class MDN_ExternalLogistic_Helper_Parser_SupplyWeb_StockMovement extends MDN_ExternalLogistic_Helper_Parser_SupplyWeb_Abstract {

    const kMnemonique = "MVT01";
    const kMvtPath = "out/";

    const kOP_CODE = "OP_CODE";
    const kQUANTITE = "QUNATITE";
    const kCODE_SOC = "CODE_SOC";
    const kCODE_ARTICLE = "CODE_ARTICLE";
    const kLOT = "LOT";
    const kDATE_DLV = "DATE_DLV";
    const kMOTIF = "MOTIF";
    const kSENS = "SENS";

    /**
     * Process Stock Movement file
     *
     * @return array $data
     */
    public function process() {

        $result = '';

        //download every MVT_STOCK files
        $streamCode = $this->getStreamCode();
        $workingDirectory = $this->getWorkingDirectory($streamCode);
        $ftp = $this->getFtpObject();
        $mvStockFiles = $ftp->downloadFilesMatchingPattern(self::kMvtPath, array("/".self::kMnemonique."/i"), $workingDirectory);
        $error = false;

        sort($mvStockFiles);
  
        if (count($mvStockFiles) > 0)
        {
            foreach($mvStockFiles as $stream){

                try{

                    if(preg_match("#\.dat$#i",$stream['localpath'])){
                        if($this->checkBalise($stream)){
                            $data = $this->parseFile($stream);
                            $result .= $this->createStockMovement($data['stock_movement']);
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

    /**
     * Parse file, build an array which contain 1 array for stock movement and
     * 1 array for po deliveries
     *
     * @param array $stream
     * @return array $retour
     */
    protected function parseFile($stream){

        $retour = array(
            'stock_movement' => array(),
            'po_deliveries' => array()
        );

        $lines = file($stream['localpath']);

        for($i = 0; $i < count($lines); $i++){

            $data = $this->buildDataArray($lines[$i]);

            // bad content
            if(count($data) < 4)
                continue;

            // add new stock movement to process
            $retour['stock_movement'][] = $data;


        }

        return $retour;

    }

    protected function buildDataArray($line){

        $retour = array(
                self::kOP_CODE => $this->getValue(substr($line, 0, 10), self::kTypeAlpha),
                self::kCODE_SOC => $this->getValue(substr($line, 10, 20), self::kTypeAlpha),
                self::kCODE_ARTICLE => $this->getValue(substr($line, 30,50), self::kTypeAlpha),
                self::kQUANTITE => $this->getValue(substr($line, 80, 10), self::kTypeNum),
                self::kLOT => $this->getValue(substr($line, 90, 50), self::kTypeAlpha),
                self::kDATE_DLV => $this->getValue(substr($line, 140, 10), self::kTypeNum),
                self::kMOTIF => $this->getValue(substr($line, 150, 50), self::kTypeAlpha)
        );

        $retour[self::kSENS] = ($retour[self::kMOTIF] == 'POS') ? '+' : '-';

        return $retour;
    }

    /**
     * Create stock Movements
     *
     * @param array $tab
     * @return string $retour or throw an Exception
     */
    protected function createStockMovement($tab){

        $retour = '';
        $error = false;
        $description = '';
        $i = 0;

        $warehouseId = Mage::getStoreConfig('externallogistic/logsys/warehouse');

        foreach($tab as $data){

            try{

                // create stock movement
                mage::helper('ExternalLogistic/StockMovement')
                    ->createStockMovement(
                            trim($data[self::kCODE_ARTICLE]),
                            trim($data[self::kQUANTITE]),
                            (trim($data[self::kSENS]) == '+'),
                            MDN_ExternalLogistic_Helper_StockMovement::kSourceTypeUndefined,
                            null,
                            $this->getMovementDescription($data[self::kMOTIF]),
                            $warehouseId
                    );

                    $i++;

            }catch(Exception $e){
                $error = true;
                $description .= $e->getMessage().', ';
            }
            
        }


        if(!$error){
            return $i.' stock movement processed.';
        }else{
            throw new Exception($description.$i.' stock movement processed.');
        }


    }

    protected function getMovementDescription($motif){

        $retour = '';

        switch($motif){

            case 'POS':
                $retour = 'Ajout';
                break;

            case 'NEG':
                $retour = 'Destruction';
                break;

        }

        return $retour;

    }

    protected function getMnemonique(){
        return self::kMnemonique;
    }

}