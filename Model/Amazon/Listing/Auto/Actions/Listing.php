<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Listing\Auto\Actions;

class Listing extends \Ess\M2ePro\Model\Listing\Auto\Actions\Listing
{
    protected $amazonFactory;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory
    )
    {
        $this->amazonFactory = $amazonFactory;
        parent::__construct($activeRecordFactory, $helperFactory, $modelFactory);
    }

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

        $listingsProducts = $this->getListing()->getProducts(true,array('product_id'=>(int)$product->getId()));

        if (count($listingsProducts) <= 0) {
            return;
        }

        /** @var \Ess\M2ePro\Model\Listing\Product[] $parentsForRemove */
        $parentsForRemove = array();

        foreach ($listingsProducts as $listingProduct) {

            if (!($listingProduct instanceof \Ess\M2ePro\Model\Listing\Product)) {
                return;
            }

            /** @var \Ess\M2ePro\Model\Amazon\Listing\Product $amazonListingProduct */
            $amazonListingProduct = $listingProduct->getChildObject();

            if ($amazonListingProduct->getVariationManager()->isRelationParentType() &&
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
                    $listingProduct->addData(array(
                        'status' => \Ess\M2ePro\Model\Listing\Product::STATUS_STOPPED
                    ))->save();
                    $listingProduct->delete();
                }

            } catch (\Exception $exception) {}
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
    )
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

        /** @var \Ess\M2ePro\Model\Amazon\Listing\Auto\Category\Group $amazonCategoryGroup */
        $amazonCategoryGroup = $categoryGroup->getChildObject();

        $params = array(
            'template_description_id' => $amazonCategoryGroup->getAddingDescriptionTemplateId(),
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
    )
    {
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

        /** @var \Ess\M2ePro\Model\Amazon\Listing $amazonListing */
        $amazonListing = $listing->getChildObject();

        $params = array(
            'template_description_id' => $amazonListing->getAutoGlobalAddingDescriptionTemplateId(),
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
    )
    {
        $logData = array(
            'reason' => __METHOD__,
        );
        $listingProduct = $this->getListing()->addProduct(
            $product, \Ess\M2ePro\Helper\Data::INITIATOR_EXTENSION, false, true, $logData
        );

        if (!($listingProduct instanceof \Ess\M2ePro\Model\Listing\Product)) {
            return;
        }

        /** @var \Ess\M2ePro\Model\Amazon\Listing $amazonListing */
        $amazonListing = $listing->getChildObject();

        $params = array(
            'template_description_id' => $amazonListing->getAutoWebsiteAddingDescriptionTemplateId(),
        );

        $this->processAddedListingProduct($listingProduct, $params);
    }

    //########################################

    protected function processAddedListingProduct(\Ess\M2ePro\Model\Listing\Product $listingProduct, array $params)
    {
        if (empty($params['template_description_id'])) {
            return;
        }

        /** @var \Ess\M2ePro\Model\Amazon\Listing\Product $amazonListingProduct */
        $amazonListingProduct = $listingProduct->getChildObject();

        if (!$amazonListingProduct->getVariationManager()->isRelationParentType()) {

            $amazonListingProduct->setData('template_description_id', $params['template_description_id']);
            $amazonListingProduct->setData(
                'is_general_id_owner',
                \Ess\M2ePro\Model\Amazon\Listing\Product::IS_GENERAL_ID_OWNER_YES
            );

            $listingProduct->save();

            return;
        }

        $processor = $amazonListingProduct->getVariationManager()->getTypeModel()->getProcessor();

        if ($listingProduct->getMagentoProduct()->isBundleType() ||
            $listingProduct->getMagentoProduct()->isSimpleTypeWithCustomOptions() ||
            $listingProduct->getMagentoProduct()->isDownloadableTypeWithSeparatedLinks()
        ) {
            $processor->process();
            return;
        }

        $detailsModel = $this->modelFactory->getObject('Amazon\Marketplace\Details');
        $detailsModel->setMarketplaceId($listingProduct->getListing()->getMarketplaceId());

        /** @var \Ess\M2ePro\Model\Template\Description $descriptionTemplate */
        $descriptionTemplate = $this->amazonFactory->getObjectLoaded(
            'Template\Description', $params['template_description_id']
        );

        /** @var \Ess\M2ePro\Model\Amazon\Template\Description $amazonDescriptionTemplate */
        $amazonDescriptionTemplate = $descriptionTemplate->getChildObject();

        $possibleThemes = $detailsModel->getVariationThemes($amazonDescriptionTemplate->getProductDataNick());

        $productAttributes = $amazonListingProduct->getVariationManager()
            ->getTypeModel()
            ->getProductAttributes();

        foreach ($possibleThemes as $theme) {
            if (count($theme['attributes']) != count($productAttributes)) {
                continue;
            }

            $amazonListingProduct->setData('template_description_id', $params['template_description_id']);
            $amazonListingProduct->setData(
                'is_general_id_owner',
                \Ess\M2ePro\Model\Amazon\Listing\Product::IS_GENERAL_ID_OWNER_YES
            );

            break;
        }

        $listingProduct->save();

        $processor->process();
    }

    //########################################
}