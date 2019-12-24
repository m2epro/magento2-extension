<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\Product\Template\Description;

use Ess\M2ePro\Controller\Adminhtml\Amazon\Main;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\Product\Template\Description\ViewGrid
 */
class ViewGrid extends Main
{
    public function execute()
    {
        $productsIds = $this->getRequestIds('products_id');

        $checkNewAsinAccepted = $this->getRequest()->getParam('check_is_new_asin_accepted', 0);
        $mapToTemplateJsFn = $this->getRequest()->getParam('map_to_template_js_fn', false);
        $createNewTemplateJsFn = $this->getRequest()->getParam('create_new_template_js_fn', false);

        if (empty($productsIds)) {
            $this->setAjaxContent('You should provide correct parameters.', false);
            return $this->getResult();
        }

        $grid = $this->createBlock('Amazon_Listing_Product_Template_Description_Grid');
        $grid->setCheckNewAsinAccepted($checkNewAsinAccepted);
        $grid->setProductsIds($productsIds);
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
