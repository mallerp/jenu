<?php

/**
 * Product:       Xtento_TrackingImport (2.0.5)
 * ID:            %!uniqueid!%
 * Packaged:      %!packaged!%
 * Last Modified: 2013-11-03T16:32:55+01:00
 * File:          app/code/local/Xtento/TrackingImport/Model/Source/Abstract.php
 * Copyright:     Copyright (c) 2015 XTENTO GmbH & Co. KG <info@xtento.com> / All rights reserved.
 */

abstract class Xtento_TrackingImport_Model_Source_Abstract extends Mage_Core_Model_Abstract implements Xtento_TrackingImport_Model_Source_Interface
{
    protected $_connection;
}