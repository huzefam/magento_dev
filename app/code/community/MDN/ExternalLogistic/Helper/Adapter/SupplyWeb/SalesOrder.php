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
class MDN_ExternalLogistic_Helper_Adapter_SupplyWeb_SalesOrder extends MDN_ExternalLogistic_Helper_Adapter_SupplyWeb_Abstract
{

    const kMnemonique = "CDC01";
    const kTypeAlpha = 'ALPHA';
    const kTypeNum = 'NUM';

    const kCOLIAF = 'COLIAF';
    const kLETMAX = 'LETMAX';
    const kLETSIMPLE = 'LETSIMPLE';

    private $_fieldsHead = null;
    private $_fieldsProduct = null;

    /**
     * Process
     *
     * @return array $data
     */
    public function process()
    {

        $this->checkTraitment();

        $salesOrderIds = array();
        $streamCode = $this->getStreamCode();
        $filename = $this->formatFilename();

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
            //generate csv
            $csv = $this->getSalesOrderCsv($salesOrderIds);

            //create working directory
            $this->saveAndUploadFile($filename, $csv, $this->getRemoteDirectory());

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

    protected function getStreamSuffix(){
        return self::kSuffix;
    }

    protected function getStreamPrefix(){
        return self::kMnemonique;
    }

    protected function getSalesOrderCsv($ids){

        $retour = '';

        foreach($ids as $id){

            $order = Mage::getModel('sales/order')->load($id);
            $shippingAddress = $order->getShippingAddress();
            $billingAddress = $order->getBillingAddress();

            // add header
            foreach($this->getFieldsHeader() as $field){

                //if(array_key_exists('required',$field) && $field['required'] === true)
                    $retour .= $this->formatFieldHeader($field, $order, $billingAddress, $shippingAddress);

            }

            $retour .= "\n";
            $i = 1;

            foreach($order->getAllItems() as $product){
                // add products
                foreach($this->getFieldsProduct() as $field){

                    //if(array_key_exists('required', $field) && $field['required'] === true)
                        $retour .= $this->formatFieldProduct($field, $order, $product, $i);

                }

                $retour .= "\n";
                $i++;
            }

        }

        return $retour;

    }

    protected function formatFieldHeader($field, $order, $billingAddress, $shippingAddress){

        $retour = '';
        $value = '';

        switch($field['name']){

            case 'OP_CODE':
                if(array_key_exists('value', $field))
                    $value = $field['value'];
                break;
            case 'CODE_SOC':
                $value = self::kCODE_SOC;
                break;
            case 'CLE_COMMANDE':
                $value = $order->getincrement_id();
                break;
            case 'NO_CLIENT':
                $customer = Mage::getModel('customer/customer')->load($order->getcustomer_id());
                $value = $customer->getentity_id();
                break;
            case 'REF_CLIENT':
                $customer = Mage::getModel('customer/customer')->load($order->getcustomer_id());
                $value = $customer->getentity_id();
                break;
            case 'DATE_DDEE':
                $value = $this->getShippingDate($order);
                break;
            case 'PRIORITE_CDE':
                break;
            case 'TRANSPORTEUR':
                $value = $this->getCarrierCode($order);
                break;
            case 'AFAC_INDIVIDU':
                $value = $billingAddress->getlastname().' '.$billingAddress->getfirstname();
                break;
            case 'AFAC_RAISON_SOCIALE':
                break;
            case 'AFAC_ADRESSE1':
                $value = $billingAddress->getStreet(1);
                break;
            case 'AFAC_ADRESSE2':
                $value = $billingAddress->getStreet(2);
                break;
            case 'AFAC_ADRESSE3':
                break;
            case 'AFAC_CP':
                $value = $billingAddress->getpostcode();
                break;
            case 'AFAC_VILLE':
                $value = $billingAddress->getcity();
                break;
            case 'AFAC_PAYS':
                $value = $billingAddress->getcountry_id();
                break;
            case 'AFAC_TEL':
                $value = $billingAddress->gettelephone();
                break;
            case 'AFAC_MAIL':
                $value = $billingAddress->getemail();
                break;
            case 'ALIV_INDIVIDU':
                $value = $shippingAddress->getlastname().' '.$shippingAddress->getfirstname();
                break;
            case 'ALIV_RAISON_SOCIALE':
                break;
            case 'ALIV_ADRESSE1':
                $value = $shippingAddress->getStreet(1);
                break;
            case 'ALIV_ADRESSE2':
                $value = $shippingAddress->getStreet(2);
                break;
            case 'ALIV_ADRESSE3':
                break;
            case 'ALIV_CP':
                $value = $shippingAddress->getpostcode();
                break;
            case 'ALIV_VILLE':
                $value = $shippingAddress->getcity();
                break;
            case 'ALIV_PAYS':
                $value = $shippingAddress->getcountry_id();
                break;
            case 'ALIV_TEL':
                $value = $shippingAddress->gettelephone();
                break;
            case 'ALIV_MAIL':
                $value = $shippingAddress->getemail();
                break;
            case 'COMMT':
                break;
            default:
                $value = "";
                break;

        }

        $retour = $this->addSeparateur($field, $value);

        return $retour;

    }

    protected function formatFieldProduct($field, $order, $product, $i){

        $retour = '';
        $value = '';

        switch($field['name']){

            case 'OP_CODE':
                if(array_key_exists('value', $field))
                    $value = $field['value'];
                break;
           case 'CLE_COMMANDE':
               $value = $order->getincrement_id();
               break;
           case 'NO_LIGNE':
               $value = $i;
               break;
           case 'CODE_ART':
               $value = $product->getsku();
               break;
           case 'QTE_CDE':
               $value = (int)$product->getqty_ordered();
               break;
           case 'LIBELLE_ART':
               $value = $product->getname();
           default:
               $value = '';
               break;
        }

        $retour = $this->addSeparateur($field, $value);

        return $retour;

    }

    protected function getShippingDate($order){
        return date('Ymd', Mage::getModel('core/date')->timestamp() + 2 * 24 *3600);
    }

    /**
     * retrouve le nom de la methode de livraison
     *  - lettre MAX => LETMAX
     *  - lettre simple => LETSIMPLE
     *  - colissimo acces france => COLIAF
     *
     * @param Mage_Sales_Model_Order $order
     * @return string $retour
     * TODO : this method must be customized for each merchant
     */
    protected function getCarrierCode($order){

        $retour = 'methode de livraison inconnue';

        $shipping_method = $order->getshipping_method();

        $tmp = explode("_",$shipping_method);
        array_shift($tmp);
        $shipping_method = implode("_",$tmp);

        switch($shipping_method){

            case 'colissimo':
                $retour = self::kCOLIAF;
                break;
            case 'lettre_max':
                $retour = self::kLETMAX;
                break;
            case 'lettre_simple':
                $retour = self::kLETSIMPLE;
                break;

        }

        return $retour;

    }

    protected function getFieldsHeader(){

        if($this->_fieldsHead == null){

            $this->_fieldsHead = array(

                'OP_CODE' => array(
                    'name' => 'OP_CODE',
                    'start' => 1,
                    'length' => 10,
                    'type' =>self::kTypeAlpha,
                    'required' => true,
                    'value' => "E01"
                ),
                'CODE_SOC' => array(
                    'name' => 'CODE_SOC',
                    'start' => 11,
                    'length' => 20,
                    'type' => self::kTypeAlpha,
                    'required' => true
                ),
                'CLE_COMMANDE' => array(
                    'name' => 'CLE_COMMANDE',
                    'start' => 31,
                    'length' => 50,
                    'type' => self::kTypeAlpha,
                    'required' => true
                ),
                'NO_CLIENT' => array(
                    'name' => 'NO_CLIENT',
                    'start' => 81,
                    'length' => 8,
                    'type' => self::kTypeAlpha
                ),
                'REF_CLIENT' => array(
                    'name' => 'REF_CLIENT',
                    'start' => 89,
                    'length' => 50,
                    'type' => self::kTypeAlpha
                ),
                'DATE_DDEEE' => array(
                    'name' => 'DATE_DDEE',
                    'start' => 139,
                    'length' => 8,
                    'type' => self::kTypeNum,
                    'required' => true
                ),
                'PRIORITE_CDE' => array(
                    'name' => 'PRIORITE_CDE',
                    'start' => 147,
                    'length' => 4,
                    'type' => self::kTypeNum
                ),
                'TRANSPORTEUR' => array(
                    'name' => 'TRANSPORTEUR',
                    'start' => 151,
                    'length' => 50,
                    'type' => self::kTypeAlpha
                ),
                'AFAC_INDIVIDU' => array(
                    'name' => 'AFAC_INDIVIDU',
                    'start' => 201,
                    'length' => 50,
                    'type' => self::kTypeAlpha
                ),
                'AFAC_RAISON_SOCIALE' => array(
                    'name' => 'AFAC_RAISON_SOCIALE',
                    'start' => 251,
                    'length' => 50,
                    'type' => self::kTypeAlpha
                ),
                'AFAC_ADRESSE1' => array(
                    'name' => 'AFAC_ADRESSE1',
                    'start' => 301,
                    'length' => 50,
                    'type' => self::kTypeAlpha
                ),
                'AFAC_ADRESSE2' => array(
                    'name' => 'AFAC_ADRESSE2',
                    'start' => 351,
                    'length' => 50,
                    'type' => self::kTypeAlpha
                ),
                'AFAC_ADESSE3' => array(
                    'name' => 'AFAC_ADRESSE3',
                    'start' => 401,
                    'length' => 50,
                    'type' => self::kTypeAlpha
                ),
                'AFAC_CP' => array(
                    'name' => 'AFAC_CP',
                    'start' => 451,
                    'length' => 8,
                    'type' => self::kTypeAlpha
                ),
                'AFAC_VILLE' => array(
                    'name' => 'AFAC_VILLE',
                    'start' => 459,
                    'length' => 50,
                    'type' => self::kTypeAlpha
                ),
                'AFAC_PAYS' => array(
                    'name' => 'AFAC_PAYS',
                    'start' => 509,
                    'length' => 50,
                    'type' => self::kTypeAlpha
                ),
                'AFAC_TEL' => array(
                    'name' => 'AFAC_TEL',
                    'start' => 559,
                    'length' => 50,
                    'type' => self::kTypeAlpha
                ),
                'AFAC_MAIL' => array(
                    'name' => 'AFAC_MAIL',
                    'start' => 609,
                    'length' => 50,
                    'type' => self::kTypeAlpha
                ),
                'ALIV_INDIVIDU' => array(
                    'name' => 'ALIV_INDIVIDU',
                    'start' => 659,
                    'length' => 50,
                    'type' => self::kTypeAlpha
                ),
                'ALIV_RAISON_SOCIALE' => array(
                    'name' => 'ALIV_RAISON_SOCIALE',
                    'start' => 709,
                    'length' => 50,
                    'type' => self::kTypeAlpha
                ),
                'ALIV_ADRESSE1' => array(
                    'name' => 'ALIV_ADRESSE1',
                    'start' => 759,
                    'length' => 50,
                    'type' => self::kTypeAlpha
                ),
                'ALIV_ADRESSE2' => array(
                    'name' => 'ALIV_ADRESSE2',
                    'start' => 809,
                    'length' => 50,
                    'type' => self::kTypeAlpha
                ),
                'ALIV_ADRESSE3' => array(
                    'name' => 'ALIV_ADRESSE3',
                    'start' => 859,
                    'length' => 50,
                    'type' => self::kTypeAlpha
                ),
                'ALIV_CP' => array(
                    'name' => 'ALIV_CP',
                    'start' => 909,
                    'length' => 8,
                    'type' => self::kTypeAlpha
                ),
                'ALIV_VILLE' => array(
                    'name' => 'ALIV_VILLE',
                    'start' => 917,
                    'length' => 50,
                    'type' => self::kTypeAlpha
                ),
                'ALIV_PAYS' => array(
                    'name' => 'ALIV_PAYS',
                    'start' => 967,
                    'length' => 50,
                    'type' => self::kTypeAlpha
                ),
                'ALIV_TEL' => array(
                    'name' => 'ALIV_TEL',
                    'start' => 1017,
                    'length' => 50,
                    'type' => self::kTypeAlpha
                ),
                'ALIV_MAIL' => array(
                    'name' => 'ALIV_TEL',
                    'start' => 1067,
                    'length' => 50,
                    'type' => self::kTypeAlpha
                ),
                'COMMT' => array(
                    'name' => 'COMMT',
                    'start' => 1117,
                    'length' => 400,
                    'type' => self::kTypeAlpha
                )

            );

        }

        return $this->_fieldsHead;

    }

    protected function getFieldsproduct(){

        if($this->_fieldsProduct === null){

            $this->_fieldsProduct = array(
                'OP_CODE' => array(
                    'name' => 'OP_CODE',
                    'start' => 1,
                    'length' => 10,
                    'type' => self::kTypeAlpha,
                    'required' => true,
                    'value' => "L01"
                ),
                'CLE_COMMANDE' => array(
                    'name' => 'CLE_COMMANDE',
                    'start' => 11,
                    'length' => 50,
                    'type' => self::kTypeAlpha,
                    'required' => true
                ),
                'NO_LIGNE' => array(
                    'name' => 'NO_LIGNE',
                    'start' => 61,
                    'length' => 4,
                    'type' => self::kTypeNum,
                    'required' => true
                ),
                'CODE_ART' => array(
                    'name' => 'CODE_ART',
                    'start' => 65,
                    'length' => 50,
                    'type' => self::kTypeAlpha,
                    'required' => true
                ),
                'QTE_CDE' => array(
                    'name' => 'QTE_CDE',
                    'start' => 115,
                    'length' => 8,
                    'type' => self::kTypeAlpha,
                    'required' => true
                ),
                'LIBELLE_ART' => array(
                    'name' => 'LIBELLE_ART',
                    'start' => 123,
                    'length' => 50,
                    'type' => self::kTypeAlpha,
                    'required' => false
                )
            );

        }

        return $this->_fieldsProduct;

    }
}
