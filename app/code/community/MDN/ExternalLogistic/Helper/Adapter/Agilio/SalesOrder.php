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
class MDN_ExternalLogistic_Helper_Adapter_Agilio_SalesOrder extends MDN_ExternalLogistic_Helper_Adapter_Agilio_Abstract {

    /**
     * Process stream to send sales orders to logistic company
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
        $orderIds = array();

        //recupere les commandes
        $collection = mage::getModel('sales/order')
                        ->getCollection();

        //si on force les numéros de commande à récupérer
        if (isset($forceSourceContent['src_sales_order']))
            $collection->addFieldToFilter('entity_id', array('in' => $forceSourceContent['src_sales_order']));
        else {
            // on filtre les commandes
            mage::helper('ExternalLogistic/SalesOrder')->addConditionsToCollection($collection);
        }
        
        // bdd connection
       $bdd = mage::helper('ExternalLogistic/Bdd')->getConnection();


        if ($bddcheck == 1) {
            //traite les commandes
            foreach ($collection as $order) {
                try {
                    $result .= 'Traitement commande ' . $order->getincrement_id() . '. \n';

                    // order check
                    $querycheck1orderexists = $bdd->select('is_commande')
                                    ->from(array('commande' => 'vp_commande_' . mage::getStoreConfig('externallogistic/bdd/login') . '_nontraitee'))
                                    ->where('c_boncommande = ?', $order->getincrement_id());

                    $check1orderexists = $bdd->query($querycheck1orderexists);
                    $check1orderexistsrow = $check1orderexists->fetch();

                    if (!empty($check1orderexistsrow['is_commande'])) {
                        $result .= 'Commande ' . $order->getincrement_id() . " déjà envoyée mais en attente de traitement chez la logistique. \n";
                    } else {
                        $querycheck2orderexists = $bdd->select('is_commande')
                                        ->from(array('commande' => 'vp_commande_' . mage::getStoreConfig('externallogistic/bdd/login') . '_traitee'))
                                        ->where('c_boncommande = ?', $order->getincrement_id());
                        $check2orderexists = $bdd->query($querycheck2orderexists);
                        $check2orderexistsrow = $check2orderexists->fetch();

                        if (!empty($check2orderexistsrow['is_commande'])) {
                            $result .= 'Commande ' . $order->getincrement_id() . ' déjà traitée chez la logistique. \n';
                        } else {


                            // todo: verif all product envoyage
                            //$fullstock = mage::getStoreConfig('externallogistic/sales_order/require_fullstock');
                            //if($fullstock==1){
                            //}
                            // verif all product envoyage
                            $breaksynchro = 0;
                            $order_items = $order->getAllItems();
                            foreach ($order_items as $order_item) {
                                $product = mage::getModel('catalog/product')->load($order_item->getProductId());
                                if ($product->getData('synchrologistique') == 0

                                    )$breaksynchro = 1;
                                break;
                            }

                            if ($breaksynchro == 0) {

                                // Customer
                                $customer = Mage::getModel("customer/customer")->load($order->getCustomerId());
                                $clientid = '';

                                // Customer Check
                                $querycheckcustomer = $bdd->select(array('is_client', 'c_codeprop'))
                                                ->from(array('client' => 'vp_client_' . mage::getStoreConfig('externallogistic/bdd/login')))
                                                ->where('c_codeprop = ?', $order->getCustomerId());

                                $customercheck = $bdd->query($querycheckcustomer);
                                $customercheckrow = $customercheck->fetch();
                                if ($customercheckrow['c_codeprop'] == $order->getCustomerId()) {
                                    $result .= 'Client existant ' . $customer->getData('lastname') . '. \n';
                                    $clientid = $customercheckrow['is_client'];
                                } else {
                                    $result .= 'Nouveau client ' . $customer->getData('lastname') . '. \n';

                                    // Customer Billing Adress
                                    $billing_adress = $order->getBillingAddress();

                                    $billing_company = '';
                                    $billing_street = '';
                                    $billing_street2 = '';
                                    $billing_postcode = '';
                                    $billing_city = '';
                                    $billing_country = '';
                                    $billing_telephone = '';
                                    $billing_fax = '';

                                    $billing_company = $billing_adress->getData('company');

                                    $billing_street = $billing_adress->getStreet1();
                                    if ($billing_adress->getStreet2() != '') {
                                        $billing_street .= ' ' . $billing_adress->getStreet2();
                                    }

                                    $billing_street = Mage::helper('core/string')->str_split($billing_street, 255, true, true);

                                    $billing_street2 = $billing_street[1];
                                    $billing_street = $billing_street[0];

                                    $billing_postcode = $billing_adress->getData('postcode');
                                    $billing_postcode = preg_replace('/[A-Za-z_-]*/', '', $billing_postcode);
                                    $billing_city = $billing_adress->getData('city');

