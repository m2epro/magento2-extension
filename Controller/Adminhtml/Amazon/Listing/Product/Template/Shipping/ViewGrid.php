<?php

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\Product\Template\Shipping;

class ViewGrid extends \Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\Product\Template\Shipping
{
    public function execute()
    {
        $productsIds   = $this->getRequest()->getParam('products_ids');
        $marketplaceId = $this->getRequest()->getParam('marketplace_id');
        $shippingMode  = $this->getRequest()->getParam('shipping_mode');

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

        if (!is_array($productsIds)) {
            $productsIds = explode(',', $productsIds);
        }

        $blockName = ($shippingMode == \Ess\M2ePro\Model\Amazon\Account::SHIPPING_MODE_OVERRIDE)
            ? 'Amazon\Listing\Product\Template\ShippingOverride\Grid'
            : 'Amazon\Listing\Product\Template\ShippingTemplate\Grid';

        $grid = $this->createBlock($blockName);
        $grid->setMarketplaceId($marketplaceId);
        $grid->setProductsIds($productsIds);

        $this->setAjaxContent($grid);

        return $this->getResult();
    }
}