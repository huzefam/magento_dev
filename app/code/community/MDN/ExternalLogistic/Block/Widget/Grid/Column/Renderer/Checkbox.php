<?php

class MDN_ExternalLogistic_Block_Widget_Grid_Column_Renderer_Checkbox
	extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract 
{
    public function render(Varien_Object $row)
    {
    	$name = 'checkbox['.$row->getId().']';
    	$html = '<input type="checkbox" name="'.$name.'" id="'.$name.'" value="1">';
    	return $html;
    }
}