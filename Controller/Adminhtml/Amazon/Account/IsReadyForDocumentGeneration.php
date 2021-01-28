<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Account;

use Ess\M2ePro\Controller\Adminhtml\Amazon\Account;
use Ess\M2ePro\Controller\Adminhtml\Context;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Amazon\Account\IsReadyForDocumentGeneration
 */
class IsReadyForDocumentGeneration extends Account
{
    protected $scopeConfig;
    protected $storeManager;

    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory,
        Context $context
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager;

        parent::__construct($amazonFactory, $context);
    }

    public function execute()
    {
        $id = $this->getRequest()->getParam('account_id');
        $newStoreMode = $this->getRequest()->getParam('new_store_mode');
        $newStoreId = $this->getRequest()->getParam('new_store_id');

        /** @var \Ess\M2ePro\Model\Account $account */
        $account = $this->amazonFactory->getObjectLoaded('Account', $id, null, false);

        if ($id && $account === null) {
            $this->setAjaxContent('You should provide correct parameters.', false);
            return $this->getResult();
        }

        $result = true;

        $accountStoreMode = $account->getChildObject()->getSetting(
            'magento_orders_settings',
            ['listing', 'store_mode'],
            \Ess\M2ePro\Model\Amazon\Account::MAGENTO_ORDERS_LISTINGS_STORE_MODE_DEFAULT
        );
        $accountStoreId = $account->getChildObject()->getMagentoOrdersListingsStoreId();

        if ($accountStoreMode != $newStoreMode) {
            $accountStoreMode = $newStoreMode;
            $accountStoreId = $newStoreId;
        }

        if ($accountStoreMode == \Ess\M2ePro\Model\Amazon\Account::MAGENTO_ORDERS_LISTINGS_STORE_MODE_CUSTOM) {
            $storeData = $this->scopeConfig->getValue(
                'general/store_information',
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                $this->storeManager->getStore($accountStoreId)
            );

            if (empty($storeData['name']) ||
                empty($storeData['country_id']) ||
                empty($storeData['street_line1']) ||
                empty($storeData['city']) ||
                empty($storeData['postcode'])
            ) {
                $result = false;
            }
        } else {
            /** @var \Ess\M2ePro\Model\ResourceModel\Listing\Collection $listingCollection */
            $listingCollection = $this->activeRecordFactory->getObject('Listing')->getCollection();
            $listingCollection->addFieldToFilter('account_id', $account->getId());

            if ($listingCollection->getSize() > 0) {
                foreach ($listingCollection->getItems() as $listing) {
                    /** @var \Ess\M2ePro\Model\Listing $listing */
                    $storeData = $this->scopeConfig->getValue(
                        'general/store_information',
                        \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                        $this->storeManager->getStore($listing->getStoreId())
                    );

                    if (empty($storeData['name']) ||
                        empty($storeData['country_id']) ||
                        empty($storeData['street_line1']) ||
                        empty($storeData['city']) ||
                        empty($storeData['postcode'])
                    ) {
                        $result = false;
                        break;
                    }
                }
            } else {
                $storeData = $this->scopeConfig->getValue('general/store_information');

                if (empty($storeData['name']) ||
                    empty($storeData['country_id']) ||
                    empty($storeData['street_line1']) ||
                    empty($storeData['city']) ||
                    empty($storeData['postcode'])
                ) {
                    $result = false;
                }
            }
        }

        $this->setJsonContent(
            [
                'success' => true,
                'result' => $result
            ]
        );

        return $this->getResult();
    }
}
