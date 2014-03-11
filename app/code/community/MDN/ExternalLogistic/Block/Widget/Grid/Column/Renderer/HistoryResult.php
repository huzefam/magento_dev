<?php

class MDN_ExternalLogistic_Block_Widget_Grid_Column_Renderer_HistoryResult
	extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract 
{
    public function render(Varien_Object $row)
    {
    	$retour = $row->getelh_result();
    	$retour = nl2br($retour);
    	return $retour;
    }
}