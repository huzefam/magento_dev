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

CREATE TABLE  {$this->getTable('external_logistic_history')}
(
 `elh_id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
 `elh_date` DATETIME NOT NULL ,
 `elh_description` VARCHAR( 255 ) NOT NULL ,
 `elh_result` TEXT NOT NULL ,
  INDEX (  `elh_date` )
);

");

																																											
$installer->endSetup();

