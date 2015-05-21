<?php

/**
 * Product:       Xtento_OrderStatusImport (1.4.0)
 * ID:            %!uniqueid!%
 * Packaged:      %!packaged!%
 * Last Modified: 2015-04-12T22:59:56+02:00
 * File:          app/code/local/Xtento/OrderStatusImport/Model/Processor/Xml.php
 * Copyright:     Copyright (c) 2015 XTENTO GmbH & Co. KG <info@xtento.com> / All rights reserved.
 */

class Xtento_OrderStatusImport_Model_Processor_Xml
{

    public function getRowsToProcess($filesToProcess)
    {
        # Get some more detailed error information from libxml
        libxml_use_internal_errors(true);

        $log = null;
        try {
            # Logger for this processor
            $writer = new Zend_Log_Writer_Stream(Mage::getBaseDir('log') . DS . 'order_status_import_processor_xml.log');
            $log = new Zend_Log($writer);
        } catch (Exception $e) {
            # Do nothing.. :|
        }

        # Updates to process, later the result
        $updatesInFilesToProcess = array();

        # Get mapping model
        $this->mappingModel = Mage::getModel('orderstatusimport/processor_mapping_fields');
        $this->mappingModel->setDataPath('orderstatusimport/processor_xml/import_mapping');

        # Load mapping
        $this->mapping = $this->mappingModel->getMappingConfig();

        # Load configuration:
        $config = array(
            'IMPORT_DATA_XPATH' => Mage::getStoreConfig('orderstatusimport/processor_xml/xpath_data'),
            'IMPORT_SHIPPED_ONE_FIELD' => Mage::getStoreConfigFlag('orderstatusimport/processor_xml/shipped_one_field'),
            'IMPORT_NESTED_ITEMS' => Mage::getStoreConfigFlag('orderstatusimport/processor_xml/node_nested_items'),
            'IMPORT_NESTED_ITEM_XPATH' => Mage::getStoreConfig('orderstatusimport/processor_xml/node_nested_path'),
            'IMPORT_NESTED_TRACKINGS' => Mage::getStoreConfigFlag('orderstatusimport/processor_xml/node_nested_trackings'),
            'IMPORT_NESTED_TRACKING_XPATH' => Mage::getStoreConfig('orderstatusimport/processor_xml/node_nested_tracking_path'),
        );

        if ($this->mapping->getOrderNumber() == null) {
            Mage::throwException('Please configure the XML processor in the configuration section of the Tracking Number Import Module. The order number field may not be empty and must be mapped.');
        }
        if ($config['IMPORT_DATA_XPATH'] == '') {
            Mage::throwException('Please configure the XML Processor in the configuration section of the Tracking Number Import Module. The Data XPath field may not be empty.');
        }

        foreach ($filesToProcess as $importFile) {
            $data = $importFile['data'];
            $filename = $importFile['filename'];
            $type = $importFile['type'];
            unset($importFile['data']);

            // Remove UTF8 BOM
            $bom = pack('H*', 'EFBBBF');
            $data = preg_replace("/^$bom/", '', $data);

            $updatesToProcess = array();

            // Prepare data - replace namespace
            $data = str_replace('xmlns=', 'ns=', $data); // http://www.php.net/manual/en/simplexmlelement.xpath.php#96153
            $data = str_replace('xmlns:', 'ns:', $data); // http://www.php.net/manual/en/simplexmlelement.xpath.php#96153

            try {
                $xmlDOM = new DOMDocument();
                $xmlDOM->loadXML($data);
            } catch (Exception $e) {
                $errors = "Could not load XML File '" . $filename . "' from '" . $type . "':\n" . $e->getMessage();
                foreach (libxml_get_errors() as $error) {
                    $errors .= "\t" . $error->message;
                }
                if ($log instanceof Zend_Log) $log->info($errors);
                continue; # Process next file..
            }

            if (!$xmlDOM) {
                $errors = "Could not load XML File '" . $filename . "' from '" . $type . "':\n" . $e->getMessage();
                foreach (libxml_get_errors() as $error) {
                    $errors .= "\t" . $error->message;
                }
                if ($log instanceof Zend_Log) $log->info($errors);
                continue; # Process next file..
            }

            $domXPath = new DOMXPath($xmlDOM);

            $updates = $domXPath->query($config['IMPORT_DATA_XPATH']);
            foreach ($updates as $update) {
                // Init "sub dom"
                $updateDOM = new DomDocument;
                $updateDOM->appendChild($updateDOM->importNode($update, true));
                $updateXPath = new DOMXPath($updateDOM);
                $this->update = $updateXPath;

                $orderNumber = $this->getFieldData('order_number');
                if (empty($orderNumber)) {
                    continue;
                }

                $carrierCode = $this->getFieldData('carrier_code');
                $carrierName = $this->getFieldData('carrier_name');
                $trackingNumber = $this->getFieldData('tracking_number');
                $status = $this->getFieldData('order_status');
                $orderComment = $this->getFieldData('order_status_history_comment');
                $skuToShip = strtolower($this->getFieldData('sku'));
                $qtyToShip = $this->getFieldData('qty');
                $customData1 = $this->getFieldData('custom1');
                $customData2 = $this->getFieldData('custom2');

                if (!isset($updatesToProcess[$orderNumber])) {
                    $updatesToProcess[$orderNumber] = array(
                        "STATUS" => $status,
                        "ORDER_COMMENT" => $orderComment,
                        "CUSTOM_DATA1" => $customData1,
                        "CUSTOM_DATA2" => $customData2,
                    );
                    $updatesToProcess[$orderNumber]['tracks'] = array();
                    if ($config['IMPORT_NESTED_TRACKINGS']) {
                        // Tracking data is nested..
                        $tracks = $updateXPath->query($config['IMPORT_NESTED_TRACKING_XPATH']);
                        foreach ($tracks as $track) {
                            // Init "sub dom"
                            $trackDOM = new DomDocument;
                            $trackDOM->appendChild($trackDOM->importNode($track, true));
                            $trackXPath = new DOMXPath($trackDOM);
                            $this->track = $trackXPath;

                            $carrierCode = $this->getFieldData('carrier_code', 'track');
                            $carrierName = $this->getFieldData('carrier_name', 'track');
                            $trackingNumber = $this->getFieldData('tracking_number', 'track');

                            if ($trackingNumber !== '') {
                                $trackingNumber = str_replace(array("/", ",", "|"), ";", $trackingNumber);
                                $trackingNumbers = explode(";", $trackingNumber); // Multiple tracking numbers in one field
                                foreach ($trackingNumbers as $trackingNumber) {
                                    $updatesToProcess[$orderNumber]['tracks'][$trackingNumber] = array(
                                        "TRACKINGNUMBER" => $trackingNumber,
                                        "CARRIER_CODE" => $carrierCode,
                                        "CARRIER_NAME" => $carrierName,
                                    );
                                }
                            }
                        }
                    } else {
                        if ($trackingNumber !== '') {
                            $trackingNumber = str_replace(array("/", ",", "|"), ";", $trackingNumber);
                            $trackingNumbers = explode(";", $trackingNumber); // Multiple tracking numbers in one field
                            foreach ($trackingNumbers as $trackingNumber) {
                                $updatesToProcess[$orderNumber]['tracks'][$trackingNumber] = array(
                                    "TRACKINGNUMBER" => $trackingNumber,
                                    "CARRIER_CODE" => $carrierCode,
                                    "CARRIER_NAME" => $carrierName,
                                );
                            }
                        }
                    }
                    $updatesToProcess[$orderNumber]['items'] = array();
                    $itemsToAdd = array();
                    if ($config['IMPORT_NESTED_ITEMS']) {
                        // Item data is nested..
                        $items = $updateXPath->query($config['IMPORT_NESTED_ITEM_XPATH']);
                        foreach ($items as $item) {
                            // Init "sub dom"
                            $itemDOM = new DomDocument;
                            $itemDOM->appendChild($itemDOM->importNode($item, true));
                            $itemXPath = new DOMXPath($itemDOM);
                            $this->item = $itemXPath;

                            $skuToShip = strtolower($this->getFieldData('sku', 'item'));
                            $qtyToShip = $this->getFieldData('qty', 'item');

                            if ($skuToShip !== '') {
                                $itemsToAdd[$skuToShip] = $qtyToShip;
                            }
                        }
                    } else {
                        if ($skuToShip !== '') {
                            $itemsToAdd[$skuToShip] = $qtyToShip;
                        }
                    }
                    foreach ($itemsToAdd as $skuToShip => $qtyToShip) {
                        if ($config['IMPORT_SHIPPED_ONE_FIELD'] == true) {
                            // We're supposed to import the SKU and Qtys all from one field. Each combination separated by a ; and sku/qty separated by :
                            $skuAndQtys = explode(";", $skuToShip);
                            foreach ($skuAndQtys as $skuAndQty) {
                                list ($sku, $qty) = explode(":", $skuAndQty);
                                $sku = strtolower($sku);
                                if ($sku !== '') {
                                    $updatesToProcess[$orderNumber]['items'][$sku] = array(
                                        "SKU" => $sku,
                                        "QTY" => $qty,
                                    );
                                }
                            }
                        } else {
                            // One row per SKU and QTY
                            if ($skuToShip !== '') {
                                if (isset($updatesToProcess[$orderNumber]['items'][$skuToShip])) {
                                    $updatesToProcess[$orderNumber]['items'][$skuToShip] = array(
                                        "SKU" => $skuToShip,
                                        "QTY" => $updatesToProcess[$orderNumber]['items'][$skuToShip]['QTY'] + $qtyToShip,
                                    );
                                } else {
                                    $updatesToProcess[$orderNumber]['items'][$skuToShip] = array(
                                        "SKU" => $skuToShip,
                                        "QTY" => $qtyToShip,
                                    );
                                }
                            }
                        }
                    }
                } else {
                    /*$orderNumber .= "|" . uniqid();
                    $updatesToProcess[$orderNumber] = array(
                        "STATUS" => "",
                        "ORDER_COMMENT" => "",
                        "CUSTOM_DATA1" => "",
                        "CUSTOM_DATA2" => "",
                    );*/
                    // Add multiple tracking numbers and items to ship to $updates
                    if ($config['IMPORT_NESTED_TRACKINGS']) {
                        // Tracking data is nested..
                        $tracks = $updateXPath->query($config['IMPORT_NESTED_TRACKING_XPATH']);
                        foreach ($tracks as $track) {
                            // Init "sub dom"
                            $trackDOM = new DomDocument;
                            $trackDOM->appendChild($trackDOM->importNode($track, true));
                            $trackXPath = new DOMXPath($trackDOM);
                            $this->track = $trackXPath;

                            $carrierCode = $this->getFieldData('carrier_code', 'track');
                            $carrierName = $this->getFieldData('carrier_name', 'track');
                            $trackingNumber = $this->getFieldData('tracking_number', 'track');

                            if ($trackingNumber !== '') {
                                $trackingNumber = str_replace(array("/", ",", "|"), ";", $trackingNumber);
                                $trackingNumbers = explode(";", $trackingNumber); // Multiple tracking numbers in one field
                                foreach ($trackingNumbers as $trackingNumber) {
                                    $updatesToProcess[$orderNumber]['tracks'][$trackingNumber] = array(
                                        "TRACKINGNUMBER" => $trackingNumber,
                                        "CARRIER_CODE" => $carrierCode,
                                        "CARRIER_NAME" => $carrierName,
                                    );
                                }
                            }
                        }
                    } else {
                        if ($trackingNumber !== '') {
                            $trackingNumber = str_replace(array("/", ",", "|"), ";", $trackingNumber);
                            $trackingNumbers = explode(";", $trackingNumber); // Multiple tracking numbers in one field
                            foreach ($trackingNumbers as $trackingNumber) {
                                $updatesToProcess[$orderNumber]['tracks'][$trackingNumber] = array(
                                    "TRACKINGNUMBER" => $trackingNumber,
                                    "CARRIER_CODE" => $carrierCode,
                                    "CARRIER_NAME" => $carrierName,
                                );
                            }
                        }
                    }

                    $itemsToAdd = array();
                    if ($config['IMPORT_NESTED_ITEMS']) {
                        // Item data is nested..
                        $items = $updateXPath->query($config['IMPORT_NESTED_ITEM_XPATH']);
                        foreach ($items as $item) {
                            // Init "sub dom"
                            $itemDOM = new DomDocument;
                            $itemDOM->appendChild($itemDOM->importNode($item, true));
                            $itemXPath = new DOMXPath($itemDOM);
                            $this->item = $itemXPath;

                            $skuToShip = strtolower($this->getFieldData('sku', 'item'));
                            $qtyToShip = $this->getFieldData('qty', 'item');

                            if ($skuToShip !== '') {
                                if (isset($itemsToAdd[$skuToShip])) {
                                    $itemsToAdd[$skuToShip] = $itemsToAdd[$skuToShip] + $qtyToShip;
                                } else {
                                    $itemsToAdd[$skuToShip] = $qtyToShip;
                                }
                            }
                        }
                    } else {
                        if ($skuToShip !== '') {
                            if (isset($itemsToAdd[$skuToShip])) {
                                $itemsToAdd[$skuToShip] = $itemsToAdd[$skuToShip] + $qtyToShip;
                            } else {
                                $itemsToAdd[$skuToShip] = $qtyToShip;
                            }
                        }
                    }

                    foreach ($itemsToAdd as $skuToShip => $qtyToShip) {
                        if ($skuToShip !== '') {
                            if (isset($updatesToProcess[$orderNumber]['items'][$skuToShip])) {
                                $updatesToProcess[$orderNumber]['items'][$skuToShip] = array(
                                    "SKU" => $skuToShip,
                                    "QTY" => $updatesToProcess[$orderNumber]['items'][$skuToShip]['QTY'] + $qtyToShip,
                                );
                            } else {
                                $updatesToProcess[$orderNumber]['items'][$skuToShip] = array(
                                    "SKU" => $skuToShip,
                                    "QTY" => $qtyToShip,
                                );
                            }
                        }
                    }
                }
            }

            // File processed
            $updatesInFilesToProcess[] = array(
                "FILE_INFORMATION" => $importFile,
                "HAS_SKU_INFO" => ($this->mapping->getSku() !== null) ? true : false,
                "ORDERS" => $updatesToProcess
            );
        }

        #ini_set('xdebug.var_display_max_depth', 10);
        #Zend_Debug::dump($updatesToProcess);
        #die();

        return $updatesInFilesToProcess;
    }

