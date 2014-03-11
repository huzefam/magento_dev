<?php
class Magestore_Giftwrap_Model_Giftwrap extends Mage_Core_Model_Abstract {
	
	const STATUS_ENABLED    = 1;
    const STATUS_DISABLED   = 0;
    protected $_eventPrefix = 'giftwrap_style';
    protected $_eventObject = 'style';
	
	public function _construct()	{
		parent::_construct();
		$this->_init('giftwrap/giftwrap');
	}
	
	public function getCheckoutSession() {
		return Mage::getSingleton('checkout/session');
	}
	
	public function getQuote()	{
		return $this->getCheckoutSession()->getQuote();
	}
	
	
	/*public function checkGiftwrap($observer){
		$quoteId = $this->getCheckoutSession()->getQuoteId();
		Zend_Debug::dump($quoteId);die();
	}*/
	public function beforeMerge($observer) {
		$oldQuoteId = $observer->getSource()->getId();
		$newQuoteId = $observer->getQuote()->getId();
		$new_items = Mage::getModel('giftwrap/selection')->getSelectionByQuoteId($newQuoteId);
		if (count($new_items) == 1 && ($new_items[0]['itemId'] == 0)) {
			//doing nothing
		}
		else {
			$items = Mage::getModel('giftwrap/selection')->getSelectionByQuoteId($oldQuoteId);
			if (count($items)==1 && ($items[0]['itemId'] == 0)) {
				Mage::getModel('giftwrap/selection')->removeSelection($newQuoteId);				
			}
			foreach ($items as $item) {
				$selModel = Mage::getModel('giftwrap/selection')->loadByQuoteId($oldQuoteId, $item['itemId']);
				if ($selModel->getId()) {
					try {				
						$selModel->setQuoteId($newQuoteId)->save();
					}
					catch (Exception $e) {
						echo $e->getMessage();
					}
				}
			}
		}
	}
	public function removeItem($observer){
		$item=$observer->getEvent()->getQuoteItem();
		
		$quote_id=$item->getQuoteId();
		
		$selection=Mage::getModel('giftwrap/selection')->deleteSelectionByQuoteId($quote_id,$item->getId());
	}
	
	public function checkGiftwrap($observer) {	
		$_cartQuote = Mage::getSingleton('checkout/session')->getQuote();	
		$items = $_cartQuote->getAllItems();
		//Hai.Ta		
		if(Mage::getSingleton('core/session')->getItemsWrap()) Mage::helper('giftwrap')->saveIdItemQuote($items, 1);		
		Mage::getSingleton('core/session')->setItemsWrap(false);
		//endHai.Ta
		$storeId = Mage::app()->getStore()->getId();
		$quoteId = $_cartQuote->getId();
		$selections = Mage::getModel('giftwrap/selection')->getCollection()
						->addFieldToFilter('quote_id',$quoteId)
						;
		foreach($selections as $selection){
			$style = Mage::getModel('giftwrap/giftwrap')->load($selection->getStyleId());
			$giftcard = Mage::getModel('giftwrap/giftcard')->load($selection->getGiftcardId());
			if($style->getStoreId() == $storeId){
				continue;
			}else{
				$newstyle = Mage::getModel('giftwrap/giftwrap')->loadByOptionAndStore($style->getOptionId(),$storeId);
				if($newstyle->getId()){
					$selection	->setStyleId($newstyle->getId())
								->save();
				}
			}
			if($giftcard->getId()){
				if($giftcard->getStoreId() == $storeId){
					continue;
				}else{
					$newgiftcard = Mage::getModel('giftwrap/giftcard')->loadByOptionAndStore($giftcard->getOptionId(),$storeId);
					if($newgiftcard->getId()){
						$selection	->setGiftcardId($newgiftcard->getId())
									->save();
					}
				}
			}
		}
	}
	
	public function loadByOptionAndStore($optionId,$storeId){
		$model = Mage::getModel('giftwrap/giftwrap');
		$collection = Mage::getModel('giftwrap/giftwrap')->getCollection()
						->addFieldToFilter('option_id',$optionId)
						->addFieldToFilter('store_id',$storeId)
						;
		if(count($collection)){
			return $model->load($collection->getFirstItem()->getId());
		}
		return $model;
	}
	
	public function updateGiftwrap($observer) {
		$_cartQuote = Mage::getSingleton('checkout/session')->getQuote();	
		$items = $_cartQuote->getAllItems();
		$delete = false;
		$quoteId = $_cartQuote->getId();
		$selections = Mage::getModel('giftwrap/selection')->getSelectionByQuoteId($quoteId);
		if(count($items)){
			foreach ($items as $it){
				$qty = 0;
				//Zend_Debug::dump($selections);die();
				foreach ($selections as $selection) {
					$selectionitem = Mage::getModel('giftwrap/selectionitem')
										->loadBySelectionAndItem($selection['id'],$it->getId());
					$qty += floatval($selection['quantity'])*floatval($selectionitem->getQty());
				}
				if($qty > floatval($it->getQty())){
					foreach ($selections as $sel){
						$selectionModel = Mage::getModel('giftwrap/selection')->load($sel['id']);
						$selectionModel->delete();
						$delete = true;
					}
				}
			}
		}else{
			foreach ($selections as $sel){
				$selectionModel = Mage::getModel('giftwrap/selection')->load($sel['id']);
				$selectionModel->delete();
				$delete = true;
			}
		}
		if($delete){
			Mage::getSingleton('checkout/session')->addError('Some gift boxes were deleted by number item wraped greater than number items in cart !');
		}
	}
	
