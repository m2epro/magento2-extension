<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\ControlPanel\Inspection\Result;

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
     * @param bool $status
     * @param string|null $errorMessage
     * @param \Ess\M2ePro\Model\ControlPanel\Inspection\Issue[]|null $issues
     *
     * @return \Ess\M2ePro\Model\ControlPanel\Inspection\Result
     */
    private function create($status, $errorMessage, $issues = [])
    {
        return $this->objectManager->create(
            \Ess\M2ePro\Model\ControlPanel\Inspection\Result::class,
            [
                'status'       => $status,
                'errorMessage' => $errorMessage,
                'issues'       => $issues,
            ]
        );
    }

    /**
     * @param \Ess\M2ePro\Model\ControlPanel\Inspection\Issue[] $issues
     *
     * @return \Ess\M2ePro\Model\ControlPanel\Inspection\Result
     */
    public function createSuccess($issues)
    {
        return $this->create(true, null, $issues);
    }

    /**
     * @param string $errorMessage
     *
     * @return \Ess\M2ePro\Model\ControlPanel\Inspection\Result
     */
    public function createFailed($errorMessage)
    {
        return $this->create(false, $errorMessage);
    }
}
