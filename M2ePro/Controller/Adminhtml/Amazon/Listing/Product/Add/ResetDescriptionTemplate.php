<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\Product\Add;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\Product\Add\ResetDescriptionTemplate
 */
class ResetDescriptionTemplate extends \Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\Product\Add
{
    //########################################

    public function execute()
    {
        $listingId = $this->getRequest()->getParam('listing_id');

        /** @var \Ess\M2ePro\Model\Listing $listing */
        $listing = $this->amazonFactory->getCachedObjectLoaded('Listing', $listingId);
        $ids = (array)$listing->getSetting('additional_data', 'adding_new_asin_listing_products_ids');
        $ids = $this->getHelper('Component_Amazon_Variation')->filterLockedProducts($ids);

        $listing->setSetting('additional_data', 'adding_new_asin_description_templates_data', []);
        $this->setDescriptionTemplate($ids, null);

        $this->setJsonContent([
            'back_url' => $this->getUrl(
                '*/amazon_listing_product_add/index',
                [
                    'id'   => $listingId,
                    'step' => 4
                ]
            )
        ]);

        return $this->getResult();
    }

    //########################################
}
