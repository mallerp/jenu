<?php
class MD_Partialpayment_Model_Payment_Paypal_Standard extends MD_Partialpayment_Model_Payment_Abstract
{
    
    protected $_configModel = 'paypal/config';
    protected $_requestArea = '';
    protected $_p = null;
    protected $_limit = null;
    
    
    public function getConfig()
    {
        $config = Mage::getModel('paypal/config',array(Mage_Paypal_Model_Config::METHOD_WPS,Mage::app()->getStore()->getId()));
        return $config;
    }
    
    public function setPageNo($p){
        $this->_p = $p;
        return $this;
    }
    
    public function setPagerLimit($limit){
        $this->_limit = $limit;
        return $this;
    }
    
    public function getPageNo(){
        return $this->_p;
    }
    public function getPagerLimit(){
        return $this->_limit;
    }
    
    public function setPaymentRequestArea($area = null){
        if(!is_null($area)){
            $this->_requestArea = $area.'_';
        }
        return $this;
    }
    
    public function getPaymentRequestArea(){
        return $this->_requestArea;
    }
    
    public function getConfigData($key, $default = null)
    {
        /*if ($this->getConfig()->hasData($key)) {
            return $this->getConfig()->getData($key);
        }*/
        return $this->getConfig()->$key ? $this->getConfig()->$key : $default;
    }
    
    public function getRequestObject()
    {
        $area = $this->getPaymentRequestArea();
        $p = $this->getPageNo();
        $limit = $this->getPagerLimit();
        $request = new Varien_Object();
        $order = $this->getOrder();
        $summary = $this->getSummary();
        $payments = $this->getPayments();
        $cancelUrl = ($area == 'adminhtml_') ? Mage::helper('adminhtml')->getUrl('md_partialpayment/'.$area.'summary/paypalCancel',array('payment_id'=>$payments->getId(),'summary_id'=>$summary->getId())): Mage::getUrl('md_partialpayment/summary/paypalCancel',array('payment_id'=>$payments->getId(),'summary_id'=>$summary->getId(),'p'=>$p,'limit'=>$limit));
        $successUrl = ($area == 'adminhtml_') ? Mage::helper('adminhtml')->getUrl('md_partialpayment/'.$area.'summary/paypalSuccess',array('payment_id'=>$payments->getId(),'summary_id'=>$summary->getId())): Mage::getUrl('md_partialpayment/summary/paypalSuccess',array('payment_id'=>$payments->getId(),'summary_id'=>$summary->getId(),'p'=>$p,'limit'=>$limit));
        $isOrderVirtual = $order->getIsVirtual();
        $address = $isOrderVirtual ? $order->getBillingAddress() : $order->getShippingAddress();
        $request->setBusiness($this->getConfigData('business_account'))
                ->setInvoice($order->getIncrementId().'_'.$summary->getId())
                ->setCurrencyCode($order->getBaseCurrencyCode())
                ->setPaymentaction(strtolower($this->getConfigData('payment_action')))
                ->setReturn($successUrl)
                ->setCancelReturn($cancelUrl)
                ->setNotifyUrl(Mage::getUrl('md_partialpayment/summary/paypalIpn',array('payment_id'=>$payments->getId(),'summary_id'=>$summary->getId())))
                ->setBn('Magento_Cart_Community')
                ->setItemName(Mage::app()->getStore()->getName())
                ->setLc(Mage::app()->getLocale()->getLocaleCode())
                ->setCharset('utf-8')
                ->setAmount(number_format($summary->getAmount(),2))
                ->setTax(number_format(0,2))
                ->setShipping(number_format(0,2))
                ->setDiscountAmount(number_format(0,2))
                ->setCmd('_ext-enter')
                ->setRedirectCmd('_xclick')
                ->setCity($address->getCity())
                ->setCountry($address->getCountryId())
                ->setEmail($order->getCustomerEmail())
                ->setFirstName($order->getCustomerFirstname())
                ->setLastName($order->getCustomerLastname())
                ->setZip($address->getPostcode())
                ->setState($address->getRegionCode())
                ->setAddress1($address->getStreet(1))
                //->setAddress2($address->getStreet(2))
                ->setRedirectUrl($this->getConfig()->getPaypalUrl())
                ->setAddressOverride(1);
        
        return $request;
    }
    
