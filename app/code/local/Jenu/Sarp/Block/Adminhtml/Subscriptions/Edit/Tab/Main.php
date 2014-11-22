<?php
/**
* aheadWorks Co.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the EULA
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://ecommerce.aheadworks.com/AW-LICENSE-ENTERPRISE.txt
 *
 * =================================================================
 *                 MAGENTO EDITION USAGE NOTICE
 * =================================================================
 * This package designed for Magento ENTERPRISE edition
 * aheadWorks does not guarantee correct work of this extension
 * on any other Magento edition except Magento ENTERPRISE edition.
 * aheadWorks does not provide extension support in case of
 * incorrect edition usage.
 * =================================================================
 *
 * @category   AW
 * @package    AW_Sarp
 * @version    1.7.0
 * @copyright  Copyright (c) 2010-2012 aheadWorks Co. (http://www.aheadworks.com)
 * @license    http://ecommerce.aheadworks.com/AW-LICENSE-ENTERPRISE.txt
 */

class Jenu_Sarp_Block_Adminhtml_Subscriptions_Edit_Tab_Main extends AW_Sarp_Block_Adminhtml_Subscriptions_Edit_Tab_Main
{
    protected function _prepareForm()
    {
        $form = new Varien_Data_Form();
        $this->setForm($form);

        $fieldset = $form->addFieldset('main_details', array('legend' => $this->__('Main Details')));
        $fieldset->addField('id', 'hidden', array(
                                                 'required' => false,
                                                 'name' => 'id'
                                            ));


        if (
            $this->getSubscription()->getStatus() == AW_Sarp_Model_Subscription::STATUS_CANCELED &&
            $this->getSubscription()->getOrder()->getPayment() &&
            $this->getSubscription()->getOrder()->getPayment()->getMethod() == 'paypal_direct'
        ) {
            $label = '<br/><span style="color:red;">' . $this->__("Subscription with this payment method can't be re-activated") . "</span>";
            $disabled = true;
        } else {
            $label = false;
            $disabled = false;
        }

        $status = $fieldset->addField('status', 'select', array(
                                                     'required' => true,
                                                     'name' => 'status',
                                                     'after_element_html' => $label,
                                                     'disabled' => $disabled,
                                                     'label' => 'Status',
                                                     'options' => Mage::getModel('sarp/source_subscription_status')->getGridOptions()

                                                ));


        $fieldset->addField('period_type', 'select', array(
                                                          'name' => 'period_type',
                                                          'disabled' => true,
                                                          'label' => 'Period',
                                                          'options' => Mage::getModel('sarp/source_subscription_periods')->getGridOptions()

                                                     ));
        
        $date = $fieldset->addField('date_canceled', 'date', array(
                                                          'name' => 'date_canceled',
                                                          'label' => 'Subscription cancel date',
                                                          'image' => $this->getSkinUrl('images/grid-cal.gif'), 
                                                          //'input_format' => AW_Sarp_Model_Subscription::DB_DATETIME_FORMAT, 
                                                          //'format' => Mage::app()->getLocale()->getDateFormat(Mage_Core_Model_Locale::FORMAT_TYPE_MEDIUM),
                                                          'format' => AW_Sarp_Model_Subscription::DB_DATE_FORMAT, 
                                                     ));
        
        $this->setChild('form_after', $this->getLayout()->createBlock('adminhtml/widget_form_element_dependence')
             ->addFieldMap($status->getHtmlId(), $status->getName())
             ->addFieldMap($date->getHtmlId(), $date->getName())
             ->addFieldDependence($date->getName(), $status->getName(), -1) );
        
        if (Mage::getSingleton('adminhtml/session')->getFormData()) {
            $data = Mage::getSingleton('adminhtml/session')->getFormData();
            $form->setValues($data);
        } elseif ($this->getSubscription()) {
            $form->setValues($this->getSubscription()->getData());
        }

        return $this;
    }
}
