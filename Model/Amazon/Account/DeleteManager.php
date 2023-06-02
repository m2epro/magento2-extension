<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Account;

class DeleteManager
{
    /** @var \Ess\M2ePro\Helper\Data\Cache\Permanent */
    private $cachePermanent;

    public function __construct(
        \Ess\M2ePro\Helper\Data\Cache\Permanent $cachePermanent
    ) {
        $this->cachePermanent = $cachePermanent;
    }

    /**
     * @param \Ess\M2ePro\Model\Account $account
     *
     * @return void
     * @throws \Ess\M2ePro\Model\Exception\Logic|\Ess\M2ePro\Model\Exception
     */
    public function process(\Ess\M2ePro\Model\Account $account): void
    {
        $otherListings = $account->getOtherListings(true);
        /** @var \Ess\M2ePro\Model\Listing\Other $otherListing */
        foreach ($otherListings as $otherListing) {
            $otherListing->deleteProcessings();
            $otherListing->deleteProcessingLocks();

            $this->assertSuccess($otherListing->delete(), 'Listing Other');
        }

        $listings = $account->getListings();
        /** @var \Ess\M2ePro\Model\Listing $listing */
        foreach ($listings as $listing) {
            $listing->deleteProcessings();
            $listing->deleteProcessingLocks();

            $listing->deleteListingProductsForce();

            $this->assertSuccess($listing->delete(), 'Listing');
        }

        $orders = $account->getOrders(true);
        /** @var \Ess\M2ePro\Model\Order $order */
        foreach ($orders as $order) {
            $order->deleteProcessings();
            $order->deleteProcessingLocks();

            $this->assertSuccess($order->delete(), 'Order');
        }

        /** @var \Ess\M2ePro\Model\Amazon\Account $amazonAccount */
        $amazonAccount = $account->getChildObject();

        $amazonAccount->deleteInventorySku();
        $amazonAccount->deleteProcessingListSku();

        $items = $amazonAccount->getAmazonItems(true);
        /** @var \Ess\M2ePro\Model\Amazon\Item $item */
        foreach ($items as $item) {
            $item->deleteProcessings();
            $item->deleteProcessingLocks();

            $this->assertSuccess($item->delete(), 'Item');
        }

        if ($amazonAccount->isRepricing()) {
            $amazonAccountRepricing = $amazonAccount->getRepricing();

            $amazonAccountRepricing->deleteProcessings();
            $amazonAccountRepricing->deleteProcessingLocks();

            $this->assertSuccess($amazonAccountRepricing->delete(), 'Account Repricing');
        }

        $amazonAccount->deleteShippingPolicies();
        $amazonAccount->deleteDictionaryTemplateShipping();

        $this->cachePermanent->removeTagValues('account');

        $account->deleteProcessings();
        $account->deleteProcessingLocks();

        $this->assertSuccess($account->delete(), 'Account');
    }

    /**
     * @param $value
     * @param string $label
     *
     * @return void
     * @throws \Ess\M2ePro\Model\Exception
     */
    private function assertSuccess($value, string $label): void
    {
        if ($value === false) {
            throw new \Ess\M2ePro\Model\Exception('Unable to delete ' . $label);
        }
    }
}
