<?php

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\Product\Template\ShippingOverride;

class ViewGrid extends \Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\Product\Template\ShippingOverride
{
    public function execute()
    {
        $productsIds = $this->getRequest()->getParam('products_ids');
        $marketplaceId = $this->getRequest()->getParam('marketplace_id');

        if (empty($productsIds) && empty($marketplaceId)) {
            $this->setAjaxContent('You should provide correct parameters.', false);
            return $this->getResult();
        }

        if (empty($marketplaceId)) {
            if (!is_array($productsIds)) {
                $productsIds = explode(',', $productsIds);
            }

            /** @var $listingProduct \Ess\M2ePro\Model\Listing\Product */
            $listingProduct = $this->amazonFactory->getObjectLoaded('Listing\Product', $productsIds[0]);
            $marketplaceId = $listingProduct->getListing()->getMarketplaceId();
        }

        $grid = $this->createBlock('Amazon\Listing\Product\Template\ShippingOverride\Grid');
        $grid->setMarketplaceId($marketplaceId);

        $this->setAjaxContent($grid);

        return $this->getResult();
    }
}