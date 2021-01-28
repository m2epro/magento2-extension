<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Other;

use Ess\M2ePro\Controller\Adminhtml\Ebay\Main;
use Ess\M2ePro\Helper\Component\Ebay as ComponentEbay;
use Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Product\Add\SourceMode as SourceModeBlock;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Other\MoveToListing
 */
class MoveToListing extends Main
{
    public function execute()
    {
        $sessionHelper = $this->getHelper('Data\Session');
        $sessionKey = ComponentEbay::NICK . '_' . \Ess\M2ePro\Helper\View::MOVING_LISTING_OTHER_SELECTED_SESSION_KEY;
        $selectedProducts = $sessionHelper->getValue($sessionKey);

        /** @var \Ess\M2ePro\Model\Listing $listingInstance */
        $listingInstance = $this->ebayFactory->getCachedObjectLoaded(
            'Listing',
            (int)$this->getRequest()->getParam('listingId')
        );

        $errorsCount = 0;
        $tempProducts = [];
        $productsHaveOnlineCategory = [];
        foreach ($selectedProducts as $otherListingProduct) {
            /** @var \Ess\M2ePro\Model\Listing\Other $listingOther */
            $listingOther = $this->ebayFactory->getObjectLoaded('Listing\Other', $otherListingProduct);

            /** @var \Ess\M2ePro\Model\Ebay\Listing\Product $listingProduct */
            $listingProduct = $listingInstance->getChildObject()->addProductFromOther(
                $listingOther,
                \Ess\M2ePro\Helper\Data::INITIATOR_USER
            );

            if (!($listingProduct instanceof \Ess\M2ePro\Model\Listing\Product)) {
                $errorsCount++;
                continue;
            }

            $tempProducts[] = $listingProduct->getId();

            $categoryData = $this->getCategoryData($listingProduct->getOnlineMainCategory(), $listingInstance);
            if (!empty($categoryData)) {
                $this->assignMainCategoryToProduct($listingProduct->getId(), $categoryData, $listingInstance);

                $productsHaveOnlineCategory[] = $listingProduct->getId();
                $listingOther->moveToListingSucceed();
            }
        }

        $tempProducts = array_diff($tempProducts, $productsHaveOnlineCategory);

        $addingProducts = array_unique(
            array_merge(
                $tempProducts,
                $listingInstance->getChildObject()->getAddedListingProductsIds()
            )
        );

        if (!empty($addingProducts)) {
            $listingInstance->getChildObject()->setData(
                'product_add_ids',
                $this->getHelper('Data')->jsonEncode($addingProducts)
            );
            $listingInstance->setSetting('additional_data', 'source', SourceModeBlock::MODE_OTHER);
            $listingInstance->getChildObject()->save();
            $listingInstance->save();
        }
        $sessionHelper->removeValue($sessionKey);

        if ($errorsCount) {
            if (count($selectedProducts) == $errorsCount) {

                $this->setJsonContent(
                    [
                        'result'  => false,
                        'message' => $this->__(
                            'Products were not moved because they already exist in the selected Listing.'
                        )
                    ]
                );
                return $this->getResult();
            }

            $this->setJsonContent(
                [
                    'result'   => true,
                    'isFailed' => true,
                    'message'  => $this->__(
                        'Some products were not moved because they already exist in the selected Listing.'
                    )
                ]
            );

        } else {
            $allProductsHaveOnlineCategory = false;
            if (empty($addingProducts) && !empty($productsHaveOnlineCategory)) {
                $allProductsHaveOnlineCategory = true;
            }

            $this->messageManager->addSuccess($this->__('Product(s) was Moved.'));
            $this->setJsonContent(['result' => true, 'hasOnlineCategory' => $allProductsHaveOnlineCategory]);
        }

        return $this->getResult();
    }

    //########################################

    protected function assignMainCategoryToProduct($productId, $categoryData, \Ess\M2ePro\Model\Listing $listing)
    {
        /** @var \Ess\M2ePro\Model\Ebay\Template\Category\Chooser\Converter $converter */
        $converter = $this->modelFactory->getObject('Ebay_Template_Category_Chooser_Converter');
        $converter->setAccountId($listing->getAccountId());
        $converter->setMarketplaceId($listing->getMarketplaceId());
        foreach ($categoryData as $type => $templateData) {
            $converter->setCategoryDataFromChooser($templateData, $type);
        }

        $categoryTpl = $this->modelFactory->getObject('Ebay_Template_Category_Builder')->build(
            $this->activeRecordFactory->getObject('Ebay_Template_Category'),
            $converter->getCategoryDataForTemplate(\Ess\M2ePro\Helper\Component\Ebay\Category::TYPE_EBAY_MAIN)
        );

        $this->activeRecordFactory->getObject('Ebay_Listing_Product')->assignTemplatesToProducts(
            $productId,
            $categoryTpl->getId()
        );
    }

    //----------------------------------------

    protected function getCategoryData($onlineMainCategory, \Ess\M2ePro\Model\Listing $listing)
    {
        $categoryData = [];

        if (empty($onlineMainCategory)) {
            return $categoryData;
        }

        list($path, $value) = explode(" (", $onlineMainCategory);
        $value = trim($value, ')');

        /** @var \Ess\M2ePro\Model\Ebay\Template\Category $templateCategory */
        $templateCategory = $this->activeRecordFactory->getObject('Ebay_Template_Category')->getCollection()
            ->addFieldToFilter('marketplace_id', $listing->getMarketplaceId())
            ->addFieldToFilter('category_id', $value)
            ->addFieldToFilter('is_custom_template', 0)
            ->getFirstItem();

        if ($templateCategory->getId()) {
            $categoryData[\Ess\M2ePro\Helper\Component\Ebay\Category::TYPE_EBAY_MAIN] = [
                'mode'               => \Ess\M2ePro\Model\Ebay\Template\Category::CATEGORY_MODE_EBAY,
                'value'              => $templateCategory->getCategoryValue(),
                'path'               => $path,
                'is_custom_template' => $templateCategory->getIsCustomTemplate(),
                'specific'           => $templateCategory->getSpecifics()
            ];
        }

        return $categoryData;
    }

    //########################################
}
