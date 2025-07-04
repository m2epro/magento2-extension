<?php

namespace Ess\M2ePro\Model\Ebay\Account;

class DeleteManager
{
    private \Ess\M2ePro\Helper\Data\Cache\Permanent $cachePermanent;
    private \Ess\M2ePro\Model\ResourceModel\Ebay\Template\Shipping\CollectionFactory $shippingTemplateCollectionFactory;
    private \Ess\M2ePro\Helper\Module\Database\Structure $moduleDatabaseStructureHelper;
    private \Ess\M2ePro\Model\Ebay\Promotion\Repository $promotionRepository;
    private \Ess\M2ePro\Model\Ebay\Video\Repository $videoRepository;
    private \Ess\M2ePro\Model\Ebay\PromotedListing\Campaign\Repository $campaignRepository;
    private \Ess\M2ePro\Model\Ebay\PromotedListing\DeleteCampaign $deleteCampaign;

    public function __construct(
        \Ess\M2ePro\Model\Ebay\Video\Repository $videoRepository,
        \Ess\M2ePro\Model\Ebay\Promotion\Repository $promotionRepository,
        \Ess\M2ePro\Helper\Module\Database\Structure $moduleDatabaseStructureHelper,
        \Ess\M2ePro\Model\ResourceModel\Ebay\Template\Shipping\CollectionFactory $shippingTemplateCollectionFactory,
        \Ess\M2ePro\Helper\Data\Cache\Permanent $cachePermanent,
        \Ess\M2ePro\Model\Ebay\PromotedListing\Campaign\Repository $campaignRepository,
        \Ess\M2ePro\Model\Ebay\PromotedListing\DeleteCampaign $deleteCampaign
    ) {
        $this->cachePermanent = $cachePermanent;
        $this->shippingTemplateCollectionFactory = $shippingTemplateCollectionFactory;
        $this->moduleDatabaseStructureHelper = $moduleDatabaseStructureHelper;
        $this->promotionRepository = $promotionRepository;
        $this->videoRepository = $videoRepository;
        $this->campaignRepository = $campaignRepository;
        $this->deleteCampaign = $deleteCampaign;
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

        /** @var \Ess\M2ePro\Model\Ebay\Account $ebayAccount */
        $ebayAccount = $account->getChildObject();

        $this->promotionRepository->removeAllByAccountId((int)$ebayAccount->getId());

        $campaigns = $this->campaignRepository->getAllByAccountId((int)$ebayAccount->getId());
        foreach ($campaigns as $campaign) {
            $this->deleteCampaign->execute($campaign, false);
        }

        $this->videoRepository->removeAllByAccountId((int)$ebayAccount->getId());

        $storeCategoriesTable = $this->moduleDatabaseStructureHelper
                                     ->getTableNameWithPrefix('m2epro_ebay_account_store_category');

        $ebayAccount->getResource()->getConnection()
             ->delete($storeCategoriesTable, ['account_id = ?' => $ebayAccount->getId()]);

        $storeCategoryTemplates = $ebayAccount->getStoreCategoryTemplates(true);
        foreach ($storeCategoryTemplates as $storeCategoryTemplate) {
            $storeCategoryTemplate->deleteProcessings();
            $storeCategoryTemplate->deleteProcessingLocks();

            $this->assertSuccess($storeCategoryTemplate->delete(), 'Store Category Template');
        }

        $feedbacks = $ebayAccount->getFeedbacks(true);
        foreach ($feedbacks as $feedback) {
            $feedback->deleteProcessings();
            $feedback->deleteProcessingLocks();

            $this->assertSuccess($feedback->delete(), 'Feedback');
        }

        $feedbackTemplates = $ebayAccount->getFeedbackTemplates(true);
        foreach ($feedbackTemplates as $feedbackTemplate) {
            $feedbackTemplate->deleteProcessings();
            $feedbackTemplate->deleteProcessingLocks();

            $this->assertSuccess($feedbackTemplate->delete(), 'Feedback Template');
        }

        $items = $ebayAccount->getEbayItems(true);
        /** @var \Ess\M2ePro\Model\Ebay\Item $item */
        foreach ($items as $item) {
            $item->deleteProcessings();
            $item->deleteProcessingLocks();

            $this->assertSuccess($item->delete(), 'Item');
        }

        $shippingTemplateCollection = $this->shippingTemplateCollectionFactory
            ->create()
            ->applyLinkedAccountFilter($ebayAccount->getId());
        /** @var \Ess\M2ePro\Model\Ebay\Template\Shipping $item */
        foreach ($shippingTemplateCollection->getItems() as $item) {
            $item->deleteShippingRateTables($account);
            $item->save();
        }

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
