<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Magento\Order\Shipment;

class Factory
{
    /** @var \Magento\Framework\ObjectManagerInterface */
    protected $objectManager;

    protected $helperFactory;

    protected $modelFactory;

    //########################################

    public function __construct(
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        \Magento\Framework\ObjectManagerInterface $objectManager
    ){
        $this->objectManager  = $objectManager;
        $this->helperFactory  = $helperFactory;
        $this->modelFactory   = $modelFactory;
    }

    //########################################

    /**
     * @param \Magento\Sales\Model\Order $order
     * @return \Magento\Sales\Api\Data\ShipmentInterface
     */
    public function create(\Magento\Sales\Model\Order $order)
    {
        return $this->resolveFactory()->create($order);
    }

    //########################################

    private function resolveFactory()
    {
        if (version_compare($this->helperFactory->getObject('Magento')->getVersion(), '2.2.0', '<')) {
            return $this->objectManager->get('\Magento\Sales\Model\Order\ShipmentFactory');
        }

        return $this->objectManager->get('\Magento\Sales\Model\Order\ShipmentDocumentFactory');
    }

    //########################################
}