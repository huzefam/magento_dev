<?php

$installer=$this;
$installer->startSetup();

$installer->run("

INSERT INTO  {$this->getTable('external_logistic_streams')}
	(`els_id` ,  `els_name` ,  `els_description` ,  `els_is_active` ,  `els_direction` ,  `els_code`, `els_helper_class`)
VALUES
	(NULL ,  'Rma product stock',  'Export rma product stock',  '1',  '0',  'rma_product_stock', 'RmaProductStock');

");


$installer->endSetup();