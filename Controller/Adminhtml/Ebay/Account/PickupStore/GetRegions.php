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
        $countryCode = $this->getRequest()->getParam('country_code');

        $this->setJsonContent(
            $this->getHelper('Magento')->getRegionsByCountryCode($countryCode)
        );
        return $this->getResult();
    }

    //########################################
}
