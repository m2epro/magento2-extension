<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Product\Category\Settings;

use \Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Product\Category\Settings;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Product\Category\Settings\StepThreeGetCategorySpecifics
 */
class StepThreeGetCategorySpecifics extends Settings
{

    //########################################

    public function execute()
    {
        $category = $this->getRequest()->getParam('category');
        $templateData = $this->getTemplatesData();
        $templateData = $templateData[$category];

        if ($templateData['category_main_mode'] == \Ess\M2ePro\Model\Ebay\Template\Category::CATEGORY_MODE_EBAY) {
            $listing = $this->getListing();

            $hasRequiredSpecifics = $this->getHelper('Component_Ebay_Category_Ebay')->hasRequiredSpecifics(
                $templateData['category_main_id'],
                $listing->getMarketplaceId()
            );
        } else {
            $hasRequiredSpecifics = true;
        }

        $this->setSessionValue('current_primary_category', $category);

        $this->setJsonContent([
            'html' => $this->getSpecificBlock()->toHtml(),
            'hasRequiredSpecifics' => $hasRequiredSpecifics
        ]);

        return $this->getResult();
    }

    //########################################
}
