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
class MDN_ExternalLogistic_Helper_Parser_L4logistic_StockMovement extends MDN_ExternalLogistic_Helper_Parser_L4logistic_Abstract {

    public function process() {

        //download every EDI files
        $workingDirectory = $this->getWorkingDirectory('STKMVT');
        $ftp = $this->getFtpObject();
        $stkMvtFiles = $ftp->downloadFilesMatchingPattern('OUT/', array("/STKMVT/i"), $workingDirectory);
        $result = '';

        //unzip files
        $error = false;
        if (count($stkMvtFiles) > 0) {
            foreach ($stkMvtFiles as $stkMvtFile) {
                //unzip file
                $zipPath = $stkMvtFile['localpath'];
                $xmlPath = str_replace('.zip', '', $zipPath);
                mage::helper('ExternalLogistic/Zip')->unzip($zipPath, $workingDirectory);
                unlink($zipPath);

                try {
                    $result .= 'Process file ' . basename($xmlPath) . ' : ';
                    $data = $this->readStkMvtFile($xmlPath);
                    $result .= $this->processData($data);
                } catch (Exception $ex) {
                    $error = true;
                    $result .= $ex->getMessage();
                }
                $result .= ', ';

                //delete file on server
                $ftp->deleteRemoteFile($stkMvtFile['remotepath']);
            }
        } else {
            $result = 'No file to process';
        }
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
     * Store xml file information in $data array
     * @param <type> $xmlPath Process file
     */
    protected function readStkMvtFile($xmlPath) {
        $data = array();

        //read xml file
        $xmlDoc = new DOMDocument();
        $xmlDoc->load($xmlPath);
        $rootElement = $xmlDoc->getElementsByTagName("StockMouvements");

        $prefix = mage::getStoreConfig('externallogistic/l4logistic/prefix');

        //parse stockmovements
        foreach ($rootElement as $rootNode) {
            $mouvementNodes = $rootNode->getElementsByTagName("Mouvement");
            foreach ($mouvementNodes as $node) {
                $mvt = array();
                $mvt['sku'] = $node->getElementsByTagName("Reference")->item(0)->nodeValue;
                $mvt['sku'] = str_replace($prefix, '', $mvt['sku']);
                $mvt['qty'] = $node->getElementsByTagName("QuantiteMouvementee")->item(0)->nodeValue;
                $mvt['direction'] = $node->getElementsByTagName("SensMouvement")->item(0)->nodeValue;
                $codeMouvement = $node->getElementsByTagName("CodeMouvement")->item(0)->nodeValue;
                $motif = $node->getElementsByTagName("Motif")->item(0)->nodeValue;
                $mvt['description'] = $this->getMouvementDescription($codeMouvement, $motif);


                $data[] = $mvt;
            }
        }

        return $data;
    }

    /**
     * Create stock movement in DB from $data
     */
    protected function processData($data) {
        $i = 0;
        $error = false;
        $description = '';
        foreach ($data as $mvt) {
            try {
                mage::helper('ExternalLogistic/StockMovement')
                        ->createStockMovement($mvt['sku'],
                                $mvt['qty'],
                                ($mvt['direction'] == '+'),
                                MDN_ExternalLogistic_Helper_StockMovement::kSourceTypeUndefined,
                                null,
                                $mvt['description']
                );
                $i++;
            } catch (Exception $ex) {
                $error = true;
                $description .= $ex->getMessage() . ', ';
            }
        }

        if (!$error)
            return $i . ' stock movement processed';
        else
            throw new Exception($description . $i . ' stock movement processed');
    }

    /**
     * return description from codemouvement & motif
     * @param <type> $codeMouvement
     * @param <type> $motif
     */
    protected function getMouvementDescription($codeMouvement, $motif) {
        $description = '';


        switch ($codeMouvement) {
            case '10':
                $description .= 'Mvt de stockage : ';
                break;
            case '20':
                $description .= 'Mvt de destockage : ';
                break;
            case '80':
                $description .= 'Mvt immobilisation : ';
                break;
            default:
                $description .= 'Code mvt inconnu : ';
                break;
        }

        switch ($motif) {
            case 'CAN':
                $description .= 'Reintegration de commande annulee';
                break;
            case 'DEM':
                $description .= 'Stockage suite demenagement ';
                break;
            case 'ESI':
                $description .= 'Erreur de saisie informatique';
                break;
            case 'IGC':
                $description .= 'Inventaire Generale Comptable ';
                break;
            case 'INV':
                $description .= 'Inventaire tournant, interne';
                break;
            case 'PAL':
                $description .= 'Stockage de palettes aux palettier pour des palettes non homogenes ';
                break;
            case 'REC':
                $description .= 'Reception forcee ';
                break;
            case 'RET':
                $description .= 'Reintegration de marchandise service retour si pas d’ANAPRO';
                break;
            case 'SH1':
                $description .= 'Remise en stock suite a une demande client ';
                break;
            case 'CAS':
                $description .= 'Casse du produit par L4 / Article defectueux';
                break;
            case 'CIN':
                $description .= 'Destockage dun produit pour servir une commande incomplete';
                break;
            case 'DCL':
                $description .= 'Destockage suite a la demande du client';
                break;
            case 'DES':
                $description .= 'Destruction du produit, jamais encore utilise';
                break;
            case 'SH2':
                $description .= 'Sortie de stock suite a une demande client ';
                break;
            case 'SSD':
                $description .= 'Destockage de palettes aux palettier pour des palettes non homogenes ';
                break;
            case 'ATT':
                $description .= 'Destockage de palettes aux palettier pour des palettes non homogenes ';
                break;
            case 'BQ':
                $description .= 'En attente decision client';
                break;
            default:
                $description .= 'Motif inconnu';
                break;
        }

        return $description;
    }

}