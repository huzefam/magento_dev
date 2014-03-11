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
class MDN_ExternalLogistic_Helper_Parser_SupplyWeb_IntegrationConfirmation extends MDN_ExternalLogistic_Helper_Parser_SupplyWeb_Abstract
{
    public function process()
    {
        //not applicable for this company

        //return result
        $data = array(
            'error' => false,
            'entity_ids' => '',
            'result' => 'Not applicable',
            'logistic_stream_code' => ''
        );
        return $data;
    }


}