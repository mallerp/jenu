<?php
/**
 * Gorilla AuthorizeNet CIM module
 *
 * @category     Gorilla
 * @copyright    Copyright (c) 2011-2012 Gorilla (http://www.gorillagroup.com)
 */
class Gorilla_AuthorizenetCim_Model_Adminhtml_Sales_Order_Create extends Mage_Adminhtml_Model_Sales_Order_Create
{
    /**
     * Create new order
     *
     * @return Mage_Sales_Model_Order
     */
    public function createOrder()
    {
        if ($this->getQuote()->getPayment()->getMethod() == 'authorizenetcim'){
            $this->_prepareCustomer();
            $this->_validate();
            $quote = $this->getQuote();
            $this->_prepareQuoteItems();

            $service = Mage::getModel('sales/service_quote', $quote);
            if ($this->getSession()->getOrder()->getId()) {
                $oldOrder = $this->getSession()->getOrder();
                $originalId = $oldOrder->getOriginalIncrementId();
                if (!$originalId) {
                    $originalId = $oldOrder->getIncrementId();
                }
                $orderData = array(
                    'original_increment_id'     => $originalId,
                    'relation_parent_id'        => $oldOrder->getId(),
                    'relation_parent_real_id'   => $oldOrder->getIncrementId(),
                    'edit_increment'            => $oldOrder->getEditIncrement()+1,
                    'increment_id'              => $originalId.'-'.($oldOrder->getEditIncrement()+1)
                );
                $quote->setReservedOrderId($orderData['increment_id']);
                $service->setOrderData($orderData);
            }

            $order = $service->submit();
            if ((!$quote->getCustomer()->getId() || !$quote->getCustomer()->isInStore($this->getSession()->getStore())) && !Mage::app()->getStore()->isAdmin()) {
                $quote->getCustomer()->setCreatedAt($order->getCreatedAt());
                $quote->getCustomer()
                    ->save()
                    ->sendNewAccountEmail('registered', '', $quote->getStoreId());;
            }
            if ($this->getSession()->getOrder()->getId()) {
                $oldOrder = $this->getSession()->getOrder();

                $this->getSession()->getOrder()->setRelationChildId($order->getId());
                $this->getSession()->getOrder()->setRelationChildRealId($order->getIncrementId());
                $this->getSession()->getOrder()->cancel()
                    ->save();
                $order->save();
            }
            if ($this->getSendConfirmation()) {
                $order->sendNewOrderEmail();
            }

            Mage::dispatchEvent('checkout_submit_all_after', array('order' => $order, 'quote' => $quote));

            return $order;
        } else {
            return parent::createOrder();
        }

    }

}
