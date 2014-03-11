<?php
 
$installer = $this;
 
$installer->startSetup();

$installer->addAttribute('catalog_product',
                         'external_logistic_run_id',
                         array(
                                'visible'           => false,
                                'required'          => false,
                                'user_defined'      => false,
                                'default'           => '',
                                'searchable'        => false,
                                'filterable'        => false,
                                'comparable'        => false,
                                'visible_on_front'  => false,
                                'unique'            => false,
                                'type'              =>'static',
                                'is_configurable'   => false,
                                'visible'           => false,
                                'visible_on_front'  => false,
                                'required'          => false,
                                'input'             => 'hidden',
                                'user_defined'      => false)
                        );

$installer->addAttribute('catalog_product', 
                         'sent_to_logistic_company',
                         array(
                                'visible'           => false,
                                'required'          => false,
                                'user_defined'      => false,
                                'default'           => '',
                                'searchable'        => false,
                                'filterable'        => false,
                                'comparable'        => false,
                                'visible_on_front'  => false,
                                'unique'            => false,
                                'type'              =>'static',
                                'is_configurable'   => false,
                                'visible'           => false,
                                'visible_on_front'  => false,
                                'required'          => false,
                                'input'             => 'hidden',
                                'user_defined'      => false)
                        );

$installer->run("

ALTER TABLE  {$this->getTable('catalog_product_entity')} ADD  `external_logistic_run_id` INT;
ALTER TABLE  {$this->getTable('catalog_product_entity')} ADD  `sent_to_logistic_company` TINYINT NOT NULL DEFAULT  '0';

");

$installer->endSetup();
