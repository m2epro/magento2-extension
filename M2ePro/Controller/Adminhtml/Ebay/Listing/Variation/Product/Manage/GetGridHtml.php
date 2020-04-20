<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Variation\Product\Manage;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Variation\Product\Manage\GetGridHtml
 */
class GetGridHtml extends \Ess\M2ePro\Controller\Adminhtml\Ebay\Listing
{
    public function execute()
    {
        $productId = $this->getRequest()->getParam('product_id');

        if (empty($productId)) {
            $this->setAjaxContent('You should provide correct parameters.', false);
            return $this->getResult();
        }

        $this->getHelper('Data\GlobalData')->setValue('listing_product_id', $productId);
        $view = $this->createBlock('Ebay_Listing_Variation_Product_Manage_View_Grid');

        $this->setAjaxContent($view);
        return $this->getResult();
    }
}
