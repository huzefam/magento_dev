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
class MDN_ExternalLogistic_Model_Source_Abstract extends Mage_Core_Model_Abstract
{
	private $_settingValues = null;
	
	/** 
	 * Set values
	 *
	 * @param unknown_type $data
	 */
	public function setSettingValues($data)
	{
		$this->_settingValues = $data;
	}

	/**
	 * return param value
	 *
	 * @param unknown_type $key
	 * @return unknown
	 */
	public function getSettingValue($key)
	{
		if ($this->_settingValues != null)
		{
			if (isset($this->_settingValues[$key]))
				return $this->_settingValues[$key];
		}
	}
	
	
	public function getFieldset()
	{
		
	}
	
	public function getCollection()
	{
		
	}
}