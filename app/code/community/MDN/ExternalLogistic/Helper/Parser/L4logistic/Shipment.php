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
 * @author : Olivier ZIMMERMANN
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class MDN_ExternalLogistic_Helper_Parser_L4logistic_Shipment extends MDN_ExternalLogistic_Helper_Parser_L4logistic_Abstract {

    public function process() {
        //download every EDI files
        $workingDirectory = $this->getWorkingDirectory('CRPCMD');
        $ftp = $this->getFtpObject();
        $crpCmdFiles = $ftp->downloadFilesMatchingPattern('OUT/', array("/CRPCMD/i"), $workingDirectory);
        $result = '';

        //unzip files
        $error = false;
        if (count($crpCmdFiles) > 0) {
            foreach ($crpCmdFiles as $crpCmdFile) {
                //unzip file
                $zipPath = $crpCmdFile['localpath'];
                $xmlPath = str_replace('.zip', '', $zipPath);
                mage::helper('ExternalLogistic/Zip')->unzip($zipPath, $workingDirectory);
                unlink($zipPath);

                //process file
                $data = $this->readFile($xmlPath);

                //create shipments
                try {
                    $result = $this->createShipments($data);
                } catch (Exception $ex) {
                    $error = true;
                    $result = $ex->getMessage();
                }

                //delete file on server
                $ftp->deleteRemoteFile($crpCmdFile['remotepath']);
            }
        } else {
            $result = 'No file to process';
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
     * Read xml file and return information
     * @param <type> $xmlPath
     */
    protected function readFile($xmlPath) {
        $data = array();

        $prefix = mage::getStoreConfig('externallogistic/l4logistic/prefix');

        //read xml file
        $xmlDoc = new DOMDocument();
        $xmlDoc->load($xmlPath);
        $rootElement = $xmlDoc->documentElement;

        foreach ($rootElement->getElementsByTagName("OP") as $node) {
            //define data
            $orderIncrementId = $node->getElementsByTagName("OrdrePreparation")->item(0)->nodeValue;
            $orderIncrementId = str_replace($prefix, '', $orderIncrementId);

            //create shipment array
            $shipment = array();
            $shipment['order_increment_id'] = $orderIncrementId;
            $shipment['status'] = $node->getElementsByTagName("EtatPreparation")->item(0)->nodeValue;
            $shipment['tracking'] = '';

            if ($node->getElementsByTagName("Colis")->item(0))
                $shipment['tracking'] = $node->getElementsByTagName("Colis")->item(0)->getElementsByTagName("NoColis")->item(0)->nodeValue;

            //add products
            $shipment['products'] = array();
            foreach ($node->getElementsByTagName("Ligne") as $nodeProduct) {
                $sku = $nodeProduct->getElementsByTagName("CodeArticle")->item(0)->nodeValue;
                $sku = str_replace($prefix, '', $sku);
                $qty = $nodeProduct->getElementsByTagName("QuantiteLivree")->item(0)->nodeValue;

                $shipment['products'][] = array('sku' => $sku, 'qty' => $qty);
            }

            $data[] = $shipment;
        }

        return $data;
    }

    /**
     * 
     */
    protected function createShipments($shipments)
    {
        $result = '';
        $error = false;

        foreach($shipments as $shipment)
        {
            try
            {
                switch($shipment['status'])
                {
                    case '10':  //shipped
                        mage::helper('ExternalLogistic/Shipment')->createShipment($shipment['order_increment_id'],
                                                                                  date('Y-m-d'),
                                                                                  $shipment['products'],
                                                                                  $shipment['tracking']);
                        $result .= 'Order #'.$shipment['order_increment_id'].' shipped, '."\n";
                        break;
                    case '20':  //canceled
                        $result .= 'Order #'.$shipment['order_increment_id'].' canceled, '."\n";
                        //todo : cancel order or throw exception ??
                        break;
                    default:
                        throw new Exception('Shipment status #'.$shipment['status'].' unknown !');
                        break;
                }
                
            }
            catch(Exception $ex)
            {
                $error = true;
                $result .= $ex->getMessage().", \n";
            }
        }

        //return result (or raise error)
        if (!$error)
            return $result;
        else
            throw new Exception ($result);
        }

}