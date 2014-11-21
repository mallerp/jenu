<?php
/**
 * Gorilla AuthorizeNet CIM module
 *
 * @category     Gorilla
 * @copyright    Copyright (c) 2011-2012 Gorilla (http://www.gorillagroup.com)
 */
class Gorilla_AuthorizenetCim_Block_Info_Cc extends Mage_Payment_Block_Info_Cc
{
    protected function _getCcDetails()
    {
        $paymentProfile = false;
        if ($this->getInfo()->getAdditionalInformation('authorizenetcim_customer_id') && $this->getInfo()->getAdditionalInformation('authorizenetcim_payment_id'))
        {
            $paymentProfile = Mage::getModel('authorizenetcim/profile')
                ->getCustomerPaymentProfile($this->getInfo()->getAdditionalInformation('authorizenetcim_customer_id'), $this->getInfo()->getAdditionalInformation('authorizenetcim_payment_id'));
        }

        if ($paymentProfile)
            return $paymentProfile;
        else
            return false;
    }

    /**
     * Prepare credit card related payment info
     *
     * @param Varien_Object|array $transport
     * @return Varien_Object
     */
    protected function _prepareSpecificInformation($transport = null)
    {
        if (null !== $this->_paymentSpecificInformation) {
            return $this->_paymentSpecificInformation;
        }
        $transport = Mage_Payment_Block_Info::_prepareSpecificInformation($transport);
        $data = array();

        if ($paymentProfile = $this->_getCcDetails()) {

            if ($ccType = $this->getCcTypeName()) {
                if ($ccType == "N/A")
                {
                    $ccType = 'Stored';
                }
                $data[Mage::helper('authorizenetcim')->__('Credit Card Type')] = $ccType;
            }

            if ($ccNumber = $paymentProfile->payment->creditCard->cardNumber) {
                $data[Mage::helper('authorizenetcim')->__('Credit Card Number')] = sprintf('xxxx-%s', substr($ccNumber,-4));
            }

            return $transport->setData(array_merge($data, $transport->getData()));

        } else {

            if ($ccType = $this->getCcTypeName()) {
                $data[Mage::helper('authorizenetcim')->__('Credit Card Type')] = $ccType;
            }

            if ($this->getInfo()->getCcLast4()) {
                $data[Mage::helper('authorizenetcim')->__('Credit Card Number')] = sprintf('xxxx-%s', $this->getInfo()->getCcLast4());
            }

            return $transport->setData(array_merge($data, $transport->getData()));
        }


    }

    /**
     * Checkout progress information block flag
     *
     * @var bool
     */
    protected $_isCheckoutProgressBlockFlag = true;
    /**
     * Set block template
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('authorizenetcim/info/cc.phtml');
    }

    /**
     * Render as PDF
     *
     * @return string
     */
    public function toPdf()
    {
        $this->setTemplate('paygate/info/pdf.phtml');
        return $this->toHtml();
    }

    /**
     * Retrieve card info object
     *
     * @return mixed
     */
    public function getInfo()
    {
        if ($this->hasCardInfoObject()) {
            return $this->getCardInfoObject();
        }
        return parent::getInfo();
    }

    /**
     * Set checkout progress information block flag
     * to avoid showing credit card information from payment quote
     * in Previously used card information block
     *
     * @param bool $flag
     * @return Mage_Paygate_Block_Authorizenet_Info_Cc
     */
    public function setCheckoutProgressBlock($flag)
    {
        $this->_isCheckoutProgressBlockFlag = $flag;
        return $this;
    }

    /**
     * Retrieve credit cards info
     *
     * @return array
     */
    public function getCards()
    {
        $cardsData = $this->getMethod()->getCardsStorage()->getCards();
        $cards = array();

        if (is_array($cardsData)) {
            foreach ($cardsData as $cardInfo) {
                $data = array();

                if ($cardInfo->getProcessedAmount()) {
                    $amount = Mage::helper('core')->currency($cardInfo->getProcessedAmount(), true, false);
                    $data[Mage::helper('authorizenetcim')->__('Processed Amount')] = $amount;
                }
                if ($cardInfo->getBalanceOnCard() && is_numeric($cardInfo->getBalanceOnCard())) {
                    $balance = Mage::helper('core')->currency($cardInfo->getBalanceOnCard(), true, false);
                    $data[Mage::helper('authorizenetcim')->__('Remaining Balance')] = $balance;
                }
                $this->setCardInfoObject($cardInfo);
                $cards[] = array_merge($this->getSpecificInformation(), $data);
                $this->unsCardInfoObject();
                $this->_paymentSpecificInformation = null;

            }
        }

        if ($this->getInfo()->getCcType() && $this->_isCheckoutProgressBlockFlag && count($cards) == 0) {
            $cards[] = $this->getSpecificInformation();
        }

        return $cards;
    }
}