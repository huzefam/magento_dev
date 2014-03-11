<?php

class MDN_ExternalLogistic_Block_System_Config_Button_ResetPurchaseOrder extends Mage_Adminhtml_Block_System_Config_Form_Field
{

    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        $this->setElement($element);
        $url = $this->getUrl('ExternalLogistic/PurchaseOrder/Reset');

        $html = '<input type="text" size="3" id="po_id" name="po_id">';
        $html .= ' <input type="button" value="Reset" onclick="document.location.href=\''.$url.'po_id/\' + document.getElementById(\'po_id\').value">';

        return $html;
    }
}