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
class MDN_ExternalLogistic_Helper_Parser_Agilio_Shipment extends MDN_ExternalLogistic_Helper_Parser_Agilio_Abstract {

    /**
     * Process shipping confirmation from logistic company
     * Use mage::helper('ExternalLogistic/Shipment')->createShipment to create a new shipment
     *
     * @param unknown_type $runDirectory is the working directory (optional) to store send files or to store logs
     *
     * @return : log information
     */
    public function process($runDirectory) {
        $result = '';


        // get order processing (waiting to shipment)
        $collection = mage::getModel('sales/order')
                        ->getCollection();

        $collection->addFieldToFilter('status', array('in' => 'processing'));

        // bdd connection
        $bdd = mage::helper('ExternalLogistic/Bdd')->getConnection();
        $result .= 'Connection établie. ';

        foreach ($collection as $order) {


            // purchase order Check
            $querycheckorder = $bdd->select(array('c_boncommande', 'is_commande'))
                            ->from(array('order' => 'vp_commande_' . mage::getStoreConfig('externallogistic/bdd/login') . '_traitee'))
                            ->where('c_boncommande = ?', $order->getincrement_id());

            $checkorder = $bdd->query($querycheckorder);
            $checkorderrow = $checkorder->fetch();
            if ($checkorderrow['c_boncommande'] == $order->getincrement_id()) {
                $orderid = $checkorderrow['is_commande'];
                $result .= 'Traitement '.$checkorderrow['is_commande'].' ';

                // check livraison 
                $querychecksuivi = $bdd->select(array('c_numcolis', 'c_typecolis', 'd_enregistrement'))
                                ->from(array('colis' => 'vp_suivicolis_' . mage::getStoreConfig('externallogistic/bdd/login')))
                                ->where('is_commande = ?', $orderid);

                $checksuivi = $bdd->query($querychecksuivi);
                $checksuivirow = $checksuivi->fetch();


                $orderIncrementId = $order->getincrement_id();
                $tracking = $checksuivirow['c_numcolis'];



                // check livraison article
                $querycheckarticle = $bdd->select(array('is_article', 'n_qtelivree', 'c_ref'))
                                ->from(array('articleligne' => 'vp_ligcommande_' . mage::getStoreConfig('externallogistic/bdd/login') . '_traitee'))
                                ->join(array('article' => 'vp_article_' . mage::getStoreConfig('externallogistic/bdd/login')),
                                        'articleligne.is_article = article.is_article')
                                ->where('is_commande = ?', $orderid);

                $checkarticle = $bdd->query($querycheckarticle);
                $products = array();
                while ($checkarticlerow = $checkarticle->fetch()) {

                    $products[] = array('sku' => $checkarticlerow['c_ref'], 'qty' => $checkarticlerow['n_qtelivree']);
                }

                if(!empty($tracking)){
                // create shipment
                    mage::helper('ExternalLogistic/Shipment')->createShipment($orderIncrementId, date(), $products, $tracking);
                    $result.= 'Expédition de la commande '.$orderIncrementId. ' OK. ';
                }else {
                    $result.= 'Aucun Tracking pour la commande '.$orderIncrementId. '. ';
                }
            }
        }


       return $result;

    }

}