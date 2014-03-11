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

ALTER TABLE  {$this->getTable('external_logistic_streams')}
ADD  `els_helper_class` VARCHAR( 255 ) NOT NULL;

update {$this->getTable('external_logistic_streams')}
set els_helper_class = 'Product'
where els_code = 'products';

update {$this->getTable('external_logistic_streams')}
set els_helper_class = 'PurchaseOrder'
where els_code = 'purchase_orders';

update {$this->getTable('external_logistic_streams')}
set els_helper_class = 'Shipment'
where els_code = 'shipments';

update {$this->getTable('external_logistic_streams')}
set els_helper_class = 'StockMovement'
where els_code = 'stock_movements';

update {$this->getTable('external_logistic_streams')}
set els_helper_class = 'StockStatus'
where els_code = 'stock_summary';

update {$this->getTable('external_logistic_streams')}
set els_helper_class = 'SalesOrder'
where els_code = 'sales_order';

");


					
$installer->endSetup();

