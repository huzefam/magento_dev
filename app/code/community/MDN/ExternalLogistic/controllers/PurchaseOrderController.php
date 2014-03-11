<?php

class MDN_ExternalLogistic_PurchaseOrderController extends Mage_Adminhtml_Controller_Action
{
	public function SendOrderToLogisticAction()
	{
		$orderId = $this->getRequest()->getParam('order_id');
		
		try 
		{
			$order= mage::getModel('Purchase/Order')->getId();
			mage::helper('ExternalLogistic/PurchaseOrder')->sendOrdersToLogistic(array($orderId));
			Mage::getSingleton('adminhtml/session')->addSuccess('Order successfully sent to logistic company');
		}
		catch (Exception $ex)
		{
			Mage::getSingleton('adminhtml/session')->addError('An error occured : '.$ex->getMessage());			
		}
		
		$this->_redirect('Purchase/Orders/Edit', array('po_num' => $orderId));
	}

	/**
	 * Display sales order grid
	 *
	 */
	public function GridAction()
	{
		$this->loadLayout();
		$this->renderLayout();
	}

        public function ResetAction()
        {
            $poId = $this->getRequest()->getParam('po_id');
            $prefix = Mage::getConfig()->getTablePrefix();
            $sql = 'update '.$prefix.'purchase_order set external_logistic_run_id = NULL, sent_to_logistic_company = 0 where po_num = '.$poId.';';
            mage::getResourceModel('cataloginventory/stock_item_collection')->getConnection()->query($sql);

            //confirm & redirect
            Mage::getSingleton('adminhtml/session')->addSuccess('PO state reseted');
            $this->_redirect('adminhtml/system_config/edit', array('section' => 'externallogistic'));
        }
}