    public function processIpnRequest($data, $summaryId = null, $paymentId = null){
        $paymentStatus = $this->_filterPaymentStatus($data['payment_status']);
        if(!is_null($summaryId) && !is_null($paymentId)){
            
            $status = 0;
            $amount = 0;
            $installmentCount = 0;
            $histryString = '';
            $summary = Mage::getModel('md_partialpayment/summary')->load($summaryId);
            $payments = $summary->getPayments();
            $order = $payments->getOrder();
            $formatedGrossAmount = strip_tags($order->formatPrice($data['mc_gross']));
            $failed = false;
            switch ($paymentStatus) {
                case Mage_Paypal_Model_Info::PAYMENTSTATUS_COMPLETED:
                                                                    $status = 1;
                                                                    $installmentCount = 1;
                                                                    $amount = $data['mc_gross'];
                                                                    $histryString = sprintf('IPN "%s". Registered notification about captured amount of %s. Transaction ID: "%s".',$data['payment_status'], $formatedGrossAmount, $data['txn_id']);
                                                                    break;
                case Mage_Paypal_Model_Info::PAYMENTSTATUS_DENIED:
                                                                    $status = 4;
                                                                    $failed = true;
                                                                    $installmentCount = 0;
                                                                    $amount = 0;
                                                                    $histryString = sprintf('IPN "%s". Payment Denied to capture amount of %s. Transaction ID: "%s".',$data['payment_status'], $formatedGrossAmount, $data['txn_id']);
                                                                 break;
                case Mage_Paypal_Model_Info::PAYMENTSTATUS_FAILED:
                                                                    $status = 4;
                                                                    $failed = true;
                                                                    $installmentCount = 0;
                                                                    $amount = 0;
                                                                    $histryString = sprintf('IPN "%s". Failed to capture amount of %s. Transaction ID: "%s".',$data['payment_status'], $formatedGrossAmount, $data['txn_id']);
                                                                break;
                case Mage_Paypal_Model_Info::PAYMENTSTATUS_PENDING:
                case Mage_Paypal_Model_Info::PAYMENTSTATUS_PROCESSED:
                                                                    $status = 0;
                                                                    $installmentCount = 0;
                                                                    $amount = 0;
                                                                    $histryString = sprintf('IPN "%s". Pending / Process to capture amount of %s. Transaction ID: "%s".',$data['payment_status'], $formatedGrossAmount, $data['txn_id']);
                                                                    break;
                case Mage_Paypal_Model_Info::PAYMENTSTATUS_EXPIRED:
                                                                    $status = 2;
                                                                    $installmentCount = 0;
                                                                    $amount = 0;
                                                                break;
                case Mage_Paypal_Model_Info::PAYMENTSTATUS_VOIDED:
                                                                    $status = 2;
                                                                    $installmentCount = 0;
                                                                    $amount = 0;
                                                                break;
                default:
                        throw new Exception("Cannot handle payment status '{$paymentStatus}'.");
            }
            $summary->setPaidDate(date('Y-m-d'))
                    ->setStatus($status)
                    ->setTransactionId($data['txn_id'])
                    ->setPaymentMethod(Mage_Paypal_Model_Config::METHOD_WPS)
                    ->setPaymentFailCount($summary->getPaymentFailCount() + $installmentCount)
                    ->setTransactionDetails(serialize($data));
            $payments->setPaidAmount($payments->getPaidAmount() + $amount)
                    ->setDueAmount(max(0,($payments->getDueAmount() - $amount)))
                    ->setLastInstallmentDate(date('Y-m-d'))
                    ->setPaidInstallments($payments->getPaidInstallments() + $installmentCount)
                    ->setDueInstallments(max(0,($payments->getDueInstallments() - $installmentCount)))
                    ->setUpdatedAt(Mage::getSingleton('core/date')->gmtDate());
            if($payments->getDueInstallments() > 0){
                $orderDueAmount = max(0,($order->getTotalDue() - $amount));
                $baseOrderDueAmount = max(0,($order->getBaseTotalDue() - $amount));
            }else{
                    $orderDueAmount = 0;
                    $baseOrderDueAmount = 0;
            }
            $order->setTotalPaid($order->getTotalPaid() + $amount)
                        ->setBaseTotalPaid($order->getBaseTotalPaid() + $amount)
                        ->setTotalDue($orderDueAmount)
                        ->setBaseTotalDue($baseOrderDueAmount);

            if(strlen($histryString) > 0){
                $order->addStatusHistoryComment($histryString);
            }

            $transaction = Mage::getModel('core/resource_transaction');
            $transaction->addObject($summary);
            $transaction->addObject($payments);
            $transaction->addObject($order);
            try{
                $transaction->save();
                $summary->sendStatusPaymentEmail(true,true);
            }catch(Exception $e){
                Mage::getSingleton('core/session')->addError($e->getMessage());
            }
            /*if($failed){
                $summary->sendFailedPaymentEmail();
            }*/
        }else{
            $id = $data['invoice'];
			
            $order = Mage::getModel('sales/order')->loadByIncrementId($id);
            $paymentStatus = $this->_filterPaymentStatus($data['payment_status']);
			
                switch($paymentStatus){
                    case Mage_Paypal_Model_Info::PAYMENTSTATUS_COMPLETED:$status = MD_Partialpayment_Model_Summary::PAYMENT_SUCCESS;$installmentCount = 1;$amount = $data['mc_gross'];break;
                    case Mage_Paypal_Model_Info::PAYMENTSTATUS_DENIED:
                    case Mage_Paypal_Model_Info::PAYMENTSTATUS_FAILED:$status = MD_Partialpayment_Model_Summary::PAYMENT_DECLINED;$failed = true;$installmentCount = 0;$amount = 0;break;
                    case Mage_Paypal_Model_Info::PAYMENTSTATUS_PENDING:
                    case Mage_Paypal_Model_Info::PAYMENTSTATUS_PROCESSED:$status = MD_Partialpayment_Model_Summary::PAYMENT_PROCESS;$installmentCount = 0;$amount = 0;break;
                    case Mage_Paypal_Model_Info::PAYMENTSTATUS_EXPIRED:$status = MD_Partialpayment_Model_Summary::PAYMENT_FAIL;$installmentCount = 0;$amount = 0;break;
                    case Mage_Paypal_Model_Info::PAYMENTSTATUS_VOIDED:$status = MD_Partialpayment_Model_Summary::PAYMENT_FAIL;$installmentCount = 0;$amount = 0;break;
                }
                foreach($order->getAllVisibleItems() as $item){
                    if($item->getPartialpaymentOptionSelected() == 1){
                        $existsPayments = Mage::getModel('md_partialpayment/payments')->getPaymentsByOrderItem($item);
                        if(!$existsPayments){
                            Mage::dispatchEvent('md_partialpayment_order_item_payment_placed',array('order_item'=>$item));
                        }else{
							
                            $summaryStatusProcessing = $existsPayments->getPaymentSummaryCollection()->getFirstItem();
                            if($status === MD_Partialpayment_Model_Summary::PAYMENT_SUCCESS){
                                $amount = $summaryStatusProcessing->getAmount();
                            }
							
                            $existsPayments->setPaidAmount($existsPayments->getPaidAmount() + $amount)
                              ->setDueAmount(max(0,($existsPayments->getDueAmount() - $amount)))
                              ->setPaidInstallments($existsPayments->getPaidInstallments() + $installmentCount)
                              ->setDueInstallments(max(0,($existsPayments->getDueInstallments() - $installmentCount)));
                            
                            $summaryStatusProcessing->setStatus($status);
                            $transaction = Mage::getModel('core/resource_transaction');
                            $transaction->addObject($summaryStatusProcessing);
                            $transaction->addObject($existsPayments);
                            try{
                                $transaction->save();
                                $summaryStatusProcessing->sendStatusPaymentEmail(true,true);
                            }catch(Exception $e){
                                Mage::getSingleton('core/session')->addError($e->getMessage());
                            }
                        }
                    }
                }
        }
    }
    
