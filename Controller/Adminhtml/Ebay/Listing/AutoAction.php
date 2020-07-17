<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Listing;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\AutoAction
 */
abstract class AutoAction extends \Ess\M2ePro\Controller\Adminhtml\Ebay\Listing
{
    //########################################

    /**
     * @param \Ess\M2ePro\Model\Listing $listing
     * @param int $categoryType
     * @param $autoMode
     * @param int $groupId
     * @param int $magentoCategoryId
     * @return \Ess\M2ePro\Model\Ebay\Template\Category|null
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    protected function getCategoryTemplate($listing, $categoryType, $autoMode, $groupId, $magentoCategoryId)
    {
        switch ($autoMode) {
            case \Ess\M2ePro\Model\Listing::AUTO_MODE_GLOBAL:
                if ($categoryType == \Ess\M2ePro\Helper\Component\Ebay\Category::TYPE_EBAY_MAIN) {
                    return $listing->getChildObject()->getAutoGlobalAddingCategoryTemplate();
                }
                return $listing->getChildObject()->getAutoGlobalAddingCategorySecondaryTemplate();

            case \Ess\M2ePro\Model\Listing::AUTO_MODE_WEBSITE:
                if ($categoryType == \Ess\M2ePro\Helper\Component\Ebay\Category::TYPE_EBAY_MAIN) {
                    return $listing->getChildObject()->getAutoWebsiteAddingCategoryTemplate();
                }
                return $listing->getChildObject()->getAutoWebsiteAddingCategorySecondaryTemplate();

            case \Ess\M2ePro\Model\Listing::AUTO_MODE_CATEGORY:
                if ($magentoCategoryId) {
                    /** @var \Ess\M2ePro\Model\Listing\Auto\Category $autoCategory */
                    $autoCategory = $this->activeRecordFactory->getObject('Listing_Auto_Category')->getCollection()
                        ->addFieldToFilter('group_id', $groupId)
                        ->addFieldToFilter('category_id', $magentoCategoryId)
                        ->getFirstItem();

                    if ($autoCategory->getId()) {
                        $template = $this->activeRecordFactory->getObjectLoaded(
                            'Ebay_Listing_Auto_Category_Group',
                            $groupId
                        );
                        if ($categoryType == \Ess\M2ePro\Helper\Component\Ebay\Category::TYPE_EBAY_MAIN) {
                            return $template->getCategoryTemplate();
                        }

                        return $template->getCategorySecondaryTemplate();
                    }
                }
                return null;
        }

        return null;
    }

    /**
     * @param \Ess\M2ePro\Model\Listing $listing
     * @param int $categoryType
     * @param $autoMode
     * @param int $groupId
     * @param int $magentoCategoryId
     * @return \Ess\M2ePro\Model\Ebay\Template\StoreCategory|null
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    protected function getStoreCategoryTemplate($listing, $categoryType, $autoMode, $groupId, $magentoCategoryId)
    {
        switch ($autoMode) {
            case \Ess\M2ePro\Model\Listing::AUTO_MODE_GLOBAL:
                if ($categoryType == \Ess\M2ePro\Helper\Component\Ebay\Category::TYPE_STORE_MAIN) {
                    return $listing->getChildObject()->getAutoGlobalAddingStoreCategoryTemplate();
                }
                return $listing->getChildObject()->getAutoGlobalAddingStoreCategorySecondaryTemplate();

            case \Ess\M2ePro\Model\Listing::AUTO_MODE_WEBSITE:
                if ($categoryType == \Ess\M2ePro\Helper\Component\Ebay\Category::TYPE_STORE_MAIN) {
                    return $listing->getChildObject()->getAutoWebsiteAddingStoreCategoryTemplate();
                }
                return $listing->getChildObject()->getAutoWebsiteAddingStoreCategorySecondaryTemplate();

            case \Ess\M2ePro\Model\Listing::AUTO_MODE_CATEGORY:
                if ($magentoCategoryId) {
                    /** @var \Ess\M2ePro\Model\Listing\Auto\Category $autoCategory */
                    $autoCategory = $this->activeRecordFactory->getObject('Listing_Auto_Category')->getCollection()
                        ->addFieldToFilter('group_id', $groupId)
                        ->addFieldToFilter('category_id', $magentoCategoryId)
                        ->getFirstItem();

                    if ($autoCategory->getId()) {
                        $template = $this->activeRecordFactory->getObjectLoaded(
                            'Ebay_Listing_Auto_Category_Group',
                            $groupId
                        );
                        if ($categoryType == \Ess\M2ePro\Helper\Component\Ebay\Category::TYPE_STORE_MAIN) {
                            return $template->getStoreCategoryTemplate();
                        }

                        return $template->getStoreCategorySecondaryTemplate();
                    }
                }
                return null;
        }

        return null;
    }



    //########################################
}
