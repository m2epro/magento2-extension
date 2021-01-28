<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Account\PickupStore;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Ebay\Account\PickupStore\GetRegions
 */
class GetRegions extends \Ess\M2ePro\Controller\Adminhtml\Ebay\Account
{
    //########################################

    public function execute()
    {
        $regions = [];

        if ($countryCode = $this->getRequest()->getParam('country_code')) {
            $regions = $this->getHelper('Magento')->getRegionsByCountryCode($countryCode);
        }

        $this->setJsonContent($regions);

        return $this->getResult();
    }

    //########################################
}
