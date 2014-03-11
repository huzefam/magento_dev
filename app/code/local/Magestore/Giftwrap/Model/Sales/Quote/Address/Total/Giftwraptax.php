<?php
class Magestore_Giftwrap_Model_Sales_Quote_Address_Total_Giftwraptax 
extends Mage_Sales_Model_Quote_Address_Total_Abstract {

	public function collect(Mage_Sales_Model_Quote_Address $address)
	{
		$active = Mage::helper('giftwrap')->enableGiftwrap();
		if (!$active)
		{
			return;
		}
		
		$items = $address->getAllItems();
		if (!count($items)) {
			return $this;
		}	
		
		/*
		*	update of version 2.0.2
		*/
		if(!Mage::getStoreConfig('giftwrap/tax/active',Mage::app()->getStore(true)->getId())){
			return;
		}
		//-------------------------
		$items = $this->_getAddressItems($address);
		$request = Mage::getSingleton('tax/calculation')->getRateRequest(
            $address,
            $address->getQuote()->getBillingAddress(),
            $address->getQuote()->getCustomerTaxClassId(),
            $this->_store
        );
		
        foreach ($items as $item) {
            if ($item->getParentItem()) {
                continue;
            }
            if ($item->getHasChildren() && $item->isChildrenCalculated()) {
                foreach ($item->getChildren() as $child) {
                    $request->setProductClassId($child->getProduct()->getTaxClassId());
                }
            }
            else {
                $request->setProductClassId($item->getProduct()->getTaxClassId());
            }
        }
		
		$rate = Mage::getSingleton('tax/calculation')->getRate($request);
		Mage::getModel('core/session')->setData('giftwrap_rate',$rate);
		//---------------------------
		$giftwrapAmount = Mage::helper('giftwrap')->giftwrapAmount();
		$giftwrapTax = floatval($giftwrapAmount)*floatval($rate)/100;
		$address->setGiftwrapTax($giftwrapTax);
		$address->setBaseGiftwrapTax(0);
		Mage::getModel('core/session')->setData('giftwrap_tax',$giftwrapTax);
		$address->setGrandTotal($address->getGrandTotal() + $giftwrapTax);
		$address->setBaseGrandTotal($address->getBaseGrandTotal() + $giftwrapTax);
		
		return $this;
	}

	public function fetch(Mage_Sales_Model_Quote_Address $address) {
		$active = Mage::helper('giftwrap')->enableGiftwrap();
		if (!$active)
		{
			return;
		}
		
		$amount = $address->getGiftwrapTax();		
		$title = Mage::helper('sales')->__('Gift Wrap Tax');
		if ($amount!=0) {
			$address->addTotal(array(
					'code'=>$this->getCode(),
					'title'=>$title,
					'value'=>$amount
			));
		}
		return $this;
	}

	public function getCheckoutSession() {
		return Mage::getSingleton('checkout/session');
	}

	public function getQuote() {
		return $this->getCheckoutSession()->getQuote();
	}

	public function getProductName($itemId) {
		$item = $this->getQuote()->getItemById($itemId);
		if($item){
			return $item->getProduct()->getName();
		}else{
			return '';
		}		
	}
}
