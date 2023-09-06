<?php

namespace Ess\M2ePro\Model\Walmart\AdvancedFilter;

use Ess\M2ePro\Block\Adminhtml\Widget\Grid\AdvancedFilter\Filters\DropDown as DropDownFilter;

class AllItemsOptions
{
    /** @var \Ess\M2ePro\Block\Adminhtml\Widget\Grid\AdvancedFilter\Filters\DropDown\OptionCollectionFactory */
    private $optionCollectionFactory;
    /** @var \Ess\M2ePro\Block\Adminhtml\Widget\Grid\AdvancedFilter\Filters\DropDown\OptionFactory */
    private $optionFactory;
    /** @var \Ess\M2ePro\Model\ResourceModel\Walmart\Listing */
    private $walmartListingResource;
    /** @var \Ess\M2ePro\Model\ResourceModel\Walmart\Listing\Product */
    private $walmartListingProductResource;
    /** @var \Ess\M2ePro\Model\ResourceModel\Template\SellingFormat */
    private $sellingPolicyResource;
    /** @var \Ess\M2ePro\Model\ResourceModel\Template\Synchronization */
    private $synchronizationPolicyResource;
    /** @var \Ess\M2ePro\Model\ResourceModel\Template\Description */
    private $descriptionPolicyResource;
    /** @var \Ess\M2ePro\Model\ResourceModel\Ebay\Template\Category */
    private $walmartCategoryResource;
    /** @var \Ess\M2ePro\Model\ResourceModel\Marketplace */
    private $marketplaceResource;
    /** @var \Ess\M2ePro\Model\ResourceModel\Account */
    private $accountResource;
    /** @var \Ess\M2ePro\Model\ResourceModel\Listing */
    private $listingResource;
    /** @var \Ess\M2ePro\Model\ResourceModel\Listing\Product */
    private $listingProductResource;

    public function __construct(
        DropDownFilter\OptionCollectionFactory $optionCollectionFactory,
        DropDownFilter\OptionFactory $optionFactory,
        \Ess\M2ePro\Model\ResourceModel\Listing $listingResource,
        \Ess\M2ePro\Model\ResourceModel\Listing\Product $listingProductResource,
        \Ess\M2ePro\Model\ResourceModel\Walmart\Listing $walmartListingResource,
        \Ess\M2ePro\Model\ResourceModel\Walmart\Listing\Product $walmartListingProductResource,
        \Ess\M2ePro\Model\ResourceModel\Template\SellingFormat $sellingPolicyResource,
        \Ess\M2ePro\Model\ResourceModel\Template\Synchronization $synchronizationPolicyResource,
        \Ess\M2ePro\Model\ResourceModel\Template\Description $descriptionPolicyResource,
        \Ess\M2ePro\Model\ResourceModel\Walmart\Template\Category $walmartCategoryResource,
        \Ess\M2ePro\Model\ResourceModel\Marketplace $marketplaceResource,
        \Ess\M2ePro\Model\ResourceModel\Account $accountResource
    ) {
        $this->walmartListingResource = $walmartListingResource;
        $this->walmartListingProductResource = $walmartListingProductResource;
        $this->sellingPolicyResource = $sellingPolicyResource;
        $this->synchronizationPolicyResource = $synchronizationPolicyResource;
        $this->descriptionPolicyResource = $descriptionPolicyResource;
        $this->walmartCategoryResource = $walmartCategoryResource;
        $this->marketplaceResource = $marketplaceResource;
        $this->accountResource = $accountResource;
        $this->listingResource = $listingResource;
        $this->listingProductResource = $listingProductResource;
        $this->optionCollectionFactory = $optionCollectionFactory;
        $this->optionFactory = $optionFactory;
    }

    public function getAccountOptions(): DropDownFilter\OptionCollection
    {
        $select = $this->getBaseSelect();
        $select->joinInner(
            ['account' => $this->accountResource->getMainTable()],
            'account.id = listing.account_id',
            [
                'value' => 'id',
                'label' => 'title',
            ]
        );
        $select->group(['account.id', 'account.title']);
        $select->order(['account.title']);

        $optionsData = $select->query()->fetchAll();

        $optionCollection = $this->optionCollectionFactory->create();
        foreach ($optionsData as $optionData) {
            $option = $this->optionFactory->create(
                $optionData['label'],
                $optionData['value']
            );
            $optionCollection->addOption($option);
        }

        return $optionCollection;
    }

    public function getMarketplaceOptions(): DropDownFilter\OptionCollection
    {
        $select = $this->getBaseSelect();
        $select->joinInner(
            ['marketplace' => $this->marketplaceResource->getMainTable()],
            'marketplace.id = listing.marketplace_id',
            [
                'value' => 'id',
                'label' => 'title',
            ]
        );
        $select->group(['marketplace.id', 'marketplace.title']);
        $select->order(['marketplace.title']);

        $optionsData = $select->query()->fetchAll();

        $optionCollection = $this->optionCollectionFactory->create();
        foreach ($optionsData as $optionData) {
            $option = $this->optionFactory->create(
                $optionData['label'],
                $optionData['value']
            );
            $optionCollection->addOption($option);
        }

        return $optionCollection;
    }

