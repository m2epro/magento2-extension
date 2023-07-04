<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Servicing\Task\Analytics;

class CollectorFactory
{
    /** @var \Magento\Framework\ObjectManagerInterface */
    private $objectManager;

    public function __construct(\Magento\Framework\ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    public function create(string $collectorClass): CollectorInterface
    {
        $collector = $this->objectManager->create($collectorClass);
        if (!$collector instanceof CollectorInterface) {
            throw new \Ess\M2ePro\Model\Exception\Logic(sprintf("Collector '%s' is not valid.", $collectorClass));
        }

        return $collector;
    }
}
