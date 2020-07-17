<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Product\Category\Settings;

use \Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Product\Category\Settings;
use Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Product\Category\Settings\Mode as CategoryTemplateBlock;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Product\Category\Settings\StepTwoSaveToSession
 */
class StepTwoSaveToSession extends Settings
{
    //########################################

    public function execute()
    {
        $templateData = $this->getRequest()->getParam('template_data');
        $templateData = (array)$this->getHelper('Data')->jsonDecode($templateData);

        $sessionData = $this->getSessionValue($this->getSessionDataKey());

        foreach ($this->getRequestIds('products_id') as $id) {
            foreach ($templateData as $categoryType => $categoryData) {
                $sessionData[$id][$categoryType] = $categoryData;
                if (empty($sessionData[$id][$categoryType])) {
                    unset($sessionData[$id][$categoryType]);
                }
            }

            if ($this->getSessionValue('mode') == CategoryTemplateBlock::MODE_CATEGORY) {
                $sessionData[$id]['listing_products_ids'] = $this->getSelectedListingProductsIdsByCategoriesIds(
                    [$id]
                );
            } else {
                $sessionData[$id]['listing_products_ids'] = [$id];
            }
        }

        $this->setSessionValue($this->getSessionDataKey(), $sessionData);

        return $this->getResult();
    }

    //########################################
}
