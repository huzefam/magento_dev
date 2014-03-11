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
class MDN_ExternalLogistic_Helper_Parser_Dimotrans_StockMovement extends MDN_ExternalLogistic_Helper_Parser_Dimotrans_Abstract
{
    public function process()
    {
        $warehouseId = mage::getStoreConfig('externallogistic/dimotrans/warehouse');
		
        //download every EDI files
        $workingDirectory = $this->getWorkingDirectory('MVTDIV');
        $ftp = $this->getFtpObject();
        $files = $ftp->downloadFilesMatchingPattern('Receive/', array("/MVTDIV_/i"), $workingDirectory);
        $result = '';
		$error = false;
		
        if (count($files) > 0)
        {
            foreach($files as $file)
            {
                $path = $file['localpath'];
				$datas = $this->getStocksMovements($path);
				
				$i = 0;
				foreach($datas as $mvt)
				{
					try {
						mage::helper('ExternalLogistic/StockMovement')
								->createStockMovement($mvt['sku'],
										$mvt['qty'],
										($mvt['direction'] == '+'),
										MDN_ExternalLogistic_Helper_StockMovement::kSourceTypeUndefined,
										null,
										$mvt['description'],
										 $warehouseId
						);
						$i++;
					} catch (Exception $ex) {
						$error = true;
						$result .= $ex->getMessage() . ', ';
					}	
				}

                $result .= 'File ' . basename($path) . ' processed, '.$i.' stock movement created';
				
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
    
	protected function getStocksMovements($xmlPath)
	{
		$data = array();
		
        //read xml file
        $xmlDoc = new DOMDocument();
        $xmlDoc->load($xmlPath);
        $rootElement = $xmlDoc->documentElement;
		
        //parse table markups
        foreach ($rootElement->getElementsByTagName("Table") as $node) 
		{
			$mvt = array();
			$mvt['sku'] = $node->getElementsByTagName("ART_CODE")->item(0)->nodeValue;
			$mvt['qty'] = $node->getElementsByTagName("MVT_QTE")->item(0)->nodeValue;
			$mvt['direction'] = $node->getElementsByTagName("MVT_SENS")->item(0)->nodeValue;
			$mvt['description'] = $node->getElementsByTagName("MVT_MOT")->item(0)->nodeValue;
			$data[] = $mvt;
		}
		
		return $data;
	}
}