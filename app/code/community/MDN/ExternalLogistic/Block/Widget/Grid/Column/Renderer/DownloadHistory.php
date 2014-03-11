<?php

class MDN_ExternalLogistic_Block_Widget_Grid_Column_Renderer_DownloadHistory
	extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{
    public function render(Varien_Object $row)
    {
        $url = Mage::helper('adminhtml')->getUrl('ExternalLogistic/History/Download', array('elh_id' => $row->getelh_id()));
    	return '<a href="'.$url.'">'.$this->__('Download').'</a>';
    }
}