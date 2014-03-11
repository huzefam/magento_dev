<?php
class Magestore_Giftwrap_Block_Giftwrap extends Mage_Core_Block_Template
{
	public function _prepareLayout()
	{
		parent::_prepareLayout();
		$this->setTemplate('giftwrap/sales/order/view/giftwrap.phtml');
	}
    
	public function getGiftwrap()     
	{ 
		if (!$this->hasData('giftwrap')) {
			$this->setData('giftwrap', Mage::registry('giftwrap'));
		}
		return $this->getData('giftwrap');		
	}
	public function getTabLabel()	{
		return Mage::helper('giftwrap')->__('Giftwrap Information');
	}

	public function getTabTitle() {
		return Mage::helper('sales')->__('Giftwrap Information');
	}
	
	public function canShowTab()	{
			return true;
	}
	
	public function isHidden()	{
			return false;
	}
	
  public function getOrder() {
		return Mage::registry('current_order');
	}
		
	public function getOrderItemGiftwrap($orderId=null) {
		if(!$orderId){
			$order_id=Mage::app()->getRequest()->getParam('order_id');
		}else{
			$order_id=$orderId;
		}
		$invoice_id=Mage::app()->getRequest()->getParam('invoice_id');
		$shipment_id=Mage::app()->getRequest()->getParam('shipment_id');
		if($order_id){
			$order=Mage::getModel('sales/order')->load($order_id);
		}else if($invoice_id){
			$order=Mage::getModel('sales/order_invoice')->load($invoice_id)->getOrder();
		}else if($shipment_id){
			$order=Mage::getModel('sales/order_shipment')->load($shipment_id)->getOrder();
		}
		$itemcollection=$order->getItemsCollection()
								;
		//Zend_Debug::dump(get_class_methods($itemcollection));die();
		
		$item=$this->getParentBlock()->getItem();
		$lastItem=$itemcollection->getLastItem();
		if($lastItem->getParentItemId()){
			$lastId=$lastItem->getParentItemId();
		}else{
			$lastId=$lastItem->getId();
		}
		if($lastId != $this->getParentBlock()->getItem()->getId()){
			return;
		}
		if(!$order->getId()){
			$order = $this->getOrder();
		}
		$quoteId=$order->getQuoteId();
		$orderAddress = Mage::getModel('sales/order_address')->getCollection()
			->addFieldToFilter('parent_id',$order->getId())
			->addAttributeToSort('entity_id', 'DESC');
			;
			foreach($orderAddress as $address){
			$addressCutomer = $address->getData('customer_address_id');
			break;
			}
		$giftwrapCollection = array();
		if ($quoteId) {
			$giftwrapCollection = Mage::getModel('giftwrap/selection')->getSelectionByQuoteId($quoteId,null,null,$addressCutomer);
			/*if (count($giftwrapCollection) == 1 && $giftwrapCollection[0]['itemId'] == 0) {
				return $this->getAllGiftwrapItemInCart();
			}*/
		}
		
		return $giftwrapCollection;
	}
	
	public function isGiftwrapAll() {
		$quoteId = $this->getOrder()->getQuoteId();
		$giftwrapCollection = array();
		if ($quoteId) {
			$giftwrapCollection = Mage::getModel('giftwrap/selection')->getSelectionByQuoteId($quoteId);
			if (count($giftwrapCollection) == 1 && $giftwrapCollection[0]['itemId'] == 0) {
				return true;
			}
		}
		return false;
	}
	
	public function getAllGiftwrapItemInCart() {
		$quoteId = $this->getOrder()->getQuoteId();
		$itemId = 0;
		$selections = Mage::getModel('giftwrap/selection')->getSelectionByQuoteId($quoteId);
		/*Zend_Debug::dump($selections);die('1');
		$giftwrapItems = array();
	
		foreach ($this->getOrder()->getAllItems() as $item) {
			$_productId = $item->getProductId();	
			if (Mage::helper('giftwrap')->isGiftwrap($_productId)) {
				$giftwrapItems[] = array(
						'id' => $selection->getId(),
						'itemId' => $item->getId(), 
						'styleId' => $selection->getStyleId(), 
						'giftcardId'	=> $selection->getGiftcardId(),
						'quoteId' => $selection->getQuoteId(),
						'giftwrap_message' => $selection->getMessage()
						);	
			}
		}*/
		return $selections;
	}
	
	public function getProduct($productId) {
		return Mage::getModel('catalog/product')->load($productId);
	}
	
	public function getGiftwrapStyleName($styleId) {
		return $this->getStyle($styleId)->getTitle();
	}
	public function getGiftcardName($giftcardId){
		return $this->getGiftcard($giftcardId)->getName();
	}
	public function getGiftwrapStyleImage($styleId) {
		return $this->getStyle($styleId)->getImage();
	}
    public function getGiftcardImage($giftcardId) {
		return $this->getGiftcard($giftcardId)->getImage();
	}
	public function getStyle($styleId) {
		return Mage::getModel('giftwrap/giftwrap')->load($styleId);		
	}
	public function getGiftcard($giftcardId){
		return Mage::getModel('giftwrap/giftcard')->load($giftcardId);
	}
	
}