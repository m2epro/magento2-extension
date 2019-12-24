<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Magento\Order\Shipment;

/**
 * Class \Ess\M2ePro\Model\Magento\Order\Shipment\DocumentFactory
 */
class DocumentFactory
{
    /** @var \Magento\Framework\ObjectManagerInterface */
    protected $objectManager;

    /** @var \Ess\M2ePro\Helper\Factory */
    protected $helperFactory;

    //########################################

    public function __construct(
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Magento\Framework\ObjectManagerInterface $objectManager
    ) {
        $this->objectManager = $objectManager;
        $this->helperFactory = $helperFactory;
    }

    //########################################

    /**
     * @param \Magento\Sales\Model\Order $order
     * @return \Magento\Sales\Api\Data\ShipmentInterface
     */
    public function create(\Magento\Sales\Model\Order $order, $items = [])
    {
        return $this->resolveFactory()->create($order, $items);
    }

    //########################################

    private function resolveFactory()
    {
        if (version_compare($this->helperFactory->getObject('Magento')->getVersion(), '2.2.0', '<')) {
            return $this->objectManager->get(\Magento\Sales\Model\Order\ShipmentFactory::class);
        }

        return $this->objectManager->get(\Magento\Sales\Model\Order\ShipmentDocumentFactory::class);
    }

    //########################################
}
