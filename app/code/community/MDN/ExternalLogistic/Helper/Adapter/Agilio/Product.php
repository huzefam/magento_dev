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
class MDN_ExternalLogistic_Helper_Adapter_Agilio_Product extends MDN_ExternalLogistic_Helper_Adapter_Agilio_Abstract {

    /**
     * Process stream to send new product to logistic company
     * $forceSourceContent may contain product to send
     *
     * @param unknown_type $runDirectory is the working directory (optional) to store send files or to store logs
     * @param unknown_type $forceSourceContent may contain product to send
     *
     * @return : log information
     */
    public function process($runDirectory, $forceSourceContent) {
        $result = '';

        //definit les commandes à récupérer
        $productIds = array();

        //recupere les commandes
        $collection = mage::getModel('catalog/product')
                        ->getCollection();


        //si on force les numéros de commande à récupérer
        if (isset($forceSourceContent['src_products'])) {
            $collection->addAttributeToSelect('*');
            $collection->addFieldToFilter('entity_id', array('in' => $forceSourceContent['src_products']));
        } else {
            $collection->addFieldToFilter('sent_to_logistic_company', array('eq' => 0));
        }
        // bdd connection
        $bdd = mage::helper('ExternalLogistic/Bdd')->getConnection();


        //traite les produits
        foreach ($collection as $product) {
            if($product->getData('synchrologistique')==1){
            $result .= 'Traitement produit ' . $product->getSku(). '...';

            $data = array(
                'c_designation' => substr($product->getData('designation1'), 0, 40),
                'c_designationf' => substr($product->getData('designationf'), 0, 30),
                'c_famille' => substr($product->getAttributeText('manufacturer'), 0, 50),
                'c_analyse' => '',
                'n_poidsuvc' => number_format($product->getData('weight'), 3),
                'c_codebarre' => substr(mage::helper('AdvancedStock/Product_Barcode')->getBarcodeForProduct($product), 0, 30),
                'd_fin' => 0,
                'c_comment' => '',
                'c_refclient' => substr($product->getSku(), 0, 50),
                'c_reff' => substr($product->getSku(), 0, 50),
                'b_actif' => ($product->getStatus() == 1 ? 1 : 0));

            //test
            //$result = print_r($data,1);
            //die ($result);
            // check livraison article
            $querycheckarticle = $bdd->select()
                            ->from(array('article' => 'vp_article_' . mage::getStoreConfig('externallogistic/bdd/login')))
                            ->where('c_ref = ?', $product->getSku());

            $checkarticle = $bdd->query($querycheckarticle);
            $checkarticlerow = $checkarticle->fetch();
            
            if($checkarticlerow['c_ref']==$product->getSku()){
                $result .= 'mise à jour du produit '.$product->getSku(). '...';
                
                $bdd->update('vp_article_' . mage::getStoreConfig('externallogistic/bdd/login'),$data,'c_ref="'.$product->getSku().'"');
            } else {
                $result .= 'nouveau produit '.$product->getSku(). '...';
                
                $data['c_ref'] = substr($product->getSku(), 0, 60);
                $bdd->insert('vp_article_' . mage::getStoreConfig('externallogistic/bdd/login'), $data); 
            }



            //marque les commandes comme traitées

            $result .= ' OK. ';
            $this->markSourcesAsProcessed($product);
            }
        }

        return $result;
    }

}