<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\Product\Template\Shipping;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\Product\Template\Shipping\ViewGrid
 */
class ViewGrid extends \Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\Product\Template\Shipping
{
    public function execute()
    {
        $productsIds   = $this->getRequest()->getParam('products_ids');
        $marketplaceId = $this->getRequest()->getParam('marketplace_id');

        if (empty($productsIds) && empty($marketplaceId)) {
            $this->setAjaxContent('You should provide correct parameters.', false);
            return $this->getResult();
        }

        if (empty($marketplaceId)) {
            if (!is_array($productsIds)) {
                $productsIds = explode(',', $productsIds);
            }

            /** @var \Ess\M2ePro\Model\Listing\Product $listingProduct */
            $listingProduct = $this->amazonFactory->getObjectLoaded('Listing\Product', $productsIds[0]);
            $marketplaceId = $listingProduct->getListing()->getMarketplaceId();
        }

        if (!is_array($productsIds)) {
            $productsIds = explode(',', $productsIds);
        }

        $grid = $this->getLayout()
                     ->createBlock(\Ess\M2ePro\Block\Adminhtml\Amazon\Listing\Product\Template\Shipping\Grid::class);
        $grid->setMarketplaceId($marketplaceId);
        $grid->setProductsIds($productsIds);

        $this->setAjaxContent($grid);

        return $this->getResult();
    }
}
