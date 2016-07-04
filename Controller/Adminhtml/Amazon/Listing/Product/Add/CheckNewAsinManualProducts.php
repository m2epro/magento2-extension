<?php

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\Product\Add;

class CheckNewAsinManualProducts extends \Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\Product\Add
{
    public function execute()
    {
        $listingProductsIds = $this->getListing()->getSetting('additional_data','adding_new_asin_listing_products_ids');

        /** @var \Ess\M2ePro\Model\ResourceModel\Amazon\Listing\Product\Collection $collection */
        $collection = $this->amazonFactory->getObject('Listing\Product')->getCollection();
        $collection->getSelect()->where(
            "`main_table`.`id` IN (?) AND `second_table`.`template_description_id` IS NULL", $listingProductsIds
        );

        $data = $collection->getData();

        if (empty($data)) {
            $this->setAjaxContent(1, false);

            return $this->getResult();
        }

        $popup = $this->createBlock('Amazon\Listing\Product\Add\NewAsin\Manual\SkipPopup');

        $this->setJsonContent([
            'total_count' => count($listingProductsIds),
            'failed_count' => count($data),
            'html' => $popup->toHtml(),
            'continueUrl' => $this->getUrl('*/*/index', array('id' => $this->getRequest()->getParam('id'), 'step' => 5))
        ]);

        return $this->getResult();
    }
}