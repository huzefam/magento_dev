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
class MDN_ExternalLogistic_Helper_Parser_L4logistic_PurchaseOrderDelivery extends MDN_ExternalLogistic_Helper_Parser_L4logistic_Abstract
{
	
    public function process()
    {

        $warehouseId = mage::getStoreConfig('externallogistic/l4logistic/warehouse');

        //download every EDI files
        $workingDirectory = $this->getWorkingDirectory('CRAPRO');
        $ftp = $this->getFtpObject();
        $craProFiles = $ftp->downloadFilesMatchingPattern('OUT/', array("/CRAPRO/i"), $workingDirectory);
        $result = '';

        //unzip files
        $error = false;
        if (count($craProFiles) > 0)
        {
            foreach($craProFiles as $craPro)
            {
                //unzip file
                $zipPath = $craPro['localpath'];
                $xmlPath = str_replace('.zip', '', $zipPath);
                mage::helper('ExternalLogistic/Zip')->unzip($zipPath, $workingDirectory);
                unlink($zipPath);

                //process file
                $craProData = $this->getPoDeliveries($xmlPath);
                foreach($craProData as $po)
                {
                    try
                    {
                        mage::helper('ExternalLogistic/PurchaseOrder')->createDelivery($po, $warehouseId);
                        $result .= 'PO  #'.$po['increment_id'].' processed, '."\n";

                        //delete file on server
                        $ftp->deleteRemoteFile($craPro['remotepath']);
                    }
                    catch(Exception $ex)
                    {
                        $result .= 'Error : '.$ex->getMessage();
                        $error = true;
                    }
                }

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


    protected function getPoDeliveries($xmlPath)
    {
        $data = array();

        $prefix = mage::getStoreConfig('externallogistic/l4logistic/prefix');

        //read xml file
        $xmlDoc = new DOMDocument();
        $xmlDoc->load($xmlPath);
        $rootElement = $xmlDoc->documentElement;

        //parse PO
        foreach ($rootElement->getElementsByTagName("Appro") as $node) {

            //main PO information
            $po = array();
            $po['increment_id'] = $node->getElementsByTagName("Reference")->item(0)->nodeValue;
            $po['delivery_date'] = $node->getElementsByTagName("DateDeReceptionEffective")->item(0)->nodeValue;
            $po['products'] = array();
            
            //parse products
            foreach ($node->getElementsByTagName("Ligne") as $nodeLigne) {
                $product = array();
                $product['sku'] = $nodeLigne->getElementsByTagName("CodeArticle")->item(0)->nodeValue;
                $product['sku'] = str_replace($prefix, '', $product['sku']);
                $product['qty'] = (int)$nodeLigne->getElementsByTagName("QuantiteTotaleReceptionnee")->item(0)->nodeValue;

                //add product only if delivered qty > 0
                if ($product['qty'] > 0)
                    $po['products'][] = $product;
            }

            $data[] = $po;
        }

        return $data;
    }
    
}