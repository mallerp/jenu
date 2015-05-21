<?php

/**
 * Product:       Xtento_StockImport (2.2.8)
 * ID:            %!uniqueid!%
 * Packaged:      %!packaged!%
 * Last Modified: 2014-08-08T11:58:50+02:00
 * File:          app/code/local/Xtento/StockImport/Model/Source/Sftp.php
 * Copyright:     Copyright (c) 2015 XTENTO GmbH & Co. KG <info@xtento.com> / All rights reserved.
 */

class Xtento_StockImport_Model_Source_Sftp extends Xtento_StockImport_Model_Source_Abstract
{

    /*
     * Download files from a FTP server
     */
    public function testConnection()
    {
        $this->initConnection();
        return $this->getTestResult();
    }

    public function initConnection()
    {
        set_include_path(Mage::getBaseDir('lib') . DS . 'phpseclib' . PATH_SEPARATOR . get_include_path());
        if (!@class_exists('Math_BigInteger')) require_once('phpseclib/Math/BigInteger.php');
        if (!@class_exists('Net_SFTP')) require_once('phpseclib/Net/SFTP.php');
        if (!@class_exists('Crypt_RSA')) require_once('phpseclib/Crypt/RSA.php');

        $this->setSource(Mage::getModel('xtento_stockimport/source')->load($this->getSource()->getId()));
        $testResult = new Varien_Object();
        $this->setTestResult($testResult);

        if (class_exists('Net_SFTP')) {
            $this->_connection = new Net_SFTP($this->getSource()->getHostname(), $this->getSource()->getPort(), $this->getSource()->getTimeout());
        } else {
            $this->getTestResult()->setSuccess(false)->setMessage(Mage::helper('xtento_stockimport')->__('No SFTP functions found. The Net_SFTP class is missing.'));
            return false;
        }

        if (!$this->_connection) {
            $this->getTestResult()->setSuccess(false)->setMessage(Mage::helper('xtento_stockimport')->__('Could not connect to SFTP server. Please make sure that there is no firewall blocking the outgoing connection to the SFTP server and that the timeout is set to a high enough value. If this error keeps occurring, please get in touch with your server hoster / server administrator AND with the server hoster / server administrator of the remote SFTP server. A firewall is probably blocking ingoing/outgoing SFTP connections.'));
            return false;
        }

        // Pub/Private key support - make sure to use adjust the loadKey function with the right key format: http://phpseclib.sourceforge.net/documentation/misc_crypt.html WARNING: Magentos version of phpseclib actually only implements CRYPT_RSA_PRIVATE_FORMAT_PKCS1.
        /*$pk = new Crypt_RSA();
        $pk->setPassword($this->getData('password'));
        #$private_key = file_get_contents('c:\\TEMP\\keys\\coreftp_rsa_no_pw.privkey'); // Or load the private key from a file
        $private_key = <<<KEY
-----BEGIN DSA PRIVATE KEY-----
Proc-Type: 4,ENCRYPTED
DEK-Info: DES-EDE3-CBC,F82184195914B351

...................................
-----END DSA PRIVATE KEY-----
KEY;

        if ($pk->loadKey($private_key, CRYPT_RSA_PRIVATE_FORMAT_PKCS1) === false) {
            $this->getTestResult()->setSuccess(false)->setMessage(Mage::helper('xtento_stockimport')->__('Could not load private key supplied. Make sure it is the PKCS1 format (openSSH) and that the supplied password is right.'));
            return false;
        }*/

        if (!@$this->_connection->login($this->getSource()->getUsername(), Mage::helper('core')->decrypt($this->getSource()->getPassword()))) {
            #if (!@$this->_connection->login($this->getData('username'), $pk)) { // If using pubkey authentication
            $this->getTestResult()->setSuccess(false)->setMessage(Mage::helper('xtento_stockimport')->__('Connection to SFTP server failed (make sure no firewall is blocking the connection). This error could also be caused by a wrong login for the SFTP server.'));
            return false;
        }

        if (!@$this->_connection->chdir($this->getSource()->getPath())) {
            $this->getTestResult()->setSuccess(false)->setMessage(Mage::helper('xtento_stockimport')->__('Could not change directory on SFTP server to import directory. Please make sure the directory exists and that we have rights to read in the directory.'));
            return false;
        }

        $this->getTestResult()->setSuccess(true)->setMessage(Mage::helper('xtento_stockimport')->__('Connection with SFTP server tested successfully.'));
        $this->getSource()->setLastResult($this->getTestResult()->getSuccess())->setLastResultMessage($this->getTestResult()->getMessage())->save();
        return true;
    }

