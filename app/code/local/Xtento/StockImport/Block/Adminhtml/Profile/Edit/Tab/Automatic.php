<?php

/**
 * Product:       Xtento_StockImport (2.2.8)
 * ID:            /rRDmPy6ZEZj9ocZGuuFjhblVHpQKfaGmtArmCqlOFM=
 * Packaged:      2015-06-18T20:41:54+00:00
 * Last Modified: 2013-07-18T17:41:03+02:00
 * File:          app/code/local/Xtento/StockImport/Block/Adminhtml/Profile/Edit/Tab/Automatic.php
 * Copyright:     Copyright (c) 2015 XTENTO GmbH & Co. KG <info@xtento.com> / All rights reserved.
 */

class Xtento_StockImport_Block_Adminhtml_Profile_Edit_Tab_Automatic extends Xtento_StockImport_Block_Adminhtml_Widget_Tab
{
    protected function _prepareForm()
    {
        $model = Mage::registry('stock_import_profile');

        $form = new Varien_Data_Form();
        $this->setForm($form);

        $fieldset = $form->addFieldset('cronjob_fieldset', array(
            'legend' => Mage::helper('xtento_stockimport')->__('Cronjob Import'),
            'class' => 'fieldset-wide',
        ));

        $fieldset->addField('cronjob_note', 'note', array(
            'text' => Mage::helper('xtento_stockimport')->__('<strong>Important</strong>: To use cron job imports, please make sure the Magento cronjob has been set up as explained <a href="http://support.xtento.com/wiki/Setting_up_the_Magento_cronjob" target="_blank">here</a>.')
        ));

        if (Mage::helper('xtcore/utils')->isCronRunning()) {
            $model->setCronjobStatus(Mage::helper('xtento_stockimport')->__("Cron seems to be running properly. Seconds since last execution: %d", (time() - Mage::helper('xtcore/utils')->getLastCronExecution())));
            $note = '';
        } else {
            if ((time() - Mage::helper('xtcore/data')->getInstallationDate()) > (60 * 30)) { // Module was not installed within the last 30 minutes
                if (Mage::helper('xtcore/utils')->getLastCronExecution() == '') {
                    $model->setCronjobStatus(Mage::helper('xtento_stockimport')->__("Cron.php doesn't seem to be set up at all. Cron did not execute within the last 15 minutes."));
                    $note = Mage::helper('xtento_stockimport')->__('Please make sure to set up the cronjob as explained <a href="http://support.xtento.com/wiki/Setting_up_the_Magento_cronjob" target="_blank">here</a> and check the cron status 15 minutes after setting up the cronjob properly again.');
                } else {
                    $model->setCronjobStatus(Mage::helper('xtento_stockimport')->__("Cron.php doesn't seem to be set up properly. Cron did not execute within the last 15 minutes."));
                    $note = Mage::helper('xtento_stockimport')->__('Please make sure to set up the cronjob as explained <a href="http://support.xtento.com/wiki/Setting_up_the_Magento_cronjob" target="_blank">here</a> and check the cron status 15 minutes after setting up the cronjob properly again.');
                }
            } else {
                $model->setCronjobStatus(Mage::helper('xtento_stockimport')->__("Cron status wasn't checked yet. Please check back in 30 minutes."));
                $note = Mage::helper('xtento_stockimport')->__('Please make sure to set up the cronjob as explained <a href="http://support.xtento.com/wiki/Setting_up_the_Magento_cronjob" target="_blank">here</a> and check the cron status 15 minutes after setting up the cronjob properly again.');
            }
        }
        $fieldset->addField('cronjob_status', 'text', array(
            'label' => Mage::helper('xtento_stockimport')->__('Cronjob Status'),
            'name' => 'cronjob_status',
            'disabled' => true,
            'note' => $note,
            'value' => $model->getCronjobStatus()
        ));

        $fieldset->addField('cronjob_enabled', 'select', array(
            'label' => Mage::helper('xtento_stockimport')->__('Enable Cronjob Import'),
            'name' => 'cronjob_enabled',
            'values' => Mage::getModel('adminhtml/system_config_source_yesno')->toOptionArray()
        ));

        $fieldset->addField('cronjob_frequency', 'select', array(
            'label' => Mage::helper('xtento_stockimport')->__('Import Frequency'),
            'name' => 'cronjob_frequency',
            'values' => Mage::getModel('xtento_stockimport/system_config_source_cron_frequency')->toOptionArray(),
            'note' => Mage::helper('xtento_stockimport')->__('How often should the import be executed?')
        ));

        $fieldset->addField('cronjob_custom_frequency', 'text', array(
            'label' => Mage::helper('xtento_stockimport')->__('Custom Import Frequency'),
            'name' => 'cronjob_custom_frequency',
            'note' => Mage::helper('xtento_stockimport')->__('A custom cron expression can be entered here. Make sure to set "Cronjob Frequency" to "Use custom frequency" if you want to enter a custom cron expression here. To set up multiple cronjobs, separate multiple cron expressions by a semi-colon ; Example: */5 * * * *;0 3 * * * '),
            'class' => 'validate-cron',
            'after_element_html' => $this->_getCronValidatorJs()
        ));

        $form->setValues($model->getData());
        $this->setForm($form);

        return parent::_prepareForm();
    }

    private function _getCronValidatorJs()
    {
        $errorMsg = Mage::helper('xtento_stockimport')->__('This is no valid cron expression.');
        $js = <<<EOT
<script>
Validation.add('validate-cron', '{$errorMsg}', function(v) {
     if (v == "") {
        return true;
     }
     return RegExp("^[-0-9,*/; ]+$","gi").test(v);
});
</script>
EOT;

        return $js;
    }
}