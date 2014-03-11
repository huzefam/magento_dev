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
 * @author : Nicolas MUGNIER
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
abstract class MDN_ExternalLogistic_Helper_Parser_SupplyWeb_Abstract extends Mage_Core_Helper_Abstract
{

    const kTypeAlpha = 'ALPHA';
    const kTypeNum = 'NUM';

    const kSuffixFile = "dat";
    const kSuffixBalise = "BAL";

    const kArchive = "archives";

    /**
     * return FTP object set to connect to Logsys FTP server
     * @return <type>
     */
    protected function getFtpObject()
    {
        //upload file
        $ftp = mage::helper('ExternalLogistic/Ftp');
        $ftp->setCredentials(
                mage::getStoreConfig('externallogistic/supplyweb/ftp_host'),
                mage::getStoreConfig('externallogistic/supplyweb/ftp_port'),
                mage::getStoreConfig('externallogistic/supplyweb/ftp_login'),
                mage::getStoreConfig('externallogistic/supplyweb/ftp_password')
                );
        return $ftp;
    }

    /**
     * Get working directory
     *
     * @param string $streamType
     * @return string $workingDirectory
     */
    protected function getWorkingDirectory($streamCode = null)
    {
        if ($streamCode == null)
            $streamCode = $this->getStreamCode();
        $workingDirectory = mage::helper('ExternalLogistic')->getWorkingDirectoryForCompany('SupplyWeb').$streamCode.'/';
        if (!file_exists($workingDirectory))
            mkdir($workingDirectory);
        return $workingDirectory;
    }

    protected function checkBalise($stream){

        $filename = $stream['localpath'];
        $tmp = explode(".", $filename);
        $filename = $tmp[0].'.'.self::kSuffixBalise;

        return file_exists($filename);

    }

    protected function getValue($field, $type){

        $retour = '';

        switch($type){

            case self::kTypeAlpha:
                $retour = $this->findAlphaValue($field);
                break;

            case self::kTypeNum:
                $retour = $this->findNumValue($field);
                break;

            default:
                break;

        }

        return $retour;

    }

    protected function findAlphaValue($field){

        $retour = '';

        $retour = rtrim($field);

        return $retour;

    }

    protected function findNumValue($field){

        $retour = '';

        $retour = ltrim($field, '0');

        return $retour;

    }

    protected function getStreamCode(){
        return $this->getMnemonique().date('ymdHis', Mage::getModel('core/date')->timestamp());
    }

    protected function getMnemonique(){
        return 'undefinied';
    }

    /**
     * Move file into archive folder
     *
     * @param array $stream
     * @param MDN_ExternalLogistic_Helper_Ftp $ftp
     * @return string
     */
    protected function archive($stream, $ftp){

        $retour = '';
        $currentRemoteFilename = $stream['remotepath'];
        $tmp = explode("/",$currentRemoteFilename);

        //check if all indexes exists
        // 0 => out
        // 1 => filename
        if(count($tmp) != 2){
            $retour =  'Unknow path : '.$stream['remotepath'];
        }else{
            $newRemoteFilename  = self::kArchive.'/CHECKED_OUT_'.$tmp[1];
            $retour = $ftp->moveRemoteFile($currentRemoteFilename, $newRemoteFilename);
        }

        $balise = str_replace(".".self::kSuffixFile, ".".self::kSuffixBalise, $stream['remotepath']);
        $ftp->deleteRemoteFile($balise);

        return $retour;

    }


}