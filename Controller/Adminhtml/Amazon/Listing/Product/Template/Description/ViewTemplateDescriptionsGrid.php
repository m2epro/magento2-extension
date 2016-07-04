<?php

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\Product\Template\Description;

use Ess\M2ePro\Controller\Adminhtml\Amazon\Main;

class ViewTemplateDescriptionsGrid extends Main
{
    public function execute()
    {
        $productsIds = $this->getRequestIds('products_id');

        $checkNewAsinAccepted = $this->getRequest()->getParam('check_is_new_asin_accepted', 0);
        $mapToTemplateJsFn = $this->getRequest()->getParam('map_to_template_js_fn', false);

        if (empty($productsIds)) {
            $this->setAjaxContent('You should provide correct parameters.', false);
            return $this->getResult();
        }

        $grid = $this->createBlock('Amazon\Listing\Product\Template\Description\Grid');
        $grid->setCheckNewAsinAccepted($checkNewAsinAccepted);
        $grid->setProductsIds($productsIds);
        if ($mapToTemplateJsFn !== false) {
            $grid->setMapToTemplateJsFn($mapToTemplateJsFn);
        }

        $this->setAjaxContent($grid);

        return $this->getResult();
    }
}