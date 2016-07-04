<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Synchronization\General\AccountPickupStore;

final class Process extends \Ess\M2ePro\Model\Ebay\Synchronization\General\AbstractModel
{
    const MAX_AFFECTED_ITEMS_COUNT = 10000;

    //########################################

    protected function getNick()
    {
        return '/account_pickup_store/process/';
    }

    protected function getTitle()
    {
        return 'Pickup Store Process';
    }

    // ---------------------------------------

    protected function getPercentsStart()
    {
        return 0;
    }

    protected function getPercentsEnd()
    {
        return 60;
    }

    //########################################

    public function performActions()
    {
        $account = $this->getHelper('Component\Ebay\PickupStore')->getEnabledAccount();
        if (!$account) {
            return;
        }

        $this->getActualOperationHistory()->addText('Starting Account "'.$account->getTitle().'"');
        // M2ePro\TRANSLATIONS
        // The "Prepare Data" Action for eBay Account: "%account_title%" is started. Please wait...
        $status = 'The "Prepare Data" Action for eBay Account: "%account_title%" is started. ';
        $status .= 'Please wait...';
        $this->getActualLockItem()->setStatus(
            $this->getHelper('Module\Translation')->__($status, $account->getTitle())
        );

        $this->getActualOperationHistory()->addTimePoint(
            __METHOD__.'process'.$account->getId(),
            'Process Account '.$account->getTitle()
        );

        $this->processAccount($account);

        $this->getActualOperationHistory()->saveTimePoint(__METHOD__.'process'.$account->getId());

        // M2ePro\TRANSLATIONS
        // The "Prepare Data" Action for eBay Account: "%account_title%" is finished. Please wait...
        $status = 'The "Prepare Data" Action for eBay Account: "%account_title%" is finished.'.
            ' Please wait...';
        $this->getActualLockItem()->setStatus(
            $this->getHelper('Module\Translation')->__($status, $account->getTitle())
        );
        $this->getActualLockItem()->activate();
    }

    //########################################

    private function processAccount(\Ess\M2ePro\Model\Account $account)
    {
        $collection = $this->activeRecordFactory->getObject('Ebay\Listing\Product\PickupStore')->getCollection();
        $collection->addFieldToFilter('is_process_required', 1);
        $collection->getSelect()->limit(self::MAX_AFFECTED_ITEMS_COUNT);

        $pickupStoreTable = $this->activeRecordFactory
            ->getObject('Ebay\Account\PickupStore')
            ->getResource()
            ->getMainTable();

        $collection->getSelect()->joinLeft(
            array('eaps' => $pickupStoreTable),
            'eaps.id = main_table.account_pickup_store_id',
            array('account_id')
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
            $listingProduct = $this->ebayFactory->getObjectLoaded('Listing\Product', $listingProductId);

            $pickupStoreStateUpdater = $this->modelFactory->getObject('Ebay\Listing\Product\PickupStore\State\Updater');
            $pickupStoreStateUpdater->setListingProduct($listingProduct);

            $affectedItemsCount += $pickupStoreStateUpdater->process();

            if ($affectedItemsCount >= self::MAX_AFFECTED_ITEMS_COUNT) {
                break;
            }
        }
    }

    //########################################
}