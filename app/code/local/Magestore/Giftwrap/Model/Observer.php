<?php

class Magestore_Giftwrap_Model_Observer
{	
	public function addOrder($observer){
		$orderIds = array();
		$orderIds = $observer->getOrder_ids();
		$quoteId = Mage::getModel('sales/order')->load($orderIds[0])->getQuoteId();
		$selection = Mage::getModel('giftwrap/selection')->getCollection()
			->addFieldToFilter('quote_id',$quoteId)
			->addAttributeToSort('addressgift_id', 'ASC');
			foreach($orderIds as $index=>$value){
				$selection[$index]->setData('order_id',$value)->save();
			}
	}
	
	// Giftwarp filter King_211112
	public function afterSaveOrder($observer){
		$order = $observer->getOrder();
		$quote = Mage::getSingleton('adminhtml/session_quote')->getQuote();
		$giftwrapAmount = $quote->getAdminGiftwrapAmount();
		if($giftwrapAmount)  $order->setGiftwrapAmount($giftwrapAmount);
	}
	
	/**
	*	Hai.Ta 6.6.2013
	**/
	public function saveItemsBeforeReorder($observer){	
		$order_id = Mage::app()->getRequest()->getParam('order_id');
		$order = Mage::getModel('sales/order')->load($order_id);
		$quote_idOld = $order->getQuoteId();
		Mage::getSingleton('core/session')->setQuoteOldId($quote_idOld);
		
		$selection = Mage::getModel('giftwrap/selection')->getCollection()
					->addFieldToFilter('quote_id', $quote_idOld);		
		$dataSelection = $selection->getData();					
		
		if(count($dataSelection)){				
			$quote_idCurrent = Mage::getSingleton('checkout/session')->getQuote()->getId();
			if(!$quote_idCurrent){
				$quote_idCurrent = Mage::getModel('sales/quote')->getCollection()->getLastItem()->getId() + 1;
			}
			
			foreach ($dataSelection as $data){				
				$model = Mage::getModel('giftwrap/selection');
				$model->setStyleId($data['style_id']);
				$model->setMessage($data['message']);
				$model->setGiftcardId($data['giftcard_id']);
				$model->setQty($data['qty']);
				$model->setType($data['type']);			
				$model->setAddressgiftId($data['addressgift_id']);	
				$model->setAddresscustomerId($data['addresscustomer_id']);	
				$model->setQuoteId($quote_idCurrent);			
				$model->save();					
				// Mage::getSingleton('core/session')->setCurrentSelectionId($model->getId());
				$this->saveItemsSelection($data['id'], $model->getId());				
			}			
			Mage::getSingleton('core/session')->setItemsWrap(true);
		}else{
			Mage::getSingleton('core/session')->setItemsWrap(false);
		}		
		
		return;
	}
	
	public function saveItemsSelection($selectionIdOld, $selectionIdNew){
		$itemsSelection = Mage::getModel('giftwrap/selectionitem')->getCollection()
			->addFieldToFilter('selection_id', $selectionIdOld);	
		// $idItems  = array();
		foreach ($itemsSelection as $item){
			$model = Mage::getModel('giftwrap/selectionitem');
			$model->setSelectionId($selectionIdNew);
			$model->setItemId(-1);
			$model->setCheckReorder($item->getItemId());
			$model->setQty($item->getQty());
			$model->save();
			// $idItems[] = $model->getId();
		}	
	}
	
	public function saveSessionBeforeReorder($observer){
		Mage::getSingleton('adminhtml/session')->setCheckReorder(true);
		Mage::getSingleton('adminhtml/session')->setIdOrder(Mage::app()->getRequest()->getParam('order_id'));
		return;
	}
	public function saveAdminItemsReorder($observer){			
		if (Mage::getSingleton('adminhtml/session')->getCheckReorder()){
			$order_id = Mage::getSingleton('adminhtml/session')->getIdOrder();			
			
			$order = Mage::getModel('sales/order')->load($order_id);
			$quote_idOld = $order->getQuoteId();
			Mage::getSingleton('core/session')->setQuoteOldId($quote_idOld);
			
			$selection = Mage::getModel('giftwrap/selection')->getCollection()
						->addFieldToFilter('quote_id', $quote_idOld);		
			$dataSelection = $selection->getData();		
			
			if(count($dataSelection)){	
				
				$quote_idCurrent = Mage::getSingleton('adminhtml/session_quote')->getQuote()->getId();
				// Zend_debug::dump($quote_idCurrent);die();
				if(!$quote_idCurrent){
					$quote_idCurrent = Mage::getModel('sales/quote')->getCollection()->getLastItem()->getId() + 1;
				}
				
				foreach ($dataSelection as $data){				
					$model = Mage::getModel('giftwrap/selection');
					$model->setStyleId($data['style_id']);
					$model->setMessage($data['message']);
					$model->setGiftcardId($data['giftcard_id']);
					$model->setQty($data['qty']);
					$model->setType($data['type']);			
					$model->setAddressgiftId($data['addressgift_id']);	
					$model->setAddresscustomerId($data['addresscustomer_id']);	
					$model->setQuoteId($quote_idCurrent);			
					$model->save();					
					// Mage::getSingleton('core/session')->setCurrentSelectionId($model->getId());
					$this->saveItemsSelection($data['id'], $model->getId());				
				}			
				
				$quoteItems = Mage::getSingleton('adminhtml/session_quote')->getQuote()->getAllItems();
				Mage::helper('giftwrap')->saveIdItemQuote($quoteItems, 2);
			}else{
				// nothing
			}		
		}		
		Mage::getSingleton('adminhtml/session')->setCheckReorder(false);
		return;
	}
	//Hai.Ta
}