<?php

class MDN_ExternalLogistic_ProductController extends Mage_Adminhtml_Controller_Action {

    public function SendOrderToLogisticAction() {
        $productId = $this->getRequest()->getParam('product_id');

        try {
            mage::helper('ExternalLogistic/Product')->sendToLogistic(array($productId));
            Mage::getSingleton('adminhtml/session')->addSuccess('Product successfully sent to logistic company');
        } catch (Exception $ex) {
            Mage::getSingleton('adminhtml/session')->addError('An error occured : ' . $ex->getMessage());
        }

        $this->_redirect('adminhtml/catalog_product/edit', array('id' => $productId));
    }

    /**
     * Display sales order grid
     *
     */
    public function GridAction() {
        $this->loadLayout();
        $this->renderLayout();
    }

    public function ResetAction() {

        //reset states for every products
        $prefix = Mage::getConfig()->getTablePrefix();
        $sql = 'update '.$prefix.'catalog_product_entity set external_logistic_run_id = NULL, sent_to_logistic_company = 0;';
        mage::getResourceModel('cataloginventory/stock_item_collection')->getConnection()->query($sql);

        //confirm & redirect
        Mage::getSingleton('adminhtml/session')->addSuccess('Products state reseted');
        $this->_redirect('adminhtml/system_config/edit', array('section' => 'externallogistic'));
    }

}