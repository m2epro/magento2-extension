<?php

namespace Ess\M2ePro\Model\Cron\Task\Ebay\Listing\Product;

use Ess\M2ePro\Model\Ebay\Listing\Product\Instruction as EbayInstruction;

class ProcessInstructions extends \Ess\M2ePro\Model\Cron\Task\AbstractModel
{
    public const NICK = 'ebay/listing/product/process_instructions';

    private \Ess\M2ePro\Model\Ebay\Listing\Product\Instruction\ProcessorFactory $instructionProcessorFactory;
    private EbayInstruction\AutoActions\Handler $ebayAutoActionHandler;
    private EbayInstruction\SynchronizationTemplate\Handler $ebaySynchronizationTemplateHandler;
    private EbayInstruction\Video\CollectHandler $ebayVideoCollectHandler;
    private EbayInstruction\ComplianceDocuments\Handler $ebayDocumentsHandler;
    private \Ess\M2ePro\Model\Config\Manager $configManager;

    public function __construct(
        \Ess\M2ePro\Model\Cron\Manager $cronManager,
        \Ess\M2ePro\Helper\Data $helperData,
        \Magento\Framework\Event\Manager $eventManager,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Factory $parentFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Cron\Task\Repository $taskRepo,
        \Magento\Framework\App\ResourceConnection $resource,
        \Ess\M2ePro\Model\Config\Manager $configManager,
        \Ess\M2ePro\Model\Ebay\Listing\Product\Instruction\ProcessorFactory $instructionProcessorFactory,
        EbayInstruction\AutoActions\Handler $ebayAutoActionHandler,
        EbayInstruction\SynchronizationTemplate\Handler $ebaySynchronizationTemplateHandler,
        EbayInstruction\Video\CollectHandler $ebayVideoCollectHandler,
        EbayInstruction\ComplianceDocuments\Handler $ebayDocumentsHandler
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

        $this->configManager = $configManager;
        $this->instructionProcessorFactory = $instructionProcessorFactory;
        $this->ebayAutoActionHandler = $ebayAutoActionHandler;
        $this->ebaySynchronizationTemplateHandler = $ebaySynchronizationTemplateHandler;
        $this->ebayVideoCollectHandler = $ebayVideoCollectHandler;
        $this->ebayDocumentsHandler = $ebayDocumentsHandler;
    }

    protected function performActions(): void
    {
        $maxListingsProductsCount = (int)$this->configManager->getGroupValue(
            '/ebay/listing/product/instructions/cron/',
            'listings_products_per_one_time'
        );

        $processor = $this->instructionProcessorFactory->create(
            $maxListingsProductsCount,
            $this->ebayVideoCollectHandler,
            $this->ebayDocumentsHandler,
            $this->ebayAutoActionHandler,
            $this->ebaySynchronizationTemplateHandler
        );
        $processor->process();
    }
}
