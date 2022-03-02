<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\ControlPanel\Inspection;

class Processor
{
    /** @var \Ess\M2ePro\Model\ControlPanel\Inspection\HandlerFactory */
    private $handlerFactory;

    /** @var \Ess\M2ePro\Model\ControlPanel\Inspection\Result\Factory */
    private $resultFactory;

    public function __construct(
        \Ess\M2ePro\Model\ControlPanel\Inspection\HandlerFactory $handlerFactory,
        \Ess\M2ePro\Model\ControlPanel\Inspection\Result\Factory $resultFactory
    )
    {
        $this->handlerFactory = $handlerFactory;
        $this->resultFactory = $resultFactory;
    }

    public function process(\Ess\M2ePro\Model\ControlPanel\Inspection\Definition $definition)
    {
        $handler = $this->handlerFactory->create($definition);

        try {
            $issues = $handler->process();
            $result = $this->resultFactory->createSuccess($issues);
        } catch (\Exception $e) {
            $result = $this->resultFactory->createFailed($e->getMessage());
        }

        return $result;
    }
}
