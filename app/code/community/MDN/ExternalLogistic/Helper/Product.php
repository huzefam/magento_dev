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
class MDN_ExternalLogistic_Helper_Product extends Mage_Core_Helper_Abstract
{
	/**
	 * Send orders to logistic
	 *
	 * @param unknown_type $orderId
	 */
	public function sendToLogistic($productIds)
	{
		//retrieve default stream
		$stream = mage::helper('ExternalLogistic/Streams')->getDefaultStream(MDN_ExternalLogistic_Helper_Streams::kDefaultStreamProduct);
		if (!$stream)
			throw new Exception($this->__('Default stream for product is not set'));

		//force source
		$stream->forceSourceContent(MDN_ExternalLogistic_Model_Sources::kSourceProducts, $productIds);

		//execute stream
		$stream->process();
        }
}