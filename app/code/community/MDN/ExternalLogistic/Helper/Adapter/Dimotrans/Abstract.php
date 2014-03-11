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
class MDN_ExternalLogistic_Helper_Adapter_Dimotrans_Abstract extends MDN_ExternalLogistic_Helper_Adapter_Abstract
{
	/**
     * Save xml file in working directory and upload file
     * @param <type> $streamCode
     * @param <type> $xml
     * @param <type> $remoteDirectory
     */
    public function saveAndUploadFile($streamCode, $xml, $remoteDirectory)
    {
        $workingDirectory = mage::helper('ExternalLogistic')->getWorkingDirectoryForCompany('Dimotrans').$streamCode.'/';
        mkdir($workingDirectory);
        
        //Save xml & zip
        $xmlPath = $workingDirectory.$streamCode.'.xml';
        $f = fopen($xmlPath, 'w');
        fwrite($f, $xml);
        fclose($f);

        //upload file
        $ftp = mage::helper('ExternalLogistic/Ftp');
        $ftp->setCredentials(
                mage::getStoreConfig('externallogistic/dimotrans/ftp_host'),
                mage::getStoreConfig('externallogistic/dimotrans/ftp_port'),
                mage::getStoreConfig('externallogistic/dimotrans/ftp_login'),
                mage::getStoreConfig('externallogistic/dimotrans/ftp_password')
                );
        $ftp->uploadFiles(array($xmlPath), $remoteDirectory, 'temp_');
    }
	
    /**
     * Format a date for L4logistic (YYYYMMDDHHMMSS)
     * @param <type> $date : magento date (string)
     */
    public function formatDate($date = null)
    {
        if ($date != null)
            $timeStamp = strtotime($date);
        else
            $timeStamp = time();
        return date('Ymd', $timeStamp);
    }
	
}