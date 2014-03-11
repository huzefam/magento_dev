<?php
/**
 * Order information tab
 *
 * @category   Mage
 * @package    Mage_Adminhtml
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class MDN_ExternalLogistic_Block_Adminhtml_Purchase_Order_Edit_Tabs_ExternalLogistic extends Mage_Adminhtml_Block_Template
{
	private $_order = null;
	private $_history = null;
	
    protected function _construct()
    {
		parent::_construct();
				
        $po_num = Mage::app()->getRequest()->getParam('po_num', false);	
        $model = Mage::getModel('Purchase/Order');
		$this->_order = $model->load($po_num);

        $this->setTemplate('ExternalLogistic/Purchase/Sales/Order/Edit/Tab/ExternalLogistic.phtml');
    }
	
    /**
     * Retrieve order model instance
     *
     * @return Mage_Sales_Model_Order
     */
    public function getOrder()
    {
        return $this->_order;
    }

    /**
     * ######################## Specific functions #################################
     */
    public function isSentToLogisticCompany()
    {
    	$orderId = $this->getOrder()->getId();
		$item = mage::getModel('ExternalLogistic/History')
							->getCollection()
							->addFieldToFilter('elh_entity_ids', array('like' => '%'.$orderId.'%'))
							->getFirstItem();
		return ($item->getId() > 0);
    }
	
	public function isConfirmed()
	{
		return ($this->getOrder()->getexternal_logistic_run_id() > 0);
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
    
    public function getSendOrderToLogisticUrl()
    {
    	return $this->getUrl('ExternalLogistic/PurchaseOrder/SendOrderToLogistic', array('order_id' => $this->getOrder()->getId()));
    }
    	
	public function getHistory()
	{
		if ($this->_history == null)
		{
			if ($this->getOrder()->getexternal_logistic_run_id() > 0)
			{
				$historyId = $this->getOrder()->getexternal_logistic_run_id();
				$this->_history = mage::getModel('ExternalLogistic/History')->load($historyId);
			}
		}
		return $this->_history;
	}
    
}