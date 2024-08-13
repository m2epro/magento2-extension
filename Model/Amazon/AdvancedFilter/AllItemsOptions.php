<?php

namespace Ess\M2ePro\Model\Amazon\AdvancedFilter;

use Ess\M2ePro\Block\Adminhtml\Widget\Grid\AdvancedFilter\Filters\DropDown as DropDownFilter;

class AllItemsOptions
{
    /** @var \Ess\M2ePro\Model\ResourceModel\Amazon\Listing */
    private $amazonListingResource;
    /** @var \Ess\M2ePro\Model\ResourceModel\Amazon\Listing\Product */
    private $amazonListingProductResource;
    /** @var \Ess\M2ePro\Model\ResourceModel\Template\SellingFormat */
    private $sellingPolicyResource;
    /** @var \Ess\M2ePro\Model\ResourceModel\Template\Synchronization */
    private $synchronizationPolicyResource;
    /** @var \Ess\M2ePro\Model\ResourceModel\Amazon\Template\Shipping */
    private $shippingPolicyResource;
    /** @var \Ess\M2ePro\Model\ResourceModel\Marketplace */
    private $marketplaceResource;
    /** @var \Ess\M2ePro\Model\ResourceModel\Account */
    private $accountResource;
    /** @var \Ess\M2ePro\Model\ResourceModel\Listing */
    private $listingResource;
    /** @var \Ess\M2ePro\Model\ResourceModel\Listing\Product */
    private $listingProductResource;
    /** @var \Ess\M2ePro\Model\ResourceModel\Amazon\Template\ProductType */
    private $amazonProductTypeResource;
    /** @var \Ess\M2ePro\Model\ResourceModel\Amazon\Dictionary\ProductType */
    private $amazonProductTypeDictionaryResource;
    /** @var \Ess\M2ePro\Model\Tag\ListingProduct\Repository */
    private $tagRelationRepository;
    /** @var \Ess\M2ePro\Block\Adminhtml\Widget\Grid\AdvancedFilter\Filters\DropDown\OptionCollectionFactory */
    private $optionCollectionFactory;
    /** @var \Ess\M2ePro\Block\Adminhtml\Widget\Grid\AdvancedFilter\Filters\DropDown\OptionFactory */
    private $optionFactory;
    /** @var \Ess\M2ePro\Model\ResourceModel\Amazon\Template\ProductTaxCode */
    private $taxCodeResource;
    private \Magento\Catalog\Model\ResourceModel\Product $magentoProductResource;
    private \Magento\Catalog\Model\Product\Type $magentoProductType;

