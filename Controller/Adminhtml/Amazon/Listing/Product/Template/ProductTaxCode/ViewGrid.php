<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  2011-2017 ESS-UA [M2E Pro]
 * @license    Any usage is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\Product\Template\ProductTaxCode;

use Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\Product\Template\ProductTaxCode;

class ViewGrid extends ProductTaxCode
{
    public function execute()
    {
        $productsIds = $this->getRequest()->getParam('products_ids');

        if (empty($productsIds)) {
            $this->setAjaxContent('You should provide correct parameters.');
            return $this->getResult();
        }

        if (!is_array($productsIds)) {
            $productsIds = explode(',', $productsIds);
        }

        $grid = $this->createBlock('Amazon\Listing\Product\Template\ProductTaxCode\Grid');
        $grid->setProductsIds($productsIds);

        $this->setAjaxContent($grid->toHtml());
        return $this->getResult();
    }
}