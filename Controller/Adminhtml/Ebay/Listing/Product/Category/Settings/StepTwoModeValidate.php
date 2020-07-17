<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Product\Category\Settings;

use \Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Product\Category\Settings;
use Ess\M2ePro\Helper\Component\Ebay\Category as eBayCategory;
use \Ess\M2ePro\Model\Ebay\Template\Category as TemplateCategory;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Product\Category\Settings\StepTwoModeValidate
 */
class StepTwoModeValidate extends Settings
{
    //########################################

    public function execute()
    {
        $sessionData = $this->getSessionValue($this->getSessionDataKey());
        $sessionData = $this->convertCategoriesIdstoProductIds($sessionData);

        $listing = $this->getListingFromRequest();
        $validateSpecifics = $this->getRequest()->getParam('validate_specifics');
        $validateCategory  = $this->getRequest()->getParam('validate_category');

        $failedProductsIds   = [];
        $succeedProducersIds = [];
        foreach ($sessionData as $listingProductId => $categoryData) {

            if (!isset($categoryData[eBayCategory::TYPE_EBAY_MAIN]) ||
                $categoryData[eBayCategory::TYPE_EBAY_MAIN]['mode'] === TemplateCategory::CATEGORY_MODE_NONE
            ) {
                $validateCategory ? $failedProductsIds[] = $listingProductId
                    : $succeedProducersIds[] = $listingProductId;
                continue;
            }

            if (!$validateSpecifics) {
                $succeedProducersIds[] = $listingProductId;
                continue;
            }

            if ($categoryData[eBayCategory::TYPE_EBAY_MAIN]['is_custom_template'] !== null) {
                $succeedProducersIds[] = $listingProductId;
                continue;
            }

            $hasRequiredSpecifics = $this->getHelper('Component_Ebay_Category_Ebay')->hasRequiredSpecifics(
                $categoryData[eBayCategory::TYPE_EBAY_MAIN]['value'],
                $listing->getMarketplaceId()
            );

            if (!$hasRequiredSpecifics) {
                $succeedProducersIds[] = $listingProductId;
                continue;
            }

            $failedProductsIds[] = $listingProductId;
        }

        $this->setJsonContent([
            'validation'      => empty($failedProductsIds),
            'total_count'     => count($failedProductsIds) + count($succeedProducersIds),
            'failed_count'    => count($failedProductsIds),
            'failed_products' => $failedProductsIds
        ]);

        return $this->getResult();
    }

    //########################################
}
