<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\ControlPanel\Inspection;

use Magento\Framework\ObjectManagerInterface;

class HandlerFactory
{
    /** @var ObjectManagerInterface */
    private $objectManager;

    /**
     * @param ObjectManagerInterface $objectManager
     */
    public function __construct(ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    /**
     * @param \Ess\M2ePro\Model\ControlPanel\Inspection\Definition $definition
     *
     * @return \Ess\M2ePro\Model\ControlPanel\Inspection\InspectorInterface
     */
    public function create(\Ess\M2ePro\Model\ControlPanel\Inspection\Definition  $definition)
    {
        return $this->objectManager->create($definition->getHandler());
    }
}
