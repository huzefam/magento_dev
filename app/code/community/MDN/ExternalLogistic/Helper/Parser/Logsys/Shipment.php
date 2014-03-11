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
class MDN_ExternalLogistic_Helper_Parser_Logsys_Shipment extends MDN_ExternalLogistic_Helper_Parser_Logsys_Abstract {

    const kShipment = "EXP";
    const kShipmentPath = "out/exp/";

    const kINDEX_CMD_ID = 0;
    const kINDEX_CMD_DATE_ENVOI = 1;
    const kINDEX_ART_ID = 2;
    const kINDEX_ART_QTE = 3;
    const kINDEX_CMD_BORDEREAU = 4;

    /**
     * Process
     *
     * @return array $data
     */
    public function process() {

        $result = '';
        $error = false;

        //download every EXP files
        $workingDirectory = $this->getWorkingDirectory(self::kShipment);
        $ftp = $this->getFtpObject();
        $shipmentFiles = $ftp->downloadFilesMatchingPattern(self::kShipmentPath, array("/" . self::kShipment . "_/i"), $workingDirectory);
        
        sort($shipmentFiles);
        
        if (count($shipmentFiles) > 0) {
            foreach ($shipmentFiles as $stream) {

                try{
                    if($this->checkFile($stream)){
                        
                        // processing
                        $result .= $this->createShipments($stream);

                        // archive
                        $result .= $this->archive($stream, $ftp);

                    }else{
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
     * Create shipments
     *
     * @param array $shipments
     * @return string $result
     */
    protected function createShipments($stream) {
        
        $result = '';
        $error = false;

        $shipments = $this->readFile($stream);

        foreach($shipments as $shipment){

            try{
                mage::helper('ExternalLogistic/Shipment')->createShipment($shipment['orderId'],
                        $shipment['shippingDate'],
                        $shipment['products'],
                        $shipment['tracking']);
                $result .= 'Order #' . $shipment['orderId'] . ' shipped, ' . "\n";
            }catch(Exception $e){
                $error = true;
                $result .= $e->getMessage() . ", \n";
            }

        }

        //return result (or raise error)
        if (!$error)
            return $result;
        else
            throw new Exception($result);
    }

    /**
     * Read EXP file
     *
     * @param array $stream
     * @return array $retour
     */
    protected function readFile($stream){

        $retour = array();

        $lines = file($stream['localpath']);

        for($i = 0; $i < count($lines) - 1; $i++){

            if($i == 0)
                continue;

            $data = explode("\t", $lines[$i]);

            $data = $this->cleanValues($data);

            if(count($data) < 5)
                continue;

            if(!array_key_exists($data[self::kINDEX_CMD_ID], $retour)){

                $retour[$data[self::kINDEX_CMD_ID]] = array(
                    'orderId' => $data[self::kINDEX_CMD_ID],
                    'shippingDate' => $data[self::kINDEX_CMD_DATE_ENVOI],
                    'tracking' => $data[self::kINDEX_CMD_BORDEREAU],
                    'products' => array()
                );
            }

            $retour[$data[self::kINDEX_CMD_ID]]['products'][] = array(
                'sku' => $data[self::kINDEX_ART_ID],
                'qty' => $data[self::kINDEX_ART_QTE],
            );

        }

        return $retour;
    }

    protected function getFileHeader(){
        return "CMD_ID\tCMD_DATE_ENVOI\tART_ID\tART_QTE\tCMD_BORDEREAU";
    }

    protected function getRemoteDirectory(){
        return self::kShipmentPath;
    }

    protected function getStreamCode(){
        return $this->getStreamPrefix().'_'.date('YmdHis', Mage::getModel('core/date')->timestamp());
    }

    protected function getStreamPrefix(){
        return self::kShipment;
    }

}