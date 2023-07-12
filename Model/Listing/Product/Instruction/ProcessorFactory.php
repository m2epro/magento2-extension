<?php

namespace Ess\M2ePro\Model\Listing\Product\Instruction;

class ProcessorFactory
{
    /** @var \Magento\Framework\ObjectManagerInterface */
    private $objectManager;

    public function __construct(\Magento\Framework\ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    public function create(): Processor
    {
        return $this->objectManager->create(Processor::class);
    }
}
