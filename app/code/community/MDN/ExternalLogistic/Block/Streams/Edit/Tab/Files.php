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

class MDN_ExternalLogistic_Block_Streams_Edit_Tab_Files
    extends Mage_Adminhtml_Block_Widget_Form
{
	
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('ExternalLogistic/Streams/Tab/Files.phtml');
    }

    /**
     * Enter description here...
     *
     * @return unknown
     */
	public function getStream()
	{
		return mage::registry('current_stream');
	}
	
	/**
	 * 
	 *
	 */
	public function getAddNewFileUrl()
	{
		return $this->getUrl('ExternalLogistic/Streams/AddNewFile', array('els_id' => $this->getStream()->getId()));
	}
	
	/**
	 * Return sources as combo
	 *
	 * @param unknown_type $name
	 * @param unknown_type $value
	 */
	public function getSourcesAsCombo($name, $defaultValue = '')
	{
		$html = '<select name="'.$name.'" id="'.$name.'">';
		$html .= '<option></option>';
		$sources = mage::helper('ExternalLogistic/Source')->getSources();
		foreach ($sources as $key => $value)
		{
			$selected = '';
			if ($value == $defaultValue)
				$selected = ' selected ';
			$html .= '<option value="'.$key.'" '.$selected.'>'.$value.'</option>';
		}
		$html .= '</select>';
		return $html;
	}



}
