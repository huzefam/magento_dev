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

CREATE TABLE  {$this->getTable('external_logistic_custom_information')}
(
 `elci_id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
 `elci_code` VARCHAR( 50 ) NOT NULL ,
 `elci_name` VARCHAR( 50 ) NOT NULL ,
 `elci_input_type` VARCHAR( 50 ) NOT NULL ,
 `elci_value` VARCHAR( 255 ) NOT NULL ,
 `elci_els_id` INT NOT NULL ,
INDEX (  `elci_els_id` )
) ENGINE = MYISAM ;

");

																																											
$installer->endSetup();

