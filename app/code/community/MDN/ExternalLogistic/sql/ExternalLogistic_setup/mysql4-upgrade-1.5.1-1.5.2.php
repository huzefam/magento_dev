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
	(`els_id` ,  `els_name` ,  `els_description` ,  `els_is_active` ,  `els_direction` ,  `els_code`, els_helper_class)
VALUES
	(NULL ,  'Integration confirmation',  'Confirm stream integration from server',  '1',  '1',  'integration_confirmation', 'IntegrationConfirmation');

");


$installer->endSetup();

