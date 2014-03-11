<?php

$installer = $this;

$installer->startSetup();

$installer->addAttribute('order', 'external_logistic_info', array(
    'type' => 'text',
    'visible' => false,
    'label' => 'External Logistic Custom Information',
    'required' => false,
    'global' => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_GLOBAL,
    'frontend' => '',
    'input' => '',
    'class' => '',
    'source' => '',
    'user_defined' => false,
    'searchable' => false,
    'filterable' => false,
    'comparable' => false,
    'visible_on_front' => false,
    'is_configurable' => false,
    'unique' => false));


$installer->endSetup();
