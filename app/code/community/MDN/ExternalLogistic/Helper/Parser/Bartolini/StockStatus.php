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
class MDN_ExternalLogistic_Helper_Parser_Bartolini_StockStatus extends MDN_ExternalLogistic_Helper_Parser_Bartolini_Abstract
{
	public function process($runDirectory)
	{
		$result = '';
		$fileContent = $this->getFileContent($runDirectory, '*XX2STKP...............DAT*');
		
		//if file empty, return direct
		if ($fileContent == null)
			return 'File empty or not found'."\n";

		//Init report
		mage::helper('BackgroundTask')->AddTask('Init stock error report', 
								'ExternalLogistic/StockStatus',
								'initReport',
								null
								);	
			
		//process file
		foreach ($fileContent as $line)
		{
			//retrieve information
			$sku = trim(substr($line, 10, 30 - 11));
			$qty = (int)substr($line, 69, 81 - 69);
			$stockNature = trim(substr($line, 51, 2));
			
			//Consider stock when nature is blank
			if ($stockNature == '')
			{
				
				//plan task to compare stocks
				mage::helper('BackgroundTask')->AddTask('Check stocks for product with sku = '.$sku.' and qty = '.$qty, 
										'ExternalLogistic/StockStatus',
										'checkProductQty',
										array('sku' => $sku, 'qty' => $qty)
										);	
			}
		}
		
		//plan transmit report
		mage::helper('BackgroundTask')->AddTask('Transmit stock report', 
								'ExternalLogistic/StockStatus',
								'transmitReport',
								null
								);	
			
		return $result;
	}

}
