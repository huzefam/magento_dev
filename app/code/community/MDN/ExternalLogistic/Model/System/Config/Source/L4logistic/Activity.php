<?php

class MDN_ExternalLogistic_Model_System_Config_Source_L4logistic_Activity extends Mage_Eav_Model_Entity_Attribute_Source_Abstract
{
    public function toOptionArray($isMultiselect=false)
    {
        return $this->getAllOptions();
    }

    public function getAllOptions()
    {
            $options = array(
            	array(
                    'value' => 'EZB',
                    'label' => 'Ezytail_BtoB',
                ),
            	array(
                    'value' => 'EZC',
                    'label' => 'Ezytail_BtoC',
                )
            );

            return $options;
    }

    public function getLabelFromCode($code)
    {
        $options = $this->getAllOptions();
        foreach($options as $option)
        {
            if ($option['value'] == $code)
                return $option['label'];
        }
        return '';
    }
}