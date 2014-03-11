<?php

class MDN_ExternalLogistic_Block_Product_Edit_Tabs extends Mage_Adminhtml_Block_Catalog_Product_Edit_Tabs
{
    protected function _prepareLayout()
    {
       parent::_prepareLayout();

            $Block = $this->getLayout()
                            ->createBlock('ExternalLogistic/Product_Edit_Tabs_ExternalLogistic')
                            ->setTemplate('ExternalLogistic/Adminhtml/Catalog/Product/Edit/Tabs/ExternalLogistic.phtml')
                            ->setProduct($this->getProduct());

            $content = $Block->toHtml();

            $this->addTab('external_logistic', array(
                'label'     => Mage::helper('Organizer')->__('External Logistic'),
                'title'     => Mage::helper('Organizer')->__('External Logistic'),
                'content'   => $content,
            ));

    }
    
}
?>
