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
abstract class MDN_ExternalLogistic_Helper_Adapter_SupplyWeb_Abstract extends MDN_ExternalLogistic_Helper_Adapter_Abstract
{

    const kTypeAlpha = 'ALPHA';
    const kTypeNum = 'NUM';
    const kCODE_SOC = "DIRECT CABLE";
    const kSuffix = "dat";

    /**
     * Get stream prefix
     */
    abstract protected function getStreamPrefix();

    abstract protected function getStreamSuffix();

    public function formatFileName($streamCode = null){
    
        if($streamCode === null)
            $streamCode = $this->getStreamCode();

        return $streamCode.'.'.$this->getStreamSuffix();

    }

    /**
     * Get remote directory
     */
    protected function getRemoteDirectory(){
        return 'in/';
    }

    protected function formatBalise($name){

        $tmp = explode(".", $name);

        return $tmp[0].'.bal';

    }

    protected function addSeparateur($field, $value){

        $value = iconv('UTF-8', 'ISO-8859-1', $value);
        $length = strlen($value);

        $debug = array(
            'current_length' => $length,
            'field_name' => $field['name'],
            'field_length' => $field['length']
        );

        if($length <= $field['length']){

            switch($field['type']){

                case self::kTypeAlpha:

                    for($i = $length; $i < $field['length']; $i++){

                        $value .= " ";

                    }

                    break;

                case self::kTypeNum:

                    for($i = $length; $i < $field['length']; $i++){

                        $value = "0".$value;

                    }

                    break;

                default:
                    break;
            }
        }else{
            $value = substr($value, 0, $field['length']);
        }

        return utf8_encode($value);

    }

    /**
     * Save csv file in working directory and upload file
     * @param string $streamCode
     * @param string $csv
     * @param string $remoteDirectory
     */
    protected function saveAndUploadFile($filename, $content, $remoteDirectory)
    {

        //$content = iconv('UTF-8', 'ISO-8859-1', $content);

        $tmp = explode(".", $filename);
        $workingDirectory = mage::helper('ExternalLogistic')->getWorkingDirectoryForCompany('SupplyWeb').$tmp[0].'/';
        mkdir($workingDirectory);
        
        //Save file
        $filePath = $workingDirectory.$filename;
        $f = fopen($filePath, 'w');
        fwrite($f, $content);
        fclose($f);

        // save balise
        $balisePath = $workingDirectory.$this->formatBalise($filename);
        $f = fopen($balisePath, 'w');
        fwrite($f, '');
        fclose($f);

        //upload file
        $ftp = mage::helper('ExternalLogistic/Ftp');
        $ftp->setCredentials(
                mage::getStoreConfig('externallogistic/supplyweb/ftp_host'),
                mage::getStoreConfig('externallogistic/supplyweb/ftp_port'),
                mage::getStoreConfig('externallogistic/supplyweb/ftp_login'),
                mage::getStoreConfig('externallogistic/supplyweb/ftp_password')
                );

        $ftp->uploadFiles(array($filePath, $balisePath), $remoteDirectory, null);
    }

    protected function checkTraitment(){

        $ftp = $this->getFtpObject();
        $workingDirectory = $this->getWorkingDirectory();

        $streams = $ftp->downloadFilesMatchingPattern("archives/", array("#/".$this->getStreamPrefix()."(.+)\.".self::kSuffix."#i"), $workingDirectory);

        foreach($streams as $stream){

            $elh_logistic_stream_code = $this->getStreamName($stream);//.'.'.self::kSuffix;

            if($elh_logistic_stream_code != ''){

                //load history
                $history = mage::getModel('ExternalLogistic/History')->load($elh_logistic_stream_code, 'elh_logistic_stream_code');

                //manage error
                if (!$history->getId())
                {
                    throw new exception('Unable to load '.$elh_logistic_stream_code.', ');
                }

                $history->confirm();

                $this->setStreamAsChecked($stream, $ftp);

            }

        }

        return 0;

    }
	
    protected function _checkRemoteDir(){

        $retour = true;

        $ftp = $this->getFtpObject();
        $workingDirectory = $this->getWorkingDirectory();

        $streams = $ftp->downloadFilesMatchingPattern("in/", array("#/".$this->getStreamPrefix()."(.+)\.".self::kSuffix."#i"), $workingDirectory);
        if(count($streams) > 0)
            $retour = false;

        return $retour;

    }

    protected function getStreamName($stream){

        $retour = '';

        if(array_key_exists('localpath', $stream)){

            $basename = basename($stream['localpath']);

            $stream_code = explode(".", $basename);

            $retour = $stream_code[0];

        }

        return $retour;

    }

    protected function setStreamAsChecked($stream, $ftp){

        $basename = basename($stream['remotepath']);
        $newRemotePath = dirname($stream['remotepath']).'/CHECKED_'.$basename;
        return $ftp->moveRemoteFile($stream['remotepath'], $newRemotePath);

    }

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
    protected function getWorkingDirectory()
    {
        $streamCode = $this->getStreamCode();
        $workingDirectory = mage::helper('ExternalLogistic')->getWorkingDirectoryForCompany('Supplyweb').$streamCode.'/';
        if (!file_exists($workingDirectory))
            mkdir($workingDirectory);
        return $workingDirectory;
    }

    protected function getStreamCode(){
        return $this->getStreamPrefix().date('ymdHis', Mage::getModel('core/date')->timestamp());
    }

}