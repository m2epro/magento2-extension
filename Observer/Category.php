<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Observer;

class Category extends AbstractModel
{
    /** @var \Ess\M2ePro\Model\Listing\Auto\Actions\Mode\Factory */
    private $listingAutoActionsModeFactory;

    public function __construct(
        \Ess\M2ePro\Model\Listing\Auto\Actions\Mode\Factory $listingAutoActionsModeFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Model\Factory $modelFactory
    ) {
        parent::__construct($helperFactory, $activeRecordFactory, $modelFactory);
        $this->listingAutoActionsModeFactory = $listingAutoActionsModeFactory;
    }

    /**
     * @return void
     * @throws \Ess\M2ePro\Model\Exception\Logic
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function process()
    {
        /** @var \Magento\Catalog\Model\Category $category */
        $category = $this->getEventObserver()->getData('category');

        $categoryId = (int)$category->getId();
        $websiteId = (int)$category->getStore()->getWebsiteId();

        $changedProductsIds = $this->getEventObserver()->getData('product_ids');
        $postedProductsIds = array_keys($this->getEventObserver()->getData('category')->getData('posted_products'));

        if (!is_array($changedProductsIds) || count($changedProductsIds) <= 0) {
            return;
        }

        $websitesProductsIds = [
            // website for default store view
            0 => $changedProductsIds
        ];

        if ($websiteId == 0) {
            foreach ($changedProductsIds as $productId) {
                $productModel = $this->modelFactory->getObject('Magento\Product')->setProductId($productId);
                foreach ($productModel->getWebsiteIds() as $websiteId) {
                    $websitesProductsIds[$websiteId][] = $productId;
                }
            }
        } else {
            $websitesProductsIds[$websiteId] = $changedProductsIds;
        }

        foreach ($websitesProductsIds as $websiteId => $productIds) {
            foreach ($productIds as $productId) {

                /** @var \Magento\Catalog\Model\Product $product */
                $product = $this->getHelper('Magento\Product')->getCachedAndLoadedProduct($productId);
                $categoryAutoAction = $this->listingAutoActionsModeFactory->createCategoryMode($product);

                if (in_array($productId, $postedProductsIds)) {
                    $categoryAutoAction->synchWithAddedCategoryId($websiteId, [$categoryId]);
                } else {
                    $categoryAutoAction->synchWithDeletedCategoryId($websiteId, [$categoryId]);
                }
            }
        }
    }
}
