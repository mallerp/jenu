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
class MD_Partialpayment_Block_Adminhtml_Catalog_Product_Edit_Tab_Partialpayment extends Mage_Adminhtml_Block_Widget_Form
{
    public function __construct()
    {
        parent::__construct();
        $this->setTemplate('md/partialpayment/catalog/product/edit/tab/partialpayment.phtml');
    }
    
    public function getPartialPaymentOption()
    {
        $product = Mage::registry('product');
        $option = null;
        if($product && $product->getId()){
            $option = Mage::getModel('md_partialpayment/options')->getOptionByProduct($product);
        }
        return $option;
    }
    
    public function getCustomerGroups()
    {
        $groups = array();
        
            $groups = Mage::getModel('md_partialpayment/system_config_source_groups')->toOptionArray();
            return $groups;
    }  
    
    public function getFrequency()
    {
        return Mage::getModel('md_partialpayment/system_config_source_frequency')->toOptionArray();
    }
}

