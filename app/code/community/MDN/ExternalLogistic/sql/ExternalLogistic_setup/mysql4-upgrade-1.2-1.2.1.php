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
$installer=$this;
$installer->startSetup();

$installer->run("

INSERT INTO  {$this->getTable('external_logistic_streams')} 
	(`els_id` ,  `els_name` ,  `els_description` ,  `els_is_active` ,  `els_direction` ,  `els_code`) 
VALUES 
	(NULL ,  'Products',  'Export products',  '1',  '0',  'products'),
	(NULL ,  'Purchase Orders',  'Export expected deliveries',  '1',  '0',  'purchase_orders'),
	(NULL ,  'Shipments',  'Imports shipments',  '1',  '1',  'shipments'),
	(NULL ,  'Stock movements',  'Imports stock movements',  '1',  '1',  'stock_movements'),
	(NULL ,  'Stock summary',  'Imports stock summary for check and adjustment purposes',  '1',  '1',  'stock_summary'),
	(NULL ,  'Sales Order',  'Export sales order to process',  '1',  '0',  'sales_order');

");

																																											
$installer->endSetup();

