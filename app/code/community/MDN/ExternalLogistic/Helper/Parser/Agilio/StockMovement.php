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
class MDN_ExternalLogistic_Helper_Parser_Agilio_StockMovement extends MDN_ExternalLogistic_Helper_Parser_Agilio_Abstract
{
	
	/**
	 * Process stock movements from logistic company to keep magento stock up to date
	 * Use mage::helper('ExternalLogistic/StockMovement')->createStockMovement to create stock movement
	 *
	 * @param unknown_type $runDirectory is the working directory (optional) to store send files or to store logs
	 * 
	 * @return : log information
	 */
	public function process($runDirectory)
	{
	$result = '';



        // get order processing (waiting to shipment)
        $collection = mage::getModel('Purchase/Order')
                        ->getCollection();

        $collection->addFieldToFilter('po_status', array('in' => 'waiting_for_delivery'));

        // bdd connection
        $bdd = mage::helper('ExternalLogistic/Bdd')->getConnection();


        foreach ($collection as $order) {


            // purchase order Check
            $querycheckorder = $bdd->select(array('is_reception'))
                            ->from(array('reception' => 'vp_ligreception_' . mage::getStoreConfig('externallogistic/bdd/login') . '_traitee'))
                            ->where('c_refcommandepro = ?', $order->getpo_order_id());

            $checkorder = $bdd->query($querycheckorder);
            $checkorderrow = $checkorder->fetch();
            if ($checkorderrow['c_refcommandepro'] == $order->getincrement_id()) {
                $orderid = $checkorderrow['is_reception'];
                $sourceId = $order->getincrement_id();
                $description = 'Livraison fournisseur ...';

                // check livraison article
                $querycheckarticle = $bdd->select(array('is_article', 'n_qtelivree', 'c_ref'))
                                ->from(array('articleligne' => 'vp_ligreception_' . mage::getStoreConfig('externallogistic/bdd/login') . '_traitee'))
                                ->join(array('article' => 'vp_article_' . mage::getStoreConfig('externallogistic/bdd/login')),
                                        'articleligne.is_article = article.is_article')
                                ->where('is_commande = ?', $orderid);

                $checkarticle = $bdd->query($querycheckarticle);
                $products = array();


                while ($checkarticlerow = $checkarticle->fetch()) {
                    $sku = $checkarticlerow['c_ref'];
                    $qty = $checkarticlerow['n_qtelivree'];
                    $isPositive = true;
                    $sourceType = MDN_ExternalLogistic_Helper_StockMovement::kSourceTypePurchaseOrder;

                    mage::helper('ExternalLogistic/StockMovement')->createStockMovement($sku, $qty, $isPositive, $sourceType, $sourceId, $description);
                }

                $result.= 'Livraison de la commande '.$orderIncrementId. ' OK. ';
            }
        }



		return $result;
	}
	
}