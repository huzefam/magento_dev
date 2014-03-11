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
class MDN_ExternalLogistic_Helper_Scheduling extends Mage_Core_Helper_Abstract
{
	
	/**
	 * return days array
	 *
	 * @return unknown
	 */
	/*public function getDays()
	{
		$values = array();
		$values[0] = 'monday';		
		$values[1] = 'tuesday';		
		$values[2] = 'wednesday';		
		$values[3] = 'thurdsay';		
		$values[4] = 'friday';		
		$values[5] = 'saturday';		
		$values[6] = 'sunday';		
		
		return $values;
	}*/
	
	/**
	 * Run schedule streams
	 *
	 */
	public function runScheduleStreams()
	{
		$streams = mage::getModel('ExternalLogistic/Streams')
						->getCollection()
						->addFieldToFilter('els_is_active', 1);
		foreach ($streams as $stream)
		{
			if ($stream->isCurrentlyScheduled())
				$stream->process();
		}
	}

        public function getMinute($min){

            $delta = 5;
            $x = 0;
            $retour = 0;

            $x = (int)($min / $delta);
            $modulo = $min % $delta;

            if($modulo > ($delta / 2))
                $x += 1;

            $retour = $delta * $x;
            
            if($retour == 0 || $retour == 60)
                $retour = '00';

            if (($retour < 10) && ($retour != '00'))
                $retour = '0'.$retour;
            
            return $retour;

        }
        

}