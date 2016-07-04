<?php

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Product\Category\Settings;

use \Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Product\Category\Settings;

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

            $hasRequiredSpecifics = $this->getHelper('Component\Ebay\Category\Ebay')->hasRequiredSpecifics(
                $templateData['category_main_id'],
                $listing->getMarketplaceId()
            );

        } else {
            $hasRequiredSpecifics = true;
        }

        $this->setSessionValue('current_primary_category', $category);

        $this->setJsonContent(array(
            'html' => $this->getSpecificBlock()->toHtml(),
            'hasRequiredSpecifics' => $hasRequiredSpecifics
        ));

        return $this->getResult();
    }

    //########################################
}