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
class MDN_ExternalLogistic_Helper_Adapter_Dimotrans_Product extends MDN_ExternalLogistic_Helper_Adapter_Dimotrans_Abstract
{
    public function process()
    {
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
		
		$streamCode = 'ARTICLE_'.date('YmdHis');
		if (count($productIds) > 0)
		{
		
			//generate xml
			$xml = $this->getProductXml($productIds);
			
			//process file
			$this->saveAndUploadFile($streamCode, $xml, 'Send/');

		}
		
        //return result
        $data = array(
            'error' => false,
            'entity_ids' => implode(',', $productIds),
            'result' => 'Stream code '.$streamCode.', '.count($productIds).' products sent',
            'logistic_stream_code' => $streamCode,
			'auto_confirm' => true
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

        //root element
        $xmlWriter->push('WINDEV_TABLE');

        //parse products
        foreach($productIds as $productId)
        {
            $product = mage::getModel('catalog/product')->load($productId);

            $xmlWriter->push('Table');

            $xmlWriter->element('ACT_CODE', mage::getStoreConfig('externallogistic/dimotrans/activity'));
            $xmlWriter->element('ART_CODE', $product->getSku());			
            $xmlWriter->element('ART_DESL', $product->getName());
            $xmlWriter->element('ART_DESC', $product->getName());			
			
			$xmlWriter->pop();
		}
		
        //end root element
        $xmlWriter->pop();

        return $xmlWriter->getXml();
		
	}
	
	
}