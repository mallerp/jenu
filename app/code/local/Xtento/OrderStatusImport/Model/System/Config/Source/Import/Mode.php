<?php

/**
 * Product:       Xtento_OrderStatusImport (1.4.0)
 * ID:            %!uniqueid!%
 * Packaged:      %!packaged!%
 * Last Modified: 2010-06-01T15:20:13+02:00
 * File:          app/code/local/Xtento/OrderStatusImport/Model/System/Config/Source/Import/Mode.php
 * Copyright:     Copyright (c) 2015 XTENTO GmbH & Co. KG <info@xtento.com> / All rights reserved.
 */

class Xtento_OrderStatusImport_Model_System_Config_Source_Import_Mode
{

    public function toOptionArray()
    {
        $modes[] = array('value' => 'XML', 'label' => Mage::helper('orderstatusimport')->__('XML'));
        $modes[] = array('value' => 'CSV', 'label' => Mage::helper('orderstatusimport')->__('CSV'));
        return $modes;
    }

}
