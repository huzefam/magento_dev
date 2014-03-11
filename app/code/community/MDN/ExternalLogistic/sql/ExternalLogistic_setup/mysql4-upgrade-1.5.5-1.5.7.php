<?php

$installer=$this;
$installer->startSetup();

$installer->run("

INSERT INTO  {$this->getTable('external_logistic_streams')}
	(`els_id` ,  `els_name` ,  `els_description` ,  `els_is_active` ,  `els_direction` ,  `els_code`, `els_helper_class`)
VALUES
	(NULL ,  'Rma accept',  'Export rma',  '1',  '0',  'rma_accepted', 'RmaAccept'),
	(NULL ,  'Rma event',  'Update rma',  '1',  '1',  'rma_event', 'RmaEvent');

");


$installer->endSetup();