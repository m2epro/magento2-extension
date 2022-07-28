<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Order;

class ViewLogGrid extends \Ess\M2ePro\Controller\Adminhtml\Order
{
    /** @var \Ess\M2ePro\Helper\Data\GlobalData */
    private $globalData;

    public function __construct(
        \Ess\M2ePro\Helper\Data\GlobalData $globalData,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($context);

        $this->globalData = $globalData;
    }

    public function execute()
    {
        $id = $this->getRequest()->getParam('id');
        $order = $this->activeRecordFactory->getObjectLoaded('Order', $id);

        $this->globalData->setValue('order', $order);
        $grid = $this->getLayout()->createBlock(\Ess\M2ePro\Block\Adminhtml\Order\View\Log\Grid::class);

        $this->setAjaxContent($grid->toHtml());
        return $this->getResult();
    }
}
