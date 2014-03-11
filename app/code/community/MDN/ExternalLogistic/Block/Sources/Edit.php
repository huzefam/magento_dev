<?php

class MDN_ExternalLogistic_Block_Sources_Edit extends Mage_Adminhtml_Block_Widget_Form
{
	private $_source = null;
	
	public function __construct()
	{
		parent::__construct();
	}
	
	public function getSource()
	{
		if ($this->_source == null)
		{
			$sourceId = $this->getRequest()->getParam('elsrc_id');
			$this->_source = mage::getModel('ExternalLogistic/Sources')->load($sourceId);
		}
		return $this->_source;
	}
	
	public function getFieldset()
	{
		return $this->getSource()->getFieldset()->toHtml();
	}
	
	public function getSubmitUrl()
	{
		return $this->getUrl('ExternalLogistic/Sources/Save');
	}
}