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
class MDN_ExternalLogistic_Helper_Parser_L4logistic_IntegrationConfirmation extends MDN_ExternalLogistic_Helper_Parser_L4logistic_Abstract
{
    public function process()
    {
        //download every EDI files
        $workingDirectory = $this->getWorkingDirectory('RPTEDI');
        $ftp = $this->getFtpObject();
        $rptEdiFiles = $ftp->downloadFilesMatchingPattern('OUT/', array("/RPTEDI_/i"), $workingDirectory);
        $result = '';

        
        //unzip files
        $error = false;
        if (count($rptEdiFiles) > 0)
        {
            foreach($rptEdiFiles as $rpt)
            {
                //unzip file
                $zipPath = $rpt['localpath'];
                $xmlPath = str_replace('.zip', '', $zipPath);
                mage::helper('ExternalLogistic/Zip')->unzip($zipPath, $workingDirectory);
                unlink($zipPath);

                //process file
                $rptData = $this->readRptEditFile($xmlPath);
                $result .= $rptData['stream_code'].'='.($rptData['has_error'] ? 'ERROR' : 'OK')." ";
                if ($rptData['has_error'])
                {
                    $error = true;
                    $result .= $rptData['msg'];
                }
                else
                {
                    //confirm entity for report
                    try
                    {
                        $this->confirmHistory($rptData['stream_type'], $rptData['stream_code']);
                    }
                    catch (Exception $ex)
                    {
                        $result .= $ex->getMessage().', ';
                        $error = true;
                    }
                }

                //delete file on server
                $ftp->deleteRemoteFile($rpt['remotepath']);

                $result .= "\n";
            }
        }
        else
            $result = 'No file to process';
        
        //return result
        $data = array(
            'error' => $error,
            'entity_ids' => '',
            'result' => $result,
            'logistic_stream_code' => $this->getStreamCode()
        );

        return $data;
    }

    /**
     * Read a report
     * @param <type> $filePath
     * @return boolean
     */
    protected function readRptEditFile($filePath)
    {
        
        //read xml file
        $xmlDoc = new DOMDocument();
        $xmlDoc->load($filePath);
        $rootElement = $xmlDoc->getElementsByTagName("RapportTraitement");

        //fill data
        $data = array();
        foreach($rootElement as $node)
        {
            $data['stream_type'] = $node->getElementsByTagName("TypeMessage")->item(0)->nodeValue;
            $data['stream_code'] = substr(basename($filePath), 13);
            $data['stream_code'] = str_replace('.xml', '', $data['stream_code']);
            $data['msg'] = '';

            $listingNode = $node->getElementsByTagName("listing");
            if ($listingNode->item(0))
            {
                    $data['has_error'] = true;
                    $data['msg'] = '';
                    $messageNodes = $listingNode->item(0)->getElementsByTagName("message");
                    $hasMessageNode = false;
                    foreach($messageNodes as $messageNode)
                    {
                        $data['msg'] .= $messageNode->nodeValue.', ';
                        $hasMessageNode = true;
                    }
                    if (!$hasMessageNode)
                        $data['has_error'] = false;

            }
            else
                $data['has_error'] = false;
        }

        return $data;
    }

    /**
     * Confirm history
     * @param <type> $streamCode
     */
    protected function confirmHistory($streamType, $streamCode)
    {
        //load history
        $history = mage::getModel('ExternalLogistic/History')->load($streamType.$streamCode, 'elh_logistic_stream_code');

        //manage error
        if (!$history->getId())
        {
            throw new exception('Unable to load '.$streamType.$streamCode.', ');
        }

        //mark history as confirmed
        $history->confirm();
        
    }

    
}