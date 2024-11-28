<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Amazon\Listing\Product\Instruction;

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
        SynchronizationTemplate\Handler $syncTemplateHandler,
        Repricing\Handler $repricingHandler
    ): \Ess\M2ePro\Model\Listing\Product\Instruction\Processor {
        return $this->processorFactory->create(
            \Ess\M2ePro\Helper\Component\Amazon::NICK,
            $maxListingsProductsCount,
            [
                $autoActionsHandler,
                $syncTemplateHandler,
                $repricingHandler,
            ]
        );
    }
}