    public function getSellingPolicyOptions(): DropDownFilter\OptionCollection
    {
        $select = $this->getBaseSelect();
        $select->joinInner(
            ['selling_policy' => $this->sellingPolicyResource->getMainTable()],
            'selling_policy.id = walmart_listing.template_selling_format_id',
            [
                'value' => 'id',
                'label' => 'title',
            ]
        );
        $select->group(['selling_policy.id', 'selling_policy.title']);
        $select->order(['selling_policy.title']);

        $optionsData = $select->query()->fetchAll();

        $optionCollection = $this->optionCollectionFactory->create();
        foreach ($optionsData as $optionData) {
            $option = $this->optionFactory->create(
                $optionData['label'],
                $optionData['value']
            );
            $optionCollection->addOption($option);
        }

        return $optionCollection;
    }

    public function getSynchronizationPolicyOptions(): DropDownFilter\OptionCollection
    {
        $select = $this->getBaseSelect();
        $select->joinInner(
            ['synchronization_policy' => $this->synchronizationPolicyResource->getMainTable()],
            'synchronization_policy.id = walmart_listing.template_synchronization_id',
            [
                'value' => 'id',
                'label' => 'title',
            ]
        );
        $select->group(['synchronization_policy.id', 'synchronization_policy.title']);
        $select->order(['synchronization_policy.title']);

        $optionsData = $select->query()->fetchAll();

        $optionCollection = $this->optionCollectionFactory->create();
        foreach ($optionsData as $optionData) {
            $option = $this->optionFactory->create(
                $optionData['label'],
                $optionData['value']
            );
            $optionCollection->addOption($option);
        }

        return $optionCollection;
    }

    public function getDescriptionPolicyOptions(): DropDownFilter\OptionCollection
    {
        $select = $this->getBaseSelect();
        $select->joinInner(
            ['description_policy' => $this->descriptionPolicyResource->getMainTable()],
            'description_policy.id = walmart_listing.template_description_id',
            [
                'value' => 'id',
                'label' => 'title',
            ]
        );
        $select->group(['description_policy.id', 'description_policy.title']);
        $select->order(['description_policy.title']);

        $optionsData = $select->query()->fetchAll();

        $optionCollection = $this->optionCollectionFactory->create();
        foreach ($optionsData as $optionData) {
            $option = $this->optionFactory->create(
                $optionData['label'],
                $optionData['value']
            );
            $optionCollection->addOption($option);
        }

        return $optionCollection;
    }

    public function getCategoryOptions(): DropDownFilter\OptionCollection
    {
        $select = $this->getBaseSelect();

        $select->joinInner(
            ['category' => $this->walmartCategoryResource->getMainTable()],
            'category.id = walmart_listing_product.template_category_id',
            [
                'value' => 'id',
                'label' => 'title',
            ]
        );
        $select->joinLeft(
            ['marketplace' => $this->marketplaceResource->getMainTable()],
            'marketplace.id = category.marketplace_id',
            [
                'group' => 'title',
            ]
        );
        $select->group(['category.id', 'marketplace.title', 'category.category_path']);
        $select->order(['marketplace.title', 'category.category_path']);

        $optionsData = $select->query()->fetchAll();

        $optionCollection = $this->optionCollectionFactory->create();
        foreach ($optionsData as $optionData) {
            $option = $this->optionFactory->create(
                $optionData['label'],
                $optionData['value'],
                $optionData['group']
            );
            $optionCollection->addOption($option);
        }

        return $optionCollection;
    }

    private function getBaseSelect(): \Magento\Framework\DB\Select
    {
        $select = $this->listingResource->getConnection()->select();
        $select->from(
            ['listing' => $this->listingResource->getMainTable()],
            []
        );
        $select->joinInner(
            ['listing_product' => $this->listingProductResource->getMainTable()],
            'listing_product.listing_id = listing.id',
            []
        );
        $select->joinInner(
            ['walmart_listing' => $this->walmartListingResource->getMainTable()],
            'walmart_listing.listing_id = listing.id',
            []
        );
        $select->joinInner(
            ['walmart_listing_product' => $this->walmartListingProductResource->getMainTable()],
            'walmart_listing_product.listing_product_id = listing_product.id',
            []
        );

        $select->where('listing.component_mode = ?', \Ess\M2ePro\Helper\Component\Walmart::NICK);
        $select->where('listing_product.component_mode = ?', \Ess\M2ePro\Helper\Component\Walmart::NICK);

        return $select;
    }
}
