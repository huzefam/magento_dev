<?php

class MDN_ExternalLogistic_Model_History extends Mage_Core_Model_Abstract {

    /**
     * Constructor
     */
    public function _construct() {
        parent::_construct();
        $this->_init('ExternalLogistic/History');
    }

    /**
     * Return small description for history
     * @return string
     */
    public function getSmallDescription() {
        $value = mage::helper('ExternalLogistic')->__('Sent on %s', mage::helper('core')->formatDate($this->getelh_date(), 'medium'));
        $url = Mage::helper('adminhtml')->getUrl('ExternalLogistic/History/View', array('elh_id' => $this->getId()));
        $value .= ' <a href="' . $url . '">' . mage::helper('ExternalLogistic')->__('View') . '</a>';

        return $value;
    }

    /**
     * Confirm history process by logistic company
     */
    public function confirm() {

        if ($this->getelh_confirmed() == 1)
            throw new Exception('Stream history already confirmed !');
			
		if ($this->getelh_entity_ids() != "")
		{

			//build query to mark objects as processed
			$tableName = $this->getEntityTableName();
			$primaryKey = $this->getPrimaryKey();
			$write = Mage::getSingleton('core/resource')->getConnection('core_write');
			$sql = "update " . $tableName . " set sent_to_logistic_company = 1, external_logistic_run_id = " . $this->getId() . " where " . $primaryKey . " in (" . $this->getelh_entity_ids() . ")";
			$write->query($sql);
		}
		
        //store information
        $this->setelh_confirmed(1)->save();
    }

    /**
     * Return magento model from L4Logistic stream type
     * @param <type> $streamType
     * @return <type>
     */
    protected function getEntityTableName() {
        switch ($this->getelh_stream_code()) {
            case 'purchase_orders':
                return mage::getResourceModel('Purchase/Order')->getTable('Purchase/Order');
                break;
            case 'sales_order':
                return mage::getResourceModel('sales/order')->getTable('sales/order');
                break;
            case 'products':
                return mage::getResourceModel('catalog/product')->getTable('catalog/product');
                break;
            case 'rma_accepted':
                return Mage::getResourceModel('ProductReturn/Rma')->getTable('ProductReturn/Rma');
                break;
            case 'rma_product_stock':
                return Mage::getResourceModel('ProductReturn/RmaProducts')->getTable('ProductReturn/RmaProducts');
                break;
            default:
                throw new Exception('Unable to find table name for ' . $this->getelh_stream_code());
                break;
        }
    }

    protected function getPrimaryKey()
    {
        switch ($this->getelh_stream_code()) {
            case 'purchase_orders':
                return 'po_num';
                break;
            case 'sales_order':
                return 'entity_id';
                break;
            case 'products':
                return 'entity_id';
                break;
            case 'rma_accepted':
                return 'rma_id';
                break;
            case 'rma_product_stock':
                return 'rp_id';
                break;
            default:
                throw new Exception('Unable to find primary key for ' . $this->getelh_stream_code());
                break;
        }
    }

    protected function _afterSave() {
        parent::_afterSave();

        //Define if shipment just created
        $creation = ($this->getelh_id() != $this->getOrigData('elh_id'));
        if ($creation) {
            if ($this->getelh_has_error())
                $this->notifyForError();
        }
    }

    public function notifyForError() {
        $developperEmail = mage::getStoreConfig('externallogistic/misc/developer_email');
        if ($developperEmail) {
            $msg = 'Error for history #' . $this->getId() . ' : ' . $this->getelh_result();
            $msg .= "\n" . 'Website : ' . mage::getStoreConfig('web/unsecure/base_url');
			mage::helper('ExternalLogistic/Mail')->sendMail($developperEmail, 'Error on external logistic history', $msg);
        }
    }

    /**
     * Return directory containing working files
     */
    public function getFilesDirectory()
    {
        $directory = mage::helper('ExternalLogistic')->getWorkingDirectoryForCompany($this->getelh_company_code()).$this->getelh_logistic_stream_code();
        return $directory;
    }

}