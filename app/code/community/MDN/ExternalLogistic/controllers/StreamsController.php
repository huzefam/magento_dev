<?php

class MDN_ExternalLogistic_StreamsController extends Mage_Adminhtml_Controller_Action
{
	
	/**
	 * Streams grid
	 *
	 */
	public function GridAction()
	{
		$this->loadLayout();
		$this->renderLayout();
	}

	/**
	 * Edit stream
	 *
	 */
	public function EditAction()
	{
		$currentStreamId = $this->getRequest()->getParam('els_id');
		$stream = mage::getModel('ExternalLogistic/Streams')->load($currentStreamId);
		mage::register('current_stream', $stream);
		
		$this->loadLayout();
		$this->renderLayout();
	}

	/**
	 * save stream data
	 *
	 */
	public function saveAction()
	{
		//save main information
		$data = $this->getRequest()->getPost('data');
		$stream = mage::getModel('ExternalLogistic/Streams')->load($data['els_id']);

                // scheduling
                if(isset($data['els_schedule_values'])){

                    $tmp = explode("\n", $data['els_schedule_values']);

                    foreach($tmp as $k => $v){

                        $tmp[$k] = trim($v);

                    }

                    $data['els_schedule_values'] = implode(",", $tmp);


                }else{
                    $data['els_schedule_values'] = '';
                }

		foreach ($data as $key => $value)
		{
			$stream->setData($key, $value);
		}
		$stream->save();
		
		//save custom information
		$customInformationsData = $this->getRequest()->getPost('custom');
		foreach ($stream->getCustomInformation() as $customInformation)
		{
			$customInformationData = $customInformationsData[$customInformation->getId()];
			if (isset($customInformationData['delete']) && ($customInformationData['delete'] == 1))
			{
				$customInformation->delete();
			}
			else 
			{
				foreach ($customInformationData as $key => $value)
				{
					$customInformation->setData($key, $value);
				}
				$customInformation->save();
			}
		}
		
		//confirm and redirect
		Mage::getSingleton('adminhtml/session')->addSuccess('Data saved');
		$this->_redirect('ExternalLogistic/Streams/Edit', array('els_id' => $stream->getId()));
				
	}
	
	/**
	 * Add a new file for a stream
	 *
	 */
	public function AddNewCustomInformationAction()
	{
		//add new custom information
		$streamId = $this->getRequest()->getParam('els_id');
		$stream = mage::getModel('ExternalLogistic/Streams')->load($streamId);
		$stream->addNewCustomInformation();
		
		//confirm and redirect
		Mage::getSingleton('adminhtml/session')->addSuccess('New custom information added');
		$this->_redirect('ExternalLogistic/Streams/Edit', array('els_id' => $stream->getId(), 'tab' => 'custominformation'));

	}

	/**
	 * Manually run stream
	 *
	 */
	public function RunAction()
	{
		$elsId = $this->getRequest()->getParam('els_id');
		$stream = mage::getModel('ExternalLogistic/Streams')->load($elsId);
		
		try 
		{			
			$stream->process();			
		}
		catch (Exception $ex)
		{
			Mage::getSingleton('adminhtml/session')->addError('An error occured : '.$ex->getMessage());
		}

		Mage::getSingleton('adminhtml/session')->addSuccess('Stream processed');
		$this->_redirect('ExternalLogistic/Streams/Edit', array('els_id' => $stream->getId(), 'tab' => 'files'));
	}
}