                                    $billing_country_id = $billing_adress->getData('country_id');
                                    //$locale = new Mage_Core_Model_Locale();
                                    //$billing_country = $locale->setLocale('fr_FR')->getCountryTranslation($billing_country_id);

                                    $billing_telephone = $billing_adress->getData('telephone');

                                    $billing_fax = $billing_adress->getData('fax');

                                    $dataclient = array('c_cNomsociete' => substr($billing_company, 0, 40),
                                        'c_adresse1' => substr($billing_street, 0, 255),
                                        'c_adresse2' => substr($billing_street2, 0, 40),
                                        'c_cp' => substr($billing_postcode, 0, 10),
                                        'c_ville' => substr($billing_city, 0, 50),
                                        'c_contact' => substr($customer->getData('lastname'), 0, 50),
                                        'c_tel' => substr($billing_telephone, 0, 22),
                                        'c_email' => substr($customer->getData('email'), 0, 50),
                                        'c_fax' => substr($billing_fax, 0, 22),
                                        'd_datecreation' => now(),
                                        'c_pays' => substr($billing_country_id, 0, 2),
                                        'd_fin' => 0,
                                        'c_codeprop' => substr($customer->getID(), 0, 20),
                                        'c_prenom' => substr($customer->getData('firstname'), 0, 50),
                                        'c_nom' => substr($customer->getData('lastname'), 0, 70),
                                    );

