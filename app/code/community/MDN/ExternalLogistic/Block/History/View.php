<?php

class MDN_ExternalLogistic_Block_History_View extends Mage_Adminhtml_Block_Widget_Form
{
	private $_history = null;
	
	public function __construct()
	{
		parent::__construct();
	}
	
	public function getHistory()
	{
		if ($this->_history == null)
		{
			$elhId = $this->getRequest()->getParam('elh_id');
			$this->_history = mage::getModel('ExternalLogistic/History')->load($elhId);
		}
		return $this->_history;
	}
	
}