<?php

class MDN_ExternalLogistic_SalesOrderController extends Mage_Adminhtml_Controller_Action
{
	/**
	 * Send one order to logistic
	 *
	 */
	public function SendOrderToLogisticAction()
	{
		//retrieve datas
		$orderId = $this->getRequest()->getPost('order_id');
		$order = mage::getModel('sales/order')->load($orderId);
		$customInformation = $this->getRequest()->getPost('custom');
		if (is_array($customInformation))
		{
			//save custom information in sales order
			$customInformationSerialized = mage::helper('ExternalLogistic/Serializer')->serializeObject($customInformation);
			$order->setexternal_logistic_info($customInformationSerialized)->save();
		}
		
		try 
		{
			$result = mage::helper('ExternalLogistic/SalesOrder')->sendOrdersToLogistic(array($orderId));
                        if($result->getelh_has_error()){
                            Mage::getSingleton('adminhtml/session')->addError('An error occured : '.$result->getelh_result());
                        }else {
                            Mage::getSingleton('adminhtml/session')->addSuccess('Order successfully sent to logistic company');
                        }
		}
		catch (Exception $ex)
		{
			Mage::getSingleton('adminhtml/session')->addError('An error occured : '.$ex->getMessage());			
		}

		$this->_redirect('adminhtml/sales_order/view', array('order_id' => $orderId));
	}
	
	/**
	 * Send several orders to logistic
	 *
	 */
	public function MassSendOrderToLogisticAction()
	{
		try 
		{
			$orderIds = $this->getRequest()->getPost('order_ids');		
			mage::helper('ExternalLogistic/SalesOrder')->sendOrdersToLogistic($orderIds);
			Mage::getSingleton('adminhtml/session')->addSuccess('Order(s) successfully sent to logistic company');
		}
		catch (Exception $ex)
		{
			Mage::getSingleton('adminhtml/session')->addError('An error occured : '.$ex->getMessage());			
		}
		
		$this->_redirect('adminhtml/sales_order/index');
		
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
	
	/**
	 * SEnd several order to logistic (and save custom informaiton values before)
	 */
	public function MassSendOrderToLogisticWithCustomInformationAction()
	{

		try 
		{
			//save custom information values
			$checkboxes = $this->getRequest()->getPost('checkbox');		
			if (count($checkboxes) == 0)
				throw new Exception($this->__('Please select at least one order'));
			
			$custom = $this->getRequest()->getPost('custom');		
			$orderIds = array();
			foreach ($checkboxes as $orderId => $value)
			{
				$orderIds[] = $orderId;	
				$order = mage::getModel('sales/order')->load($orderId);
				$customInformationSerialized = mage::helper('ExternalLogistic/Serializer')->serializeObject($custom[$orderId]);
				$order->setexternal_logistic_info($customInformationSerialized)->save();
			}
			
			//send orders to logistic
			mage::helper('ExternalLogistic/SalesOrder')->sendOrdersToLogistic($orderIds);
			Mage::getSingleton('adminhtml/session')->addSuccess('Order(s) successfully sent to logistic company');
		}
		catch (Exception $ex)
		{
			Mage::getSingleton('adminhtml/session')->addError('An error occured : '.$ex->getMessage());			
		}
		
		$this->_redirect('ExternalLogistic/SalesOrder/grid');
		
		
	}
}