                                    $bdd->insert('vp_client_' . mage::getStoreConfig('externallogistic/bdd/login'), $dataclient);
                                    $clientid = $bdd->lastInsertId('is_client');
                                }

                                // Customer Shipping Adress
                                $shipping_adress = $order->getShippingAddress();

                                $shipping_company = '';
                                $shipping_street = '';
                                $shipping_street2 = '';
                                $shipping_postcode = '';
                                $shipping_city = '';
                                $shipping_country = '';
                                $shipping_telephone = '';
                                $shipping_fax = '';

                                $shipping_company = $shipping_adress->getData('company');

                                $shipping_street = $shipping_adress->getStreet1();
                                if ($shipping_adress->getStreet2() != '') {
                                    $shipping_street .= ' ' . $shipping_adress->getStreet2();
                                }

                                $shipping_street = Mage::helper('core/string')->str_split($shipping_street, 255, true, true);
                                $shipping_street2 = $shipping_street[1];
                                $shipping_street = $shipping_street[0];

                                $shipping_postcode = $shipping_adress->getData('postcode');
                                $shipping_postcode = preg_replace('/[A-Za-z_-]*/', '', $shipping_postcode);

                                $shipping_city = $shipping_adress->getData('city');
                                $shipping_country_id = $shipping_adress->getData('country_id');
                                $shipping_telephone = $shipping_adress->getData('telephone');
                                $shipping_fax = $shipping_adress->getData('fax');

                                $dataadrlivraison = array('is_client' => $clientid,
                                    'c_adresse1' => substr($shipping_street, 0, 255),
                                    'c_adresse2' => substr($shipping_street2, 0, 40),
                                    'c_cp' => substr($shipping_postcode, 0, 10),
                                    'c_ville' => substr($shipping_city, 0, 50),
                                    'c_pays' => substr($shipping_country_id, 0, 2),
                                    'c_tel' => substr($shipping_telephone, 0, 22),
                                    'c_fax' => substr($billing_fax, 0, 22),
                                    'c_email' => substr($customer->getData('email'), 0, 50),
                                    'c_contact' => substr($customer->getData('lastname') . ' ' . $customer->getData('firstname'), 0, 50),
                                    'd_fin' => 0,
                                    'c_comment' => substr('', 0, 50),
                                );

                                // Customer shipping Check
                                $querycheckcustomershipping = $bdd->select()
                                                ->from(array('client' => 'vp_adrlivraisontype_' . mage::getStoreConfig('externallogistic/bdd/login')))
                                                ->where('is_client = ?', $clientid);

                                $checkcustomershipping = $bdd->query($querycheckcustomershipping);
                                $newadress = array();
                                $i = 0;
                                while ($checkcustomershippingrow = $checkcustomershipping->fetch()) {
                                    $newadress[$i]['id'] = $checkcustomershippingrow['is_adrlivraison'];
                                    $newadress[$i]['check'] = 0;
                                    if ($checkcustomershippingrow['c_adresse1'] != $dataadrlivraison['c_adresse1']
                                    
                                        )$newadress[$i]['check']+=1;
                                    if ($checkcustomershippingrow['c_adresse2'] != $dataadrlivraison['c_adresse2']
                                    
                                        )$newadress[$i]['check']+=1;
                                    if ($checkcustomershippingrow['c_cp'] != $dataadrlivraison['c_cp']
                                    
                                        )$newadress[$i]['check']+=1;
                                    if ($checkcustomershippingrow['c_ville'] != $dataadrlivraison['c_ville']
                                    
                                        )$newadress[$i]['check']+=1;
                                    if ($checkcustomershippingrow['c_pays'] != $dataadrlivraison['c_pays']
                                    
                                        )$newadress[$i]['check']+=1;
                                    if ($checkcustomershippingrow['c_tel'] != $dataadrlivraison['c_tel']
                                    
                                        )$newadress[$i]['check']+=1;
                                    if ($checkcustomershippingrow['c_fax'] != $dataadrlivraison['c_fax']
                                    
                                        )$newadress[$i]['check']+=1;
                                    if ($checkcustomershippingrow['c_email'] != $dataadrlivraison['c_email']
                                    
                                        )$newadress[$i]['check']+=1;
                                    if ($checkcustomershippingrow['c_contact'] != $dataadrlivraison['c_contact']
                                    
                                        )$newadress[$i]['check']+=1;
                                    if ($checkcustomershippingrow['c_comment'] != $dataadrlivraison['c_comment']
                                    
                                        )$newadress[$i]['check']+=1;
                                    $i++;
                                }

                                if (empty($newadress)) {
                                    $bdd->insert('vp_adrlivraisontype_' . mage::getStoreConfig('externallogistic/bdd/login'), $dataadrlivraison);
                                    $clientadrlivraisonid = $bdd->lastInsertId('is_adrlivraison');
                                    $result .= 'Création d\'une nouvelle adresse ' . $clientadrlivraisonid . '. \n';
                                } else {
                                    $findadresse = 0;
                                    foreach ($newadress as $newadressrow) {
                                        if ($newadressrow['check'] == 0 && $findadresse != 1) {
                                            $clientadrlivraisonid = $newadressrow['id'];
                                            $findadresse = 1;
                                            break;
                                        }
                                        if ($findadresse == 0) {
                                            $bdd->insert('vp_adrlivraisontype_' . mage::getStoreConfig('externallogistic/bdd/login'), $dataadrlivraison);
                                            $clientadrlivraisonid = $bdd->lastInsertId('is_adrlivraison');
                                        }
                                    }
                                }

                                // Order Invoice
                                $invoices = $order->getInvoiceCollection();
                                $invoiceid = '';
                                foreach ($invoices as $invoice) {
                                    if (!$invoice->isCanceled()) {

                                        // order check

                                        $querycheckinvoiceexists = $bdd->select('is_facture')
                                                        ->from(array('facture' => 'vp_facture_' . mage::getStoreConfig('externallogistic/bdd/login')))
                                                        ->where('name = ?', $invoice->getOrderIncrementId());

                                        $checkinvoiceexists = $bdd->query($querycheckinvoiceexists);
                                        $checkinvoiceexistsrow = $checkinvoiceexists->fetch();

                                        if (!empty($checkinvoiceexistsrow['is_facture'])) {
                                            $invoiceid = $checkinvoiceexistsrow['is_facture'];
                                            break;
                                        } else {
                                            // envoie de la facture
                                            $invoicepdf = Mage::getModel('sales/order_pdf_invoice')->getPdf(array($invoice));
                                            $file_name = 'var/tmp/tmp.pdf';
                                            $invoicepdf->save($file_name);

                                            $datainvoice = array('content_type' => 'application/x-pdf', //'application/octet-stream', // 'application/pdf'
                                                'size' => filesize($file_name),
                                                'name' => $invoice->getOrderIncrementId(),
                                                'facture' => $invoicepdf->render(),
                                            );

                                            unlink($file_name);

                                            $invoice_creation_date = $invoice->getCreatedAtStoreDate()->toString('yyyy-MM-dd');

                                            $bdd->insert('vp_facture_' . mage::getStoreConfig('externallogistic/bdd/login'), $datainvoice);
                                            $invoiceid = $bdd->lastInsertId('is_facture');
                                            break;
                                        }
                                    }
                                }
                                // items
                                $order_items = $order->getAllItems();

                                $nbligne = count($order_items);
                                $shipping = 4;
                                if ($order->getData('shipping_method') == "owebiashipping1_colissimo_france_sign") {
                                    $shipping = 4;
                                } else if ($order->getData('shipping_method') == "owebiashipping2_chronopost_18_france") {
                                    $shipping = 11;
                                } else if ($order->getData('shipping_method') == "owebiashipping3_transporteur_france_sign") {
                                    $shipping = 23;
                                }

                                $dataorder = array('is_client' => $clientid,
                                    'is_adrlivraison' => $clientadrlivraisonid,
                                    'is_transporteur' => $shipping,
                                    'c_boncommande' => $order->getincrement_id(),
                                    'd_commande' => $order->getCreatedAtStoreDate()->toString('yyyy-MM-dd'),
                                    'd_datedemandee' => $invoice_creation_date,
                                    'n_nbligne' => $nbligne,
                                    'd_fin' => 0,
                                    'c_notebl' => '',
                                    'c_notebp' => '',
                                    'h_heuredemandee' => '',
                                    'is_facture' => $invoiceid,
                                    'n_nbfacture' => 0,
                                );


                                $bdd->insert('vp_commande_' . mage::getStoreConfig('externallogistic/bdd/login') . '_nontraitee', $dataorder);
                                $orderid = $bdd->lastInsertId('is_commande');
                                //$result .= print_r($datainvoice, 1);
                                //$result .= print_r($dataorder, 1);

                                foreach ($order_items as $order_item) {
                                    $querycheckitem = $bdd->select('is_article')
                                                    ->from(array('article' => 'vp_article_' . mage::getStoreConfig('externallogistic/bdd/login')))
                                                    ->where('c_ref = ?', $order_item->getSku());

                                    $checkitem = $bdd->query($querycheckitem);
                                    $checkitemrow = $checkitem->fetch();

                                    $order_item_qty = intval($order_item->getData('qty_ordered'));

                                    $dataitems = array('is_commande' => $orderid,
                                        'is_article' => $checkitemrow['is_article'],
                                        'n_qtecommandee' => $order_item_qty,
                                    );

                                    //$result .= print_r($dataitems, 1);
                                    $bdd->insert('vp_ligcommande_' . mage::getStoreConfig('externallogistic/bdd/login') . '_nontraitee', $dataitems);
                                }

                                $result.= 'Commande correctement envoyée au logisticien. \n';
                                //marque les commandes comme traitées
                                $this->markSourcesAsProcessed($order);
                            } else {
                                $result .= 'Commande ' . $order->getincrement_id() . ' contenant des articles non envoyable au logisticien. \n';
                            }
                        }
                    }
                } catch (Exception $ex) {
                    $result .= 'Erreur : ' . $ex->getMessage() . "\n";
                }
            }
        }

        return $result;
    }

}