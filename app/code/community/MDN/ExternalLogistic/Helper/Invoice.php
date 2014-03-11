<?php
/* 
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

class MDN_ExternalLogistic_Helper_Invoice extends Mage_Core_Helper_Abstract {

    public function createInvoice($order){

        //on cree la facture
        $convertor = Mage::getModel('sales/convert_order');
        $invoice = $convertor->toInvoice($order);

        //parcourt les �l�ments de la commande
        foreach ($order->getAllItems() as $orderItem) {
            //ajout au invoice
            $InvoiceItem = $convertor->itemToInvoiceItem($orderItem);
            $InvoiceItem->setQty($orderItem->getqty_ordered());
            $invoice->addItem($InvoiceItem);
        }

        //sauvegarde la facture
        $invoice->collectTotals();
        $invoice->register();
        $invoice->getOrder()->setIsInProcess(true);
        $transactionSave = Mage::getModel('core/resource_transaction')
                        ->addObject($invoice)
                        ->addObject($invoice->getOrder())
                        ->save();

        $invoice->save();

        return $invoice;

    }


}
