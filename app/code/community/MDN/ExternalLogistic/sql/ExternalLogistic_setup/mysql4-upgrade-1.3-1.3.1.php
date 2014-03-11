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

CREATE TABLE  {$this->getTable('external_logistic_sources')} (
 `elsrc_id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
 `elsrc_code` VARCHAR( 255 ) NOT NULL ,
 `elsrc_name` VARCHAR( 255 ) NOT NULL ,
 elsrc_settings TEXT,
INDEX (  `elsrc_id` )
);

insert into {$this->getTable('external_logistic_sources')}
(elsrc_name, elsrc_code)
values
('Sales order', 'sales_order'),
('Products', 'products'),
('Purchase order', 'purchase_order');

");

																																											
$installer->endSetup();

