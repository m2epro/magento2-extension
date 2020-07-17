<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Listing\Auto\Actions;

/**
 * Class \Ess\M2ePro\Model\Ebay\Listing\Auto\Actions\Listing
 */
class Listing extends \Ess\M2ePro\Model\Listing\Auto\Actions\Listing
{
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

        /** @var \Ess\M2ePro\Model\Ebay\Listing\Auto\Category\Group $group */
        $group = $categoryGroup->getChildObject();

        $params = [
            'template_category_id'                 => $group->getAddingTemplateCategoryId(),
            'template_category_secondary_id'       => $group->getAddingTemplateCategorySecondaryId(),
            'template_store_category_id'           => $group->getAddingTemplateStoreCategoryId(),
            'template_store_category_secondary_id' => $group->getAddingTemplateStoreCategorySecondaryId(),
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

        /** @var \Ess\M2ePro\Model\Ebay\Listing $eListing */
        $eListing = $listing->getChildObject();

        $params = [
            'template_category_id'                 => $eListing->getAutoGlobalAddingTemplateCategoryId(),
            'template_category_secondary_id'       => $eListing->getAutoGlobalAddingTemplateCategorySecondaryId(),
            'template_store_category_id'           => $eListing->getAutoGlobalAddingTemplateStoreCategoryId(),
            'template_store_category_secondary_id' => $eListing->getAutoGlobalAddingTemplateStoreCategorySecondaryId()
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

        /** @var \Ess\M2ePro\Model\Ebay\Listing $eListing */
        $eListing = $listing->getChildObject();

        $params = [
            'template_category_id'                 => $eListing->getAutoWebsiteAddingTemplateCategoryId(),
            'template_category_secondary_id'       => $eListing->getAutoWebsiteAddingTemplateCategorySecondaryId(),
            'template_store_category_id'           => $eListing->getAutoWebsiteAddingTemplateStoreCategoryId(),
            'template_store_category_secondary_id' => $eListing->getAutoWebsiteAddingTemplateStoreCategorySecondaryId()
        ];

        $this->processAddedListingProduct($listingProduct, $params);
    }

    //########################################

    protected function processAddedListingProduct(\Ess\M2ePro\Model\Listing\Product $listingProduct, array $params)
    {
        $ebayListingProduct = $listingProduct->getChildObject();

        $keys = [
            'template_category_id',
            'template_category_secondary_id',
            'template_store_category_id',
            'template_store_category_secondary_id'
        ];

        foreach ($keys as $key) {
            !empty($params[$key]) && $ebayListingProduct->setData($key, $params[$key]);
        }

        $listingProduct->save();
    }

    //########################################
}
