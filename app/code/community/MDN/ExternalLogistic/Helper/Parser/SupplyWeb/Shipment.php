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
class MDN_ExternalLogistic_Helper_Parser_SupplyWeb_Shipment extends MDN_ExternalLogistic_Helper_Parser_SupplyWeb_Abstract {
    const kMnemonique = "CRE01";
    const kExpPath = "out/";

    const kOP_CODE = "OP_CODE";
    const kCODE_SOC = "CODE_SOC";
    const kNO_CLIENT = "NO_CLIENT";
    const kN_CDE = "N_CDE";
    const kNO_COLIS = "NO_COLIS";
    const kNO_TRACKING = "NO_TRACKING";
    const kNO_EXPEDITION = "NO_EXPEDITION";
    const kDATE_EXPED = "DATE_EXPED";

    protected $_shipments = array();
    protected $_cre_tab = array();
    protected $_crp_tab = array();
    protected $_ftp = null;

    private $_debug = '';

    /**
     * Import sales order shipments confirmation
     * @return boolean
     */
    public function process() {

        $result = '';

        //download every CRE01 and CRP01 files
        $streamCode = $this->getStreamCode();
        $workingDirectory = $this->getWorkingDirectory($streamCode);
        $this->_ftp = $this->getFtpObject();
        $shipFiles = $this->_ftp->downloadFilesMatchingPattern(self::kExpPath,
                        array("/" . self::kMnemonique . "/i", "/CRP01/i"),
                        $workingDirectory);
        $error = false;

        sort($shipFiles);

        $this->_debug .= count($shipFiles).' files, ';

        if (count($shipFiles) > 0) {
            foreach ($shipFiles as $stream) {

                try {

                    $tmp = explode("/", $stream['localpath']);

                    if (preg_match('#^CRE01(.+)\.dat$#i', $tmp[count($tmp) - 1])) {
                        $this->_debug .= 'process file '.$tmp[count($tmp) - 1].', ';
                        if ($this->checkBalise($stream)) {

                            $this->parseStreamCRE($stream);
                        } else {
                            $result .= 'Balise for file ' . $stream['remotepath'] . ' isn\'t available, ';
                        }
                    }

                    if (preg_match('#^CRP01(.+)\.dat$#i', $tmp[count($tmp) - 1])) {
                        $this->_debug .= 'process file '.$tmp[count($tmp) - 1].', ';
                        if ($this->checkBalise($stream)) {


                            $this->parseStreamCRP($stream);
                        } else {

                            $result .= 'Balise for file ' . $stream['remotepath'] . ' isn\'t available, ';
                        }
                    }
                } catch (Exception $e) {
                    $error = true;
                    $result .= $e->getMessage() . ', ';
                }
            }


            try {
                $result .= $this->createShipment($workingDirectory);
            } catch (Exception $ex) {
                $error = true;
                $result .= $ex->getMessage();
            }
        } else {
            $result .= 'No file to process';
        }

        //return result
        $data = array(
            'error' => $error,
            'entity_ids' => '',
            'result' => $result,
            'logistic_stream_code' => $streamCode
        );
        return $data;
    }

    /**
     * Create shipments from _shipments information (filled from parseStreamCRE & parseStreamCRP functions)
     * @return string
     */
    protected function createShipment($workingDirectory) {

        $error = false;
        $result = '';

        //save _shipment variable content in debug file
        $filePath = $workingDirectory.'debug.txt';
        $debugContent = $this->varDumpToString($this->_shipments);
        file_put_contents($filePath, $debugContent);

        foreach ($this->_shipments as $order_id => $shipment) {

            try {

                if ($shipment['shipment_date'] == '')
                    $shipment['shipment_date'] = date('Y-m-d');

                Mage::Helper('ExternalLogistic/Shipment')->createShipment(
                        $order_id,
                        $shipment['shipment_date'],
                        $shipment['products'],
                        $shipment['tracking']
                );

                $result .= "Order #" . $order_id . " shipped, \n";

                $this->archiveStreams($order_id);
            } catch (Exception $e) {

                $error = true;
                $result .= 'Order #' . $order_id . ' : ' . $e->getMessage() . ", \n";
            }
        }

        if (!$error) {
            return $result;
        } else {
            throw new Exception($result);
        }
    }

    /**
     * Put files in archive directory on server
     * @param <type> $order_id
     * @return <type>
     */
    protected function archiveStreams($order_id) {

        if (array_key_exists($order_id, $this->_cre_tab)) {
            foreach ($this->_cre_tab[$order_id] as $stream) {

                $this->archive($stream, $this->_ftp);
            }
        }

        if (array_key_exists($order_id, $this->_crp_tab)) {
            foreach ($this->_crp_tab[$order_id] as $stream) {

                $this->archive($stream, $this->_ftp);
            }
        }

        return 0;
    }

    /**
     * Import shipments general information (date, tracking ..)
     * @param <type> $stream
     * @return <type>
     */
    protected function parseStreamCRE($stream) {

        $lines = file($stream['localpath']);
        $order_ids = array();

        foreach ($lines as $line) {

            $order_id = $this->getValue(substr($line, 38, 50), self::kTypeAlpha);

            if (!in_array($order_id, $order_ids))
                $order_ids[] = $order_id;

            if (!array_key_exists($order_id, $this->_shipments)) {

                $this->_shipments[$order_id] = array(
                    'products' => array(),
                    'tracking' => $this->getValue(substr($line, 138, 50), self::kTypeAlpha),
                    'colis' => $this->getValue(substr($line, 88, 50), self::kTypeAlpha),
                    'shipment_date' => $this->getValue(substr($line, 238, 8), self::kTypeNum)
                );
            } else {

                $this->_shipments[$order_id]['tracking'] = $this->getValue(substr($line, 138, 50), self::kTypeAlpha);
                $this->_shipments[$order_id]['colis'] = $this->getValue(substr($line, 88, 50), self::kTypeAlpha);
                $this->_shipments[$order_id]['shipment_date'] = $this->getValue(substr($line, 238, 8), self::kTypeNum);
            }
        }

        foreach ($order_ids as $id) {
            $this->_cre_tab[$id][] = $stream;
        }

        return 0;
    }

    /**
     * Import shipments products and quantities
     * @param <type> $stream
     * @return <type>
     */
    protected function parseStreamCRP($stream) {

        $lines = file($stream['localpath']);
        $order_ids = array();

        foreach ($lines as $line) {

            $order_id = $this->getValue(substr($line, 30, 50), self::kTypeAlpha);

            if (!in_array($order_id, $order_ids))
                $order_ids[] = $order_id;

            if (!array_key_exists($order_id, $this->_shipments)) {

                $this->_shipments[$order_id] = array(
                    'tracking' => '',
                    'colis' => '',
                    'shipment_date' => '',
                    'products' => array()
                );
            }

            $this->_shipments[$order_id]['products'][] = array(
                'sku' => $this->getValue(substr($line, 150, 50), self::kTypeAlpha),
                'qty' => (int)$this->getValue(substr($line, 200, 10), self::kTypeAlpha)
            );
        }

        foreach ($order_ids as $id) {
            $this->_crp_tab[$id][] = $stream;
        }

        return 0;
    }

    protected function getMnemonique() {
        return self::kMnemonique;
    }

    /**
     * Return variable content as string
     * @param <type> $var
     */
    protected function varDumpToString($var) {
        ob_start();
        var_dump($var);
        $result = ob_get_clean();

        //echo '<pre>';
        //var_dump($this->_shipments);
        //die();


        return $result;
    }

}