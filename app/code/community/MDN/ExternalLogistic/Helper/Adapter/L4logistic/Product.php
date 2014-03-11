<?php

/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * @copyright  Copyright (c) 2009 Maison du Logiciel (http://www.maisondulogiciel.com)
 * @author : Olivier ZIMMERMANN
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class MDN_ExternalLogistic_Helper_Adapter_L4logistic_Product extends MDN_ExternalLogistic_Helper_Adapter_L4logistic_Abstract
{
    public function process()
    {
        //get last send product ID
        $lastSentProductId = mage::helper('ExternalLogistic/Parameters')->getParamValue('L4logistic', 'last_sent_product_id');
        if ($lastSentProductId == '')
            $lastSentProductId = 0;

        //collect products
        $max = mage::getStoreConfig('externallogistic/misc/max');
        $select = mage::getResourceModel('catalog/product')
                        ->getReadConnection()
                        ->select()
                        ->from(mage::getResourceModel('catalog/product')->getTable('catalog/product'))
                        ->order('entity_id ASC')
                        ->where('sent_to_logistic_company <> 1')
                        ->where("type_id = 'simple'")
                        ->limit($max);
        $productIds = mage::getResourceModel('catalog/product')->getReadConnection()->fetchCol($select);
        
        //generate xml
        $xml = $this->getProductXml($productIds);
        
        //process file
        $streamCode = 'REFART'.date('YmdHis');
        $this->saveAndUploadFile($streamCode, $xml, 'IN/');

        //return result
        $data = array(
            'error' => false,
            'entity_ids' => implode(',', $productIds),
            'result' => 'Stream code '.$streamCode.', '.count($productIds).' products sent',
            'logistic_stream_code' => $streamCode
        );

        return $data;
    }

    //***************************************************************************************************************************************
    //***************************************************************************************************************************************
    // XML GENERATION
    //***************************************************************************************************************************************
    //***************************************************************************************************************************************


    /**
     * Generate XML for products
     * @param <type> $collection
     */
    protected function getProductXml($productIds)
    {
        $xmlWriter = mage::helper('ExternalLogistic/XmlWriter');
        $xmlWriter->init();

        $prefix = mage::getStoreConfig('externallogistic/l4logistic/prefix');

        //root element
        $xmlWriter->push('ReferentielArticles');

        //parse products
        foreach($productIds as $productId)
        {
            $product = mage::getModel('catalog/product')->load($productId);

            $xmlWriter->push('Article');

            $xmlWriter->element('CodeActivite', mage::getStoreConfig('externallogistic/l4logistic/activity'));
            $xmlWriter->element('Activite', mage::getModel('ExternalLogistic/System_Config_Source_L4logistic_Activity')->getLabelFromCode(mage::getStoreConfig('externallogistic/l4logistic/activity')));
            $xmlWriter->element('CodeArticle', $prefix.$product->getSku());
            $xmlWriter->element('Designation', $this->cleanStringValue($product->getName(), 30));
            $xmlWriter->element('Poids', (int)($product->getweight() * 1000));
            $xmlWriter->element('Longueur', $product->getlongueur_package());
            $xmlWriter->element('Largeur', $product->getlargeur_package());
            $xmlWriter->element('Hauteur', $product->gethauteur_package());
            $xmlWriter->element('CodeArticleFournisseur', $this->getSupplierReference($product));
            $xmlWriter->element('DesignationFournisseur', $this->getSupplierName($product));
            $xmlWriter->element('CodeFournisseur', $this->getSupplierCode($product));
            $xmlWriter->element('CodeMarque', $product->getmanufacturer());
            $xmlWriter->element('Famille', $product->getFamille_logistique());
            $xmlWriter->element('CodeAnalyse', ($product->getcode_analyse() == '' ? 'E' : $product->getcode_analyse()));
            $xmlWriter->element('Marque', $product->getmanufacturer());

            $barcodes = mage::helper('AdvancedStock/Product_Barcode')->getBarcodesForProduct($product->getId());
            foreach($barcodes as $barcode)
            {
                $xmlWriter->push('DeclarationCAB');
                $xmlWriter->element('CAB', $barcode->getppb_barcode());
                $xmlWriter->element('Primaire', ($barcode->getppb_is_main() ? 'true' : 'false'));
                $xmlWriter->element('PCB', '1');
                $xmlWriter->pop();
            }

            $xmlWriter->pop();
        }
        
        //end root element
        $xmlWriter->pop();

        return $xmlWriter->getXml();
    }

    //***************************************************************************************************************************************
    //***************************************************************************************************************************************
    // SUPPLIERS
    //***************************************************************************************************************************************
    //***************************************************************************************************************************************

    protected $_productsSuppliers = array();

    /**
     * Get first supplier for product
     */
    protected function getSupplier($product)
    {
        if (!isset($this->_productsSuppliers[$product->getId()]))
        {
            $productSupplier = mage::getModel('Purchase/ProductSupplier')
                                    ->getCollection()
                                    ->addFieldToFilter('pps_product_id', $product->getId())
                                    ->join('Purchase/Supplier', 'pps_supplier_num=sup_id')
                                    ->getFirstItem();

            $this->_productsSuppliers[$product->getId()] = $productSupplier;

            return $productSupplier;
        }
        else
            return $this->_productsSuppliers[$product->getId()];
    }

    /**
     * Return first supplier reference
     * @param <type> $product
     */
    protected function getSupplierReference($product)
    {
        $supplier = $this->getSupplier($product);
        if ($supplier->getId())
        {
            return $supplier->getpps_reference();
        }
        else
            return '';
    }

    /**
     * Return first supplier name
     * @param <type> $product
     */
    protected function getSupplierName($product)
    {
        $supplier = $this->getSupplier($product);
        if ($supplier->getId())
        {
            return $supplier->getsup_name();
        }
        else
            return '';
    }

    /**
     * Return first supplier code
     * @param <type> $product
     */
    protected function getSupplierCode($product)
    {
        $supplier = $this->getSupplier($product);
        if ($supplier->getId())
        {
            return $supplier->getsup_code();
        }
        else
            return '';
    }
}
