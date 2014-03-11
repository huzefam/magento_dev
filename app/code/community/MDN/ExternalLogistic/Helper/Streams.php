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
class MDN_ExternalLogistic_Helper_Streams extends Mage_Core_Helper_Abstract
{
	const kDefaultStreamSalesOrder = 'sales_order';
	const kDefaultStreamPurchaseOrder = 'purchase_order';
	const kDefaultStreamProduct = 'product';
	
	/**
	 * Return default streams
	 *
	 */
	public function getDefaultStreams()
	{
		$retour = array();
		$retour[self::kDefaultStreamPurchaseOrder] = $this->__(self::kDefaultStreamPurchaseOrder);
		$retour[self::kDefaultStreamSalesOrder] = $this->__(self::kDefaultStreamSalesOrder);
                $retour[self::kDefaultStreamProduct] = $this->__(self::kDefaultStreamProduct);
		return $retour;
	}
	
	/**
	 * Return default stream for stream type
	 *
	 * @param unknown_type $streamType
	 */
	public function getDefaultStream($streamType)
	{
		$stream = mage::getModel('ExternalLogistic/Streams')->load($streamType, 'els_is_default_stream_for');
		if ($stream->getId())
			return $stream;
		else 
			return null;
	}
}