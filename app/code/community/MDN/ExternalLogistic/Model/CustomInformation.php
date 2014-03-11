<?php

class MDN_ExternalLogistic_Model_CustomInformation extends Mage_Core_Model_Abstract
{
	const kInputTypeText = 'text';
	const kInputTypeSelect = 'select';
	
	public function _construct()
	{
		parent::_construct();
		$this->_init('ExternalLogistic/CustomInformation');
	}


    /**
     * Return possible input types for a custom information
     *
     * @return unknown
     */
    public function getInputTypes()
    {
    	$retour = array();
    	$retour[self::kInputTypeSelect] = mage::helper('ExternalLogistic')->__(self::kInputTypeSelect);
    	$retour[self::kInputTypeText] = mage::helper('ExternalLogistic')->__(self::kInputTypeText);    	
    	return $retour;
    }
	
    /**
     * Render custom field
     *
     * @param unknown_type $name
     * @param unknown_type $value
     */
    public function renderHtml($name = null, $value)
    {
    	//init vars
    	$retour = '';
    	if ($name == null)
    		$name = 'custom['.$this->getelci_code().']';
    		
    		
    	switch ($this->getelci_input_type())
    	{
    		case self::kInputTypeSelect:
    			$collection = explode(';', $this->getelci_value());
    			$retour = '<select name="'.$name.'" id="'.$name.'">';
    			foreach ($collection as $item)
    			{
    				$t = explode(':', $item);
    				if (count($t) == 2)
    				{
    					$caption = $t[0];
    					$itemValue = $t[1];
    					$selected = '';
    					if ($itemValue == $value)
    						$selected = ' selected ';
    					$retour .= '<option value="'.$itemValue.'" '.$selected.'>'.$caption.'</option>';
    				}
    			}
    			$retour .= '</select>';
    			break;
    		case self::kInputTypeText:
    			$retour = '<input type="text" name="'.$name.'" id="'.$name.'" value="'.$value.'">';
    			break;
    	}
    	return $retour;
    }
    
    /**
	 * Return value for custom information
	 *
	 * @param unknown_type $customInformation
	 * @return unknown
	 */
	public function getCustomInformationValue($customInformation, $arraySerialized)
	{
		$code = $customInformation->getelci_code();
		$arrayUnserialized = mage::helper('ExternalLogistic/Serializer')->unserializeObject($arraySerialized);

		if (isset($arrayUnserialized[$code]))
			return $arrayUnserialized[$code];
		else 
		{
			return $customInformation->getelci_value();
		}
	}
}