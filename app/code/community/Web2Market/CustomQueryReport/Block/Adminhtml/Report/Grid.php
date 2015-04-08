<?php

class Web2Market_CustomQueryReport_Block_Adminhtml_Report_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
    public function __construct()
    {
        parent::__construct();

        $this->setId('reportsGrid');
        $this->setDefaultSort('report_id');
        $this->setDefaultDir('ASC');
        $this->setSaveParametersInSession(true);

        // TODO: remove this direct helper access and replace with an action element in the layout XML
        $this->setAllowEdit(Mage::helper('customqueryreport')->getAllowEdit());
    }

    protected function _prepareCollection()
    {
        /** @var $collection Web2Market_CustomQueryReport_Model_Mysql4_Report_Collection */
        $collection = Mage::getModel('customqueryreport/report')->getCollection();
        $collection->setOrder('title', 'ASC');

        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        $this->addColumn(
            'title',
            array(
                'header' => $this->__('Title'),
                'index'  => 'title',
            )
        );

        $actions = array(
            array(
                'caption' => $this->__('View'),
                'url'     => array(
                    'base'   => '*/*/view',
                    'params' => array(),
                ),
                'field'   => 'report_id'
            )
        );

        if ($this->getAllowEdit()) {
            $actions[] = array(
                'caption' => $this->__('Edit'),
                'url'     => array(
                    'base'   => '*/*/edit',
                    'params' => array(),
                ),
                'field'   => 'report_id'
            );
        }

        $this->addColumn(
            'action_view',
            array(
                'header'     => $this->__('Action'),
                'index'      => 'report_id',
                'sortable'   => false,
                'filter'     => false,
                'type'       => 'action',
                'actions'    => $actions,
                'link_limit' => 2,
            )
        );

        return parent::_prepareColumns();
    }

    /**
     * @param $item Web2Market_CustomQueryReport_Model_Report
     *
     * @return string
     */
    public function getRowUrl($item)
    {
        return $this->getUrl('*/*/view', array('report_id' => $item->getId()));
    }
}