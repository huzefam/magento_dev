<?php
/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magentocommerce.com for more information.
 *
 * @category    Mage
 * @package     Mage_Adminhtml
 * @copyright   Copyright (c) 2009 Irubin Consulting Inc. DBA Varien (http://www.varien.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class MDN_ExternalLogistic_Block_Streams_Edit_Tab_Information
    extends Mage_Adminhtml_Block_Widget_Form
{
	
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('ExternalLogistic/Streams/Tab/Information.phtml');
    }

    /**
     * Enter description here...
     *
     * @return unknown
     */
	public function getStream()
	{
		$stream = mage::registry('current_stream');
		return $stream;
	}
	
	/**
	 * Get directions combo box
	 *
	 * @param unknown_type $name
	 * @param unknown_type $defaultValue
	 * @return unknown
	 */
	public function getDirectionsAsCombo($name, $defaultValue = '')
	{
		$retour = '<select name="'.$name.'" id="'.$name.'" disabled="disabled">';
		$values = $this->getStream()->getDirections();
		foreach ($values as $key => $value)
		{
			$selected = '';
			if ($key == $defaultValue)
				$selected = ' selected ';
			$retour .= '<option value="'.$key.'" '.$selected.'>'.$value.'</option>';
		}
		
		$retour .= '</select>';
		return $retour;
	}
	
	/**
	 * Get directions combo box
	 *
	 * @param unknown_type $name
	 * @param unknown_type $defaultValue
	 * @return unknown
	 */
	public function getActiveAsCombo($name, $defaultValue = '')
	{
		$retour = '<select name="'.$name.'" id="'.$name.'">';
		$values = array();
		$values[0] = mage::helper('ExternalLogistic')->__('Not active');
		$values[1] = mage::helper('ExternalLogistic')->__('Active');
		foreach ($values as $key => $value)
		{
			$selected = '';
			if ($key == $defaultValue)
				$selected = ' selected ';
			$retour .= '<option value="'.$key.'" '.$selected.'>'.$value.'</option>';
		}
		
		$retour .= '</select>';
		return $retour;
	}
	
	/**
	 * Return companies as combo
	 *
	 * @param unknown_type $defaultValue
	 */
	public function getCompanyAsCombo($name, $defaultValue = '')
	{
		$retour = '<select name="'.$name.'" id="'.$name.'">';
		$values = mage::getSingleton('ExternalLogistic/System_Config_Source_LogisticCompanies')->getAllOptions();
		foreach ($values as $item)
		{
			$selected = '';
			if ($item['value'] == $defaultValue)
				$selected = ' selected ';
			$retour .= '<option value="'.$item['value'].'" '.$selected.'>'.$item['label'].'</option>';
		}
		
		$retour .= '</select>';
		return $retour;
		
	}
	
	/**
	 * Return Zip as combo
	 *
	 * @param unknown_type $name
	 * @param unknown_type $value
	 */
	public function getZipAsCombo($name, $defaultValue = '')
	{
		$html = '<select name="'.$name.'" id="'.$name.'">';
		$sources = array('0' => $this->__('No'), '1' => $this->__('Yes'));
		foreach ($sources as $key => $value)
		{
			$selected = '';
			if ($key == $defaultValue)
				$selected = ' selected ';
			$html .= '<option value="'.$key.'" '.$selected.'>'.$value.'</option>';
		}
		$html .= '</select>';
		return $html;
	}

	/**
	 * Return default stream selector
	 *
	 */
	public function getIsDefaultStreamForAsCombo($name, $defaultValue)
	{
		$html = '<select name="'.$name.'" id="'.$name.'">';
		$html .= '<option value="" ></option>';
		$collection = mage::helper('ExternalLogistic/Streams')->getDefaultStreams();
		foreach ($collection as $key => $value)
		{
			$selected = '';
			if ($key == $defaultValue)
				$selected = ' selected ';
			$html .= '<option value="'.$key.'" '.$selected.'>'.$value.'</option>';
		}
		$html .= '</select>';
		return $html;	
	}
}
