<?php

namespace Ess\M2ePro\Model\ControlPanel\Inspection\Result;

use Magento\Framework\ObjectManagerInterface;
use \Ess\M2ePro\Model\ControlPanel\Inspection\AbstractInspection as Inspection;
use \Ess\M2ePro\Model\ControlPanel\Inspection\Result as Result;

class Factory
{
    /** @var ObjectManagerInterface  */
    protected $objectManager;

    public function __construct(ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    public function create($inspector, $state, $message, $metadata)
    {
        return $this->objectManager->create(
            \Ess\M2ePro\Model\ControlPanel\Inspection\Result::class,
            ['args' => [$inspector, $state, $message, $metadata] ]
        );
    }

    //########################################

    public function createSuccess(Inspection $inspector, $message = null, $metadata = null)
    {
        return $this->create($inspector, Result::STATE_SUCCESS, $message, $metadata);
    }

    public function createNotice(Inspection $inspector, $message, $metadata = null)
    {
        return $this->create($inspector, Result::STATE_NOTICE, $message, $metadata);
    }

    public function createWarning(Inspection $inspector, $message, $metadata = null)
    {
        return $this->create($inspector, Result::STATE_WARNING, $message, $metadata);
    }

    public function createError(Inspection $inspector, $message, $metadata = null)
    {
        return $this->create($inspector, Result::STATE_ERROR, $message, $metadata);
    }
}
