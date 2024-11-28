<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Ebay\Listing\Product\Instruction;

class ProcessorFactory
{
    private \Ess\M2ePro\Model\Listing\Product\Instruction\ProcessorFactory $processorFactory;

    public function __construct(\Ess\M2ePro\Model\Listing\Product\Instruction\ProcessorFactory $processorFactory)
    {
        $this->processorFactory = $processorFactory;
    }

    public function create(
        int $maxListingsProductsCount,
        Video\CollectHandler $videoCollectHandler,
        ComplianceDocuments\Handler $documentsHandler,
        AutoActions\Handler $autoActionHandler,
        SynchronizationTemplate\Handler $synchronizationTemplateHandler
    ): \Ess\M2ePro\Model\Listing\Product\Instruction\Processor {
        return $this->processorFactory->create(
            \Ess\M2ePro\Helper\Component\Ebay::NICK,
            $maxListingsProductsCount,
            [
                $videoCollectHandler,
                $documentsHandler,
                $autoActionHandler,
                $synchronizationTemplateHandler,
            ]
        );
    }
}
