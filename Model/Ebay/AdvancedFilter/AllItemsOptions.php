<?php

namespace Ess\M2ePro\Model\Ebay\AdvancedFilter;

use Ess\M2ePro\Block\Adminhtml\Widget\Grid\AdvancedFilter\Filters\DropDown as DropDownFilter;

class AllItemsOptions
{
    /** @var DropDownFilter\OptionCollectionFactory */
    private $optionCollectionFactory;
    /** @var DropDownFilter\OptionFactory */
    private $optionFactory;
    /** @var \Ess\M2ePro\Model\ResourceModel\Ebay\Listing */
    private $ebayListingResource;
    /** @var \Ess\M2ePro\Model\ResourceModel\Ebay\Listing\Product */
    private $ebayListingProductResource;
    /** @var \Ess\M2ePro\Model\ResourceModel\Template\SellingFormat */
    private $sellingPolicyResource;
    /** @var \Ess\M2ePro\Model\ResourceModel\Template\Synchronization */
    private $synchronizationPolicyResource;
    /** @var \Ess\M2ePro\Model\ResourceModel\Template\Description */
    private $descriptionPolicyResource;
    /** @var \Ess\M2ePro\Model\ResourceModel\Ebay\Template\Shipping */
    private $shippingPolicyResource;
    /** @var \Ess\M2ePro\Model\ResourceModel\Ebay\Template\ReturnPolicy */
    private $returnPolicyResource;
    /** @var \Ess\M2ePro\Model\ResourceModel\Ebay\Template\Category */
    private $categoryResource;
    /** @var \Ess\M2ePro\Model\ResourceModel\Marketplace */
    private $marketplaceResource;
    /** @var \Ess\M2ePro\Model\ResourceModel\Account */
    private $accountResource;
    /** @var \Ess\M2ePro\Model\ResourceModel\Listing */
    private $listingResource;
    /** @var \Ess\M2ePro\Model\ResourceModel\Listing\Product */
    private $listingProductResource;
    /** @var \Ess\M2ePro\Model\Tag\ListingProduct\Repository */
    private $tagRelationRepository;

    private \Ess\M2ePro\Model\ResourceModel\Ebay\Promotion $promotionResource;
    private \Ess\M2ePro\Model\ResourceModel\Ebay\Listing\Product\Promotion $listingProductPromotionResource;

