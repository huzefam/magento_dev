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
class MDN_ExternalLogistic_Helper_Adapter_Logsys_SalesOrder extends MDN_ExternalLogistic_Helper_Adapter_Logsys_Abstract
{

    const kCommandeClient = "CMD";
    const kCommandeClientPath = "in/com/";

    protected function getRemoteDirectory(){
        return self::kCommandeClientPath;
    }

    protected function getStreamPrefix(){
        return self::kCommandeClient;
    }

    /**
     * Process
     *
     * @return array $data
     */
    public function process()
    {
        $this->checkTraitment();

        $salesOrderIds = array();
        $documents = array();
        $streamCode = $this->formatFilename($this->getStreamPrefix());
        $collection = mage::getModel('Orderpreparation/ordertopreparepending')
                            ->getCollection()
                            ->addFieldToFilter('opp_type', 'fullstock');
        
        foreach($collection as $item)
        {
            $order = mage::getModel('sales/order')->load($item->getopp_order_id());
            if ($order->getsent_to_logistic_company() == 1 || $order->getpayment_validated() != 1)
                    continue;
            $salesOrderIds[] = $item->getopp_order_id();
            // build customers documents
            $documents[$order->getincrement_id()] = $this->getDocuments($order);
        }

        if (count($salesOrderIds) > 0)
        {
            //generate csv
            $csv = $this->getSalesOrderCsv($salesOrderIds);

            //create working directory
            $this->saveAndUploadFile($streamCode, $csv, $this->getRemoteDirectory());
            $this->uploadDocuments($documents);
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

    /**
     * Build csv
     *
     * @param array $salesOrderIds
     * @return string $retour
     */
    protected function getSalesOrderCsv($salesOrderIds)
    {
        $retour = "CMD_ID\tDEST_NOM\tDEST_PRENOM\tDEST_ADRESSE1\tDEST_ADRESSE2\tDEST_CODEPOSTAL\tDEST_VILLE\tDEST_PAYS\tDEST_TEL1\tDEST_MAIL\tART_ID\tART_QTE_COMMANDE\tART_QTE_EXEP\tLIV_MODE\tLIV_MODE_DETAIL\tLIV_MODE_PR\tCMD_ID_ANNULE\n";

        foreach($salesOrderIds as $id){

            $order = Mage::getModel('sales/order')->load($id);

            // one line by product
            foreach($order->getAllItems() as $product){

                $retour .= "\"".$order->getincrement_id()."\"\t"; // numero de commande magento *

                // shipping infos
                $shippingAddress = $order->getShippingAddress();
                $retour .= "\"".$shippingAddress->getlastname()."\"\t"; // nom *
                $retour .= "\"".$shippingAddress->getfirstname()."\"\t"; // prenom
                $retour .= "\"".$shippingAddress->getStreet(1)."\"\t"; // adresse1 *
                $retour .= "\"".$shippingAddress->getStreet(2)."\"\t"; // adresse 2
                $retour .= "\"".$shippingAddress->getpostcode()."\"\t"; // code postal *
                $retour .= "\"".$shippingAddress->getcity()."\"\t"; // ville *
                $retour .= "\"".$shippingAddress->getcountry_id()."\"\t"; // pays *
                $retour .= "\"".$shippingAddress->gettelephone()."\"\t"; // telephone
                $retour .= "\"".$shippingAddress->getcustomer_email()."\"\t"; // email

                // product infos
                $retour .= "\"".$product->getsku()."\"\t"; // reference produit *
                $retour .= "\"".(int)$product->getqty_ordered()."\"\t"; // quantite commande *
                $retour .= "\"".(int)$product->getqty_ordered()."\"\t"; // * quantite exep TODO : qu'est ce qu'on met là ??

                // shipping mode
                $retour .= "\"CHRO\"\t";//"\"".$this->getShippingMethodTitle($order->getshipping_method())."\"\t"; // methode de livraison*
                $retour .= "\"09H\"\t"; // si so colissimo (a domicile, point relais, etc)
                $retour .= "\"\"\t"; // si so colissimo > point relais
                $retour .= "\"\""; // numero de commande a annuler
                
                $retour .= "\n";

            }

        }

        return $retour;
    }

    /**
     * Retrieve shipping method title
     *
     * @param string $shipping_method
     * @return string $title
     */
    protected function getShippingMethodTitle($shipping_method) {

        $title = "";

        if ($shipping_method != "") {

            $tab = explode('_', $shipping_method);

            $options = array();
            

            // get shipment methods
            $carriers = Mage::getStoreConfig('carriers', 0);

            foreach ($carriers as $carrierKey => $item) {
                if ($carrierKey == $tab[0]) {
                    $title = mage::getModel($item['model'])->getConfigData('title');
                }
            }

        }

        return $title;
    }

    /**
     * Get pdf documents to join on csv order file
     *
     * @param Mage_Sales_Model_Order $order
     * @return array $retour
     */
    protected function getDocuments($order){

        $retour = array();
        $invoice = null;
        
        // retrieve invoice id
        $invoice_id = $this->retrieveInvoice($order);

        // get invoice
        if($invoice_id == ''){
            // create invoice
            $invoice = Mage::Helper('ExternalLogistic/Invoice')->createInvoice($order);

        }else{
            // load invoice
            $invoice = Mage::getModel('sales/order_invoice')->load($invoice_id);
        }

        
        if($invoice->getid()){
            if ($invoice->getStoreId()) {
                Mage::app()->setCurrentStore($invoice->getStoreId());
            }

            $invoice_pdf = Mage::getModel('sales/order_pdf_invoice')->getPdf(array($invoice));

            $retour['invoice'] = $invoice_pdf->render();
        }

        $retour['shipment'] = mage::getModel('ExternalLogistic/Pdf_OrderForLogisticCompany')->getPdf(array($order))->render();

        return $retour;

    }

    /**
     * Retrieve invoice associate to order $order
     *
     * @param Mage_Sales_Model_Order $order
     * @return string $retour
     */
    public function retrieveInvoice($order){

        $retour = '';

        $collection = $order->getInvoiceCollection();

        foreach($collection as $_invoice){

            $retour = $_invoice->getId();
            break;

        }

        return $retour;
    }

    /**
     * Upload documents on ftp
     *
     * @param array $documents
     * @return int 0
     */
    public  function uploadDocuments($documents){

        foreach($documents as $k => $tab){

            foreach($tab as $type => $pdf){

                $this->saveAndUploadFile($type.'_'.$k.'.pdf', $pdf, self::kCommandeClientPath);

            }

        }

        return 0;

    }

}