	public function updateGiftwrapBefore($observer){
		$item = $observer->getQuoteItem();
		$selectionItems = Mage::getModel('giftwrap/selectionitem')
							->getCollection()
							->addFieldToFilter('item_id',$item->getId())
							;
		foreach($selectionItems as $selectionItem){
			$selection = Mage::getModel('giftwrap/selection')->load($selectionItem->getSelectionId());
			if($selection->getId()){
				$selection->delete();
			}
		}
	}
	
	public function afterSaveOrder($observer) {	
		if(!Mage::helper('magenotification')->checkLicenseKey('Giftwrap')){
			return;
		}
		if(Mage::registry('UPDATE_GIFTWRAP'))
			return;
		Mage::register('UPDATE_GIFTWRAP',true);
		try{
			$order = $observer->getOrder();
			// $quote = $observer->getQuote();	
			$quote = Mage::getSingleton('checkout/session')
							->getQuote();
			$quoteId=$order->getQuoteId();
			$orderAddress = Mage::getModel('sales/order_address')->getCollection()
			->addFieldToFilter('parent_id',$order->getId())
			->addAttributeToSort('entity_id', 'DESC');
			;
			foreach($orderAddress as $address){
			$addressCutomer = $address->getData('customer_address_id');
			break;
			}
			$selections = Mage::getModel('giftwrap/selection')->getSelectionByQuoteId($quote->getId(),null,null,$addressCutomer);
			$giftwrap_amount = Mage::helper('giftwrap')->giftwrapAmount(null,null,$addressCutomer);
			$order->setGiftwrapAmount($giftwrap_amount);
			if(Mage::getStoreConfig('giftwrap/tax/active',Mage::app()->getStore(true)->getId())){
				$percent = Mage::getModel('core/session')->getData('giftwrap_rate');			
				if($percent){
					$giftwrap_tax = floatval($percent)*floatval($giftwrap_amount)/100;
					$order->setGiftwrapTax($giftwrap_tax);
				}
			}
			$order->save();	
		}catch(Exception $e){
		}
	}
	
	public function getOptionGiftWrapArray()
	{
		 return array(
            self::STATUS_ENABLED    => Mage::helper('catalog')->__('Yes'),
            self::STATUS_DISABLED   => Mage::helper('catalog')->__('No')
        );
	}
	
	public function getStoreGiftwrap($giftwrap_id,$store_id)
	{
		$option_id = Mage::getModel('giftwrap/giftwrap')->load($giftwrap_id)->getOptionId();
		return Mage::getModel('giftwrap/giftwrap')
					->getCollection()
					->addFieldToFilter('option_id',$option_id)
					->addFieldToFilter('store_id',$store_id)
					->getFirstItem()
					;
	}	

	public function deleteSelection($observer){
		$style = $observer->getStyle();
		if($style->getStatus()==2){
			$selectionCollection = Mage::getModel('giftwrap/selection')
						->getCollection()
						->addFieldToFilter('style_id',$style->getId())
						;
			if(count($selectionCollection)){
				foreach($selectionCollection as $selection){
					$selection->delete();
				}
			}
		}
	}
	
	public function deleteSelectionGiftcard($observer){
		$giftcard = $observer->getGiftcard();
		if($giftcard->getStatus()==2){
			$selectionCollection = Mage::getModel('giftwrap/selection')
						->getCollection()
						->addFieldToFilter('giftcard_id',$giftcard->getId())
						;
			if(count($selectionCollection)){
				foreach($selectionCollection as $selection){
					$selection->delete();
				}
			}
		}
	}
	
	public function paypal_prepare_line_items($observer)
	{
		$paypalCart = $observer->getEvent()->getPaypalCart();
		if ($paypalCart){
			$salesEntity = $paypalCart->getSalesEntity();
			if(Mage::getModel('core/session')->getData('giftwrap_amount') > 0){
				$paypalCart->updateTotal(Mage_Paypal_Model_Cart::TOTAL_SUBTOTAL,abs((float)Mage::getModel('core/session')->getData('giftwrap_amount')),Mage::helper('giftwrap')->__('Giftwrap Amount'));
				if(Mage::getModel('core/session')->getData('giftwrap_tax') > 0){
					$paypalCart->updateTotal(Mage_Paypal_Model_Cart::TOTAL_SUBTOTAL,abs((float)Mage::getModel('core/session')->getData('giftwrap_tax')),Mage::helper('giftwrap')->__('Giftwrap Tax'));
				}
			}
		}

	}
	
	public function afterSaveLogin($observer){
		$quote = Mage::getSingleton('checkout/session')->getQuote();
        $allItems = $quote->getItemsCollection();
        $check_product = array();
             foreach($allItems as $item){
                 $productId = $item->getProduct()->getId();
                 $check_product[$productId] = $item->getId();
             }
		$quote_id = $quote->getId();
		$quote_id_old = Mage::getModel('core/session')->getData('quote_giftwrap');
        $cache = array();
        $cache = Mage::getModel('core/session')->getData('cache');
        
        $select_items_ids = array();
		if($quote_id_old):
		$collection = Mage::getModel('giftwrap/selection')->getCollection()
    			->addFieldToFilter('quote_id',$quote_id_old)
    			;
		foreach($collection as $gift){
            $select_items_ids[] = $gift->getId();
			$gift->setQuoteId($quote_id);
			$gift->save();
		}
		endif;
        //$items = array();
        $itemsColletion = Mage::getModel('giftwrap/selectionitem')->getCollection()
            ->addFieldToFilter('selection_id',array('in'=>$select_items_ids));
        foreach($itemsColletion as $item){
            $check = $cache[$item['item_id']];
            $item_id = $check_product[$check];
            if($check){
                
                $item->setItemId($item_id);
                $item->save();
            }
        }
         
      /*  foreach($items as $item){
            Mage::getModel('sales/quote_item')->load($item)
                ->setQuoteId($quote_id)->save();
        }
        */
	}
}