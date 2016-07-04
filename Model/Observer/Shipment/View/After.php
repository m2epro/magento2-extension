<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Observer\Shipment\View;

class After extends \Ess\M2ePro\Model\Observer\AbstractModel
{

    protected $amazonFactory;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Model\Factory $modelFactory
    )
    {
        $this->amazonFactory = $amazonFactory;
        parent::__construct($helperFactory, $activeRecordFactory, $modelFactory);
    }
    //########################################

    public function execute(\Magento\Framework\Event\Observer $eventObserver)
    {
        // event dispatched for ALL rendered magento blocks, so we need to skip unnecessary blocks ASAP
        if (!($eventObserver->getEvent()->getBlock() instanceof \Magento\Shipping\Block\Adminhtml\Create)) {
            return;
        }

        parent::execute($eventObserver);
    }

    public function process()
    {
        /** @var \Magento\Shipping\Block\Adminhtml\Create $block */
        $block = $this->getEvent()->getBlock();

        $orderId = $block->getRequest()->getParam('order_id');
        if (empty($orderId)) {
            return;
        }

        try {
            /** @var \Ess\M2ePro\Model\Order $order */
            $order = $this->amazonFactory->getObjectLoaded('Order', (int)$orderId, 'magento_order_id');
        } catch (\Exception $exception) {
            return;
        }

        if (is_null($order) || !$order->getId()) {
            return;
        }

        /** @var \Ess\M2ePro\Model\Amazon\Order $amazonOrder */
        $amazonOrder = $order->getChildObject();

        if (!$amazonOrder->isEligibleForMerchantFulfillment() || $amazonOrder->isMerchantFulfillmentApplied()) {
            return;
        }
        return;
        //TODO unsupported feature
//        $generalBlock = $block->getLayout()->createBlock('M2ePro/adminhtml_general');
//
//        /** @var \Ess\M2ePro\Block\Adminhtml\Common\Amazon\Order\MerchantFulfillment\Magento\Shipment $amazonBlock */
//        $amazonBlock = $block->getLayout()->createBlock(
//            'M2ePro/adminhtml_common_amazon_order_merchantFulfillment\magento_shipment'
//        );
//        $amazonBlock->setOrder($order);
//
//        /** @var Varien\Object $transport */
//        $transport = $this->getEvent()->getTransport();
//        $transport->setData('html', $transport->getData('html').$generalBlock->toHtml().$amazonBlock->toHtml());
    }

    //########################################
}