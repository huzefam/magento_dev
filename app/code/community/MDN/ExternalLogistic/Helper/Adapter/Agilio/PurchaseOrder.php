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
class MDN_ExternalLogistic_Helper_Adapter_Agilio_PurchaseOrder extends MDN_ExternalLogistic_Helper_Adapter_Agilio_Abstract {

    /**
     * Process stream to send new purchase orders to logistic company
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
        $purchaseOrderIds = array();

        //recupere les commandes
        $collection = mage::getModel('Purchase/Order')
                        ->getCollection();

        //si on force les numéros de commande à récupérer
        if (isset($forceSourceContent['src_purchase_order']))
            $collection->addFieldToFilter('po_num', array('in' => $forceSourceContent['src_purchase_order']));
        else {
            throw new Exception('Auto selection for purchase orders not implemented');
        }

        // bdd connection
        $bdd = mage::helper('ExternalLogistic/Bdd')->getConnection();

        //traite les commandes fournisseur
        foreach ($collection as $purchaseOrder) {
            $result .= 'Traitement commande fournisseur ' . $purchaseOrder->getpo_order_id() . '...';

            // Supplier
            $supplier = $purchaseOrder->getSupplier();
            $supplierid = '';
            // supplier Check
            $querychecksupplier = $bdd->select(array('c_nom', 'is_fournisseur'))
                            ->from(array('client' => 'vp_fournisseur_' . mage::getStoreConfig('externallogistic/bdd/login')))
                            ->where('c_nom = ?', $supplier->getSup_name());

            $checksupplier = $bdd->query($querychecksupplier);
            $checksupplierrow = $checksupplier->fetch();
            if ($checksupplierrow['c_nom'] == $supplier->getSup_name()) {
                $result .= 'Fournisseur existant ' . $supplier->getSup_name() . '...';
                $supplierid = $checksupplierrow['is_fournisseur'];
            } else {
                $result .= 'Nouveau fournisseur ' . $supplier->getSup_name() . '...';

                $supplier_street = Mage::helper('core/string')->str_split($supplier->getSup_address1() . $supplier->getSup_address2(), 40, true, true);

                $c_adresse2 = $supplier_street[1];
                $c_adresse1 = $supplier_street[0];

                $datasupplier = array('c_nom' => substr($supplier->getSup_name(), 0, 50),
                    'c_adresse1' => substr($c_adresse1, 0, 40),
                    'c_adresse2' => substr($c_adresse2, 0, 40),
                    'c_cp' => substr($supplier->getSup_zipcode(), 0, 10),
                    'c_pays' => substr($supplier->getSup_country(), 0, 2),
                    'c_tel' => substr($supplier->getSup_tel(), 0, 20),
                    'c_fax' => substr($supplier->getSup_fax(), 0, 20),
                    'c_email' => substr($supplier->getSup_mail(), 0, 50),
                    'c_contact' => substr($supplier->getSup_contact(), 0, 50),
                    'c_remarques' => $supplier->getSup_comments(),
                    'd_fin' => 0,
                    'c_comment' => substr($supplier->getSup_rma_comments(), 0, 50),
                    'c_ville' => substr($supplier->getSup_city(), 0, 40),
                );

                $bdd->insert('vp_fournisseur_' . mage::getStoreConfig('externallogistic/bdd/login'), $datasupplier);
                $supplierid = $bdd->lastInsertId('is_fournisseur');
            }

            $purchaseorderid = '';
            // purchase order Check
            $querycheckpurchaseorder = $bdd->select(array('c_refcommandepro', 'is_reception'))
                            ->from(array('purchaseorder' => 'vp_reception_' . mage::getStoreConfig('externallogistic/bdd/login') . '_nontraitee'))
                            ->where('c_refcommandepro = ?', $purchaseOrder->getpo_order_id());

            $checkpurchaseorder = $bdd->query($querycheckpurchaseorder);
            $checkpurchaseorderrow = $checkpurchaseorder->fetch();
            if ($checkpurchaseorderrow['c_refcommandepro'] == $purchaseOrder->getpo_order_id()) {
                $result .= 'Commande fournisseur existe déjà ' . $purchaseOrder->getpo_order_id() . '...';
                $purchaseorderid = $checkpurchaseorderrow['is_reception'];
            } else {
                $result .= 'Nouvelle commande fournisseur ' . $purchaseOrder->getpo_order_id() . '...';


                $datapurchaseorder = array('is_fournisseur' => $supplierid,
                    'c_refcommandepro' => substr($purchaseOrder->getpo_order_id(), 0, 50),
                    'd_receptionprevue' => $purchaseOrder->getpo_supply_date(),
                    'c_numerobl' => '',
                    'd_recepeffective' => '',
                    'b_retour' => 0,
                    'd_fin' => 0,
                    'is_client' => 0,
                );
                //die(' '. print_r($datapurchaseorder,1));
                $bdd->insert('vp_reception_' . mage::getStoreConfig('externallogistic/bdd/login') . '_nontraitee', $datapurchaseorder);
                $purchaseorderid = $bdd->lastInsertId('is_reception');

                $order_items = $purchaseOrder->getProducts();
                foreach ($order_items as $order_item) {
                    
                    $querycheckitem = $bdd->select('is_article')
                                    ->from(array('article' => 'vp_article_' . mage::getStoreConfig('externallogistic/bdd/login')))
                                    ->where('c_ref = ?', $order_item->getSku());

                    $checkitem = $bdd->query($querycheckitem);
                    $checkitemrow = $checkitem->fetch();
                    
                    $order_item_qty = intval($order_item->getPop_qty());

                    $dataitems = array('is_article' => $checkitemrow['is_article'],
                        'is_reception' => $purchaseorderid,
                        'n_qtecommandee' => $order_item_qty,
                    );
                    //$result .= print_r($dataitems, 1);
                    $bdd->insert('vp_ligreception_' . mage::getStoreConfig('externallogistic/bdd/login') . '_nontraitee', $dataitems);
                }

                $result.= ' OK. ';
                //marque les commandes comme traitées
                $this->markSourcesAsProcessed($purchaseOrder);
            }
        }

        return $result;
    }

}