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
class MDN_ExternalLogistic_Model_Stream_Products extends MDN_ExternalLogistic_Model_Stream_Abstract 
{
	/**
	 * Direction
	 *
	 */
	public function getDirection()
	{
		return 	MDN_ExternalLogistic_Model_Stream_Abstract::kDirectionMagentoToLogistic;
	}
	
	/**
	 * Process stream
	 *
	 */
	public function process($tTemplates, $transporter)
	{
		//get collection
		$collection = mage::getModel('catalog/product')->getCollection();
		
		//process templates
		foreach ($tTemplates as $template)
		{
			$template->init();
			foreach ($collection as $product)
			{
				//define values array
				$values = $this->getValuesForObject($product);
				
				//add row
				$template->processRow($values);
			}
			
			//transport files
			$transporter->transportFile($template);
		}
	}

	/**
	 * Return an array with possible values
	 *
	 * @param unknown_type $product
	 */
	public function getValuesForObject($product)
	{
		return $product->getData();
	}
	
}
