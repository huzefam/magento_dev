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
class MDN_ExternalLogistic_Helper_Mail extends Mage_Core_Helper_Abstract
{
	public function sendMail($to, $subject, $body)
	{
		
    	$identity = Mage::getStoreConfig('externallogistic/email/email_identity');
    	$emailTemplate = Mage::getStoreConfig('externallogistic/email/email_template');

		//definies datas
	    $data = array
	    	(
				'subject'	=>	$subject,
	    		'body'		=>	$body
	    	);
    	
    	//send email
    	$translate = Mage::getSingleton('core/translate');
        $translate->setTranslateInline(false);
    	Mage::getModel('core/email_template')
            ->setDesignConfig(array('area'=>'adminhtml'))
            ->sendTransactional(
                $emailTemplate,
                $identity,
                $to,
                '',
                $data,
                null);
		
		$translate->setTranslateInline(true);
	}
}