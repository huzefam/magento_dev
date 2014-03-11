<?php
class Magestore_Giftwrap_Block_Catalog_Product_Giftwrap extends Mage_Core_Block_Template {
	public function __construct()	{
		parent::__construct();
		$this->setTemplate('giftwrap/catalog/product/view/giftwrap.phtml');		
	}
	
	public function getProduct() {
		return Mage::registry('product');
	}
	
	public function getGiftWrapToolTip() {
		return Mage::getStoreConfig('giftwrap/message/product_giftwrap');
	}
	
	public function getNoGiftWrapToolTip() {
		return Mage::getStoreConfig('giftwrap/message/product_no_giftwrap');
	}
	
	protected function _beforeToHtml()
	{
		if(!Mage::helper('magenotification')->checkLicenseKey('Giftwrap')){
			$this->setTemplate(null);
		}
		return parent::_beforeToHtml();
	}	
}