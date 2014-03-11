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
class MDN_ExternalLogistic_Helper_Adapter_Dropshipper_SalesOrder extends MDN_ExternalLogistic_Helper_Adapter_Dropshipper_Abstract
{
    public function process()
    {
        //collect pending orders from order preparation
        $collection = mage::getModel('sales/order')
                        ->getCollection()
                        ->addFieldToFilter('state', 'processing')
						->addFieldToFilter('default_fullfiler', 'Lisse');

        $supplierId = mage::getStoreConfig('externallogistic/dropshipper/supplier');
        $supplier = mage::getModel('Purchase/Supplier')->load($supplierId);

        $email = mage::getStoreConfig('externallogistic/dropshipper/send_order_to_email');
        $emailCC = mage::getStoreConfig('externallogistic/dropshipper/send_order_to_email_cc');
        $identity = Mage::getStoreConfig('externallogistic/dropshipper/email_identity');
        $emailTemplate = Mage::getStoreConfig('externallogistic/dropshipper/email_template');
        if ($emailTemplate == '')
            throw new Exception('Email template for dropshipper is not set !');

        $orderIds = array();

        foreach($collection as $order)
        {
			try
			{
				//create shipment
				if ($order->canShip())
				{
					$shipment = mage::helper('ExternalLogistic/Shipment')->createShipment($order->getincrement_id(),
																			  date('Y-m-d'),
																			  $this->getProductsArray($order),
																			  null);
				}
				
				//get mondial relay shipping label
				$shipmentId = $this->getShipmentId($order);
				$pdf = null;				
				$pdf = mage::helper('pointsrelais/Label')->getPdfLabelForShipment($shipmentId);

				//create custom PDF with order information
				$pdfObject = mage::getModel('ExternalLogistic/Pdf_OrderForLogisticCompany');
				$pdfObject->pdf = $pdf;
				$pdfObject->setSupplier($supplier);
				$pdf = $pdfObject->getPdf(array($order));

				
				//##### Send email
				//set attachlment
				$Attachment = null;
				$Attachment = array();
				$Attachment['name'] = 'commande_'.$order->getincrement_id().'.pdf';
				$Attachment['content'] = $pdf->render();
				$Attachments = array();
				$Attachments[] = $Attachment;

				//send email
				$translate = Mage::getSingleton('core/translate');
				$translate->setTranslateInline(false);
				Mage::getModel('core/email_template')
					->setDesignConfig(array('area'=>'adminhtml'))
					->sendTransactional(
						$emailTemplate,
						$identity,
						$email,
						'',
						array(),
						null,
						$Attachments);
				$translate->setTranslateInline(true);
				
				//send email to CC
				$translate = Mage::getSingleton('core/translate');
				$translate->setTranslateInline(false);
				Mage::getModel('core/email_template')
					->setDesignConfig(array('area'=>'adminhtml'))
					->sendTransactional(
						$emailTemplate,
						$identity,
						$emailCC,
						'',
						array(),
						null,
						$Attachments);
				$translate->setTranslateInline(true);

				//add comments
				mage::helper('ExternalLogistic/SalesOrder')->addComment($order, 'Commande transmise a lisse');
				
				
			}
			catch(Exception $ex)
			{
				$msg = 'Error processing order #'.$order->getincrement_id().' : '.$ex->getMessage();
				throw new Exception($msg);
			}
		
            $orderIds[] = $order->getId();

        }

        //return result
        $data = array(
            'error' => false,
            'entity_ids' => implode(',', $orderIds),
            'result' => count($orderIds).' orders sent',
            'logistic_stream_code' => null
        );
        return $data;

    }

    /**
     * Return shipment id for order (return first)
     * @param <type> $order
     * @return <type>
     */
    protected function getShipmentId($order)
    {
        foreach($order->getShipmentsCollection() as $shipment)
        {
            return $shipment->getId();
        }

    }

    /**
     * return sales order products list as array
     * @param <type> $order
     * @return <type>
     */
    protected function getProductsArray($order)
    {
        $temp = array();
        foreach($order->getAllItems() as $item)
        {
            if (!isset($temp[$item->getsku()]))
                    $temp[$item->getsku()] = 0;
            $temp[$item->getsku()] += $item->getqty_ordered();
        }

        //convert table
        $products = array();
        foreach($temp as $sku => $qty)
        {
            $products[] = array('sku' => $sku, 'qty' => $qty);
        }

        return $products;
    }
}
