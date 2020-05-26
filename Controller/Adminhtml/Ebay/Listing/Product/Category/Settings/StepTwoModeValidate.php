<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Product\Category\Settings;

use \Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Product\Category\Settings;

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

        $this->clearSpecificsSession();

        $failedProductsIds   = [];
        $succeedProducersIds = [];
        foreach ($sessionData as $listingProductId => $categoryData) {
            if ($categoryData['category_main_mode'] == \Ess\M2ePro\Model\Ebay\Template\Category::CATEGORY_MODE_EBAY) {
                $key = 'category_main_id';
            } else {
                $key = 'category_main_attribute';
            }

            if (!$categoryData[$key]) {
                $failedProductsIds[] = $listingProductId;
            } else {
                $succeedProducersIds[] = $listingProductId;
            }
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
