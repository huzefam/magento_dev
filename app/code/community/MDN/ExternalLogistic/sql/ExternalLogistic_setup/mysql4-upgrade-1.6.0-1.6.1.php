<?php

$installer = $this;

$installer->startSetup();



if(Mage::getStoreConfig('productreturn/general/is_installed') == 1){

        $installer->run("

            ALTER TABLE {$this->getTable('rma')} ADD `sent_to_logistic_company` TINYINT NOT NULL DEFAULT '0',
            ADD `external_logistic_run_id` INTEGER;
            
            ALTER TABLE {$this->getTable('rma_products')} ADD `sent_to_logistic_company` TINYINT NOT NULL DEFAULT '0',
            ADD `external_logistic_run_id` INTEGER;
            
        ");

}

$installer->endSetup();