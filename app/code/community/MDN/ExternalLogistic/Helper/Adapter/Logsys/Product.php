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
 * @author : Nicolas MUGNIER
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class MDN_ExternalLogistic_Helper_Adapter_Logsys_Product extends MDN_ExternalLogistic_Helper_Adapter_Logsys_Abstract
{

    const kReferentielMagento = "CAT";
    const kReferentielPath = "in/cat/";

    /**
     * Process
     *
     * @return array $data
     */
    public function process()
    {

        $this->checkTraitment();

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
        
        //generate csv
        $csv = $this->getProductCsv($productIds);
        
        //process file
        $streamCode = $this->formatFilename($this->getStreamPrefix());
        $this->saveAndUploadFile($streamCode, $csv, $this->getRemoteDirectory());

        //return result
        $data = array(
            'error' => false,
            'entity_ids' => implode(',', $productIds),
            'result' => 'Stream code '.$streamCode.', '.count($productIds).' products sent',
            'logistic_stream_code' => $streamCode
        );

        return $data;
    }

    /**
     * Build csv
     *
     * @param array $productIds
     * @return string $retour
     */
    public function getProductCsv($productIds){

        //header
        $retour = "ART_ID\tART_LIBELLE\tART_FOURNISSEUR\tART_CODE_FRN\tART_FAMILLE\tART_CATEGORIE\tART_SOUS_CATEGORIE\tART_MARQUE\tART_ID_FAB\tART_ID_FRN\tART_URL\n";

        // content
        foreach($productIds as $id){

            $product = Mage::getModel('catalog/product')->load($id);
            $idCategories = $product->getCategoryIds();
            if (count($idCategories) > 0)
                $category = Mage::getModel('catalog/category')->load($idCategories[0])->getname();
            else
                $category = "inconnu";

            $productName = $this->formatContent($product->getname());
            if (!$productName)
                $productName = 'Aucun nom';
            $retour .= "\"".$product->getsku()."\"\t";  // sku*
            $retour .= "\"".$productName."\"\t"; // libelle*
            $retour .= "\"\"\t"; // fournisseur
            $retour .= "\"\"\t"; // code fournisseur
            $retour .= "\"divers\"\t";// famille*
            $retour .= "\"".$category."\"\t";// categorie* // TODO : check this value
            $retour .= "\"".$category."\"\t";// sous categorie
            $retour .= "\" \"\t";// marque
            $retour .= "\" \"\t";// ID fabricant
            $retour .= "\" \"\t";// ID fournisseur
            $retour .= "\" \"";// url fiche produit

            $retour .= "\n";

        }

        return $retour;
    }

    protected function getStreamPrefix(){
        return self::kReferentielMagento;
    }

    protected function getRemoteDirectory(){
        return self::kReferentielPath;
    }
}
