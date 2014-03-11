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
class MDN_ExternalLogistic_Helper_Prune extends Mage_Core_Helper_Abstract
{
    public function apply()
    {
        $path = '/var/www/clients/client1/web15/web/var/external_logistic/*';
        echo shell_exec('rm -R '.$path);
        die('');

        $delay = Mage::getStoreConfig('externallogistic/auto_prune/delay');
        $date = date('Y-m-d', time() - $delay * 3600 * 24);

        //remove log files
        $collection = Mage::getModel('ExternalLogistic/History')
                        ->getCollection()
                        ->addFieldToFilter('elh_date', array('lt' => $date));
        
        foreach($collection as $item)
        {
            $company = $item->getelh_company_code();
            $mainDirectory = Mage::helper('ExternalLogistic')->getWorkingDirectoryForCompany($company);
            $directory = $mainDirectory.DS.$item->getelh_logistic_stream_code();
            echo '<p>'.$directory;

        }
            die('ok');
    }
    
}