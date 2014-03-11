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
abstract class MDN_ExternalLogistic_Helper_Adapter_Logsys_Abstract extends MDN_ExternalLogistic_Helper_Adapter_Abstract {
    const kArchive = 'ARCHIVES';
    const kEchec = 'ECHEC';

    /**
     * Get remote directory
     */
    abstract protected function getRemoteDirectory();

    /**
     * Get stream prefix
     */
    abstract protected function getStreamPrefix();

    /**
     * Save csv file in working directory and upload file
     * @param string $streamCode
     * @param string $csv
     * @param string $remoteDirectory
     */
    protected function saveAndUploadFile($streamCode, $content, $remoteDirectory) {
        $workingDirectory = mage::helper('ExternalLogistic')->getWorkingDirectoryForCompany('Logsys') . $streamCode . '/';
        if (!is_dir($workingDirectory))
            mkdir($workingDirectory);

        //Save file
        $filePath = $workingDirectory . $streamCode;
        $filePath = (preg_match('#.pdf$#i', $filePath)) ? $filePath : $filePath . '.txt';
        $f = fopen($filePath, 'w');
        fwrite($f, $content);
        fclose($f);

        //upload file
        $ftp = mage::helper('ExternalLogistic/Ftp');
        $ftp->setCredentials(
                mage::getStoreConfig('externallogistic/logsys/ftp_host'),
                mage::getStoreConfig('externallogistic/logsys/ftp_port'),
                mage::getStoreConfig('externallogistic/logsys/ftp_login'),
                mage::getStoreConfig('externallogistic/logsys/ftp_password')
        );
        $ftp->uploadFiles(array($filePath), $remoteDirectory, null);
    }

    /**
     * return FTP object set to connect to Logsys FTP server
     * @return <type>
     */
    protected function getFtpObject() {
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
     *
     * @param string $ID_FICHIER
     * <ul>
     * <li>CAT : Referentiel Magento</li>
     * <li>CMDF : Commandes fournisseur</li>
     * <li>CMD : commandes a expedier</li>
     * </ul>
     * @return string <ID_FICHIER>_<YYYYMMDDHHMMSS>
     */
    public function formatFileName($ID_FICHIER) {

        return $ID_FICHIER . '_' . date('YmdHis', Mage::getModel('core/date')->timestamp());
    }

    /**
     * Format text (replace " by "")
     *
     * @param string $txt
     * @return string
     */
    public function formatContent($txt) {

        return str_replace('"', '""', $txt);
    }

    /**
     * Get working directory
     *
     * @param string $streamType
     * @return string $workingDirectory
     */
    protected function getWorkingDirectory() {
        $streamCode = $this->getStreamCode();
        $workingDirectory = mage::helper('ExternalLogistic')->getWorkingDirectoryForCompany('Logsys') . $streamCode . '/';
        if (!file_exists($workingDirectory))
            mkdir($workingDirectory);
        return $workingDirectory;
    }

    protected function getStreamCode() {
        return $this->getStreamPrefix() . '_' . date('YmdHis', Mage::getModel('core/date')->timestamp());
    }

    /**
     * Check files in ARCHIVE and ECHEC directories
     * <ul>
     * <li>ARCHIVE : confirm history</li>
     * <li>ECHEC : set history as error</li>
     * </ul>
     */
    protected function checkTraitment() {

        $this->checkDirectories(
                array(
                    self::kArchive,
                    self::kEchec
                )
        );

        return 0;
    }

    protected function checkDirectories($dir) {

        $ftp = $this->getFtpObject();
        $workingDirectory = $this->getWorkingDirectory();

        foreach ($dir as $currentDir) {

            if (!in_array($currentDir, array(self::kArchive, self::kEchec)))
                continue;

            //download txt files for current directory (chess or success)
            $streams = $ftp->downloadFilesMatchingPattern($this->getRemoteDirectory() . $currentDir, array("#/" . $this->getStreamPrefix() . "_(.+)\.txt$#i"), $workingDirectory);

            foreach ($streams as $stream) {

                $elh_logistic_stream_code = $this->getStreamName($stream);

                if ($elh_logistic_stream_code != '') {

                    //load history
                    $history = mage::getModel('ExternalLogistic/History')->load($elh_logistic_stream_code, 'elh_logistic_stream_code');

                    //manage error
                    if (!$history->getId()) {
                        throw new exception('Unable to load history ' . $elh_logistic_stream_code . ', ');
                    }

                    // update status
                    switch ($currentDir) {
                        case self::kArchive:
                            $history->confirm();
                            break;

                        case self::kEchec:
                            $history->setelh_has_error(1)->save();
                            break;
                    }

                    $this->setStreamAsChecked($stream, $ftp);
                }
            }
        }

        return 0;
    }

    protected function getStreamName($stream) {

        $retour = '';

        if (array_key_exists('localpath', $stream)) {

            $basename = basename($stream['localpath']);

            $stream_code = explode(".", $basename);

            $retour = $stream_code[0];
        }

        return $retour;
    }

    protected function setStreamAsChecked($stream, $ftp) {

        $basename = basename($stream['remotepath']);
        $newRemotePath = dirname($stream['remotepath']) . '/CHECKED_' . $basename;
        return $ftp->moveRemoteFile($stream['remotepath'], $newRemotePath);
    }

}