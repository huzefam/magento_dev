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

class MDN_ExternalLogistic_Block_Streams_Edit_Tab_CustomInformation
    extends Mage_Adminhtml_Block_Widget_Form
{
	
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('ExternalLogistic/Streams/Tab/CustomInformation.phtml');
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
	 * Return custom information collection
	 *
	 * @return unknown
	 */
	public function getCustomInformation()
	{
		return $this->getStream()->getCustomInformation();
	}
	
	public function getInputTypeAsCombo($name, $defaultValue)
	{
		$retour = '<select name="'.$name.'" id="'.$name.'">';
		$values = mage::getModel('ExternalLogistic/CustomInformation')->getInputTypes();
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
	
}
