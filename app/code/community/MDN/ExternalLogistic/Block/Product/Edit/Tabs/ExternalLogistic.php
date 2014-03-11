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
//Todo : add tab to product sheet
class MDN_ExternalLogistic_Block_Product_Edit_Tabs_ExternalLogistic extends Mage_Adminhtml_Block_Widget_Form
{
    private $_history = null;

    //current product
    private $_product = null;
    public function setProduct($Product)
    {
            $this->_product = $Product;
            return $this;
    }
    public function getProduct()
    {
            return $this->_product;
    }

	
 	/**
     * ######################## Specific functions #################################
     */
    public function isSentToLogisticCompany()
    {
    	return ($this->getProduct()->getexternal_logistic_run_id() > 0);
    }
    
    public function getStreamInformation()
    {
    	$value = '';
    	if ($this->getHistory() != null)
    	{
    		$value = $this->getHistory()->getSmallDescription();
    	}
    	return $value;
    }
    	
	public function getHistory()
	{
		if ($this->_history == null)
		{
			if ($this->getProduct()->getexternal_logistic_run_id() > 0)
			{
				$historyId = $this->getProduct()->getexternal_logistic_run_id();
				$this->_history = mage::getModel('ExternalLogistic/History')->load($historyId);
			}
		}
		return $this->_history;
	}

        public function getSendOrderToLogisticUrl()
        {
            return $this->getUrl('ExternalLogistic/Product/SendOrderToLogistic', array('product_id' => $this->getProduct()->getId()));
        }
	
}