<?php
/**
 * Created by PhpStorm.
 * User: rtolentino
 * Date: 2/13/15
 * Time: 1:51 PM
 */

class Jenu_GoogleAnalytics_Block_Ga extends Mage_GoogleAnalytics_Block_Ga
{

    protected function _getPageTrackingCodeUniversal($accountId)
    {
        $visitorData = Mage::getSingleton('core/session')->getVisitorData();
        $loggedInId = $visitorData['visitor_id'];
        return "
        var customUserId;
        customUserId = '{$loggedInId}';
        ga('create', '{$this->jsQuoteEscape($accountId)}', {'userId': customUserId});
        ga('create', '{$this->jsQuoteEscape($accountId)}', 'auto');
        " . $this->_getAnonymizationCode() . "
        ga('require', 'displayfeatures');
        ga('set', 'dimension1', customUserId);
        ga('send', 'pageview');
        ";
    }

}