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
class MDN_ExternalLogistic_Helper_Parser_Agilio_StockStatus extends MDN_ExternalLogistic_Helper_Parser_Agilio_Abstract {

    /**
     * Compare magento stock levels with logistic company stock levels
     *
     * @param unknown_type $runDirectory is the working directory (optional) to store send files or to store logs
     *
     * @return : log information
     */
    public function process($runDirectory) {
        $result = '';

        //Init report
        mage::helper('BackgroundTask')->AddTask('Init stock error report',
                'ExternalLogistic/StockStatus',
                'initReport',
                null
        );


        // bdd connection
        $bdd = mage::helper('ExternalLogistic/Bdd')->getConnection();

        $queryproductstock = $bdd->select(array('stock', 'c_ref'))
                        ->from(array('stock' => 'vp_stock_' . mage::getStoreConfig('externallogistic/bdd/login')))
                        ->join(array('article' => 'vp_article_' . mage::getStoreConfig('externallogistic/bdd/login')),
                                'stock.is_article = article.is_article');

        $productstock = $bdd->query($queryproductstock);

        while ($row = $productstock->fetch()) {

            $sku = $row['c_ref'];
            $qty = $row['stock'];
            $result .= 'Traitement produit '.$sku;
            mage::helper('BackgroundTask')->AddTask('Check stocks for product with sku = ' . $sku . ' and qty = ' . $qty,
                    'ExternalLogistic/StockStatus',
                    'checkProductQty',
                    array('sku' => $sku, 'qty' => $qty)
            );
        }
        //transmit report
        mage::helper('BackgroundTask')->AddTask('Transmit stock report',
                'ExternalLogistic/StockStatus',
                'transmitReport',
                null
        );

        return $result;
    }

}