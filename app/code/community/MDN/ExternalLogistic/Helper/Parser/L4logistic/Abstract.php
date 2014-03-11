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
class MDN_ExternalLogistic_Helper_Parser_L4logistic_Abstract extends Mage_Core_Helper_Abstract
{
    private $_streamCode = null;

    /**
     * return FTP object set to connect to L4Logistic FTP server
     * @return <type>
     */
    protected function getFtpObject()
    {
        //upload file
        $ftp = mage::helper('ExternalLogistic/Ftp');
        $ftp->setCredentials(
                mage::getStoreConfig('externallogistic/l4logistic/ftp_host'),
                mage::getStoreConfig('externallogistic/l4logistic/ftp_port'),
                mage::getStoreConfig('externallogistic/l4logistic/ftp_login'),
                mage::getStoreConfig('externallogistic/l4logistic/ftp_password')
                );
        return $ftp;
    }

    /**
     *
     * @return string Return working directory
     */
    protected function getWorkingDirectory($streamType)
    {
        $streamCode = $this->getStreamCode($streamType);
        $workingDirectory = mage::helper('ExternalLogistic')->getWorkingDirectoryForCompany('L4logistic').$streamCode.'/';
        if (!file_exists($workingDirectory))
            mkdir($workingDirectory);
        return $workingDirectory;
    }

    protected function getStreamCode($streamType = null)
    {
        if ($this->_streamCode == null)
        {
            $this->_streamCode = $streamType.date('YmdHis');
        }
        return $this->_streamCode;
    }
}