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
class MDN_ExternalLogistic_Helper_Parser_Dimotrans_StockStatus extends MDN_ExternalLogistic_Helper_Parser_Dimotrans_Abstract
{
    public function process()
    {
            
        $warehouseId = mage::getStoreConfig('externallogistic/dimotrans/warehouse');
		
        //download every EDI files
        $workingDirectory = $this->getWorkingDirectory('STOCK');
        $ftp = $this->getFtpObject();
        $files = $ftp->downloadFilesMatchingPattern('Receive/', array("/STOCK_/i"), $workingDirectory);
        $result = '';
		$error = false;
			
        if (count($files) > 0)
        {
            foreach($files as $file)
            {
                $path = $file['localpath'];
				$datas = $this->getStocksStatus($path);
				
				foreach($datas as $product)
				{
					$helper = mage::helper('ExternalLogistic/StockStatus');
					$helper->checkProductQty($product, $warehouseId);				
				}

                $compareResult = $helper->transmitReport();
				if ($compareResult != '')
					$error = true;
                $result .= 'File ' . basename($path) . ' processed : '.$compareResult;
				
				//delete file on server
				$ftp->deleteRemoteFile($file['remotepath']);
				
            }
		}
        else
            $result = 'No file to process';
			
        //return result
        $data = array(
            'error' => $error,
            'entity_ids' => '',
            'result' => $result,
            'logistic_stream_code' => ''
        );

        return $data;
    }
	
	protected function getStocksStatus($xmlPath)
	{
		$data = array();
		
        //read xml file
        $xmlDoc = new DOMDocument();
        $xmlDoc->load($xmlPath);
        $rootElement = $xmlDoc->documentElement;
		
        //parse table markups
        foreach ($rootElement->getElementsByTagName("Table") as $node) {

			//set PO data
			$product = array();
			$product['sku'] = $node->getElementsByTagName("ART_CODE")->item(0)->nodeValue;
			$product['qty'] = $node->getElementsByTagName("STK_QTE")->item(0)->nodeValue;

            $data[] = $product;
        }
		
		return $data;
	}
    
}