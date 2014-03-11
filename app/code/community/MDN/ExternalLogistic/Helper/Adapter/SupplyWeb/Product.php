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
class MDN_ExternalLogistic_Helper_Adapter_SupplyWeb_Product extends MDN_ExternalLogistic_Helper_Adapter_SupplyWeb_Abstract
{

    const kMnemonique = "ART01";

    private $_fields = null;

    private $_debug = '';
    
    /**
     * Process
     *
     * @return array $data
     */
    public function process()
    {

        $this->checkTraitment();
        $streamCode = $this->getStreamCode();
        $filename = $this->formatFilename($streamCode);
        $productIds = array();

        if($this->_checkRemoteDir() === true){

            //collect products
            $max = mage::getStoreConfig('externallogistic/misc/max');
            $select = mage::getResourceModel('catalog/product')
                            ->getReadConnection()
                            ->select()
                            ->from(mage::getResourceModel('catalog/product')->getTable('catalog/product'))
                            ->order('entity_id ASC')
                            //->where('sent_to_logistic_company <> 1')
                            ->where("type_id = 'simple'")
                            ->limit($max);

            $productIds = mage::getResourceModel('catalog/product')->getReadConnection()->fetchCol($select);

            if(count($productIds) > 0){
                //generate csv
                $csv = $this->getProductCsv($productIds);


                //process file
                $this->saveAndUploadFile($filename, $csv, $this->getRemoteDirectory());
            }
        }

        //return result
        $data = array(
            'error' => false,
            'entity_ids' => implode(',', $productIds),
            'result' => 'Stream code '.$streamCode.', '.count($productIds).' products sent',
            'logistic_stream_code' => $streamCode
        );

        return $data;
    }

    public function getProductCsv($ids){

        $retour = '';

        foreach($ids as $id){

            $_product = Mage::getModel('catalog/product')->load($id);

            foreach($this->getFields() as $field){

                $retour .= $this->formatField($field, $_product);

            }

            $retour .= "\n";

        }

        return $retour;
    }

    protected function getStreamPrefix(){
        return self::kMnemonique;
    }

    protected function getStreamSuffix(){
        return self::kSuffix;
    }

    protected function formatField($field, $_product){

        $retour = '';

        switch($field['name']){

            case 'OP_CODE':

                $retour = $this->addSeparateur($field, "ART01");

                break;
            case 'CODE_SOC':

                $retour = $this->addSeparateur($field, self::kCODE_SOC);

                break;
            case 'CODE_ARTICLE':

                $retour = $this->addSeparateur($field, $_product->getsku());

                break;
            case 'LIBELLE_ARTICLE':

                $retour = $this->addSeparateur($field, $_product->getname());

                break;
            case 'POIDS_UVC':
                $retour = $this->addSeparateur($field, ((int)($_product->getweight() * 100)));  //poids en grammes

                break;

            default:

                $retour = $this->addSeparateur($field, "");

                break;

        }

        return $retour;

    }

    protected function getFields(){

        if($this->_fields === null){

            $this->_fields = array(
                'OP_CODE' => array(
                    'name' => 'OP_CODE',
                    'start' => 1,
                    'length' => 10,
                    'type' => self::kTypeAlpha
                ),
                'CODE_SOC' => array(
                    'name' => 'CODE_SOC',
                    'start' => 11,
                    'length' => 20,
                    'type' => self::kTypeAlpha
                ),
                'CODE_ARTICLE' => array(
                    'name' => 'CODE_ARTICLE',
                    'start' => 31,
                    'length' => 50,
                    'type' => self::kTypeAlpha
                ),
                'LIBELLE_ARTICLE' => array(
                    'name' => 'LIBELLE_ARTICLE',
                    'start' => 81,
                    'length' => 80,
                    'type' => self::kTypeAlpha
                ),
                'QTE_UVC' => array(
                    'name' => 'QTE_UVC',
                    'start' => 161,
                    'length' => 10,
                    'type' => self::kTypeNum
                ),
                'EAN_UVC' => array(
                    'name' => 'EAN_UVC',
                    'start' => 171,
                    'length' => 14,
                    'type' => self::kTypeNum
                ),
                'POIDS_UVC' => array(
                    'name' => 'POIDS_UVC',
                    'start' => 185,
                    'length' => 10,
                    'type' => self::kTypeNum
                ),
                'VOLUME_UVC' => array(
                    'name' => 'VOLUME_UVC',
                    'start' => 195,
                    'length' => 10,
                    'type' => self::kTypeNum
                ),
                'EAN_COLIS' => array(
                    'name' => 'EAN_COLIS',
                    'start' => 205,
                    'length' => 14,
                    'type' => self::kTypeNum
                ),
                'QTE_COLIS' => array(
                    'name' => 'QTE_COLIS',
                    'start' => 219,
                    'length' => 10,
                    'type' => self::kTypeNum
                ),
                'POIDS_COLIS' => array(
                    'name' => 'POIDS_COLIS',
                    'start' => 229,
                    'length' => 10,
                    'type' => self::kTypeNum
                ),
                'VOLUME_COLIS' => array(
                    'name' => 'VOLUME_COLIS',
                    'start' => 239,
                    'length' => 10,
                    'type' => self::kTypeNum
                ),
                'EAN_PALETTE' => array(
                    'name' => 'EAN_PALETTE',
                    'start' => 249,
                    'length' => 14,
                    'type' => self::kTypeNum
                ),
                'QTE_PALETTE' => array(
                    'name' => 'QTE_PALETTE',
                    'start' => 263,
                    'length' => 10,
                    'type' => self::kTypeNum
                ),
                'POIDS_PALETTE' => array(
                    'name' => 'POIDS_PALETTE',
                    'start' => 273,
                    'length' => 10,
                    'type' => self::kTypeNum
                ),
                'VOLUME_PALETTE' => array(
                    'name' => 'VOLUME_PALETTE',
                    'start' => 283,
                    'length' => 10,
                    'type' => self::kTypeNum
                )
            );
        }

        return $this->_fields;

    }

}
