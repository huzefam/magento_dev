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
class MDN_ExternalLogistic_Helper_Parser_Dimotrans_PurchaseOrderDelivery extends MDN_ExternalLogistic_Helper_Parser_Dimotrans_Abstract
{
    public function process()
    {
        $warehouseId = mage::getStoreConfig('externallogistic/dimotrans/warehouse');
		
        //download every EDI files
        $workingDirectory = $this->getWorkingDirectory('CONFREC');
        $ftp = $this->getFtpObject();
        $files = $ftp->downloadFilesMatchingPattern('Receive/', array("/CONFREC_/i"), $workingDirectory);
        $result = '';
				
		//process files
        $error = false;
        if (count($files) > 0)
        {
            foreach($files as $file)
            {
                $path = $file['localpath'];
				$datas = $this->getPoDeliveries($path);
				
                foreach($datas as $po)
                {
                    try
                    {
						$purchaseOrder = mage::getModel('Purchase/Order')->load($po['increment_id'], 'po_order_id');
						$purchaseOrderUpdater = mage::getModel('Purchase/Order_Updater')->init($purchaseOrder);
						
                        mage::helper('ExternalLogistic/PurchaseOrder')->createDelivery($po, $warehouseId);
                        $result .= 'PO  #'.$po['increment_id'].' processed, '."\n";
						
						//update order status & delivery percent
						$purchaseOrder->computeDeliveryProgress();
						
						//toggle to complete
						if ($purchaseOrder->isCompletelyDelivered())
							$purchaseOrder->setpo_status(MDN_Purchase_Model_Order::STATUS_COMPLETE)->save();
							
						//apply updater
						$result = $purchaseOrderUpdater->checkForChangesAndLaunchUpdates($purchaseOrder);
                    }
                    catch(Exception $ex)
                    {
                        $result .= 'Error : '.$ex->getMessage();
                        $error = true;
                    }
                }

				//delete file on server
				//$ftp->deleteRemoteFile($file['remotepath']);
				
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
	
	/**
	 * Parse xml files and return datas as array
	 */
	public function getPoDeliveries($xmlPath)
	{
		$retour = array();
		
        //read xml file
        $xmlDoc = new DOMDocument();
        $xmlDoc->load($xmlPath);
        $rootElement = $xmlDoc->documentElement;
		
        //parse table markups
        foreach ($rootElement->getElementsByTagName("Table") as $node) {

			//set PO data
			$po = array();
			$po['increment_id'] = $node->getElementsByTagName("REE_NOFO")->item(0)->nodeValue;
			$po['delivery_date'] = date('Y-m-d');
			$po['products'] = array();
		
			//set product data
			$product = array();
			$product['sku'] = $node->getElementsByTagName("ART_CODE")->item(0)->nodeValue;
			$product['qty'] = $node->getElementsByTagName("REL_QTRE")->item(0)->nodeValue;
			$po['products'][] = $product;

            $retour[] = $po;
        }
		
		return $retour;
	}
    
}