    public function __construct(
        DropDownFilter\OptionCollectionFactory $optionCollectionFactory,
        DropDownFilter\OptionFactory $optionFactory,
        \Ess\M2ePro\Model\ResourceModel\Listing $listingResource,
        \Ess\M2ePro\Model\ResourceModel\Listing\Product $listingProductResource,
        \Ess\M2ePro\Model\ResourceModel\Amazon\Listing $amazonListingResource,
        \Ess\M2ePro\Model\ResourceModel\Amazon\Listing\Product $amazonListingProductResource,
        \Ess\M2ePro\Model\ResourceModel\Template\SellingFormat $sellingPolicyResource,
        \Ess\M2ePro\Model\ResourceModel\Template\Synchronization $synchronizationPolicyResource,
        \Ess\M2ePro\Model\ResourceModel\Amazon\Template\Shipping $amazonShippingPolicyResource,
        \Ess\M2ePro\Model\ResourceModel\Amazon\Template\ProductType $amazonProductTypeResource,
        \Ess\M2ePro\Model\ResourceModel\Amazon\Dictionary\ProductType $amazonProductTypeDictionaryResource,
        \Ess\M2ePro\Model\Tag\ListingProduct\Repository $tagRelationRepository,
        \Ess\M2ePro\Model\ResourceModel\Marketplace $marketplaceResource,
        \Ess\M2ePro\Model\ResourceModel\Amazon\Template\ProductTaxCode $taxCodeResource,
        \Ess\M2ePro\Model\ResourceModel\Account $accountResource,
        \Magento\Catalog\Model\ResourceModel\Product $magentoProductResource,
        \Magento\Catalog\Model\Product\Type $magentoProductType
    ) {
        $this->amazonListingResource = $amazonListingResource;
        $this->amazonListingProductResource = $amazonListingProductResource;
        $this->sellingPolicyResource = $sellingPolicyResource;
        $this->synchronizationPolicyResource = $synchronizationPolicyResource;
        $this->shippingPolicyResource = $amazonShippingPolicyResource;
        $this->marketplaceResource = $marketplaceResource;
        $this->accountResource = $accountResource;
        $this->listingResource = $listingResource;
        $this->listingProductResource = $listingProductResource;
        $this->amazonProductTypeResource = $amazonProductTypeResource;
        $this->amazonProductTypeDictionaryResource = $amazonProductTypeDictionaryResource;
        $this->tagRelationRepository = $tagRelationRepository;
        $this->optionCollectionFactory = $optionCollectionFactory;
        $this->optionFactory = $optionFactory;
        $this->taxCodeResource = $taxCodeResource;
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
            'selling_policy.id = amazon_listing.template_selling_format_id',
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
            'synchronization_policy.id = amazon_listing.template_synchronization_id',
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

    public function getShippingPolicyOptions(): DropDownFilter\OptionCollection
    {
        $select = $this->getBaseSelect();
        $select->joinInner(
            ['shipping_policy' => $this->shippingPolicyResource->getMainTable()],
            'shipping_policy.id = IFNULL(
                    amazon_listing_product.template_shipping_id,
                    amazon_listing.template_shipping_id
                   )',
            [
                'value' => 'id',
                'label' => 'title',
            ]
        );
        $select->joinLeft(
            ['marketplace' => $this->marketplaceResource->getMainTable()],
            'marketplace.id = shipping_policy.marketplace_id',
            [
                'group' => 'title',
            ]
        );
        $select->group(['shipping_policy.id', 'marketplace.title', 'shipping_policy.title']);
        $select->order(['marketplace.title', 'shipping_policy.title']);

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

    public function getProductTypeOptions(): DropDownFilter\OptionCollection
    {
        $select = $this->getBaseSelect();
        $select->joinInner(
            ['product_type' => $this->amazonProductTypeResource->getMainTable()],
            'product_type.id = amazon_listing_product.template_product_type_id',
            [
                'value' => 'id',
            ]
        );
        $select->joinInner(
            ['product_type_dict' => $this->amazonProductTypeDictionaryResource->getMainTable()],
            'product_type.dictionary_product_type_id = product_type_dict.id',
            [
                'label' => 'title',
            ]
        );
        $select->joinLeft(
            ['marketplace' => $this->marketplaceResource->getMainTable()],
            'marketplace.id = product_type_dict.marketplace_id',
            [
                'group' => 'title',
            ]
        );
        $select->group(['product_type.id', 'marketplace.title', 'product_type_dict.title']);
        $select->order(['marketplace.title', 'product_type_dict.title']);

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

    public function getErrorOptions(): DropDownFilter\OptionCollection
    {
        $tags = $this
            ->tagRelationRepository
            ->getTagEntitiesWithoutHasErrorsTag(\Ess\M2ePro\Helper\Component\Amazon::NICK);

        $optionCollection = $this->optionCollectionFactory->create();
        foreach ($tags as $tag) {
            $option = $this->optionFactory->create(
                $tag->getErrorCode(),
                $tag->getId()
            );
            $optionCollection->addOption($option);
        }

        return $optionCollection;
    }

    public function getTaxCodeOptions(): DropDownFilter\OptionCollection
    {
        $select = $this->getBaseSelect();
        $select->joinInner(
            ['tax_code' => $this->taxCodeResource->getMainTable()],
            'tax_code.id = amazon_listing_product.template_product_tax_code_id',
            [
                'value' => 'id',
                'label' => 'title',
            ]
        );

        $select->group(['tax_code.id', 'tax_code.title']);
        $select->order(['tax_code.title']);

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
            ['amazon_listing' => $this->amazonListingResource->getMainTable()],
            'amazon_listing.listing_id = listing.id',
            []
        );
        $select->joinInner(
            ['amazon_listing_product' => $this->amazonListingProductResource->getMainTable()],
            'amazon_listing_product.listing_product_id = listing_product.id',
            []
        );

        $select->where('listing.component_mode = ?', \Ess\M2ePro\Helper\Component\Amazon::NICK);
        $select->where('listing_product.component_mode = ?', \Ess\M2ePro\Helper\Component\Amazon::NICK);

        return $select;
    }
}
