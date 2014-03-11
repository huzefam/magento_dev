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
class MDN_ExternalLogistic_Helper_Parser_Bartolini_StockMovement extends MDN_ExternalLogistic_Helper_Parser_Bartolini_Abstract
{
	public function process($runDirectory)
	{
		$result = '';
		$fileContent = $this->getFileContent($runDirectory, '*XX2MVTP...............DAT*');
		
		//if file empty, return direct
		if ($fileContent == null)
			return 'File empty or not found'."\n";

		//process file
		foreach ($fileContent as $line)
		{
			//init vars
			$sku = trim(substr($line, 30, 20));
			$directionCode = substr($line, 13, 1);					//E=entrée, S=sortie
			$isPositiveStockMovement = ($directionCode == 'E');
			$qty = (int)substr($line, 90, 7);
			$natureOfStock = trim(substr($line, 50, 2));
			
			//if $natureOfStock = NV, it means that product are out of business
			if (($natureOfStock == 'NV') || ($natureOfStock == 'D'))
			{
				//send email to logistic manager
				$stockManagerEmail = mage::getStoreConfig('externallogistic/misc/stockmanager_email');
				$body = 'Purchase Order delivery for sku = '.$sku.' contains out of business or damaged products';
				$subject = 'Problem with a purchase order delivery';
				mage::helper('ExternalLogistic/Mail')->sendMail($stockManagerEmail, $subject, $body);
			}
			else 
			{
				//Good material, create stock movement
				$sourceCode = substr($line, 135, 1);	//if E = purchase order, 
				$description = '';						//movement description
				$sourceId = substr($line, 136, 15);							//contains purchase order id or RMA id
				$sourceType = MDN_ExternalLogistic_Helper_StockMovement::kSourceTypeUndefined;
				switch ($sourceCode)
				{
					case 'E':
						$sourceType = MDN_ExternalLogistic_Helper_StockMovement::kSourceTypePurchaseOrder;
						$description = 'From purchase order #'.$sourceId;						
						break;
					case 'R':
						$sourceType = MDN_ExternalLogistic_Helper_StockMovement::kSourceTypeProductReturn;
						$description = 'From product return #'.$sourceId;						
						break;
				}
				$result .= '('.$sku.'-'.$isPositiveStockMovement.'-'.$qty.'-'.$sourceType.'-'.$description.')';
				
				//create stock movement
				mage::helper('ExternalLogistic/StockMovement')->createStockMovement($sku, $qty, $isPositiveStockMovement, $sourceType, $sourceId, $description);
			}
		}
			
		return $result;
	}
	
}
