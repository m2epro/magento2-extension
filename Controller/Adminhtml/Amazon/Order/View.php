<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Order;

use Ess\M2ePro\Controller\Adminhtml\Amazon\Order;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Amazon\Order\View
 */
class View extends Order
{
    /** @var \Ess\M2ePro\Helper\Data\GlobalData */
    private $helperDataGlobalData;

    public function __construct(
        \Ess\M2ePro\Helper\Data\GlobalData $helperDataGlobalData,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($amazonFactory, $context);

        $this->helperDataGlobalData = $helperDataGlobalData;
    }

    public function execute()
    {
        $id = $this->getRequest()->getParam('id');
        $order = $this->amazonFactory->getObjectLoaded('Order', (int)$id);

        $this->helperDataGlobalData->setValue('order', $order);

        $this->init();

        $this->getResultPage()->getConfig()->getTitle()->prepend($this->__('View Order Details'));

        $this->addContent($this->getLayout()->createBlock(\Ess\M2ePro\Block\Adminhtml\Amazon\Order\View::class));

        return $this->getResult();
    }
}
