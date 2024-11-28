<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Listing\Product\Instruction;

class ProcessorFactory
{
    private \Magento\Framework\ObjectManagerInterface $objectManager;

    public function __construct(\Magento\Framework\ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    /**
     * @param \Ess\M2ePro\Model\Listing\Product\Instruction\Handler\HandlerInterface[] $handlers
     */
    public function create(
        string $component,
        int $maxListingsProductsCount,
        array $handlers
    ): Processor {
        return $this->objectManager->create(
            Processor::class,
            [
                'component' => $component,
                'maxListingsProductsCount' => $maxListingsProductsCount,
                'handlers' => $handlers,
            ]
        );
    }
}
