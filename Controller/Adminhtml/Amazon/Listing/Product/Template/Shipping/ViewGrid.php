<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\Product\Template\Shipping;

class ViewGrid extends \Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\Product\Template\Shipping
{
    public function execute()
    {
        $productsIds = $this->getRequest()->getParam('products_ids');
        $accountId = $this->getRequest()->getParam('account_id');

        if (empty($productsIds) && empty($accountId)) {
            $this->setAjaxContent('You should provide correct parameters.', false);

            return $this->getResult();
        }

        if (empty($accountId)) {
            if (!is_array($productsIds)) {
                $productsIds = explode(',', $productsIds);
            }

            /** @var \Ess\M2ePro\Model\Listing\Product $listingProduct */
            $listingProduct = $this->amazonFactory->getObjectLoaded('Listing\Product', $productsIds[0]);
            $accountId = $listingProduct->getListing()->getAccountId();
        }

        if (!is_array($productsIds)) {
            $productsIds = explode(',', $productsIds);
        }

        $grid = $this->getLayout()
                     ->createBlock(\Ess\M2ePro\Block\Adminhtml\Amazon\Listing\Product\Template\Shipping\Grid::class);

        $grid->setAccountId($accountId);
        $grid->setProductsIds($productsIds);

        $this->setAjaxContent($grid);

        return $this->getResult();
    }
}