    protected function _filterPaymentStatus($ipnPaymentStatus)
    {
        switch ($ipnPaymentStatus) {
            case 'Created': // break is intentionally omitted
            case 'Completed': return Mage_Paypal_Model_Info::PAYMENTSTATUS_COMPLETED;
            case 'Denied':    return Mage_Paypal_Model_Info::PAYMENTSTATUS_DENIED;
            case 'Expired':   return Mage_Paypal_Model_Info::PAYMENTSTATUS_EXPIRED;
            case 'Failed':    return Mage_Paypal_Model_Info::PAYMENTSTATUS_FAILED;
            case 'Pending':   return Mage_Paypal_Model_Info::PAYMENTSTATUS_PENDING;
            case 'Refunded':  return Mage_Paypal_Model_Info::PAYMENTSTATUS_REFUNDED;
            case 'Reversed':  return Mage_Paypal_Model_Info::PAYMENTSTATUS_REVERSED;
            case 'Canceled_Reversal': return Mage_Paypal_Model_Info::PAYMENTSTATUS_UNREVERSED;
            case 'Processed': return Mage_Paypal_Model_Info::PAYMENTSTATUS_PROCESSED;
            case 'Voided':    return Mage_Paypal_Model_Info::PAYMENTSTATUS_VOIDED;
        }
        return '';
    }
    
