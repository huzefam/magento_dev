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
class MDN_ExternalLogistic_Helper_Zip extends Mage_Core_Helper_Abstract
{
	/**
	 * Zip files
	 *
	 * @param unknown_type $files
	 * @param unknown_type $targetFileName
	 * @param unknown_type $deleteFiles
	 */
	public function zip($files, $targetFileName, $deleteFiles = false)
	{
            if (mage::getStoreConfig('externallogistic/misc/use_shell_to_zip') != 1)
            {
		//create archive
		$zip = new ZipArchive();
		$zip->open($targetFileName, ZIPARCHIVE::CREATE);
                if (!is_array($files))
                    $files = array($files);
		foreach ($files as $file)
		{
			if (file_exists($file))
				$zip->addFile($file, basename($file));
		}
		$zip->close();
		
		//delete files if required
		if ($deleteFiles)
		{
			foreach ($files as $file)
			{
				if (file_exists($file))
					unlink($file);
			}
		}
            }
            else
            {
                $shellCmd = 'gzip '.$files;
                shell_exec($shellCmd);
            }
	}
	
	/**
	 * Unzip to a folder
	 *
	 * @param unknown_type $file
	 * @param unknown_type $targetPath
	 */
	public function unzip($file, $targetPath)
	{
		$zip = new ZipArchive;
		if (file_exists($file))
		{
			$zip->open($file);
			$zip->extractTo($targetPath);
		    $zip->close();
		}
	}
	
}