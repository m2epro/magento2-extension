<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Product\Category\Settings;

use \Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Product\Category\Settings;
use \Ess\M2ePro\Helper\Component\Ebay\Category as eBayCategory;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Product\Category\Settings\GetChooserBlockHtml
 */
class GetChooserBlockHtml extends Settings
{
    //########################################

    public function execute()
    {
        $listing = $this->getListingFromRequest();

        /** @var $chooserBlock \Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Product\Category\Settings\Chooser */
        $chooserBlock = $this->createBlock('Ebay_Listing_Product_Category_Settings_Chooser');
        $chooserBlock->setAccountId($listing->getAccountId());
        $chooserBlock->setMarketplaceId($listing->getMarketplaceId());
        $chooserBlock->setCategoryMode($this->getRequest()->getParam('category_mode'));

        $categoriesData = $this->getCategoriesDataForChooserBlock();
        $chooserBlock->setCategoriesData($categoriesData);

        $this->setAjaxContent($chooserBlock->toHtml());

        return $this->getResult();
    }

    //########################################

    protected function getCategoriesDataForChooserBlock()
    {
        $sessionData = $this->getSessionValue($this->getSessionDataKey());

        $neededProducts = [];
        foreach ($this->getRequestIds('products_id') as $id) {
            $temp = [];
            foreach ($this->getHelper('Component_Ebay_Category')->getCategoriesTypes() as $categoryType) {
                isset($sessionData[$id][$categoryType]) && $temp[$categoryType] = $sessionData[$id][$categoryType];
            }

            $neededProducts[$id] = $temp;
        }

        $first = reset($neededProducts);
        $resultData = $first;

        foreach ($neededProducts as $lp => $templatesData) {
            if (empty($resultData)) {
                return [];
            }

            foreach ($templatesData as $categoryType => $categoryData) {
                if (!isset($resultData[$categoryType])) {
                    continue;
                }

                !isset($first[$categoryType]['specific']) && $first[$categoryType]['specific'] = [];
                !isset($categoryData['specific']) && $categoryData['specific'] = [];

                if ($first[$categoryType]['template_id'] !== $categoryData['template_id'] ||
                    $first[$categoryType]['is_custom_template'] !== $categoryData['is_custom_template'] ||
                    $first[$categoryType]['specific'] !== $categoryData['specific']
                ) {
                    $resultData[$categoryType]['template_id'] = null;
                    $resultData[$categoryType]['is_custom_template'] = null;
                    $resultData[$categoryType]['specific'] = [];
                }

                if ($first[$categoryType]['mode'] !== $categoryData['mode'] ||
                    $first[$categoryType]['value'] !== $categoryData['value'] ||
                    $first[$categoryType]['path'] !== $categoryData['path']
                ) {
                    unset($resultData[$categoryType]);
                }
            }
        }

        return !$resultData ? [] : $resultData;
    }

    //########################################
}
