<?php

/**
 * Product:       Xtento_StockImport (2.2.8)
 * ID:            %!uniqueid!%
 * Packaged:      %!packaged!%
 * Last Modified: 2013-08-07T22:29:31+02:00
 * File:          app/code/local/Xtento/StockImport/Model/Log.php
 * Copyright:     Copyright (c) 2015 XTENTO GmbH & Co. KG <info@xtento.com> / All rights reserved.
 */

class Xtento_StockImport_Model_Log extends Mage_Core_Model_Abstract
{
    /*
     * Log model which keeps track of successful/failed import attempts
     */
    protected $_resultMessages = array();
    protected $_debugMessages = array();

    // Log result types
    const RESULT_NORESULT = 0;
    const RESULT_SUCCESSFUL = 1;
    const RESULT_WARNING = 2;
    const RESULT_FAILED = 3;

    protected function _construct()
    {
        parent::_construct();
        $this->_init('xtento_stockimport/log');
    }

    public function setResult($resultLevel)
    {
        if ($this->getResult() === NULL) {
            $this->setData('result', $resultLevel);
        } else if ($resultLevel > $this->getResult()) { // If result is failed, do not reset to warning for example.
            $this->setData('result', $resultLevel);
        }
    }

    public function addResultMessage($message)
    {
        array_push($this->_resultMessages, $message);
    }

    public function getResultMessages()
    {
        if (empty($this->_resultMessages)) {
            return false;
        }
        return (count($this->_resultMessages) > 1) ? implode("\n", $this->_resultMessages) : $this->_resultMessages[0];
    }

    public function addDebugMessage($message)
    {
        if ($this->getLogDebugMessages()) {
            array_push($this->_debugMessages, $message);
        }
    }

    public function getDebugMessages()
    {
        if (empty($this->_debugMessages)) {
            return false;
        }
        return (count($this->_debugMessages) > 1) ? implode("\n", $this->_debugMessages) : $this->_debugMessages[0];
    }
}