    public function loadFiles()
    {
        $filesToProcess = array();

        $logEntry = Mage::registry('stock_import_log');
        // Test connection
        $testResult = $this->testConnection();
        if (!$testResult->getSuccess()) {
            $logEntry->setResult(Xtento_StockImport_Model_Log::RESULT_WARNING);
            $logEntry->addResultMessage(Mage::helper('xtento_stockimport')->__('Source "%s" (ID: %s): %s', $this->getSource()->getName(), $this->getSource()->getId(), $testResult->getMessage()));
            return false;
        }

        $filelist = @$this->_connection->rawlist();
        foreach ($filelist as $filename => $fileinfo) {
            if (!preg_match($this->getSource()->getFilenamePattern(), $filename)) {
                continue;
            }
            if (!isset($fileinfo['size'])) {
                continue; // This is a directory.
            }
            $fs_filename = $filename;
            if ($buffer = @$this->_connection->get($fs_filename)) {
                if (!empty($buffer)) {
                    $filesToProcess[] = array('source_id' => $this->getSource()->getId(), 'path' => $this->getSource()->getPath(), 'filename' => $filename, 'data' => $buffer);
                } else {
                    $this->archiveFiles(array(array('source_id' => $this->getSource()->getId(), 'path' => $this->getSource()->getPath(), 'filename' => $filename)), false, false);
                }
            } else {
                $logEntry->setResult(Xtento_StockImport_Model_Log::RESULT_WARNING);
                $logEntry->addResultMessage(Mage::helper('xtento_stockimport')->__('Source "%s" (ID: %s): Could not download file "%s" from SFTP server. Please make sure we\'ve got rights to read the file.', $this->getSource()->getName(), $this->getSource()->getId(), $filename));
            }
        }

        return $filesToProcess;
    }

    public function archiveFiles($filesToProcess, $forceDelete = false, $chDir = true)
    {
        $logEntry = Mage::registry('stock_import_log');

        if ($this->_connection) {
            if ($forceDelete) {
                foreach ($filesToProcess as $file) {
                    if ($file['source_id'] !== $this->getSource()->getId()) {
                        continue;
                    }
                    if (!@$this->_connection->delete($file['path'] . '/' . $file['filename'])) {
                        $logEntry->setResult(Xtento_StockImport_Model_Log::RESULT_WARNING);
                        $logEntry->addResultMessage(Mage::helper('xtento_stockimport')->__('Source "%s" (ID: %s): Could not delete file "%s%s" from the SFTP import directory after processing it. Please make sure the directory exists and that we have rights to read/write in the directory.', $this->getSource()->getName(), $this->getSource()->getId(), $file['path'], $file['filename']));
                    }
                }
            } else if ($this->getSource()->getArchivePath() !== "") {
                if ($chDir) {
                    if (!@$this->_connection->chdir($this->getSource()->getArchivePath())) {
                        $logEntry->setResult(Xtento_StockImport_Model_Log::RESULT_WARNING);
                        $logEntry->addResultMessage(Mage::helper('xtento_stockimport')->__('Source "%s" (ID: %s): Could not change directory on SFTP server to archive directory. Please make sure the directory exists and that we have rights to read/write in the directory.', $this->getSource()->getName(), $this->getSource()->getId()));
                        return false;
                    }
                }
                foreach ($filesToProcess as $file) {
                    if ($file['source_id'] !== $this->getSource()->getId()) {
                        continue;
                    }
                    if (!@$this->_connection->rename($file['path'] . '/' . $file['filename'], $this->getSource()->getArchivePath() . '/' . $file['filename'])) {
                        $logEntry->setResult(Xtento_StockImport_Model_Log::RESULT_WARNING);
                        $logEntry->addResultMessage(Mage::helper('xtento_stockimport')->__('Source "%s" (ID: %s): Could not move file "%s%s" to the SFTP archive directory located at "%s". Please make sure the directory exists and that we have rights to read/write in the directory.', $this->getSource()->getName(), $this->getSource()->getId(), $file['path'], $file['filename'], $this->getSource()->getArchivePath()));
                    }
                }
            } else if ($this->getSource()->getDeleteImportedFiles() == true) {
                foreach ($filesToProcess as $file) {
                    if ($file['source_id'] !== $this->getSource()->getId()) {
                        continue;
                    }
                    if (!@$this->_connection->delete($file['path'] . '/' . $file['filename'])) {
                        $logEntry->setResult(Xtento_StockImport_Model_Log::RESULT_WARNING);
                        $logEntry->addResultMessage(Mage::helper('xtento_stockimport')->__('Source "%s" (ID: %s): Could not delete file "%s%s" from the SFTP import directory after processing it. Please make sure the directory exists and that we have rights to read/write in the directory.', $this->getSource()->getName(), $this->getSource()->getId(), $file['path'], $file['filename']));
                    }
                }
            }
        }
    }
}