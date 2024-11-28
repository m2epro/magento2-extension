<?php

namespace Ess\M2ePro\Model\Cron\Task\Amazon\Listing\Product;

class ProcessInstructions extends \Ess\M2ePro\Model\Cron\Task\AbstractModel
{
    public const NICK = 'amazon/listing/product/process_instructions';

    private \Ess\M2ePro\Model\Amazon\Listing\Product\Instruction\ProcessorFactory $instructionProcessorFactory;
    private \Ess\M2ePro\Model\Amazon\Listing\Product\Instruction\AutoActions\Handler $autoActionsHandler;
    private \Ess\M2ePro\Model\Amazon\Listing\Product\Instruction\SynchronizationTemplate\Handler $syncTemplateHandler;
    private \Ess\M2ePro\Model\Amazon\Listing\Product\Instruction\Repricing\Handler $repricingHandler;
    private \Ess\M2ePro\Model\Config\Manager $configManager;

    public function __construct(
        \Ess\M2ePro\Model\Amazon\Listing\Product\Instruction\ProcessorFactory $instructionProcessorFactory,
        \Ess\M2ePro\Model\Amazon\Listing\Product\Instruction\AutoActions\Handler $autoActionsHandler,
        \Ess\M2ePro\Model\Amazon\Listing\Product\Instruction\SynchronizationTemplate\Handler $syncTemplateHandler,
        \Ess\M2ePro\Model\Amazon\Listing\Product\Instruction\Repricing\Handler $repricingHandler,
        \Ess\M2ePro\Model\Config\Manager $configManager,
        \Ess\M2ePro\Model\Cron\Manager $cronManager,
        \Ess\M2ePro\Helper\Data $helperData,
        \Magento\Framework\Event\Manager $eventManager,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Factory $parentFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Cron\Task\Repository $taskRepo,
        \Magento\Framework\App\ResourceConnection $resource
    ) {
        parent::__construct(
            $cronManager,
            $helperData,
            $eventManager,
            $parentFactory,
            $modelFactory,
            $activeRecordFactory,
            $helperFactory,
            $taskRepo,
            $resource
        );

        $this->instructionProcessorFactory = $instructionProcessorFactory;
        $this->autoActionsHandler = $autoActionsHandler;
        $this->syncTemplateHandler = $syncTemplateHandler;
        $this->repricingHandler = $repricingHandler;
        $this->configManager = $configManager;
    }

    protected function performActions()
    {
        $maxListingsProductsCount = (int)$this->configManager->getGroupValue(
            '/amazon/listing/product/instructions/cron/',
            'listings_products_per_one_time'
        );

        $processor = $this->instructionProcessorFactory->create(
            $maxListingsProductsCount,
            $this->autoActionsHandler,
            $this->syncTemplateHandler,
            $this->repricingHandler
        );

        $processor->process();
    }
}
