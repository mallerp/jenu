<?php
/**
 * Mirasvit
 *
 * This source file is subject to the Mirasvit Software License, which is available at http://mirasvit.com/license/.
 * Do not edit or add to this file if you wish to upgrade the to newer versions in the future.
 * If you wish to customize this module for your needs.
 * Please refer to http://www.magentocommerce.com for more information.
 *
 * @category  Mirasvit
 * @package   Follow Up Email
 * @version   1.0.23
 * @build     667
 * @copyright Copyright (C) 2016 Mirasvit (http://mirasvit.com/)
 */


class Mirasvit_EmailReport_Model_Aggregated extends Mage_Core_Model_Abstract
{
    protected function _construct()
    {
        $this->_init('emailreport/aggregated');
    }

    public function aggregate($date)
    {
        $this->getResource()->aggregate($date);

        return $this;
    }

    public function aggregateAll()
    {
        $this->getResource()->aggregate();

        return $this;
    }
}