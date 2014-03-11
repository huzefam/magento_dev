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
class MDN_ExternalLogistic_Helper_Parameters extends Mage_Core_Helper_Abstract
{
    public function getParamValue($company, $paramName)
    {
        $path = 'externallogistic/'.$company.'/'.$paramName;
        return mage::getStoreConfig($path);
    }

    public function setParamValue($company, $paramName, $value)
    {
        $path = 'externallogistic/'.$company.'/'.$paramName;
        $obj = new Mage_Core_Model_Config();
        $obj ->saveConfig($path, $value);
    }
}