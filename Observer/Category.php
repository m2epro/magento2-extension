<?php

namespace Ess\M2ePro\Observer;

class Category extends AbstractModel
{
    /** @var \Ess\M2ePro\Model\Listing\Auto\Actions\CategoryProductRelationProcessor */
    private $categoryProductRelationshipsProcessor;

    public function __construct(
        \Ess\M2ePro\Model\Listing\Auto\Actions\CategoryProductRelationProcessor  $categoryProductRelationshipsProcessor,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Model\Factory $modelFactory
    ) {
        parent::__construct($helperFactory, $activeRecordFactory, $modelFactory);

        $this->categoryProductRelationshipsProcessor = $categoryProductRelationshipsProcessor;
    }

    /**
     * @see \Magento\Catalog\Model\ResourceModel\Category::_saveCategoryProducts()
     */
    public function process(): void
    {
        /** @var \Magento\Catalog\Model\Category $category */
        $category = $this->getEventObserver()->getData('category');

        /**
         * For insert\update
         * @var int[] $changedProductsIds
         */
        $changedProductsIds = $category->getChangedProductIds();
        if (empty($changedProductsIds)) {
            return;
        }

        $categoryId = (int)$category->getId();
        if (!$this->categoryProductRelationshipsProcessor->isNeedProcess($categoryId)) {
            return;
        }

        /**
         * Product IDs from new category-product relationships
         * @var int[] $postedProductsIds
         */
        $postedProductsIds = array_keys($category->getPostedProducts());
        $websiteId = (int)$category->getStore()->getWebsiteId();

        $this->categoryProductRelationshipsProcessor->process(
            $categoryId,
            $websiteId,
            $postedProductsIds,
            $changedProductsIds
        );
    }
}
