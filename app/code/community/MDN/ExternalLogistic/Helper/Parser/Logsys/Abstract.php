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
abstract class MDN_ExternalLogistic_Helper_Parser_Logsys_Abstract extends Mage_Core_Helper_Abstract
{

    const kArchive = 'archives';
    const kEchec = 'echec';

    abstract protected function getStreamCode();

    abstract protected function getStreamPrefix();

    abstract protected function getRemoteDirectory();

    abstract protected function getFileHeader();

    /**
     * return FTP object set to connect to Logsys FTP server
     * @return <type>
     */
    protected function getFtpObject()
    {
        //upload file
        $ftp = mage::helper('ExternalLogistic/Ftp');
        $ftp->setCredentials(
                mage::getStoreConfig('externallogistic/logsys/ftp_host'),
                mage::getStoreConfig('externallogistic/logsys/ftp_port'),
                mage::getStoreConfig('externallogistic/logsys/ftp_login'),
                mage::getStoreConfig('externallogistic/logsys/ftp_password')
                );
        return $ftp;
    }

    /**
     * Get working directory
     *
     * @param string $streamType
     * @return string $workingDirectory
     */
    protected function getWorkingDirectory($streamType)
    {
        $streamCode = $this->getStreamCode($streamType);
        $workingDirectory = mage::helper('ExternalLogistic')->getWorkingDirectoryForCompany('Logsys').$streamCode.'/';
        if (!file_exists($workingDirectory))
            mkdir($workingDirectory);
        return $workingDirectory;
    }

    protected function checkFile($stream){
        return true;
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
        // 1 => stock / exp
        // 2 => filename
        if(count($tmp) != 3){
            $retour =  'Unknow path : '.$stream['remotepath'];
        }else{
            $newRemoteFilename  = $tmp[0].'/'.$tmp[1].'/'.self::kArchive.'/'.$tmp[2];
            $retour = $ftp->moveRemoteFile($currentRemoteFilename, $newRemoteFilename);
        }
        
        return $retour;

    }

    /**
     * Move file into echec folder
     *
     * @param array $streams
     * @param MDN_ExternalLogistic_Helper_Ftp $ftp
     * @return string
     */
    protected function echec($stream, $ftp){

        $retour = '';
        $currentRemoteFilename = $stream['remotepath'];
        $tmp = explode("/",$currentRemoteFilename);

        //check if all indexes exists
        // 0 => out
        // 1 => stock / exp
        // 2 => filename
        if(count($tmp) != 3){
            $retour = 'Unknow path : '.$stream['remotepath'];
        }else{
            $newRemoteFilename  = $tmp[0].'/'.$tmp[1].'/'.self::kEchec.'/'.$tmp[2];
            $retour = $ftp->moveRemoteFile($currentRemoteFilename, $newRemoteFilename);
        }

        return $retour;

    }

    protected function cleanValues($tab){

        foreach($tab as $k => $v){
            $tab[$k] = trim($v, "\" \t\n\r");
        }

        return $tab;

    }

}