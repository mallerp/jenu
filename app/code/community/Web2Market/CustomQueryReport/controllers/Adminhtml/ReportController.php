<?php

class Web2Market_CustomQueryReport_Adminhtml_ReportController extends Mage_Adminhtml_Controller_Action
{
    protected $_report;

    public function preDispatch()
    {
        parent::preDispatch();

        $this->_title($this->__("Special Reports"));
    }

    public function indexAction()
    {
        $this->loadLayout();
        $this->renderLayout();
    }

    public function newAction()
    {
        $this->_forward('edit');
    }

    public function editAction()
    {
        Mage::register('current_report', $this->_getReport());
        $this->_title($this->__("Edit: %s", $this->_getReport()->getTitle()));

        $this->loadLayout();
        $this->renderLayout();
    }

    public function viewAction()
    {
        Mage::register('current_report', $this->_getReport());
        $this->_title($this->_getReport()->getTitle());

        $this->loadLayout();
        $this->renderLayout();
    }

    public function saveAction()
    {
        $report = $this->_getReport();
        $postData = $this->getRequest()->getParams();

        $report->addData($postData['report']);
        $report->save();

        Mage::getSingleton('adminhtml/session')->addSuccess($this->__("Saved report: %s", $report->getTitle()));

        $this->_redirect('*/*');

        return $this;
    }

    public function deleteAction()
    {
        $report = $this->_getReport();
        if (!$report->getId()) {
            Mage::getSingleton('adminhtml/session')->addSuccess($this->__("Wasn't able to find the report"));
            $this->_redirect('admin_customqueryreport/adminhtml_report');
            return $this;
        }

        $report->delete();

        Mage::getSingleton('adminhtml/session')->addSuccess($this->__("Deleted report: %s", $report->getTitle()));

        $this->_redirect('*/*');

        return $this;
    }

    /**
     * Export grid to CSV format
     */
    public function exportCsvAction()
    {
        Mage::register('current_report', $this->_getReport());
        $this->loadLayout();

        /** @var $grid Mage_Adminhtml_Block_Widget_Grid */
        $grid = $this->getLayout()->getBlock('report.view.grid');
        if(!$grid instanceof Mage_Adminhtml_Block_Widget_Grid) {
            $this->_forward('noroute');
            return;
        }

        $fileName = strtolower(str_replace(' ', '_', $this->_getReport()->getTitle())) . '_' . time() . '.csv';

        $this->_prepareDownloadResponse(
            $fileName,
            $grid->getCsvFile(),
            'text/csv'
        );
    }

    /**
     * @return Web2Market_CustomQueryReport_Model_Report
     */
    protected function _getReport()
    {
        if (isset($this->_report)) {
            return $this->_report;
        }

        $report = Mage::getModel('customqueryreport/report');
        if ($this->getRequest()->getParam('report_id')) {
            $report->load($this->getRequest()->getParam('report_id'));
        }

        $this->_report = $report;
        return $this->_report;
    }

    protected function _isAllowed()
    {
        $isView = in_array($this->getRequest()->getActionName(), array('index', 'view'));

        /** @var $helper Clean_SqlReport_Helper_Data */
        $helper = Mage::helper('customqueryreport');

        return ($isView ? $helper->getAllowView() : $helper->getAllowEdit());
    }
}
