<?php
/**
 * OnePica_AvaTax
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0), a
 * copy of which is available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * @category   OnePica
 * @package    OnePica_AvaTax
 * @author     OnePica Codemaster <codemaster@onepica.com>
 * @copyright  Copyright (c) 2009 One Pica, Inc.
 * @license    http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */

/**
 * Tax totals calculation model
 */
class OnePica_AvaTax_Model_Sales_Quote_Address_Total_Tax extends Mage_Sales_Model_Quote_Address_Total_Abstract
{

    /**
     * Class constructor
     */
    public function __construct() {
        $this->setCode('tax');
    }

    /**
     * Collect tax totals for quote address
     *
     * @param   Mage_Sales_Model_Quote_Address $address
     * @return  Mage_Tax_Model_Sales_Total_Quote
     */
    public function collect(Mage_Sales_Model_Quote_Address $address) {
        $this->_setAddress($address);
        parent::collect($address);
        
        $store = $address->getQuote()->getStore();
        $customer = $address->getQuote()->getCustomer();
        $calculator = Mage::getModel('avatax/avatax_estimate');
        
        $address->setTotalAmount($this->getCode(), 0);
        $address->setBaseTotalAmount($this->getCode(), 0);
        
        $address->setTaxAmount(0);
        $address->setBaseTaxAmount(0);
        $address->setShippingTaxAmount(0);
        $address->setBaseShippingTaxAmount(0);

    	foreach($address->getAllItems() as $item) {
            $item->setAddress($address);
    		$amount = $calculator->getItemTax($item);
    		$percent = $calculator->getItemRate($item);
    		
    		$item->setTaxAmount($amount);
    		$item->setBaseTaxAmount($amount);
    		$item->setTaxPercent($percent);

    		$item->setPriceInclTax($item->getPrice()+($amount/$item->getQty()));
    		$item->setBasePriceInclTax($item->getBasePrice()+($amount/$item->getQty()));
    		$item->setRowTotalInclTax($item->getRowTotal()+$amount);
    		$item->setBaseRowTotalInclTax($item->getBaseRowTotal()+$amount);
    		
    		if(!$calculator->isProductCalculated($item)) {
		        $this->_addAmount($amount);
		        $this->_addBaseAmount($amount);
    		}
    	}
    	
    	if($address->getAddressType() == Mage_Sales_Model_Quote_Address::TYPE_SHIPPING || $address->getUseForShipping()) {
			$shippingItem = new Varien_Object();
			$shippingItem->setId(Mage::helper('avatax')->getShippingSku($store->getId()));
			$shippingItem->setProductId(Mage::helper('avatax')->getShippingSku($store->getId()));
			$shippingItem->setAddress($address);
			$shippingTax = $calculator->getItemTax($shippingItem);

            $shippingAmt = $address->getTotalAmount('shipping');
            $baseShippingAmt = $address->getBaseTotalAmount('shipping');
	    	
	        $address->setShippingTaxAmount($shippingTax);
	        $address->setBaseShippingTaxAmount($shippingTax);
            $address->setShippingInclTax($shippingAmt + $shippingTax);
            $address->setBaseShippingInclTax($baseShippingAmt + $shippingTax);
            $address->setShippingTaxable($shippingTax ? $shippingAmt : 0);
            $address->setBaseShippingTaxable($shippingTax ? $baseShippingAmt : 0);
            $address->setIsShippingInclTax(false);

	        $this->_addAmount($shippingTax);
	        $this->_addBaseAmount($shippingTax);
    	}
        
        return $this;
    }

    /**
     * Add tax totals information to address object
     *
     * @param   Mage_Sales_Model_Quote_Address $address
     * @return  Mage_Tax_Model_Sales_Total_Quote
     */
    public function fetch(Mage_Sales_Model_Quote_Address $address) {
        $config = Mage::getSingleton('tax/config');
        $quote = $address->getQuote();
        $store  = $quote->getStore();
        $amount = $address->getTaxAmount();

        $fullInfo = array();
        $summary = Mage::getModel('avatax/avatax_estimate')->getSummary($address->getId());
        
        foreach($summary as $key=>$row) {
            $id = 'avatax-' . $key;
            $fullInfo[$id] = array(
                'rates' => array(array(
                    'code' => $row['name'],
                    'title' => $row['name'],
                    'percent' => $row['rate'],
                    'position' => $key,
                    'priority' => $key,
                    'rule_id' => 0
                )),
                'percent' => $row['rate'],
                'id' => $id,
                'process' => 0,
                'amount' => $row['amt'],
                'base_amount' => $row['amt']
            );
        }
        
        if (($amount!=0) || (Mage::helper('tax')->displayZeroTax($store))) {
	        $address->addTotal(array(
	            'code'  => $this->getCode(),
	            'title' => Mage::helper('tax')->__('Tax'),
                'full_info' => $fullInfo,
	            'value' => $amount,
	            'area'  => null
	        ));
        }

        /**
         * Modify subtotal
         */
        if ($config->displayCartSubtotalBoth($store) || $config->displayCartSubtotalInclTax($store)) {
            $subtotalInclTax = $address->getSubtotal()+$address->getTaxAmount()-$address->getShippingTaxAmount();
            $address->setSubtotalInclTax($subtotalInclTax);

            $address->addTotal(array(
                'code'      => 'subtotal',
                'title'     => Mage::helper('sales')->__('Subtotal'),
                'value'     => $subtotalInclTax,
                'value_incl_tax' => $subtotalInclTax,
                'value_excl_tax' => $address->getSubtotal(),
            ));
        }
        
        return $this;
    }
    
    
    /* BELOW ARE MAGE CORE PROPERTIES AND METHODS ADDED FOR OLDER VERSION COMPATABILITY */
    
    
    /**
     * Total Code name
     *
     * @var string
     */
    protected $_code;
    protected $_address = null;

    /**
     * Add total model amount value to address
     *
     * @param   float $amount
     * @return  Mage_Sales_Model_Quote_Address_Total_Abstract
     */
    protected function _addAmount($amount)
    {
        $this->_getAddress()->addTotalAmount($this->getCode(),$amount);
        return $this;
    }

    /**
     * Add total model base amount value to address
     *
     * @param   float $amount
     * @return  Mage_Sales_Model_Quote_Address_Total_Abstract
     */
    protected function _addBaseAmount($baseAmount)
    {
        $this->_getAddress()->addBaseTotalAmount($this->getCode(), $baseAmount);
        return $this;
    }

    /**
     * Set address shich can be used inside totals calculation
     *
     * @param   Mage_Sales_Model_Quote_Address $address
     * @return  Mage_Sales_Model_Quote_Address_Total_Abstract
     */
    protected function _setAddress(Mage_Sales_Model_Quote_Address $address)
    {
        $this->_address = $address;
        return $this;
    }

    /**
     * Get quote address object
     *
     * @throw   Mage_Core_Exception if address not declared
     * @return  Mage_Sales_Model_Quote_Address
     */
    protected function _getAddress()
    {
        if ($this->_address === null) {
            Mage::throwException(
                Mage::helper('sales')->__('Address model is not defined')
            );
        }
        return $this->_address;
    }
}
