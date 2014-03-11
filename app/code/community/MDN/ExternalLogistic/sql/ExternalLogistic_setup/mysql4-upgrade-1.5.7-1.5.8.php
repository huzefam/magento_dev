<?php
/* 
 * Magento
 * 
 * NOTICE OF LICENSE
 * 
 * This source file is subject to the Open Software License (OSL 3.0)
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * 
 * @copyright  Copyright (c) 2009 Maison du Logiciel (http://www.maisondulogiciel.com)
 * @author : Nicolas MUGNIER
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

$installer = $this;

$installer->startSetup();

$installer->run("

        ALTER TABLE {$this->getTable('ExternalLogistic/Streams')} ADD els_schedule_values VARCHAR(255) NOT NULL;
        ALTER TABLE {$this->getTable('ExternalLogistic/Streams')} DROP els_schedule_hours;
        ALTER TABLE {$this->getTable('ExternalLogistic/Streams')} DROP els_schedule_days;

        ");

$installer->endSetup();
