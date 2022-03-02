<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\ControlPanel\Inspection\Issue;

use Magento\Framework\ObjectManagerInterface;

class Factory
{
    /** @var ObjectManagerInterface  */
    private $objectManager;

    public function __construct(ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    /**
     * @param string|null $message
     * @param array|string|null $metadata
     *
     * @return \Ess\M2ePro\Model\ControlPanel\Inspection\Issue
     */
    public function create($message, $metadata = null)
    {
        return $this->objectManager->create(
            \Ess\M2ePro\Model\ControlPanel\Inspection\Issue::class,
            [
                'message'  => $message,
                'metadata' => $metadata
            ]
        );
    }
}
