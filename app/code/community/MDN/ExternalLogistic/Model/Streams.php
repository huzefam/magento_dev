<?php

class MDN_ExternalLogistic_Model_Streams extends Mage_Core_Model_Abstract {

    private $_forceSourceContent = array();
    private $_customInformation = null;

    //stream directions
    const kDirectionMagentoToLogistic = 0;
    const kDirectionLogisticToMagento = 1;

    public function _construct() {
        parent::_construct();
        $this->_init('ExternalLogistic/Streams');
    }

    public function getDirections() {
        $retour = array();
        $retour[self::kDirectionLogisticToMagento] = mage::helper('ExternalLogistic')->__('Logistic to Magento');
        $retour[self::kDirectionMagentoToLogistic] = mage::helper('ExternalLogistic')->__('Magento to Logistic');
        return $retour;
    }

    //#################################################################################################
    //#################################################################################################
    // SCHEDULING
    //#################################################################################################
    //#################################################################################################

    /**
     * Return true if stream is scheduled at specified hour / minute pair
     *
     * @param unknown_type $hour
     * @param unknown_type $minute
     */
    public function isScheduledAt($hour, $minute) {

        $retour = false;
        $pattern = $hour . 'h' . $minute;

        $tmp = explode(",", $this->getels_schedule_values());

        if (count($tmp) == 1 && $tmp[0] == "*") {
            $retour = true;
        } else {
            foreach ($tmp as $v) {

                if (trim($v) == $pattern) {
                    $retour = true;
                    break;
                }
            }
        }

        $debug = 'check if stream scheduled at '.$pattern.' for stream '.$this->getels_code().' : result is '.($retour ? 'yes' : 'false').' (scheduling is '.$this->getels_schedule_values().')';
        Mage::log($debug, null, 'externalog_logistic.log');

        return $retour;
    }

    /**
     * define is stream is scheduled for current day / hour
     *
     * @return unknown
     */
    public function isCurrentlyScheduled() {
        $timestamp = Mage::getModel('core/date')->timestamp();

        $hour = date('H', $timestamp);
        $minute = Mage::Helper('ExternalLogistic/Scheduling')->getMinute(date('i', $timestamp));

        return $this->isScheduledAt($hour, $minute);
    }

    /**
     * Get scheduled values
     *
     * @return string $retour
     */
    public function getScheduledValues() {

        $retour = '';

        $tmp = explode(",", $this->getels_schedule_values());

        $retour = implode("\n", $tmp);

        return $retour;
    }

    //#################################################################################################
    //#################################################################################################
    // PROCESS
    //#################################################################################################
    //#################################################################################################

    /**
     * Process stream
     *
     */
    public function process() {
        $activeCompanies = mage::helper('ExternalLogistic/Companies')->getActiveCompanies();
        foreach ($activeCompanies as $company) {
            $companyCode = $company['value'];
            $companyLabel = $company['label'];

            $helperType = ($this->getels_direction() == self::kDirectionMagentoToLogistic ? 'Adapter' : 'Parser');
            $helperName = 'ExternalLogistic/' . $helperType . '_' . $companyCode . '_' . $this->getels_helper_class();

            try {
                $data = mage::helper($helperName)->process(null);
            } catch (Exception $ex) {
                $data = array('entity_ids' => null,
                    'result' => $ex->getMessage(),
                    'error' => 1,
                    'logistic_stream_code' => null);
            }

            $history = $this->addRunToHistory(
                            $companyCode,
                            $data['entity_ids'],
                            $data['result'],
                            $data['error'],
                            $data['logistic_stream_code']);

            //confirm history
            if (isset($data['auto_confirm'])) {
                if ($data['auto_confirm'] == true) {
                    $history->confirm();
                }
            }
        }

        $this->setelh_last_execution(date('Y-m-d H:i'))->save();
    }

    //#################################################################################################
    //#################################################################################################
    // HISTORY
    //#################################################################################################
    //#################################################################################################

    /**
     * Add entry in history
     *
     * @param unknown_type $description
     */
    public function addRunToHistory($companyCode, $entityIds, $result, $error, $logisticStreamCode = null) {
        $obj = mage::getModel('ExternalLogistic/History')
                        ->setelh_date(date('Y_m_d H:i:s'))
                        ->setelh_description(mage::helper('ExternalLogistic')->__('Stream %s', $this->getels_code()))
                        ->setelh_result($result)
                        ->setelh_stream_code($this->getels_code())
                        ->setelh_company_code($companyCode)
                        ->setelh_entity_ids($entityIds)
                        ->setelh_has_error($error)
                        ->setelh_logistic_stream_code($logisticStreamCode)
                        ->setelh_confirmed(0)
                        ->save();

        return $obj;
    }

    /**
     * Return stream history
     *
     * @return unknown
     */
    public function getHistory() {
        $collection = mage::getModel('ExternalLogistic/History')
                        ->getCollection()
                        ->addFieldToFilter('elh_stream_code', $this->getels_code());
        return $collection;
    }

    //#################################################################################################
    //#################################################################################################
    // CUSTOM INFORMATION
    //#################################################################################################
    //#################################################################################################

    /**
     * Return custom information collection
     *
     * @return unknown
     */
    public function getCustomInformation() {
        if ($this->_customInformation == null) {
            $this->_customInformation = mage::getModel('ExternalLogistic/CustomInformation')
                            ->getCollection()
                            ->addFieldToFilter('elci_els_id', $this->getId());
        }
        return $this->_customInformation;
    }

    /**
     * Add new custom information
     *
     */
    public function addNewCustomInformation() {
        if ($this->getId()) {
            $newCustomInformation = mage::getModel('ExternalLogistic/CustomInformation');
            $newCustomInformation->setelci_els_id($this->getId());
            $newCustomInformation->save();
        }
    }

    //#################################################################################################
    //#################################################################################################
    // MISC
    //#################################################################################################
    //#################################################################################################

    /**
     * Force source content
     *
     * @return unknown
     */
    public function forceSourceContent($sourceType, $sourceIds) {
        $this->_forceSourceContent[$sourceType] = $sourceIds;
    }

    public function getRunDirectory() {
        $mainDirectory = Mage::getBaseDir('var') . '/external_logistic/';
        if (!is_dir($mainDirectory))
            mkdir($mainDirectory);
        $runDirectory = $mainDirectory . date('YmdHis') . '_' . $this->getels_code() . '/';
        if (!is_dir($runDirectory))
            mkdir($runDirectory);
        return $runDirectory;
    }

}