<?php

/**
 * Product:       Xtento_TrackingImport (2.0.5)
 * ID:            %!uniqueid!%
 * Packaged:      %!packaged!%
 * Last Modified: 2013-11-06T18:19:07+01:00
 * File:          app/code/local/Xtento/TrackingImport/Block/Adminhtml/Profile.php
 * Copyright:     Copyright (c) 2015 XTENTO GmbH & Co. KG <info@xtento.com> / All rights reserved.
 */

class Xtento_TrackingImport_Block_Adminhtml_Profile extends Mage_Adminhtml_Block_Widget_Grid_Container
{
    public function __construct()
    {
        $this->_blockGroup = 'xtento_trackingimport';
        $this->_controller = 'adminhtml_profile';
        $this->_headerText = Mage::helper('xtento_trackingimport')->__('Tracking Import - Profiles');
        $this->_addButtonLabel = Mage::helper('xtento_trackingimport')->__('Add New Profile');
        parent::__construct();
    }

    protected function _toHtml()
    {
        return $this->getLayout()->createBlock('xtento_trackingimport/adminhtml_widget_menu')->toHtml() . parent::_toHtml();
    }
}