<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\Product\Template\ProductType;

class ViewGrid extends \Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\Product\Template\ProductType
{
    /**
     * @inheridoc
     */
    public function execute()
    {
        $productsIds = $this->getRequest()->getParam('products_ids');
        $marketplaceId = $this->getRequest()->getParam('marketplace_id');

        $checkNewAsinAccepted = $this->getRequest()->getParam('check_is_new_asin_accepted', 0);
        $mapToTemplateJsFn = $this->getRequest()->getParam('map_to_template_js_fn', false);
        $createNewTemplateJsFn = $this->getRequest()->getParam('create_new_template_js_fn', false);

        if (empty($productsIds)) {
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

        $grid = $this
            ->getLayout()
            ->createBlock(\Ess\M2ePro\Block\Adminhtml\Amazon\Listing\Product\Template\ProductType\Grid::class);
        $grid->setMarketplaceId((int)$marketplaceId);
        $grid->setProductsIds($productsIds);
        $grid->setCheckNewAsinAccepted((bool)$checkNewAsinAccepted);
        if ($mapToTemplateJsFn !== false) {
            $grid->setMapToTemplateJsFn((string)$mapToTemplateJsFn);
        }
        if ($createNewTemplateJsFn !== false) {
            $grid->setCreateNewTemplateJsFn((string)$createNewTemplateJsFn);
        }

        $this->setAjaxContent($grid);

        return $this->getResult();
    }
}
