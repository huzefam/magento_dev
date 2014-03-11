<?php

class MDN_ExternalLogistic_Block_Widget_Grid_Column_Renderer_Schedule
	extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{
    public function render(Varien_Object $row)
    {
        $schedule = $row->getels_schedule_values();
        $t = explode(',', $schedule);
        $i = 0;
        $retour = '';
        foreach($t as $item)
        {
            if ($item == '')
                continue;
            $retour .= $item.', ';
            if ($i == 7)
            {
                $retour .= '<br>';
                $i = 0;
            }
            $i++;
        }
    	return $retour;
    }
}