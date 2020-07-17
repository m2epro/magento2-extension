<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Product\Category\Settings;

use \Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Product\Category\Settings;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Product\Category\Settings\StepTwoReset
 */
class StepTwoReset extends Settings
{
    //########################################

    public function execute()
    {
        $sessionData = $this->getSessionValue($this->getSessionDataKey());
        !$sessionData && $sessionData = [];

        foreach ($this->getRequestIds('products_id') as $id) {
            $sessionData[$id] = [];
        }

        $this->setSessionValue($this->getSessionDataKey(), $sessionData);

        return $this->getResult();
    }

    //########################################
}
