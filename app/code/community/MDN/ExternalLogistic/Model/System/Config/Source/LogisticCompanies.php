<?php

class MDN_ExternalLogistic_Model_System_Config_Source_LogisticCompanies extends Mage_Eav_Model_Entity_Attribute_Source_Abstract
{
    public function toOptionArray($isMultiselect=false)
    {
        return $this->getAllOptions();
    }

    public function getAllOptions()
    {
       return mage::helper('ExternalLogistic/Companies')->getCompanies();
    }
}