<?php

class MDN_ExternalLogistic_Block_Widget_Grid_Column_Renderer_CustomInformation
	extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract 
{
    public function render(Varien_Object $row)
    {
    	//init vars
    	$customInformaton = $this->getColumn()->getcustom_information();
    	$value = $customInformaton->getCustomInformationValue($customInformaton, $row->getexternal_logistic_info());
    	
    	//render input control
    	$name = 'custom['.$row->getId().']['.$customInformaton->getelci_code().']';
    	return $customInformaton->renderHtml($name, $value);
    }
}