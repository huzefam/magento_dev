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
class MDN_ExternalLogistic_Helper_Parser_Rotawheelsuk_DownloadStockLevel extends MDN_ExternalLogistic_Helper_Parser_Rotawheelsuk_Abstract {

    public function process() {

        // init
        $productUpdated = 0;
        $productNotFind = 0;

        // load config
        $warehouseId = Mage::getStoreConfig('externallogistic/rotawheelsuk/warehouse');
        $supplierId = Mage::getStoreConfig('externallogistic/rotawheelsuk/supplier');
        $url = Mage::getStoreConfig('externallogistic/rotawheelsuk/url');
        $pattern_line = Mage::getStoreConfig('externallogistic/rotawheelsuk/pattern_line');
        $pattern_sku = Mage::getStoreConfig('externallogistic/rotawheelsuk/pattern_product_supplier_id');
        $pattern_stock = Mage::getStoreConfig('externallogistic/rotawheelsuk/pattern_product_supplier_stock');

        // 1 connect on http://stock.rotawheelsuk.com/index.php
        $client = new Zend_Http_Client();
        $client->setUri($url . '/index.php');

        $client->setConfig(array(
            'maxredirects' => 0,
            'timeout' => 30)
        );

        $client->setHeaders(array(
            'Content-Type' => 'application/x-www-form-urlencoded',
            'User-Agent' => 'MDN Tool/1.0 (Language=PHP/' . phpversion() . '; Platform=Linux/Gentoo)',
        ));

        $client->setParameterPost(array(
            'password' => Mage::getStoreConfig('externallogistic/rotawheelsuk/password'),
            'username' => Mage::getStoreConfig('externallogistic/rotawheelsuk/login'),
        ));

        $response = $client->request('POST');

        $headers = $response->getheaders();
        $tmp = explode(";", $headers['Set-cookie']);
        $values = explode('=', $tmp[0]);

        // 2 get feed http://stock.rotawheelsuk.com/feed.php
        $client->setUri($url . '/feed.php');
        $client->setParameterPost(array());
        $client->setParameterGet(array(
            'PHPSESSID' => $values[1]
        ));

        $response = $client->request('GET');

        $feed = $response->getBody();

        // 3 parse it, use regex
        $pattern = '#' . $pattern_line . '#'; //"[0-9]+":\{("[a-zA-Z_-]+":[/\a-zA-Z0-9_\. \[\]"-]+[,]?){1,}\}
        preg_match_all($pattern, $feed, $matches);

        // 4 update stock
        $products = array();
        foreach ($matches[0] as $line) {

            preg_match('#' . $pattern_sku . '#', $line, $match_sku); //"wheel_id":"[a-zA-Z0-9_-]+"

            preg_match('#' . $pattern_stock . '#', $line, $match_stock); //"stock":"[0-9]+"

            $tmp = explode(":", $match_sku[0]);
            $sku = preg_replace('#"#', '', $tmp[1]);
            $tmp = explode(":", $match_stock[0]);
            $stock = preg_replace('#"#', '', $tmp[1]);

            $products[] = array(
                'sku' => $sku,
                'stock' => $stock
            );
        }

        $nbr_products = count($products);

        foreach ($products as $v) {

            $productId = Mage::getModel('catalog/product')->getIdBySku($v['sku']);

            if (!$productId) {
                // try to by product supplier assoc
                $productId = Mage::getModel('Purchase/productSupplier')->getCollection()
                                ->addFieldToFilter('pps_supplier_num', $supplierId)
                                ->addFieldToFilter('pps_reference', $v['sku'])
                                ->getFirstItem()
                                ->getpps_product_id();
            }

            if ($productId) {

                // update product stock
                $stockItem = mage::getModel('cataloginventory/stock_item')->loadByProductWarehouse($productId, $warehouseId);

                if ($stockItem == null) {

                    //if no stock item, create a stock movement
                    mage::getModel('AdvancedStock/StockMovement')->createStockMovement($productId, null, $warehouseId, $v['stock'], 'Init drop shipper stock');
                } else {

                    //if stock item exists, update qty

                    if($stockItem->getqty() != $v['stock']){
                        $stockItem->setqty($v['stock'])->save();

                        mage::helper('BackgroundTask')->AddTask('Update product availability status for product #' . $productId . ' (stock information changed)',
                                'SalesOrderPlanning/ProductAvailabilityStatus',
                                'RefreshForOneProduct',
                                $productId,
                                null,
                                true
                        );
                    }
                }

                //update information between product & supplier (buy price)
                $productSupplier = mage::getModel('Purchase/ProductSupplier')->loadForProductSupplier($productId, $supplierId);

                if ($productSupplier !== null) {

                    $productSupplier->setpps_quantity_product($v['stock'])
                            ->save();
                }

                $productUpdated++;
            } else {

                $productNotFind++;
            }
        }

        //return result
        $data = array(
            'error' => false,
            'entity_ids' => '',
            'result' => $productUpdated . ' products updated, ' . $productNotFind . ' products not find, ' . $productSupplierUpdated . '(' . $nbr_products . ' products)',
            'logistic_stream_code' => ''
        );
        return $data;
    }

}