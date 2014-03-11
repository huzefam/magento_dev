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
class MDN_ExternalLogistic_Helper_StockStatus extends Mage_Core_Helper_Abstract
{
	/**
	 * check product qty
	 *
	 * @param unknown_type $data
	 */
	public function checkProductQty($data, $warehouseId = 1)
	{
		$qty = $data['qty'];
		$sku = $data['sku'];

		//check if there is difference
		$productId = mage::getModel('catalog/product')->getIdBySku($sku);
		if ($productId)
		{
			$stockItem = $this->getStockItem($productId, $warehouseId);
			if ($stockItem == null)
				return false;
				
			if (((int)$stockItem->getQty()) != ((int)$qty))
			{
				$product = mage::getModel('catalog/product')->load($productId);
			
				//if difference, log
				$body = '************************************************'."\n";
				$body .= 'Stock error for '.$product->getName()."\n";
				$body .= 'Sku : '.$product->getsku()."\n";
				$body .='Stock level in magento : '.((int)$stockItem->getQty())."\n";
				$body .= 'Stock level in warehouse : '.$qty."\n";

				$this->log($body);
			}
		}
		else
                {
				$body = '************************************************'."\n";
				$body .= 'Unable to find product with sku = '.$sku."\n";
                }
	}

	public function getStockItem($productId, $warehouseId)
	{
		$stockItem = mage::getModel('cataloginventory/stock_item')
						->getCollection()
						->addFieldToFilter('product_id', $productId)
						->addFieldToFilter('stock_id', $warehouseId)
						->getFirstItem();
		if ($stockItem != null)
		{
			if (!$stockItem->getId())
				$stockItem = null;
		}
		
		return $stockItem;
	}
	

	public function addManualException($productId, $logisticStockLevel, $magentoStockLevel)
	{
		$product = mage::getModel('catalog/product')->load($productId);
		$body = '************************************************'."\n";
		$body .= 'Stock error for '.$product->getName()."\n";
		$body .= 'Sku : '.$product->getsku()."\n";
		$body .='Stock level in magento : '.((int)$magentoStockLevel)."\n";
		$body .= 'Stock level in warehouse : '.$logisticStockLevel."\n";

		$this->log($body);
	}

	/**
	 * Init report file
	 *
	 */
	public function initReport()
	{
		$path = $this->getReportPath();
		
		//delete file
		if (file_exists($path))
			unlink($path);
			
	}
	
	/**
	 * Transmit report file
	 *
	 */
	public function transmitReport()
	{
		$path = $this->getReportPath();
		if (!file_exists($path))
			return false;		
		
		$stockManagerEmail = mage::getStoreConfig('externallogistic/misc/stockmanager_email');
		
		$body = file_get_contents($path);
		
		$subject = 'Stock error report';

		mage::helper('ExternalLogistic/Mail')->sendMail($stockManagerEmail, $subject, $body);

		unlink($path);
		
		return $body;
	}
	
	/**
	 * Write text in report file
	 *
	 * @param unknown_type $txt
	 */
	protected function log($txt)
	{
		$path = $this->getReportPath();

		$f = fopen($path, 'a');
		fwrite($f, $txt);
		fclose($f);
	}
	
	/**
	 * Return report path
	 *
	 */
	protected function getReportPath()
	{
		$path = $mainDirectory = Mage::getBaseDir('var').'/external_logistic/stock_status_report.txt';
		return $path;
	}
}