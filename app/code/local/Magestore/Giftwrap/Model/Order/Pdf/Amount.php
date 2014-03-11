<?php

class Magestore_Giftwrap_Model_Order_Pdf_Amount 
		extends Mage_Sales_Model_Order_Pdf_Total_Default
{
    public function getTotalsForDisplay()
    {
		$amount = $this->getAmount();
		$tax = $this->getTax();
		$fontSize = $this->getFontSize() ? $this->getFontSize() : 7;
		if(floatval($amount))
		{
			$amount = $this->getOrder()->formatPriceTxt($amount);
			if ($this->getAmountPrefix()) 
			{
				$amount = $this->getAmountPrefix().$amount;
			}		
			
			$totals = array(array(
						'label' => Mage::helper('giftwrap')->__('Giftwrap Amount'),
						'amount' => $amount,
						'font_size' => $fontSize,
						)
				);
			if(floatval($this->getTax())) {
				$tax = $this->getOrder()->formatPriceTxt($tax);
				if ($this->getAmountPrefix()) 
					$tax = $this->getAmountPrefix().$tax;
				$totals[] = array(
						'label' => Mage::helper('giftwrap')->__('Giftwrap Tax'),
						'amount' => $tax,
						'font_size' => $fontSize,
						);
			}
				
			return $totals;
		}
	}
	
	public function getTax()
	{
		return $this->getOrder()->getGiftwrapTax();
	}
    public function getAmount()
    {
        return $this->getOrder()->getGiftwrapAmount();
    }	
}