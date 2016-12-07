<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Account\PickupStore;

class GetRegions extends \Ess\M2ePro\Controller\Adminhtml\Ebay\Account
{
    //########################################

    public function execute()
    {
        $countryCode = $this->getRequest()->getParam('country_code');

        $this->setAjaxContent(json_encode(
            $this->getHelper('Magento')->getRegionsByCountryCode($countryCode)
        ), false);
        return $this->getResult();
    }

    //########################################
}