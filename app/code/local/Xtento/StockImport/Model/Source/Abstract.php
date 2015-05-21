<?php

/**
 * Product:       Xtento_StockImport (2.2.8)
 * ID:            %!uniqueid!%
 * Packaged:      %!packaged!%
 * Last Modified: 2013-07-28T13:00:42+02:00
 * File:          app/code/local/Xtento/StockImport/Model/Source/Abstract.php
 * Copyright:     Copyright (c) 2015 XTENTO GmbH & Co. KG <info@xtento.com> / All rights reserved.
 */

abstract class Xtento_StockImport_Model_Source_Abstract extends Mage_Core_Model_Abstract implements Xtento_StockImport_Model_Source_Interface
{
    protected $_connection;
}