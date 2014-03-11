<?php

class MDN_ExternalLogistic_Block_Widget_Grid_Column_Renderer_IsFullStock
	extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{
    public function render(Varien_Object $row)
    {
        if (!$row->IsFullStock())
            $retour = '<font color="red">'.$this->__('No').'</font>';
        else
            $retour = '<font color="green">'.$this->__('Yes').'</font>';
    	return $retour;
    }
}