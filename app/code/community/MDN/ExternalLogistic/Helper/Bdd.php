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
class MDN_ExternalLogistic_Helper_Bdd extends Mage_Core_Helper_Abstract
{

	//***************************************************************************************************************************************************
	//***************************************************************************************************************************************************
	// TOOLS
	//***************************************************************************************************************************************************
	//***************************************************************************************************************************************************

	public function getConnection()
	{
		//init vars
                $db = new Zend_Db_Adapter_Pdo_Mysql(array(
                    'host' => mage::getStoreConfig('externallogistic/bdd/host'),
                    'username' => mage::getStoreConfig('externallogistic/bdd/login'),
                    'password' => mage::getStoreConfig('externallogistic/bdd/password'),
                    'port' => mage::getStoreConfig('externallogistic/bdd/port'),
                    'dbname' => mage::getStoreConfig('externallogistic/bdd/dbname')
                ));

                $db->getConnection();
		if (!$db)
			throw new Exception('Unable to connect to Bdd server, please check bdd logins in system > configuration > External logistic');

		return $db;
	}


}