<?php

class MDN_ExternalLogistic_Model_Sources extends Mage_Core_Model_Abstract
{
	private $_specificClass = null;
	
	const kSourceProducts = 'src_products';
	const kSourceSalesOrder = 'src_sales_order';
	const kSourceSalesOrderItem = 'src_sales_order_item';
	const kSourcePurchaseOrder = 'src_purchase_order';
	const kSourcePurchaseOrderItem = 'src_purchase_order_item';
	
	public function _construct()
	{
		parent::_construct();
		$this->_init('ExternalLogistic/Sources');
	}
	
	/**
	 * Return specific class
	 *
	 * @return unknown
	 */
	protected function getSpecificClass()
	{
		if ($this->_specificClass == null)
		{
			switch ($this->getelsrc_code())
			{
				case self::kSrcSalesOrder :
					$this->_specificClass = mage::getModel('ExternalLogistic/Source_SalesOrder');
					break;
				case self::kSrcProducts  :
					$this->_specificClass = mage::getModel('ExternalLogistic/Source_Product');					
					break;
				case self::kSrcPurchaseOrder:
					$this->_specificClass = mage::getModel('ExternalLogistic/Source_PurchaseOrder');					
					break;
				default:
					throw new Exception('Source class cant be found !');
					break;
			}
		}
		return $this->_specificClass;
	}
	
	/**
	 * Return setting fields
	 *
	 * @return unknown
	 */
	public function getFieldset()
	{
		return $this->getSpecificClass()->getFieldset();
	}

}
	