<?php

class MDN_ExternalLogistic_Model_System_Config_Source_Suppliers extends Mage_Eav_Model_Entity_Attribute_Source_Abstract
{
    public function toOptionArray($isMultiselect=false)
    {
        return $this->getAllOptions();
    }

    public function getAllOptions()
    {
        $options = array();

        $suppliers = mage::getModel('Purchase/Supplier')->getCollection();
        foreach($suppliers as $supplier)
        {
            $options[] = array(
                        'value' => $supplier->getId(),
                        'label' => $supplier->getsup_name(),
                    );
                    }

        return $options;
    }
}