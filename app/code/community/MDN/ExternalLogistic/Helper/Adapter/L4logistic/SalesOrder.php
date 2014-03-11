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
class MDN_ExternalLogistic_Helper_Adapter_L4logistic_SalesOrder extends MDN_ExternalLogistic_Helper_Adapter_L4logistic_Abstract
{
    public function process()
    {
        //collect order ids (todo : dont add sent to logistic items)
        $salesOrderIds = array();
        $collection = mage::getModel('Orderpreparation/ordertopreparepending')
                            ->getCollection()
                            ->addFieldToFilter('opp_type', 'fullstock');
        foreach($collection as $item)
        {
            $order = mage::getModel('sales/order')->load($item->getopp_order_id());
            if ($order->getsent_to_logistic_company() == 1)
                    continue;
            $salesOrderIds[] = $item->getopp_order_id();
        }

        if (count($salesOrderIds) > 0)
        {
            //generate xml
            $xml = $this->getSalesOrderXml($salesOrderIds);

            //create working directory
            $streamCode = 'CMDCLI'.date('YmdHis');
            $this->saveAndUploadFile($streamCode, $xml, 'IN/');
        }
        
        //return result
        $data = array(
            'error' => false,
            'entity_ids' => implode(',', $salesOrderIds),
            'result' => 'Stream code '.$streamCode.', '.count($salesOrderIds).' orders sent',
            'logistic_stream_code' => $streamCode
        );

        return $data;
    }

    //***************************************************************************************************************************************
    //***************************************************************************************************************************************
    // XML GENERATION
    //***************************************************************************************************************************************
    //***************************************************************************************************************************************


