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
class MDN_ExternalLogistic_Helper_Adapter_L4logistic_Abstract extends MDN_ExternalLogistic_Helper_Adapter_Abstract
{
    /**
     * Clean string and fit to max size
     * @param <type> $string
     * @param <type> $maxLength
     * @return <type>
     */
    protected function cleanStringValue($string, $maxLength)
    {
        if (strlen($string) > $maxLength)
        {
            $string = substr($string, 0, $maxLength);
        }

        
        //$string = utf8_decode($string);
        $string = str_replace('"', ' ', $string);
        $string = str_replace('\'', ' ', $string);
        //$string = str_replace('&', ' ', $string);

        return $string;
    }

    /**
     * Format a date for L4logistic (YYYYMMDDHHMMSS)
     * @param <type> $date : magento date (string)
     */
    protected function formatDate($date = null)
    {
        if ($date != null)
            $timeStamp = strtotime($date);
        else
            $timeStamp = time();
        return date('Y-m-d\TH:i:s', $timeStamp);
    }

    /**
     * Save xml file in working directory and upload file
     * @param <type> $streamCode
     * @param <type> $xml
     * @param <type> $remoteDirectory
     */
    protected function saveAndUploadFile($streamCode, $xml, $remoteDirectory)
    {
        $workingDirectory = mage::helper('ExternalLogistic')->getWorkingDirectoryForCompany('L4logistic').$streamCode.'/';
        mkdir($workingDirectory);
        
        //Save xml & zip
        $xmlPath = $workingDirectory.$streamCode.'.xml';
        $tempName = 'temp_'.$streamCode.'.xml.gz';
        $zipPath = $workingDirectory.$streamCode.'.xml.zip';
        $f = fopen($xmlPath, 'w');
        fwrite($f, $xml);
        fclose($f);
        mage::helper('ExternalLogistic/Zip')->zip($xmlPath, $zipPath);

        //upload file
        $ftp = mage::helper('ExternalLogistic/Ftp');
        $ftp->setCredentials(
                mage::getStoreConfig('externallogistic/l4logistic/ftp_host'),
                mage::getStoreConfig('externallogistic/l4logistic/ftp_port'),
                mage::getStoreConfig('externallogistic/l4logistic/ftp_login'),
                mage::getStoreConfig('externallogistic/l4logistic/ftp_password')
                );
        $ftp->uploadFiles(array($zipPath), $remoteDirectory, 'temp_');
    }
}