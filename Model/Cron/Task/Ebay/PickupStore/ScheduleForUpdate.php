<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Cron\Task\Ebay\PickupStore;

class ScheduleForUpdate extends \Ess\M2ePro\Model\Cron\Task\AbstractModel
{
    const NICK = 'ebay/pickup_store/schedule_for_update';

    const MAX_AFFECTED_ITEMS_COUNT = 10000;

    /** @var \Ess\M2ePro\Helper\Component\Ebay\PickupStore */
    private $componentEbayPickupStore;

    public function __construct(
        \Ess\M2ePro\Helper\Component\Ebay\PickupStore $componentEbayPickupStore,
        \Ess\M2ePro\Helper\Data $helperData,
        \Magento\Framework\Event\Manager $eventManager,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Factory $parentFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Cron\Task\Repository $taskRepo,
        \Magento\Framework\App\ResourceConnection $resource
    ) {
        parent::__construct(
            $helperData,
            $eventManager,
            $parentFactory,
            $modelFactory,
            $activeRecordFactory,
            $helperFactory,
            $taskRepo,
            $resource
        );

        $this->componentEbayPickupStore = $componentEbayPickupStore;
    }

    public function performActions()
    {
        $accounts = $this->componentEbayPickupStore->getEnabledAccounts();

        if (empty($accounts)) {
            return;
        }

        foreach ($accounts as $account) {
            /** @var \Ess\M2ePro\Model\Account $account */

            $this->getOperationHistory()->addText('Starting Account "' . $account->getTitle() . '"');

            $this->getOperationHistory()->addTimePoint(
                __METHOD__ . 'process' . $account->getId(),
                'Process Account ' . $account->getTitle()
            );

            try {
                $this->processAccount($account);
            } catch (\Exception $exception) {
                $message = $this->getHelper('Module\Translation')->__(
                    'The "Prepare Data" Action for eBay Account: "%account%" was completed with error.',
                    $account->getTitle()
                );

                $this->processTaskAccountException($message, __FILE__, __LINE__);
                $this->processTaskException($exception);
            }

            $this->getOperationHistory()->saveTimePoint(__METHOD__ . 'process' . $account->getId());
        }
    }

    //########################################

    protected function processAccount(\Ess\M2ePro\Model\Account $account)
    {
        $collection = $this->activeRecordFactory->getObject('Ebay_Listing_Product_PickupStore')->getCollection();
        $collection->addFieldToFilter('is_process_required', 1);
        $collection->getSelect()->limit(self::MAX_AFFECTED_ITEMS_COUNT);

        $collection->getSelect()->joinLeft(
            [
                'eaps' => $this->activeRecordFactory->getObject('Ebay_Account_PickupStore')
                    ->getResource()->getMainTable()
            ],
            'eaps.id = main_table.account_pickup_store_id',
            ['account_id']
        );

        $collection->addFieldToFilter('eaps.account_id', $account->getId());

        $listingProductIds = $collection->getColumnValues('listing_product_id');
        if (empty($listingProductIds)) {
            return;
        }

        $listingProductIds = array_unique($listingProductIds);

        $affectedItemsCount = 0;

        foreach ($listingProductIds as $listingProductId) {
            /** @var \Ess\M2ePro\Model\Listing\Product $listingProduct */
            $listingProduct = $this->parentFactory->getObjectLoaded(
                \Ess\M2ePro\Helper\Component\Ebay::NICK,
                'Listing\Product',
                $listingProductId
            );

            $pickupStoreStateUpdater = $this->modelFactory->getObject('Ebay_Listing_Product_PickupStore_State_Updater');
            $pickupStoreStateUpdater->setListingProduct($listingProduct);

            $affectedItemsCount += $pickupStoreStateUpdater->process();

            if ($affectedItemsCount >= self::MAX_AFFECTED_ITEMS_COUNT) {
                break;
            }
        }
    }

    //########################################
}
