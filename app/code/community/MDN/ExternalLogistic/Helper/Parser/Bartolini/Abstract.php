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
class MDN_ExternalLogistic_Helper_Parser_Bartolini_Abstract extends Mage_Core_Helper_Abstract
{
	/**
	 * Retrieve file content
	 *
	 * @param unknown_type $directory
	 * @return unknown
	 */
	protected function getFileContent($directory, $fileNamePattern)
	{
		//find filename
		$fileName = '';
		$handle = opendir($directory);
		//$pattern = '*XX2STKP...............DAT*';
		while (false !== ($file = readdir($handle))) 
		{
			if (preg_match($fileNamePattern, $file))
	        	$fileName = $file;
	    }
	    
	    //if file name set
	    $retour = '';
	    if ($fileName != '')
	    {
	    	$filePath = $directory.$fileName;
	    	$fileContent = file($filePath);
	    	return $fileContent;
	    }
	    
	    return null;
	}
}