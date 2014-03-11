<?php

class MDN_ExternalLogistic_Model_Pdf_OrderForLogisticCompany extends MDN_ExternalLogistic_Model_Pdf_Pdfhelper {

    protected $_supplier = null;

    public function setSupplier($supplier)
    {
        $this->_supplier = $supplier;
    }
    public function getSupplier()
    {
        return $this->_supplier;
    }

    public function getPdf($order = array()) {

        $this->_beforeGetPdf();
        $this->_initRenderer('invoice');

        $order = $order[0];

        if ($this->pdf == null)
            $this->pdf = new Zend_Pdf();
        else
            $this->firstPageIndex = count($this->pdf->pages);

        $style = new Zend_Pdf_Style();
        $style->setFont(Zend_Pdf_Font::fontWithName(Zend_Pdf_Font::FONT_HELVETICA_BOLD), 10);

        //create new page
        $titre = mage::helper('ExternalLogistic')->__('Order #') . $order->getincrement_id();
        $settings = array();
        $settings['title'] = $titre;
        $settings['store_id'] = 0;
        $page = $this->NewPage($settings);

        //address block
        $txt_date = "Date :  " . mage::helper('core')->formatDate($order->getCreatedAt(), 'long');
        $txt_order = '';
        $customer = mage::getmodel('customer/customer')->load($order->getCustomerId());
        $adresse_client = mage::helper('ExternalLogistic')->__('Shipping Address') . ":\n" . $this->FormatAddress($order->getShippingAddress(), '', false, $customer->gettaxvat());
        $adresse_fournisseur = mage::helper('ExternalLogistic')->__('Billing Address') . ":\n" . $this->FormatAddress($order->getBillingAddress(), '', false, $customer->gettaxvat());
        $this->AddAddressesBlock($page, $adresse_fournisseur, $adresse_client, $txt_date, $txt_order);

        //table header
        $this->drawTableHeader($page);
        $this->y -=10;

        //parse products
        $page->setFillColor(new Zend_Pdf_Color_GrayScale(0.2));
        $page->setFont(Zend_Pdf_Font::fontWithName(Zend_Pdf_Font::FONT_HELVETICA), 10);
        foreach ($order->getAllItems() as $item) {
            //Load product
            $product = mage::getModel('catalog/product')->load($item->getproduct_id());

            //add product picture
            if ($product->getSmallImage()) {
                $picturePath = Mage::getBaseDir() . DS . 'media' . DS . 'catalog' . DS . 'product' . $product->getSmallImage();
                if (file_exists($picturePath)) {
                    $zendPicture = Zend_Pdf_Image::imageWithPath($picturePath);
                    $page->drawImage($zendPicture, 10, $this->y - 15, 10 + 30, $this->y - 15 + 30);
                }
            }

            //draw product information
            $page->drawText($this->getSupplierSku($product->getId()), 100, $this->y, 'UTF-8');
            $name = $this->WrapTextToWidth($page, $product->getName(), 250);
            $offset = $this->DrawMultilineText($page, $name, 300, $this->y, 10, 0.2, 11);
            $page->drawText((int)$item->getqty_ordered(), 530, $this->y, 'UTF-8');

            $offset += 20;

            $this->y -= $offset;

            //new page if remainins space too low
            if ($this->y < ($this->_BLOC_FOOTER_HAUTEUR + 40)) {
                $this->drawFooter($page);
                $page = $this->NewPage($settings);
                $this->drawTableHeader($page);
            }
        }

        $this->drawFooter($page);
        $this->AddPagination($this->pdf);
        $this->_afterGetPdf();

        return $this->pdf;
    }

    /**
     * Draw table header
     *
     * @param unknown_type $page
     */
    public function drawTableHeader(&$page) {

        $this->y -= 15;
        $page->setFont(Zend_Pdf_Font::fontWithName(Zend_Pdf_Font::FONT_HELVETICA), 12);

        $page->drawText(mage::helper('ExternalLogistic')->__('Sku'), 100, $this->y, 'UTF-8');
        $page->drawText(mage::helper('ExternalLogistic')->__('Name'), 300, $this->y, 'UTF-8');
        $page->drawText(mage::helper('ExternalLogistic')->__('Qty'), 530, $this->y, 'UTF-8');

        $this->y -= 8;
        $page->drawLine(10, $this->y, $this->_BLOC_ENTETE_LARGEUR, $this->y);

        $this->y -= 15;
    }

    /**
     * Return supplier sku
     * @param <type> $productId
     */
    protected function getSupplierSku($productId)
    {

        $ref = '';
        $supplier = $this->getSupplier();

        if($supplier !== null){

            $supplierId = $supplier->getId();

            $ref = mage::getModel('Purchase/ProductSupplier')->getSupplierSku($productId, $supplierId);
            if ($ref == '')
            {
                $product = mage::getModel('catalog/product')->load($productId);
                $ref = $product->getSku();
            }

        }else{
            $ref = Mage::getModel('catalog/product')->load($productId)->getsku();
        }


        return $ref;
    }
}