    public function getDetails()
    {
        $transactionDetails = unserialize($this->getSummary()->getTransactionDetails());
        
        $details = array();
        $helper = Mage::helper('md_partialpayment');
        if(is_array($transactionDetails) && count($transactionDetails) > 0){
            if(array_key_exists('payer_id',$transactionDetails)){
                $details[] = '<b>'.$helper->__('Payer ID').':</b> '.$transactionDetails['payer_id'];
            }
            
            if(array_key_exists('payer_email',$transactionDetails)){
                $details[] = '<b>'.$helper->__('Payer Email').':</b> '.$transactionDetails['payer_email'];
            }
            
            if(array_key_exists('payer_status',$transactionDetails)){
                $details[] = '<b>'.$helper->__('Payer Status').':</b> '.$transactionDetails['payer_status'];
            }
            
            if(array_key_exists('protection_eligibility',$transactionDetails)){
                $details[] = '<b>'.$helper->__('Merchant Protection Eligibility').':</b> '.$transactionDetails['protection_eligibility'];
            }
            
            if(array_key_exists('txn_id',$transactionDetails)){
                $details[] = '<b>'.$helper->__('Last Transaction ID').':</b> '.$transactionDetails['txn_id'];
            }
            
            if(array_key_exists('mc_currency',$transactionDetails)){
                $details[] = $helper->__('Order was placed using <b>%s</b>', $transactionDetails['mc_currency']);
            }
        }
        
        return $details;
    }
    
    public function getResponseText()
    {
        $transactionDetails = unserialize($this->getSummary()->getTransactionDetails());
        $message = null;
        if(count($transactionDetails) > 0 && array_key_exists('payment_status',$transactionDetails)){
            $paymentStatus = $this->_filterPaymentStatus($transactionDetails['payment_status']);
            $helper = Mage::helper('paypal');
            $info = Mage::getModel('paypal/info');
            $reasonComment = $info->explainReasonCode($transactionDetails['reason_code']);
            $order = $this->getSummary()->getPayments()->getOrder();
            $amount = strip_tags($order->formatPrice($transactionDetails['mc_gross']));
            switch($paymentStatus){
                case Mage_Paypal_Model_Info::PAYMENTSTATUS_COMPLETED:
                    $message = $helper->__('IPN "%s".Registered notification about captured amount of %s.Transaction ID: "%s".',$transactionDetails['payment_status'],$amount,$transactionDetails['txn_id']);    
                    break;
                case Mage_Paypal_Model_Info::PAYMENTSTATUS_DENIED:
                    $message = $helper->__('IPN "%s".Registered notification about denied payment amount of %s.Transaction ID: "%s".',$transactionDetails['payment_status'],$amount,$transactionDetails['txn_id']);
                    break;
                case Mage_Paypal_Model_Info::PAYMENTSTATUS_FAILED:
                    $message = $helper->__('IPN "%s".Transaction ID: "%s".',$transactionDetails['payment_status'],$transactionDetails['txn_id']);
                    break;
                case Mage_Paypal_Model_Info::PAYMENTSTATUS_PENDING:
                    $pandingReason = $info->explainPendingReason($transactionDetails['pending_reason']);
                    $message = $helper->__('IPN "%s".%s.Transaction ID: "%s".',$transactionDetails['payment_status'],$pandingReason,$transactionDetails['txn_id']);
                    break;
                case Mage_Paypal_Model_Info::PAYMENTSTATUS_REFUNDED:
                    $message = $helper->__('IPN "%s".%s.Transaction ID: "%s".',$transactionDetails['payment_status'],$reasonComment,$transactionDetails['txn_id']);
                    break;
                case Mage_Paypal_Model_Info::PAYMENTSTATUS_REVERSED:
                case Mage_Paypal_Model_Info::PAYMENTSTATUS_UNREVERSED:
                    $message = $helper->__('IPN "%s". %s Transaction amount %s. Transaction ID: "%s"',$transactionDetails['payment_status'],$reasonComment,$transactionDetails['txn_id']);
                    break;
                case Mage_Paypal_Model_Info::PAYMENTSTATUS_PROCESSED:
                    $message = $helper->__('IPN "%s".',$transactionDetails['payment_status']);
                    break;
                case Mage_Paypal_Model_Info::PAYMENTSTATUS_VOIDED:
                case Mage_Paypal_Model_Info::PAYMENTSTATUS_EXPIRED:
                    $message = $helper->__('IPN "%s".Registered a Void notification.',$transactionDetails['payment_status']);
                    break;
            }
            return $message;
        }else{
           return "Unsuccessfull payment due to some error occured."; 
        }
        
    }
}

