<?php
class Magestore_Giftwrap_Model_Sales_Order_Invoice_Total_Giftwrap extends Mage_Sales_Model_Order_Invoice_Total_Abstract {
	
	public function collect(Mage_Sales_Model_Order_Invoice $invoice) {
		$invoice->setGiftwrapAmount(0);        

        $orderGiftwrapAmount = $invoice->getOrder()->getGiftwrapAmount();
		$orderGiftwrapTax = $invoice->getOrder()->getGiftwrapTax();
        $baseOrderShippingAmount = $invoice->getOrder()->getGiftwrapAmount();
        if ($orderGiftwrapAmount) {
            $invoice->setGiftwrapAmount($orderGiftwrapAmount);           
            $invoice->setGrandTotal($invoice->getGrandTotal()+$orderGiftwrapAmount);
			$invoice->setBaseGrandTotal($invoice->getBaseGrandTotal()+$orderGiftwrapAmount);
			if($orderGiftwrapTax){
				$invoice->setGiftwrapTax($orderGiftwrapTax);           
				$invoice->setGrandTotal($invoice->getGrandTotal()+$orderGiftwrapTax);
				$invoice->setBaseGrandTotal($invoice->getBaseGrandTotal()+$orderGiftwrapTax);
			}
        }
        return $this;
	}
}