    public function __construct(
        DropDownFilter\OptionCollectionFactory $optionCollectionFactory,
        DropDownFilter\OptionFactory $optionFactory,
        \Ess\M2ePro\Model\ResourceModel\Listing $listingResource,
        \Ess\M2ePro\Model\ResourceModel\Listing\Product $listingProductResource,
        \Ess\M2ePro\Model\ResourceModel\Ebay\Listing $ebayListingResource,
        \Ess\M2ePro\Model\ResourceModel\Ebay\Listing\Product $ebayListingProductResource,
        \Ess\M2ePro\Model\ResourceModel\Template\SellingFormat $sellingPolicyResource,
        \Ess\M2ePro\Model\ResourceModel\Template\Synchronization $synchronizationPolicyResource,
        \Ess\M2ePro\Model\ResourceModel\Template\Description $descriptionPolicyResource,
        \Ess\M2ePro\Model\ResourceModel\Ebay\Template\Shipping $shippingPolicyResource,
        \Ess\M2ePro\Model\ResourceModel\Ebay\Template\ReturnPolicy $returnPolicyResource,
        \Ess\M2ePro\Model\ResourceModel\Ebay\Template\Category $categoryResource,
        \Ess\M2ePro\Model\Tag\ListingProduct\Repository $tagRelationRepository,
        \Ess\M2ePro\Model\ResourceModel\Marketplace $marketplaceResource,
        \Ess\M2ePro\Model\ResourceModel\Account $accountResource,
        \Ess\M2ePro\Model\ResourceModel\Ebay\Promotion $promotionResource,
        \Ess\M2ePro\Model\ResourceModel\Ebay\Listing\Product\Promotion $listingProductPromotionResource
    ) {
        $this->ebayListingResource = $ebayListingResource;
        $this->ebayListingProductResource = $ebayListingProductResource;
        $this->sellingPolicyResource = $sellingPolicyResource;
        $this->synchronizationPolicyResource = $synchronizationPolicyResource;
        $this->descriptionPolicyResource = $descriptionPolicyResource;
        $this->shippingPolicyResource = $shippingPolicyResource;
        $this->returnPolicyResource = $returnPolicyResource;
        $this->categoryResource = $categoryResource;
        $this->marketplaceResource = $marketplaceResource;
        $this->accountResource = $accountResource;
        $this->listingResource = $listingResource;
        $this->listingProductResource = $listingProductResource;
        $this->tagRelationRepository = $tagRelationRepository;
        $this->optionCollectionFactory = $optionCollectionFactory;
        $this->optionFactory = $optionFactory;
        $this->promotionResource = $promotionResource;
        $this->listingProductPromotionResource = $listingProductPromotionResource;
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
            'selling_policy.id = IFNULL(
                    ebay_listing_product.template_selling_format_id,
                    ebay_listing.template_selling_format_id
                   )',
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
            'synchronization_policy.id = IFNULL(
                    ebay_listing_product.template_synchronization_id,
                    ebay_listing.template_synchronization_id
                   )',
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
            'description_policy.id = IFNULL(
                    ebay_listing_product.template_description_id,
                    ebay_listing.template_description_id
                   )',
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

    public function getShippingPolicyOptions(): DropDownFilter\OptionCollection
    {
        $select = $this->getBaseSelect();
        $select->joinInner(
            ['shipping_policy' => $this->shippingPolicyResource->getMainTable()],
            'shipping_policy.id = IFNULL(
                    ebay_listing_product.template_shipping_id,
                    ebay_listing.template_shipping_id
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

    public function getReturnPolicyOptions(): DropDownFilter\OptionCollection
    {
        $select = $this->getBaseSelect();
        $select->joinInner(
            ['return_policy' => $this->returnPolicyResource->getMainTable()],
            'return_policy.id = IFNULL(
                    ebay_listing_product.template_return_policy_id,
                    ebay_listing.template_return_policy_id
                   )',
            [
                'value' => 'id',
                'label' => 'title',
            ]
        );
        $select->joinLeft(
            ['marketplace' => $this->marketplaceResource->getMainTable()],
            'marketplace.id = return_policy.marketplace_id',
            [
                'group' => 'title',
            ]
        );
        $select->group(['return_policy.id', 'marketplace.title', 'return_policy.title']);
        $select->order(['marketplace.title', 'return_policy.title']);

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

    public function getCategoryOptions(): DropDownFilter\OptionCollection
    {
        $select = $this->getBaseSelect();

        $select->joinInner(
            ['category' => $this->categoryResource->getMainTable()],
            'category.id = ebay_listing_product.template_category_id',
            [
                'category_id' => 'category_id',
                'category_path' => new \Zend_Db_Expr('MAX(category.category_path)'),
            ]
        );
        $select->joinLeft(
            ['marketplace' => $this->marketplaceResource->getMainTable()],
            'marketplace.id = category.marketplace_id',
            [
                'group' => 'title',
            ]
        );
        $select->where('category.category_path IS NOT NULL');
        $select->group(['category.category_id', 'marketplace.title']);
        $select->order(['marketplace.title', 'category.category_path']);

        $categories = $select->query()->fetchAll();

        $optionCollection = $this->optionCollectionFactory->create();
        foreach ($categories as $category) {
            if (empty($category['category_path'])) {
                $firstCategory = '';
                $lastCategory = '';
            } else {
                $pathElements = explode('>', $category['category_path']);
                $firstCategory = array_shift($pathElements);
                $lastCategory = array_pop($pathElements);
            }

            $groupLabel = sprintf('%s (%s)', $firstCategory, $category['group']);

            $option = $this->optionFactory->create(
                "$lastCategory ({$category['category_id']})",
                $category['category_id'],
                $groupLabel
            );

            $optionCollection->addOption($option);
        }

        return $optionCollection;
    }

    public function getErrorOptions(): DropDownFilter\OptionCollection
    {
        $tags = $this
            ->tagRelationRepository
            ->getTagEntitiesWithoutHasErrorsTag(\Ess\M2ePro\Helper\Component\Ebay::NICK);

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

    public function getPromotionOptions(): DropDownFilter\OptionCollection
    {
        $select = $this->getBaseSelect();
        $select->joinInner(
            ['ebay_listing_product_promotion' => $this->listingProductPromotionResource->getMainTable()],
            'ebay_listing_product_promotion.listing_product_id = ebay_listing_product.listing_product_id',
            [
                'promotion_id' => 'promotion_id',
            ]
        );
        $select->joinLeft(
            ['ebay_promotion' => $this->promotionResource->getMainTable()],
            'ebay_promotion.id = ebay_listing_product_promotion.promotion_id',
            [
                'value' => 'id',
                'label' => 'name',
            ]
        );
        $select->group(['ebay_promotion.id', 'ebay_promotion.name']);
        $select->order(['ebay_promotion.name']);

        $optionsData = $select->query()->fetchAll();

        $optionCollection = $this->optionCollectionFactory->create();
        foreach ($optionsData as $optionData) {
            $option = $this->optionFactory->create(
                $optionData['label'],
                $optionData['value'],
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
            ['ebay_listing' => $this->ebayListingResource->getMainTable()],
            'ebay_listing.listing_id = listing.id',
            []
        );
        $select->joinInner(
            ['ebay_listing_product' => $this->ebayListingProductResource->getMainTable()],
            'ebay_listing_product.listing_product_id = listing_product.id',
            []
        );

        $select->where('listing.component_mode = ?', \Ess\M2ePro\Helper\Component\Ebay::NICK);
        $select->where('listing_product.component_mode = ?', \Ess\M2ePro\Helper\Component\Ebay::NICK);

        return $select;
    }
}
