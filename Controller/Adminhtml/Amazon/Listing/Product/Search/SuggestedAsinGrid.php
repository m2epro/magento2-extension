<?php

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\Product\Search;

use Ess\M2ePro\Controller\Adminhtml\Amazon\Main;

class SuggestedAsinGrid extends Main
{
    public function execute()
    {
        $productId = $this->getRequest()->getParam('product_id');

        if (empty($productId)) {
            $this->setAjaxContent('ERROR: No Product ID!', false);

            return $this->getResult();
        }

        /** @var $listingProduct \Ess\M2ePro\Model\Listing\Product */
        $listingProduct = $this->amazonFactory->getObjectLoaded('Listing\Product',$productId);

        $marketplaceId = $listingProduct->getListing()->getMarketplaceId();

        $searchSettingsData = $listingProduct->getChildObject()->getSettings('search_settings_data');
        if (!empty($searchSettingsData['data'])) {
            $this->getHelper('Data\GlobalData')->setValue('product_id',$productId);
            $this->getHelper('Data\GlobalData')->setValue('marketplace_id',$marketplaceId);
            $this->getHelper('Data\GlobalData')->setValue('search_data',$searchSettingsData);

            $this->setAjaxContent($this->createBlock('Amazon\Listing\Product\Search\Grid'));
        } else {
            $this->setAjaxContent($this->__('NO DATA'), false);
        }

        return $this->getResult();
    }
}