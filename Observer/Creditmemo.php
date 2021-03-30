<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Observer;

/**
 * Class \Ess\M2ePro\Observer\Creditmemo
 */
class Creditmemo extends AbstractModel
{
    protected $amazonFactory;
    protected $urlBuilder;
    protected $messageManager;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory,
        \Magento\Framework\UrlInterface $urlBuilder,
        \Magento\Framework\Message\Manager $messageManager,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Model\Factory $modelFactory
    ) {
        $this->amazonFactory = $amazonFactory;
        $this->urlBuilder = $urlBuilder;
        $this->messageManager = $messageManager;
        parent::__construct($helperFactory, $activeRecordFactory, $modelFactory);
    }

    //########################################

    /**
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function process()
    {
        /** @var \Magento\Sales\Model\Order\Creditmemo $creditmemo */
        $creditmemo = $this->getEvent()->getCreditmemo();
        $magentoOrderId = $creditmemo->getOrderId();

        try {
            /** @var $order \Ess\M2ePro\Model\Order */
            $order = $this->activeRecordFactory->getObjectLoaded('Order', $magentoOrderId, 'magento_order_id');
        } catch (\Exception $e) {
            return;
        }

        if ($order === null) {
            return;
        }

        $componentMode = ucfirst($order->getComponentMode());

        if (!$this->getHelper("Component_{$componentMode}")->isEnabled()) {
            return;
        }

        $order->getLog()->setInitiator(\Ess\M2ePro\Helper\Data::INITIATOR_EXTENSION);

        /** @var \Ess\M2ePro\Model\Order\Creditmemo\Handler $handler */
        $handler = $this->modelFactory->getObject("{$componentMode}_Order_Creditmemo_Handler");
        $handler->handle($order, $creditmemo);
    }

    //########################################
}
