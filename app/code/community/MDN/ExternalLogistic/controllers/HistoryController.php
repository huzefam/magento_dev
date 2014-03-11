<?php

class MDN_ExternalLogistic_HistoryController extends Mage_Adminhtml_Controller_Action {

    public function GridAction() {
        $this->loadLayout();
        $this->renderLayout();
    }

    public function ViewAction() {
        $this->loadLayout();
        $this->renderLayout();
    }

    public function PruneAction() {

        Mage::helper('ExternalLogistic/Prune')->apply();

        //confirm & redirect
        Mage::getSingleton('adminhtml/session')->addSuccess('Logs pruned');
        $this->_redirect('adminhtml/system_config/edit', array('section' => 'externallogistic'));
    }

    /**
     * Download history files
     */
    public function DownloadAction()
    {
        //init vars
        $elhId = $this->getRequest()->getParam('elh_id');
        $history = Mage::getModel('ExternalLogistic/History')->load($elhId);
        $filesDirectory = $history->getFilesDirectory();
        if (is_dir($filesDirectory))
        {
            //create a tar with all files
            $tarFile = Mage::helper('ExternalLogistic')->getWorkingDirectory().'history.tar';
            $cmd = 'tar cfv '.$tarFile.' '.$filesDirectory;
            shell_exec($cmd);

            //return content
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename=' . basename($tarFile));
            header('Content-Transfer-Encoding: binary');
            header('Content-Length: ' . filesize($tarFile));
            ob_clean();
            flush();
            readfile($tarFile);
            exit;
        }
        else
        {
            Mage::getSingleton('adminhtml/session')->addError($this->__('Unable to load history directory (%s)', $filesDirectory));
            $this->_redirectReferer();
        }
    }

}
