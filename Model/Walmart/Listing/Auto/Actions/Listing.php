<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Walmart\Listing\Auto\Actions;

/**
 * Class \Ess\M2ePro\Model\Walmart\Listing\Auto\Actions\Listing
 */
class Listing extends \Ess\M2ePro\Model\Listing\Auto\Actions\Listing
{
    //########################################

    /**
     * @param \Magento\Catalog\Model\Product $product
     * @param $deletingMode
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function deleteProduct(\Magento\Catalog\Model\Product $product, $deletingMode)
    {
        if ($deletingMode == \Ess\M2ePro\Model\Listing::DELETING_MODE_NONE) {
            return;
        }

        $listingsProducts = $this->getListing()->getProducts(true, ['product_id' => (int)$product->getId()]);

        if (count($listingsProducts) <= 0) {
            return;
        }

        /** @var \Ess\M2ePro\Model\Listing\Product[] $parentsForRemove */
        $parentsForRemove = [];

        foreach ($listingsProducts as $listingProduct) {
            if (!($listingProduct instanceof \Ess\M2ePro\Model\Listing\Product)) {
                return;
            }

            /** @var \Ess\M2ePro\Model\Walmart\Listing\Product $walmartListingProduct */
            $walmartListingProduct = $listingProduct->getChildObject();

            if ($walmartListingProduct->getVariationManager()->isRelationParentType() &&
                $deletingMode == \Ess\M2ePro\Model\Listing::DELETING_MODE_STOP_REMOVE
            ) {
                $parentsForRemove[$listingProduct->getId()] = $listingProduct;
                continue;
            }

            try {
                if ($deletingMode == \Ess\M2ePro\Model\Listing::DELETING_MODE_STOP) {
                    $listingProduct->isStoppable() &&
                    $this->activeRecordFactory->getObject('StopQueue')->add($listingProduct);
                }

                if ($deletingMode == \Ess\M2ePro\Model\Listing::DELETING_MODE_STOP_REMOVE) {
                    $listingProduct->isStoppable() &&
                    $this->activeRecordFactory->getObject('StopQueue')->add($listingProduct);
                    $listingProduct->addData([
                        'status' => \Ess\M2ePro\Model\Listing\Product::STATUS_STOPPED
                    ])->save();
                    $listingProduct->delete();
                }
            } catch (\Exception $exception) {
            }
        }

        if (empty($parentsForRemove)) {
            return;
        }

        foreach ($parentsForRemove as $parentListingProduct) {
            $parentListingProduct->setData('status', \Ess\M2ePro\Model\Listing\Product::STATUS_NOT_LISTED);
            $parentListingProduct->delete();
        }
    }

    //########################################

    /**
     * @param \Magento\Catalog\Model\Product $product
     * @param \Ess\M2ePro\Model\Listing\Auto\Category\Group $categoryGroup
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function addProductByCategoryGroup(
        \Magento\Catalog\Model\Product $product,
        \Ess\M2ePro\Model\Listing\Auto\Category\Group $categoryGroup
    ) {
        $logData = [
            'reason'     => __METHOD__,
            'rule_id'    => $categoryGroup->getId(),
            'rule_title' => $categoryGroup->getTitle(),
        ];
        $listingProduct = $this->getListing()->addProduct(
            $product,
            \Ess\M2ePro\Helper\Data::INITIATOR_EXTENSION,
            false,
            true,
            $logData
        );

        if (!($listingProduct instanceof \Ess\M2ePro\Model\Listing\Product)) {
            return;
        }

        /** @var \Ess\M2ePro\Model\Walmart\Listing\Auto\Category\Group $walmartCategoryGroup */
        $walmartCategoryGroup = $categoryGroup->getChildObject();

        $params = [
            'template_category_id' => $walmartCategoryGroup->getAddingCategoryTemplateId(),
        ];

        $this->processAddedListingProduct($listingProduct, $params);
    }

    /**
     * @param \Magento\Catalog\Model\Product $product
     * @param \Ess\M2ePro\Model\Listing $listing
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function addProductByGlobalListing(
        \Magento\Catalog\Model\Product $product,
        \Ess\M2ePro\Model\Listing $listing
    ) {
        $logData = [
            'reason' => __METHOD__,
        ];
        $listingProduct = $this->getListing()->addProduct(
            $product,
            \Ess\M2ePro\Helper\Data::INITIATOR_EXTENSION,
            false,
            true,
            $logData
        );

        if (!($listingProduct instanceof \Ess\M2ePro\Model\Listing\Product)) {
            return;
        }

        $this->logAddedToMagentoProduct($listingProduct);

        /** @var \Ess\M2ePro\Model\Walmart\Listing $walmartListing */
        $walmartListing = $listing->getChildObject();

        $params = [
            'template_category_id' => $walmartListing->getAutoGlobalAddingCategoryTemplateId(),
        ];

        $this->processAddedListingProduct($listingProduct, $params);
    }

    /**
     * @param \Magento\Catalog\Model\Product $product
     * @param \Ess\M2ePro\Model\Listing $listing
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function addProductByWebsiteListing(
        \Magento\Catalog\Model\Product $product,
        \Ess\M2ePro\Model\Listing $listing
    ) {
        $logData = [
            'reason' => __METHOD__,
        ];
        $listingProduct = $this->getListing()->addProduct(
            $product,
            \Ess\M2ePro\Helper\Data::INITIATOR_EXTENSION,
            false,
            true,
            $logData
        );

        if (!($listingProduct instanceof \Ess\M2ePro\Model\Listing\Product)) {
            return;
        }

        /** @var \Ess\M2ePro\Model\Walmart\Listing $walmartListing */
        $walmartListing = $listing->getChildObject();

        $params = [
            'template_category_id' => $walmartListing->getAutoWebsiteAddingCategoryTemplateId(),
        ];

        $this->processAddedListingProduct($listingProduct, $params);
    }

    //########################################

    protected function processAddedListingProduct(\Ess\M2ePro\Model\Listing\Product $listingProduct, array $params)
    {
        if (empty($params['template_category_id'])) {
            return;
        }

        /** @var \Ess\M2ePro\Model\Walmart\Listing\Product $walmartListingProduct */
        $walmartListingProduct = $listingProduct->getChildObject();
        $walmartListingProduct->setData('template_category_id', $params['template_category_id']);
        $walmartListingProduct->save();

        if ($walmartListingProduct->getVariationManager()->isRelationParentType()) {
            $processor = $walmartListingProduct->getVariationManager()->getTypeModel()->getProcessor();
            $processor->process();
        }
    }

    //########################################
}
