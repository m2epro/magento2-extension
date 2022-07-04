<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Any usage is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\Product\Template\ProductTaxCode;

use Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\Product\Template\ProductTaxCode;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\Product\Template\ProductTaxCode\ViewGrid
 */
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

        $grid = $this->getLayout()
                ->createBlock(\Ess\M2ePro\Block\Adminhtml\Amazon\Listing\Product\Template\ProductTaxCode\Grid::class);
        $grid->setProductsIds($productsIds);

        $this->setAjaxContent($grid->toHtml());
        return $this->getResult();
    }
}
