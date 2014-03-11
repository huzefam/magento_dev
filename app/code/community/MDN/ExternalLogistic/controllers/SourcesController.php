<?php

class MDN_ExternalLogistic_SourcesController extends Mage_Adminhtml_Controller_Action
{
	
	/**
	 * Sources grid
	 *
	 */
	public function GridAction()
	{
		$this->loadLayout();
		$this->renderLayout();
	}
	
	/**
	 * Edit source
	 *
	 */
	public function EditAction()
	{
		$this->loadLayout();
		$this->renderLayout();
	}

	/**
	 * Save
	 *
	 */
	public function SaveAction()
	{
		$srcId = $this->getRequest()->getPost('elsrc_id');
		$source = mage::getModel('ExternalLogistic/Sources')->load($srcId);
		
		
		//confirm & redirect
		Mage::getSingleton('adminhtml/session')->addSuccess('Data saved');
		$this->_redirect('ExternalLogistic/Sources/Edit', array('elsrc_id' => $source->getId()));

	}
}