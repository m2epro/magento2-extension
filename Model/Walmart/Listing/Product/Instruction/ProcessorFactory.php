<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Walmart\Listing\Product\Instruction;

class ProcessorFactory
{
    private \Ess\M2ePro\Model\Listing\Product\Instruction\ProcessorFactory $processorFactory;

    public function __construct(\Ess\M2ePro\Model\Listing\Product\Instruction\ProcessorFactory $processorFactory)
    {
        $this->processorFactory = $processorFactory;
    }

    public function create(
        int $maxListingsProductsCount,
        AutoActions\Handler $autoActionsHandler,
        SynchronizationTemplate\Handler $syncTemplateHandler
    ): \Ess\M2ePro\Model\Listing\Product\Instruction\Processor {
        return $this->processorFactory->create(
            \Ess\M2ePro\Helper\Component\Walmart::NICK,
            $maxListingsProductsCount,
            [
                $autoActionsHandler,
                $syncTemplateHandler,
            ]
        );
    }
}
