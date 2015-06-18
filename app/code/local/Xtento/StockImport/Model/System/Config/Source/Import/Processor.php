<?php

/**
 * Product:       Xtento_StockImport (2.2.8)
 * ID:            /rRDmPy6ZEZj9ocZGuuFjhblVHpQKfaGmtArmCqlOFM=
 * Packaged:      2015-06-18T20:41:54+00:00
 * Last Modified: 2013-07-20T18:51:07+02:00
 * File:          app/code/local/Xtento/StockImport/Model/System/Config/Source/Import/Processor.php
 * Copyright:     Copyright (c) 2015 XTENTO GmbH & Co. KG <info@xtento.com> / All rights reserved.
 */

class Xtento_StockImport_Model_System_Config_Source_Import_Processor
{
    public function toOptionArray()
    {
        return Mage::getSingleton('xtento_stockimport/import')->getProcessors();
    }
}