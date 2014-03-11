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

CREATE TABLE  {$this->getTable('external_logistic_files')} (
 `elf_id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
 `elf_els_id` INT NOT NULL ,
 `elf_filename` VARCHAR( 255 ) NOT NULL ,
INDEX (  `elf_els_id` )
);

CREATE TABLE  {$this->getTable('external_logistic_fields')} (
 `elfd_id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
 `elfd_elf_id` INT NOT NULL ,
 `elfd_name` VARCHAR( 255 ) NOT NULL ,
INDEX (  `elfd_id` )
);

");

																																											
$installer->endSetup();