    protected function getSalesOrderXml($salesOrderIds)
    {
        $xmlWriter = mage::helper('ExternalLogistic/XmlWriter');
        $xmlWriter->init();

        $prefix = mage::getStoreConfig('externallogistic/l4logistic/prefix');

        //root element
        $xmlWriter->push('OPs');

        foreach($salesOrderIds as $orderId)
        {
			$order = mage::getModel('sales/order')->load($orderId);
            $xmlWriter->push('OP');

            //main information for order
            $xmlWriter->element('CodeActivite', mage::getStoreConfig('externallogistic/l4logistic/activity'));
            $xmlWriter->element('Activite', mage::getModel('ExternalLogistic/System_Config_Source_L4logistic_Activity')->getLabelFromCode(mage::getStoreConfig('externallogistic/l4logistic/activity')));
            $xmlWriter->element('OrdrePreparation', $prefix.$order->getincrement_id());
            $xmlWriter->element('DateValidation', $this->formatDate());
            
            //shipping information
            $shippingAddress = $order->getShippingAddress();
            $xmlWriter->element('LivraisonCivilite', $shippingAddress->getprefix());
            $xmlWriter->element('LivraisonNom', $this->cleanStringValue($shippingAddress->getlastname(), 50));
            $xmlWriter->element('LivraisonPrenom', $this->cleanStringValue($shippingAddress->getfirstname(), 50));
            $xmlWriter->element('LivraisonSociete', $this->cleanStringValue($shippingAddress->getcompany(), 50));
            for($i=0;$i<$this->getStreetLineCount();$i++)
            {
                $xmlWriter->element('LivraisonAdresse'.($i + 1), $this->cleanStringValue($shippingAddress->getStreet($i + 1), 35));
            }
            $xmlWriter->element('LivraisonCodePostal', $shippingAddress->getpostcode());
            $xmlWriter->element('LivraisonVille', $shippingAddress->getcity());
            $xmlWriter->element('LivraisonPays', $shippingAddress->getcountry_id());
            $xmlWriter->element('LivraisonTelephone', $shippingAddress->gettelephone());
            $xmlWriter->element('LivraisonFax', $shippingAddress->getfax());
            $xmlWriter->element('LivraisonEmail', $order->getcustomer_email());
            $xmlWriter->element('LivraisonB2B', 'false');
            $xmlWriter->element('LivraisonCommentaire', '');

            //billing information
            $billingAddress = $order->getBillingAddress();
            $xmlWriter->element('FacturationCivilite', $billingAddress->getprefix());
            $xmlWriter->element('FacturationNom', $this->cleanStringValue($billingAddress->getlastname(), 50));
            $xmlWriter->element('FacturationPrenom', $this->cleanStringValue($billingAddress->getfirstname(), 50));
            $xmlWriter->element('FacturationSociete', $this->cleanStringValue($billingAddress->getcompany(), 50));
            for($i=0;$i<$this->getStreetLineCount();$i++)
            {
                $xmlWriter->element('FacturationAdresse'.($i + 1), $this->cleanStringValue($billingAddress->getStreet($i + 1), 35));
            }
            $xmlWriter->element('FacturationCodePostal', $billingAddress->getpostcode());
            $xmlWriter->element('FacturationVille', $billingAddress->getcity());
            $xmlWriter->element('FacturationPays', $billingAddress->getcountry_id());
            $xmlWriter->element('FacturationTelephone', $billingAddress->gettelephone());
            $xmlWriter->element('FacturationFax', $billingAddress->getfax());
            $xmlWriter->element('FacturationEmail', $order->getcustomer_email());
            $xmlWriter->element('FacturationNumeroFacture', $this->getInvoiceRef($order));
            $xmlWriter->element('FacturationDateFacture', $this->formatDate());
            $xmlWriter->element('FacturationModePaiement', $order->getPayment()->getmethod());
            $xmlWriter->element('FacturationDevise', $order->getorder_currency_code());
            $xmlWriter->element('FacturationCoursDevise', '1');
            $xmlWriter->element('FacturationNumeroTVA', ($order->getCustomer() ? $order->getCustomer()->gettaxvat() : ''));

            //shipping method
            $xmlWriter->element('TransportGestion', $this->getTransportGestion($order));
            $xmlWriter->element('TransportContrat', $this->getTransportContrat($order));
            $xmlWriter->element('TransportImpose', 'true');
            $xmlWriter->element('TransportInstructionsLivraison', '');
            $xmlWriter->element('TransportCRBT', 'false');

            //preparation information
            $xmlWriter->element('PreparationPriorite', '0');
            $xmlWriter->element('PreparationInstructions', '');
            $xmlWriter->element('PreparationGroupeOrdresPreparation', $prefix);
            $xmlWriter->element('PreparationGroupeOrdresPreparationDescription', mage::getStoreConfig('externallogistic/l4logistic/customer_name'));
            $xmlWriter->element('PreparationImpressionDocLiv', 'true');
            $xmlWriter->element('PreparationImpressionLangue', $billingAddress->getcountry_id());

            //parse products to merge product lines
            $products = array();
            foreach($order->getAllItems() as $product)
            {
                //consider only simple products
                if (($product->getproduct_type() != 'simple') && ($product->getproduct_type() != 'grouped'))
                        continue;

                $sku = $product->getSku();
                if (!isset($products[$sku]))
                {
                    $products[$sku] = array();
                    $products[$sku]['qty'] =  (int)$product->getqty_ordered();
                    $products[$sku]['name'] =  $this->cleanStringValue($product->getName(), 30);
                }
                else
                    $products[$sku]['qty'] += (int)$product->getqty_ordered();
            }

            //create products markups
            $i = 1;
            foreach($products as $sku => $productInfo)
            {

                $xmlWriter->push('Ligne');
                $xmlWriter->element('CodeArticle', $prefix.$sku);
                $xmlWriter->element('Quantite', $productInfo['qty']);
                $xmlWriter->element('Priorite', '0');
                $xmlWriter->element('NumeroLigne', $i);
                $xmlWriter->element('DesignationLangue1', $productInfo['name']);
                $xmlWriter->element('DesignationLangue2', $productInfo['name']);
                $xmlWriter->pop();

                $i++;
            }

            //end order
            $xmlWriter->pop();
        }

        //end root element
        $xmlWriter->pop();

        return $xmlWriter->getXml();
    }


    //***********************************************************************************************************************************
    //***********************************************************************************************************************************
    // TOOLS
    //***********************************************************************************************************************************
    //***********************************************************************************************************************************

    protected function getStreetLineCount()
    {
        return mage::getStoreConfig('customer/address/street_lines');
    }

    protected function getInvoiceRef($order)
    {
        foreach($order->getInvoiceCollection() as $invoice)
        {
            return $invoice->getincrement_id();
        }
    }

    protected function getInvoiceDate($order)
    {
        foreach($order->getInvoiceCollection() as $invoice)
        {
            return date('YmdHis', strtotime($invoice->getcreated_at()));
        }
    }

    protected function getTransportGestion($order)
    {
        return 'ANONYME EZT';
    }

    protected function getTransportContrat($order)
    {
        return 'ANONYME EZT';
    }


}