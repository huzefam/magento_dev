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
class MDN_ExternalLogistic_Helper_Companies extends Mage_Core_Helper_Abstract
{
    protected $_companies;
    protected $_activeCompanies;

    /**
     * Return all companies
     * @return <type>
     */
    public function getCompanies()
    {
        if (!$this->_companies) {
            $this->_companies = array(
            	array(
                    'value' => 'dropshipper',
                    'label' => 'DropShipper',
                ),
                array(
                    'value' => 'l4logistic',
                    'label' => 'L4Logistic',
                ),
                array(
                    'value' => 'dimotrans',
                    'label' => 'Dimotrans',
                ),
                array(
                    'label' => 'logsys',
                    'value' => 'Logsys',
                ),
                array(
                    'label' => 'supplyweb',
                    'value' => 'SupplyWeb'
                )
            );
        }
        return $this->_companies;
    }

    /**
     * Return active companies (set in system > configuration > external logistic)
     * @return <type>
     */
    public function getActiveCompanies()
    {
        if (!$this->_activeCompanies)
        {
            $this->_activeCompanies = array();

            foreach($this->getCompanies() as $company)
            {
                $xmlPath = strtolower('externallogistic/'.$company['value'].'/is_enabled');
                if (mage::getStoreConfig($xmlPath) == 1)
                {
                    $this->_activeCompanies[] = $company;
                }
            }
        }
        return $this->_activeCompanies;
    }
}