    private function _runCurrentUntilString($array)
    {
        // Run the current function on the returned SimpleXMLElement until a string (just no array!) gets returned
        $runCount = 0;
        while (true) {
            if ($array instanceof DOMElement && isset($array->nodeValue)) {
                return $array->nodeValue;
            }
            if (is_object($array) && $array instanceof DOMNodeList) {
                $continue = false;
                foreach ($array as $node) {
                    $array = $node;
                    $continue = true;
                }
                if ($continue) {
                    continue;
                }
            }
            if (is_array($array) || is_object($array)) {
                $array = current($array);
            } else {
                return $array;
            }
            $runCount++;
            if ($runCount > 15) {
                // Do not run this loop too often.
                return '';
            }
        }
    }

    public function getFieldData($field, $type = 'update')
    {
        if ($this->mapping->getData($field) !== null) {
            $data = $this->_runCurrentUntilString($this->$type->query($this->mapping->getData($field)));
            /*
             * Alternate method to pull fields, when xpath fails.
             */
            if ($data == '') {
                foreach ($this->$type as $key => $value) {
                    if ($key == $this->mapping->getData($field)) {
                        $data = (string)$value;
                    }
                }
            }
            if ($data == '') {
                // Try to get the default value at least.. otherwise ''
                $data = $this->mappingModel->getDefaultValue($field);
            }
        } else {
            // Try to get the default value at least.. otherwise ''
            $data = $this->mappingModel->getDefaultValue($field);
        }
        return trim($data);
    }
}
