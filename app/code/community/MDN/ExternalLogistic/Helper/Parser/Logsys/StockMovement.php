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
class MDN_ExternalLogistic_Helper_Parser_Logsys_StockMovement extends MDN_ExternalLogistic_Helper_Parser_Logsys_Abstract {

    const kMvStock = "MVT";
    const kMvStockPath = "out/mvt/";

    const kINDEX_MVT_TYPE = 0;
    const kINDEX_ART_ID = 1;
    const kINDEX_MVT_SENS = 2;
    const kINDEX_ART_QTE = 3;
    const kINDEX_CMDF_ID = 4;
    const kINDEX_ART_ACTION = 5;

    const kMovementReception = "R";
    const kMovementDestruction = "D";
    const kMovementTransfert = "T";
    const kMovementSortie = "S";
    const kMovementInventaire = "I";

    /**
     * Process Stock Movement file
     *
     * @return array $data
     */
    public function process() {

        $result = '';

        //download every MVT_STOCK files
        $workingDirectory = $this->getWorkingDirectory(self::kMvStock);
        $ftp = $this->getFtpObject();
        $mvStockFiles = $ftp->downloadFilesMatchingPattern(self::kMvStockPath, array("/".self::kMvStock."_/i"), $workingDirectory);
        $error = false;

        sort($mvStockFiles);

        if (count($mvStockFiles) > 0)
        {
            foreach($mvStockFiles as $stream){

                try{
                    // processing
                    if($this->checkFile($stream)){

                        $data = $this->parseFile($stream);

                        $result .= $this->createStockMovement($data['stock_movement']);
                        $result .= $this->createPoDeliveries($data['po_deliveries']);

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
     * Check file header
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

            // skip header
            if($i == 0)
                continue;

            $data = explode("\t", $lines[$i]);


            $data = $this->cleanValues($data);

            //echo Zend_debug::dump($data);die('44');

            // bad content
            if(count($data) < 5 || in_array($data[self::kINDEX_MVT_TYPE], array('S')))
                continue;

            //echo $data[self::kINDEX_CMDF_ID];die();
            //echo '<pre>';var_dump($data);die('</pre>');

            if(empty($data[self::kINDEX_CMDF_ID])){

                // add new stock movement to process
                $retour['stock_movement'][] = $data;

            }else{

                // check if purchase order id entry exists, if not add it
                if(!array_key_exists($data[self::kINDEX_CMDF_ID], $retour['po_deliveries'])){
                    $retour['po_deliveries'][$data[self::kINDEX_CMDF_ID]] = array(
                        'increment_id' => $data[self::kINDEX_CMDF_ID],
                        'delivery_date' => date('Y-m-d', Mage::getModel('core/date')->timestamp()),
                        'products' => array()
                    );
                }

                // add product to purchase order entry
                if($data[self::kINDEX_ART_QTE] > 0){
                    $retour['po_deliveries'][$data[self::kINDEX_CMDF_ID]]['products'][] = array(
                        'sku' => $data[self::kINDEX_ART_ID],
                        'qty' => $data[self::kINDEX_ART_QTE]
                    );
                }

            }

        }

        //echo '<pre>';var_dump($retour);die('</pre>');

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
                            trim($data[self::kINDEX_ART_ID]),
                            trim($data[self::kINDEX_ART_QTE]),
                            (trim($data[self::kINDEX_MVT_SENS]) == '+'),
                            MDN_ExternalLogistic_Helper_StockMovement::kSourceTypeUndefined,
                            null,
                            $this->getMovementDescription($data[self::kINDEX_MVT_TYPE]),
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

    /**
     * Create purchase order deliveries
     *
     * @param array $tab
     * @return string $result or throw an Exception
     */
    protected function createPoDeliveries($tab){

        $warehouseId = Mage::getStoreConfig('externallogistic/logsys/warehouse');
        $error = false;
        $result = '';

        foreach($tab as $po){

            try{

                Mage::Helper('ExternalLogistic/PurchaseOrder')->createDelivery($po, $warehouseId);
                $result .=  'PO '.$po['increment_id'].' processed, '."\n";

            }catch(Exception $e){

                $result .= 'Error : '.$e->getMessage();
                $error = true;

            }

        }

        if(!$error){
            return $result;
        }else{
            throw new Exception($result);
        }

    }

    /**
     * Get stock movement description
     *
     * @param string $type
     * @return string $retour
     */
    protected function getMovementDescription($type){

        $retour = '';

        switch($type){

            case self::kMovementReception:
                $retour = "Réception";
                break;

            case self::kMovementDestruction:
                $retour = "Destruction";
                break;

            case self::kMovementTransfert:
                $retour = "Transfert";
                break;

            case self::kMovementSortie:
                $retour = "Sortie";
                break;

            case self::kMovementInventaire:
                $retour = "Inventaire";
                break;

            default:
                $retour = "Non défini...";
                break;

        }

        return $retour;
    }

    protected function getFileHeader(){
        return "MVT_TYPE\tART_ID\tMVT_SENS\tART_QTE\tCMDF_ID\tART_ACTION";
    }

    protected function getStreamCode(){
        return $this->getStreamPrefix().'_'.date('YmdHis', Mage::getModel('core/date')->timestamp());
    }

    protected function getStreamPrefix(){
        return self::kMvStock;
    }

    protected function getRemoteDirectory(){
        return self::kMvStockPath;
    }

}