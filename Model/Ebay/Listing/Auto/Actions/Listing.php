<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Listing\Auto\Actions;

class Listing extends \Ess\M2ePro\Model\Listing\Auto\Actions\Listing
{
    //########################################

    /**
     * @param \Magento\Catalog\Model\Product $product
     * @param \Ess\M2ePro\Model\Listing\Auto\Category\Group $categoryGroup
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function addProductByCategoryGroup(\Magento\Catalog\Model\Product $product,
                                              \Ess\M2ePro\Model\Listing\Auto\Category\Group $categoryGroup)
    {
        $logData = array(
            'reason'     => __METHOD__,
            'rule_id'    => $categoryGroup->getId(),
            'rule_title' => $categoryGroup->getTitle(),
        );
        $listingProduct = $this->getListing()->addProduct(
            $product, \Ess\M2ePro\Helper\Data::INITIATOR_EXTENSION, false, true, $logData
        );

        if (!($listingProduct instanceof \Ess\M2ePro\Model\Listing\Product)) {
            return;
        }

        /** @var \Ess\M2ePro\Model\Ebay\Listing\Auto\Category\Group $ebayCategoryGroup */
        $ebayCategoryGroup = $categoryGroup->getChildObject();

        $params = array(
            'template_category_id' => $ebayCategoryGroup->getAddingTemplateCategoryId(),
            'template_other_category_id' => $ebayCategoryGroup->getAddingTemplateOtherCategoryId(),
        );

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
        $logData = array(
            'reason' => __METHOD__,
        );
        $listingProduct = $this->getListing()->addProduct(
            $product, \Ess\M2ePro\Helper\Data::INITIATOR_EXTENSION, false, true, $logData
        );

        if (!($listingProduct instanceof \Ess\M2ePro\Model\Listing\Product)) {
            return;
        }

        $this->logAddedToMagentoProduct($listingProduct);

        /** @var \Ess\M2ePro\Model\Ebay\Listing $ebayListing */
        $ebayListing = $listing->getChildObject();

        $params = array(
            'template_category_id' => $ebayListing->getAutoGlobalAddingTemplateCategoryId(),
            'template_other_category_id' => $ebayListing->getAutoGlobalAddingTemplateOtherCategoryId(),
        );

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
        $logData = array(
            'reason' => __METHOD__,
        );
        $listingProduct = $this->getListing()->addProduct(
            $product, \Ess\M2ePro\Helper\Data::INITIATOR_EXTENSION, false, true, $logData
        );

        if (!($listingProduct instanceof \Ess\M2ePro\Model\Listing\Product)) {
            return;
        }

        /** @var \Ess\M2ePro\Model\Ebay\Listing $ebayListing */
        $ebayListing = $listing->getChildObject();

        $params = array(
            'template_category_id' => $ebayListing->getAutoWebsiteAddingTemplateCategoryId(),
            'template_other_category_id' => $ebayListing->getAutoWebsiteAddingTemplateOtherCategoryId(),
        );

        $this->processAddedListingProduct($listingProduct, $params);
    }

    //########################################

    protected function processAddedListingProduct(\Ess\M2ePro\Model\Listing\Product $listingProduct, array $params)
    {
        $ebayListingProduct = $listingProduct->getChildObject();

        if (!empty($params['template_category_id'])) {
            $ebayListingProduct->setData('template_category_id',$params['template_category_id']);
        }

        if (!empty($params['template_other_category_id'])) {
            $ebayListingProduct->setData('template_other_category_id',$params['template_other_category_id']);
        }

        $listingProduct->save();
    }

    //########################################
}