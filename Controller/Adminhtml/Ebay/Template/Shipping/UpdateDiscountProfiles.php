<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Template\Shipping;

use Ess\M2ePro\Controller\Adminhtml\Ebay\Template;

class UpdateDiscountProfiles extends Template
{
    /** @var \Ess\M2ePro\Helper\Data */
    private $dataHelper;

    public function __construct(
        \Ess\M2ePro\Helper\Data $dataHelper,
        \Ess\M2ePro\Model\Ebay\Template\Manager $templateManager,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $ebayFactory,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($templateManager, $ebayFactory, $context);

        $this->dataHelper = $dataHelper;
    }

    public function execute()
    {
        $accountId = $this->getRequest()->getParam('account_id');
        $marketplaceId = $this->getRequest()->getParam('marketplace_id');

        /** @var \Ess\M2ePro\Model\Ebay\Account $account */
        $account = $this->ebayFactory->getCachedObjectLoaded('Account', $accountId)->getChildObject();
        $account->updateShippingDiscountProfiles($marketplaceId);

        $accountProfiles = $this->getHelper('Data')->jsonDecode($account->getData('ebay_shipping_discount_profiles'));

        $profiles = [];
        if (is_array($accountProfiles) && isset($accountProfiles[$marketplaceId]['profiles'])) {
            foreach ($accountProfiles[$marketplaceId]['profiles'] as $profile) {
                $profiles[] = [
                    'type' => $this->dataHelper->escapeHtml($profile['type']),
                    'profile_id' => $this->dataHelper->escapeHtml($profile['profile_id']),
                    'profile_name' => $this->dataHelper->escapeHtml($profile['profile_name'])
                ];
            }
        }

        $this->setJsonContent($profiles);
        return $this->getResult();
    }
}
