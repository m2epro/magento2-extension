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
use Ess\M2ePro\Helper\Component\Ebay\Category as EbayCategory;

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
            if (!empty($categoryData) && !isset($categoryData['create_new_category'])) {
                $this->activeRecordFactory->getObject('Ebay_Listing_Product')->assignTemplatesToProducts(
                    $listingProduct->getId(),
                    $categoryData['id']
                );

                $productsHaveOnlineCategory[] = $listingProduct->getId();
                $listingOther->moveToListingSucceed();
            } elseif (!empty($categoryData) && isset($categoryData['create_new_category'])) {
                $categoryData[EbayCategory::TYPE_EBAY_MAIN] = $categoryData['create_new_category'];
                unset($categoryData['create_new_category']);
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
            $categoryData['id'] = $templateCategory->getId();
        } else {
            $categoryData['create_new_category'] = [
                'mode'               => \Ess\M2ePro\Model\Ebay\Template\Category::CATEGORY_MODE_EBAY,
                'value'              => $value,
                'path'               => $path,
                'is_custom_template' => 0,
                'specific'           => []
            ];
        }

        return $categoryData;
    }

    //########################################
}
