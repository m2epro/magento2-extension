<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Log\Order;

class Grid extends \Ess\M2ePro\Controller\Adminhtml\Ebay\Log\Order
{
    public function execute()
    {
        $response = $this->getLayout()->createBlock(\Ess\M2ePro\Block\Adminhtml\Ebay\Log\Order\Grid::class)->toHtml();
        $this->setAjaxContent($response);

        return $this->getResult();
    }
}
