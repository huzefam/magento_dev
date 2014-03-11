<?php

class MDN_ExternalLogistic_Block_System_Config_Button_ResetProducts extends Mage_Adminhtml_Block_System_Config_Form_Field
{

    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        $this->setElement($element);
        $url = $this->getUrl('ExternalLogistic/Product/Reset');

        $html = $this->getLayout()->createBlock('adminhtml/widget_button')
                    ->setType('button')
                    ->setClass('scalable')
                    ->setLabel('Reset')
                    ->setOnClick("setLocation('$url')")
                    ->toHtml();

        return $html;
    }
}