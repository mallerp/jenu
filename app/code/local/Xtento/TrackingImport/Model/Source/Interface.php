<?php

/**
 * Product:       Xtento_TrackingImport (2.0.5)
 * ID:            %!uniqueid!%
 * Packaged:      %!packaged!%
 * Last Modified: 2013-11-03T16:32:55+01:00
 * File:          app/code/local/Xtento/TrackingImport/Model/Source/Interface.php
 * Copyright:     Copyright (c) 2015 XTENTO GmbH & Co. KG <info@xtento.com> / All rights reserved.
 */

interface Xtento_TrackingImport_Model_Source_Interface
{
    public function testConnection();
    public function loadFiles();
    public function archiveFiles($filesToProcess, $forceDelete = false);
}