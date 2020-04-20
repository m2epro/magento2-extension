<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\Product\Add;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\Product\Add\CheckNewAsinCategoryProducts
 */
class CheckNewAsinCategoryProducts extends \Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\Product\Add
{
    public function execute()
    {
        $listing = $this->getListing();

        $descriptionTemplatesIds = $listing->getSetting(
            'additional_data',
            'adding_new_asin_description_templates_data'
        );

        foreach ($descriptionTemplatesIds as $listingProductId => $descriptionTemplateId) {
            if (empty($descriptionTemplateId)) {
                $this->setJsonContent([
                    'type' => 'error',
                    'text' => $this->__('You have not selected the Description Policy for some Magento Categories.')
                ]);

                return $this->getResult();
            }
        }

        $listing = $this->getListing();

        $listing->setSetting('additional_data', 'adding_new_asin_description_templates_data', []);
        $listing->save();

        $this->setAjaxContent(1, false);

        return $this->getResult();
    }
}
