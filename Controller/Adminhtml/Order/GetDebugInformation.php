<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Order;

class GetDebugInformation extends \Ess\M2ePro\Controller\Adminhtml\Order
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

        if ($id === null) {
            $this->setAjaxContent('', false);
            return $this->getResult();
        }

        try {
            $order = $this->activeRecordFactory->getObjectLoaded('Order', (int)$id);
        } catch (\Exception $e) {
            $this->setAjaxContent('', false);
            return $this->getResult();
        }

        $this->globalData->setValue('order', $order);

        $debugBlock = $this->getLayout()->createBlock(\Ess\M2ePro\Block\Adminhtml\Order\Debug::class);

        $this->setAjaxContent($debugBlock->toHtml());

        return $this->getResult();
    }
}
