<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Template\Shipping;

use Ess\M2ePro\Controller\Adminhtml\Ebay\Template;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Ebay\Template\Shipping\UpdateDiscountProfiles
 */
class UpdateDiscountProfiles extends Template
{
    //########################################

    public function execute()
    {
        $accountId = $this->getRequest()->getParam('account_id');
        $marketplaceId = $this->getRequest()->getParam('marketplace_id');

        /** @var $account \Ess\M2ePro\Model\Ebay\Account */
        $account = $this->ebayFactory->getCachedObjectLoaded('Account', $accountId)->getChildObject();
        $account->updateShippingDiscountProfiles($marketplaceId);

        $accountProfiles = $this->getHelper('Data')->jsonDecode($account->getData('ebay_shipping_discount_profiles'));

        $profiles = [];
        if (is_array($accountProfiles) && isset($accountProfiles[$marketplaceId]['profiles'])) {
            $helper = $this->getHelper('Data');
            foreach ($accountProfiles[$marketplaceId]['profiles'] as $profile) {
                $profiles[] = [
                    'type' => $helper->escapeHtml($profile['type']),
                    'profile_id' => $helper->escapeHtml($profile['profile_id']),
                    'profile_name' => $helper->escapeHtml($profile['profile_name'])
                ];
            }
        }

        $this->setJsonContent($profiles);
        return $this->getResult();
    }

    //########################################
}
