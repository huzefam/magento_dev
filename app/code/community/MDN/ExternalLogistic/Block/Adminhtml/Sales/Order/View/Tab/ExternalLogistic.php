<?php
/**
 * Order information tab
 *
 * @category   Mage
 * @package    Mage_Adminhtml
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class MDN_ExternalLogistic_Block_Adminhtml_Sales_Order_View_Tab_ExternalLogistic
    extends Mage_Adminhtml_Block_Sales_Order_Abstract
    implements Mage_Adminhtml_Block_Widget_Tab_Interface
{
	private $_history = null;
	
    protected function _construct()
    {
        parent::_construct();
        
        $this->setTemplate('ExternalLogistic/Adminhtml/Sales/Order/View/Tab/ExternalLogistic.phtml');
    }
	
    /**
     * Retrieve order model instance
     *
     * @return Mage_Sales_Model_Order
     */
    public function getOrder()
    {
        return Mage::registry('current_order');
    }

    /**
     * Retrieve source model instance
     *
     * @return Mage_Sales_Model_Order
     */
    public function getSource()
    {
        return $this->getOrder();
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
    	return $this->getUrl('ExternalLogistic/SalesOrder/SendOrderToLogistic');
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
	
	public function getCustomInformation()
	{
		$retour = null;
		$defaultStream = mage::helper('ExternalLogistic/Streams')->getDefaultStream(MDN_ExternalLogistic_Helper_Streams::kDefaultStreamSalesOrder);
		if ($defaultStream)
		{
			$retour = $defaultStream->getCustomInformation();
		}
		return $retour;
	}
	
	/**
	 * Return value for custom information
	 *
	 * @param unknown_type $customInformation
	 * @return unknown
	 */
	public function getCustomInformationValue($customInformation)
	{
		return mage::getModel('ExternalLogistic/CustomInformation')->getCustomInformationValue($customInformation, $this->getOrder()->getexternal_logistic_info());
	}
    
    /**
     * ######################## TAB settings #################################
     */
    public function getTabLabel()
    {
        return Mage::helper('ExternalLogistic')->__('External Logistic');
    }

    public function getTabTitle()
    {
        return Mage::helper('ExternalLogistic')->__('External Logistic');
    }

    public function canShowTab()
    {
        return true;
    }

    public function isHidden()
    {
        return false;
    }
}