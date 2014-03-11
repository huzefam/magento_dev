<?php

class MDN_ExternalLogistic_Block_SalesOrder_Grid extends Mage_Adminhtml_Block_Widget_Grid
{

    public function __construct()
    {
        parent::__construct();
        $this->setId('salesOrderGrid');
        
        $this->setDefaultSort('created_at');
        $this->setDefaultDir('desc');
        
        $this->setVarNameFilter('external_logistic_sales_order');
        $this->_parentTemplate = $this->getTemplate();
        $this->setEmptyText(Mage::helper('ExternalLogistic')->__('No items'));
    }

    protected function _prepareCollection()
    {
        //build collection depending of magento version
        if (mage::helper('ExternalLogistic/FlatOrder')->ordersUseEavModel())
        {
            $collection = Mage::getResourceModel('sales/order_collection')
                ->addAttributeToSelect('*')
                ->joinAttribute('billing_firstname', 'order_address/firstname', 'billing_address_id', null, 'left')
                ->joinAttribute('billing_lastname', 'order_address/lastname', 'billing_address_id', null, 'left')
                ->joinAttribute('shipping_firstname', 'order_address/firstname', 'shipping_address_id', null, 'left')
                ->joinAttribute('shipping_lastname', 'order_address/lastname', 'shipping_address_id', null, 'left')
                ->addExpressionAttributeToSelect('billing_name',
                    'CONCAT({{billing_firstname}}, " ", {{billing_lastname}})',
                    array('billing_firstname', 'billing_lastname'))
                ->addExpressionAttributeToSelect('shipping_name',
                    'CONCAT({{shipping_firstname}},  IFNULL(CONCAT(\' \', {{shipping_lastname}}), \'\'))',
                    array('shipping_firstname', 'shipping_lastname'));
        }
        else
        {
            $collection = mage::getModel('sales/order')
                ->getCollection()
                ->join('sales/order_address', '`sales/order_address`.entity_id=shipping_address_id', array('shipping_name' => "concat(firstname, ' ', lastname)"));
            
        }
        $this->setCollection($collection);
        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {

        $this->addColumn('real_order_id', array(
            'header'=> Mage::helper('sales')->__('Order #'),
            'width' => '80px',
            'type'  => 'text',
            'index' => 'increment_id',
        ));

        if (!Mage::app()->isSingleStoreMode()) {
            $this->addColumn('store_id', array(
                'header'    => Mage::helper('sales')->__('Purchased from (store)'),
                'index'     => 'store_id',
                'type'      => 'store',
                'store_view'=> true,
                'display_deleted' => true,
            ));
        }

        $this->addColumn('created_at', array(
            'header' => Mage::helper('sales')->__('Purchased On'),
            'index' => 'created_at',
            'type' => 'datetime',
            'width' => '100px',
        ));

        $this->addColumn('shipping_name', array(
            'header' => Mage::helper('sales')->__('Ship to Name'),
            'index' => 'shipping_name',
        ));

        $this->addColumn('base_grand_total', array(
            'header' => Mage::helper('sales')->__('G.T. (Base)'),
            'index' => 'base_grand_total',
            'type'  => 'currency',
            'currency' => 'base_currency_code',
        ));

        $this->addColumn('status', array(
            'header' => Mage::helper('sales')->__('Status'),
            'index' => 'status',
            'type'  => 'options',
            'width' => '70px',
            'options' => Mage::getSingleton('sales/order_config')->getStatuses(),
        ));

        $this->addColumn('is_full_stock', array(
            'header' => Mage::helper('sales')->__('Is Fullstock'),
            'renderer' => 'ExternalLogistic/Widget_Grid_Column_Renderer_IsFullStock',
            'sort' => false,
            'filter' => false,
            'align' => 'center'
        ));

        $this->addColumn('sent_to_logistic_company', array(
            'header'=> Mage::helper('ExternalLogistic')->__('Sent to Logistic'),
            'index' => 'sent_to_logistic_company',
            'align' => 'center',
            'type' => 'options',
            'options' => array(
                '1' => Mage::helper('ExternalLogistic')->__('Yes'),
                '0' => Mage::helper('ExternalLogistic')->__('No'),
				)            
        ));
        
        //add custom fields
		$defaultStream = mage::helper('ExternalLogistic/Streams')->getDefaultStream(MDN_ExternalLogistic_Helper_Streams::kDefaultStreamSalesOrder);
		if ($defaultStream)
		{
			foreach ($defaultStream->getCustomInformation() as $customInformation)
			{
		        $this->addColumn('ci_'.$customInformation->getelci_code(), array(
		            'header' => $customInformation->getelci_name(),
		            'index' => 'ci_'.$customInformation->getelci_code(),
		            'renderer' => 'MDN_ExternalLogistic_Block_Widget_Grid_Column_Renderer_CustomInformation',
		            'custom_information' => $customInformation,
		            'filter' => false,
		            'sortable' => false
		        ));				
			}
		}
                
        if (Mage::getSingleton('admin/session')->isAllowed('sales/order/actions/view')) {
            $this->addColumn('action',
                array(
                    'header'    => Mage::helper('sales')->__('Action'),
                    'width'     => '50px',
                    'type'      => 'action',
                    'getter'     => 'getId',
                    'actions'   => array(
                        array(
                            'caption' => Mage::helper('sales')->__('View'),
                            'url'     => array('base'=>'adminhtml/sales_order/view'),
                            'field'   => 'order_id'
                        )
                    ),
                    'filter'    => false,
                    'sortable'  => false,
                    'index'     => 'stores',
                    'is_system' => true,
            ));
        }
                

        return parent::_prepareColumns();
    }
    
    protected function getDefaultStream()
    {
    	
    }

    public function getGridParentHtml()
    {
        $templateName = Mage::getDesign()->getTemplateFilename($this->_parentTemplate, array('_relative'=>true));
        return $this->fetchView($templateName);
    }

    public function getRowUrl($row)
    {
    	return $this->getUrl('adminhtml/sales_order/view', array('order_id' => $row->getId()));
    }


    public function getSubmitUrl()
    {
    	return $this->getUrl('ExternalLogistic/SalesOrder/MassSendOrderToLogisticWithCustomInformation');
    }
}
