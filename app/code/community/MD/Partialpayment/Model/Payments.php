<?php
/**
* Magedelight
* Copyright (C) 2014 Magedelight <info@magedelight.com>
*
* NOTICE OF LICENSE
*
* This program is free software: you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation, either version 3 of the License, or
* (at your option) any later version.
*
* This program is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
* GNU General Public License for more details.
*
* You should have received a copy of the GNU General Public License
* along with this program. If not, see http://opensource.org/licenses/gpl-3.0.html.
*
* @category MD
* @package MD_Partialpayment
* @copyright Copyright (c) 2014 Mage Delight (http://www.magedelight.com/)
* @license http://opensource.org/licenses/gpl-3.0.html GNU General Public License,version 3 (GPL-3.0)
* @author Magedelight <info@magedelight.com>
*/
class MD_Partialpayment_Model_Payments extends Mage_Core_Model_Abstract
{
    protected $_order = null;
    protected $_orderItem = null;
    protected $_paymentSummary = null;
    
    public function _construct() {
        parent::_construct();
        $this->_init('md_partialpayment/payments');
    }
    
    protected function _beforeSave() {
        parent::_beforeSave();
        $this->setUpdatedAt(Mage::getSingleton('core/date')->gmtDate());
        if($this->getDueInstallments() == 0){
            $this->setDueAmount(0);
        }
    }
    
    protected function _beforeDelete() {
        parent::_beforeDelete();
        foreach($this->getPaymentSummaryCollection() as $summary){
            $summary->setId($summary->getId())->delete();
        }
    }
    
    public function getOrder()
    {
        if(is_null($this->_order)){
            $this->_order = Mage::getModel('sales/order')->loadByIncrementId($this->getOrderId());
        }
        return $this->_order;
    }
    
    public function getOrderItem()
    {
        if(is_null($this->_orderItem)){
            $this->_orderItem = $this->getOrder()->getItemById($this->getOrderItemId());
        }
        return $this->_orderItem;
    }
    
    public function getPaymentSummaryCollection()
    {
        if(is_null($this->_paymentSummary)){
            $this->_paymentSummary = Mage::getModel('md_partialpayment/summary')->getCollection()
                                            ->addFieldToFilter('payment_id',$this->getId());
        }
        return $this->_paymentSummary;
    }
    
    public function getPaymentsByOrderItem(Mage_Sales_Model_Order_Item $item){
        if($item instanceof Mage_Sales_Model_Order_Item){
            $payment = $this->getCollection()
                            ->addFieldToFilter('order_item_id',array('eq'=>$item->getId()));
            
            if($payment->count()){
                return $payment->getFirstItem();
            }else{
                return null;
            }
            
        }
        return null;
    }
    
    public function getLastPaidInstallmentId()
    {
        $activeSummary = $this->getPaymentSummaryCollection()
                                ->addFieldToFilter('status',array('eq'=>  MD_Partialpayment_Model_Summary::PAYMENT_SUCCESS))
                                ->getLastItem();
        
        if($activeSummary){
            return $activeSummary->getId();
        }else{
            return null;
        }
        
    }
    public function getNextPaidInstallmentId()
	{
		$allIds = $this->getPaymentSummaryCollection()->getAllIds();
		$lastPaid = $this->getLastPaidInstallmentId();
		
		$nextPaidId = $allIds[0];
		if($lastPaid){
			$key = array_search($lastPaid,$allIds);
			$nextPaidId = $allIds[$key + 1];
		}
		return $nextPaidId;
	}
}

