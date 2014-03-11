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
class MDN_ExternalLogistic_Helper_Parser_Dropshipper_DownloadStockLevel extends MDN_ExternalLogistic_Helper_Parser_Dropshipper_Abstract
{
    public function process()
    {
        //collect info
        $filePath = mage::getStoreConfig('externallogistic/dropshipper/stock_file_path');
        $supplierId = mage::getStoreConfig('externallogistic/dropshipper/supplier');
        $warehouseId = mage::getStoreConfig('externallogistic/dropshipper/warehouse');

        //read file
        if (file_exists($filePath))
        {
			$lines = file($filePath);

			$debug = '';
			$skippedProduct = 0;
			$updatedProduct = 0;

			//parse file
			for($i=3;$i<count($lines);$i++)
			{
				//skip incorrect lines
				$line = $lines[$i];
				$fields = explode(';', $line);
				if (count($fields) < 2)
					continue;

				//get data
				$supplierSku = $fields[0];
				
				//$price = $fields[1];
				//$price = str_replace(',', '.', $price);
				//$price = str_replace('€', '', $price);
				//$price = str_replace(' ', '', $price);
				
				$stockLevel = (int)$fields[1];
				if (!is_numeric($stockLevel))
					$stockLevel = 0;
					
				//force stock level at 0 if it is lower than 3
				//todo : add parameter in system config to define this limit
				if ($stockLevel <=3)
					$stockLevel = 0;

				//second check
				if ($supplierSku == '')
					continue;

				//retrieve product
				$productId = mage::getModel('Purchase/ProductSupplier')->getProductIdFromSupplierSku($supplierSku, $supplierId);
				if (!$productId)
				{
					$debug .= 'Unable to find product with supplier sku = '.$supplierSku."\n";
					$skippedProduct++;
				}
				else
				{
					$debug .= 'Update product with sku = '.$supplierSku."\n";
					
					try
					{
						//update stock level
						$stockItem = mage::getModel('cataloginventory/stock_item')->loadByProductWarehouse($productId, $warehouseId);
						if ($stockItem == null)
						{
							//if no stock item, create a stock movement
							mage::getModel('AdvancedStock/StockMovement')->createStockMovement($productId, null, $warehouseId, $stockLevel, 'Init drop shipper stock');
						}
						else
						{
							//if stock item exists, update qty
							$stockItem->setqty($stockLevel)->save();
						}

						//update information between product & supplier (buy price)
						$productSupplier = mage::getModel('Purchase/ProductSupplier')->loadForProductSupplier($productId, $supplierId);
						$productSupplier->setpps_quantity_product($stockLevel)
										->save();

						$updatedProduct++;
						
					}
					catch (Exception $ex)
					{
						$debug .= 'Error for product #'.$productId.' : '.$ex->getMessage();
					}
					

				}
			}
			
			//delete file
			unlink($filePath);
		}
		else
			$debug = 'File is missing';
			
        //return result
        $data = array(
            'error' => false,
            'entity_ids' => '',
            'result' => $updatedProduct.' product updated, '.$skippedProduct.' product skipped, '.$debug,
            'logistic_stream_code' => ''
        );
        return $data;
    }
}