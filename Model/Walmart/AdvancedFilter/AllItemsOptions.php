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
    /** @var \Ess\M2ePro\Model\ResourceModel\Marketplace */
    private $marketplaceResource;
    /** @var \Ess\M2ePro\Model\ResourceModel\Account */
    private $accountResource;
    /** @var \Ess\M2ePro\Model\ResourceModel\Listing */
    private $listingResource;
    /** @var \Ess\M2ePro\Model\ResourceModel\Listing\Product */
    private $listingProductResource;
    private \Magento\Catalog\Model\ResourceModel\Product $magentoProductResource;
    private \Magento\Catalog\Model\Product\Type $magentoProductType;

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
        \Ess\M2ePro\Model\ResourceModel\Marketplace $marketplaceResource,
        \Ess\M2ePro\Model\ResourceModel\Account $accountResource,
        \Magento\Catalog\Model\ResourceModel\Product $magentoProductResource,
        \Magento\Catalog\Model\Product\Type $magentoProductType
    ) {
        $this->walmartListingResource = $walmartListingResource;
        $this->walmartListingProductResource = $walmartListingProductResource;
        $this->sellingPolicyResource = $sellingPolicyResource;
        $this->synchronizationPolicyResource = $synchronizationPolicyResource;
        $this->descriptionPolicyResource = $descriptionPolicyResource;
        $this->marketplaceResource = $marketplaceResource;
        $this->accountResource = $accountResource;
        $this->listingResource = $listingResource;
        $this->listingProductResource = $listingProductResource;
        $this->optionCollectionFactory = $optionCollectionFactory;
        $this->optionFactory = $optionFactory;
        $this->magentoProductResource = $magentoProductResource;
        $this->magentoProductType = $magentoProductType;
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

    public function getMagentoProductTypeOptions(): DropDownFilter\OptionCollection
    {
        $select = $this->getBaseSelect();
        $select->joinInner(
            ['mp' => $this->magentoProductResource->getEntityTable()],
            sprintf(
                'mp.entity_id = listing_product.%s',
                \Ess\M2ePro\Model\ResourceModel\Listing\Product::PRODUCT_ID_FIELD
            ),
            ['type_id']
        );

        $select->group('mp.type_id');
        $select->order('mp.type_id');

        $optionsData = $select->query()->fetchAll();
        $magentoProductTypes = $this->magentoProductType->getOptionArray();

        $optionCollection = $this->optionCollectionFactory->create();
        foreach ($optionsData as $optionData) {
            $optionLabel = $magentoProductTypes[($optionData['type_id'])];
            $optionValue = $optionData['type_id'];

            $option = $this->optionFactory->create($optionLabel, $optionValue);
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
