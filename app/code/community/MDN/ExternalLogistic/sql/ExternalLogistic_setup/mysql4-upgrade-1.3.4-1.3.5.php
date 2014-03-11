<?php
 
$installer = $this;
 
$installer->startSetup();

//define if magento version uses eav model for orders
$tableName = mage::getResourceModel('sales/order')->getTable('sales/order');
$useEavModel = ($tableName == 'sales_order');

if ($useEavModel)
{
	$installer->addAttribute('order', 'external_logistic_run_id', array('type'=>'static'));
	$installer->addAttribute('order', 'sent_to_logistic_company', array('type'=>'static'));
}

$installer->endSetup();
