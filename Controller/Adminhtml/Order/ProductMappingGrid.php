<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Order;

use Ess\M2ePro\Controller\Adminhtml\Order;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Order\ProductMappingGrid
 */
class ProductMappingGrid extends Order
{
    public function execute()
    {
        $grid = $this->createBlock('Order_Item_Product_Mapping_Grid');

        $this->setAjaxContent($grid->toHtml());
        return $this->getResult();
    }
}
