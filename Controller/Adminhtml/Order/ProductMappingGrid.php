<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Order;

use Ess\M2ePro\Controller\Adminhtml\Order;

class ProductMappingGrid extends Order
{
    public function execute()
    {
        $grid = $this->createBlock('Order\Item\Product\Mapping\Grid');

        $this->setAjaxContent($grid->toHtml());
        return $this->getResult();
    }
}