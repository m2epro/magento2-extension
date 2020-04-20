<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Walmart\Listing\Product\Template\Category;

use Ess\M2ePro\Controller\Adminhtml\Walmart\Main;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Walmart\Listing\Product\Template\Category\ViewGrid
 */
class ViewGrid extends Main
{
    public function execute()
    {
        $listingProductsIds    = $this->getRequest()->getParam('products_ids');
        $magentoCategoryIds    = $this->getRequest()->getParam('magento_categories_ids');
        $mapToTemplateJsFn = $this->getRequest()->getParam('map_to_template_js_fn', false);
        $createNewTemplateJsFn = $this->getRequest()->getParam('create_new_template_js_fn', false);

        if (empty($listingProductsIds)) {
            $this->setAjaxContent('You should provide correct parameters.', false);
            return $this->getResult();
        }

        !is_array($listingProductsIds) && $listingProductsIds = array_filter(explode(',', $listingProductsIds));
        !is_array($magentoCategoryIds) && $magentoCategoryIds = array_filter(explode(',', $magentoCategoryIds));

        /** @var \Ess\M2ePro\Block\Adminhtml\Walmart\Listing\Product\Template\Category\Grid $grid */
        $grid = $this->createBlock('Walmart_Listing_Product_Template_Category_Grid');
        $grid->setProductsIds($listingProductsIds);
        $grid->setMagentoCategoryIds($magentoCategoryIds);
        if ($mapToTemplateJsFn !== false) {
            $grid->setMapToTemplateJsFn($mapToTemplateJsFn);
        }
        if ($createNewTemplateJsFn !== false) {
            $grid->setCreateNewTemplateJsFn($createNewTemplateJsFn);
        }

        $this->setAjaxContent($grid);

        return $this->getResult();
    }
}
