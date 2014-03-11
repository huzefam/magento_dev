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
class MDN_ExternalLogistic_Helper_Parser_L4logistic_StockStatus extends MDN_ExternalLogistic_Helper_Parser_L4logistic_Abstract {

    public function process() {
        //download every EDI files
        $workingDirectory = $this->getWorkingDirectory('STKETA');
        $ftp = $this->getFtpObject();
        $stkEtaFiles = $ftp->downloadFilesMatchingPattern('OUT/', array("/STKETA/i"), $workingDirectory);
        $result = '';

        //unzip files
        $error = false;
        if (count($stkEtaFiles) > 0) {
            foreach ($stkEtaFiles as $stkEta) {

                //unzip file
                $zipPath = $stkEta['localpath'];
                $xmlPath = str_replace('.zip', '', $zipPath);
                mage::helper('ExternalLogistic/Zip')->unzip($zipPath, $workingDirectory);
                unlink($zipPath);

                //process file
                $products = $this->readFile($xmlPath);

                //check stocks for sku in xml file
                $helper = mage::helper('ExternalLogistic/StockStatus');
                $productIds = array();
                foreach ($products as $product) {
                    $productId = mage::getModel('catalog/product')->getIdBySku($product['sku']);
                    $productIds[] = $productId;
                    $helper->checkProductQty($product);
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
                $result .= 'File ' . basename($xmlPath) . ' processed, ';

                //delete file on server
                $ftp->deleteRemoteFile($stkEta['remotepath']);
            }
        }
        else
            $result = 'No file to process';

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
     * Read xml file and return an array with sku => qty
     */
    protected function readFile($xmlPath) {
        $data = array();

        $prefix = mage::getStoreConfig('externallogistic/l4logistic/prefix');

        //read xml file
        $xmlDoc = new DOMDocument();
        $xmlDoc->load($xmlPath);
        $rootElement = $xmlDoc->documentElement;
        $produitsElement = $rootElement->getElementsByTagName("Produits")->item(0);

        //fill data
        $data = array();
        foreach ($produitsElement->getElementsByTagName("Produit") as $node) {
            $sku = $node->getElementsByTagName("Reference")->item(0)->nodeValue;
            $sku = str_replace($prefix, '', $sku);
            $qty = $node->getElementsByTagName("QuantiteTotale")->item(0)->nodeValue;

            $item = array('sku' => $sku, 'qty' => $qty);
            $data[] = $item;
        }

        return $data;
    }

}