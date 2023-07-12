<?php

namespace Ess\M2ePro\Model\Cron\Task\Ebay\Listing\Product;

use Ess\M2ePro\Model\Ebay\Listing\Product\Instruction as EbayInstruction;

class ProcessInstructions extends \Ess\M2ePro\Model\Cron\Task\AbstractModel
{
    public const NICK = 'ebay/listing/product/process_instructions';

    /** @var \Ess\M2ePro\Model\Listing\Product\Instruction\Processor */
    private $instructionProcessorFactory;
    /** @var EbayInstruction\AutoActions\Handler */
    private $ebayAutoActionHandler;
    /** @var EbayInstruction\SynchronizationTemplate\Handler */
    private $ebaySynchronizationTemplateHandler;
    /** @var \Ess\M2ePro\Model\Config\Manager */
    private $configManager;

    public function __construct(
        \Ess\M2ePro\Helper\Data $helperData,
        \Magento\Framework\Event\Manager $eventManager,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Factory $parentFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Cron\Task\Repository $taskRepo,
        \Magento\Framework\App\ResourceConnection $resource,
        \Ess\M2ePro\Model\Config\Manager $configManager,
        \Ess\M2ePro\Model\Listing\Product\Instruction\ProcessorFactory $instructionProcessorFactory,
        EbayInstruction\AutoActions\Handler $ebayAutoActionHandler,
        EbayInstruction\SynchronizationTemplate\Handler $ebaySynchronizationTemplateHandler
    ) {
        parent::__construct(
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
    }

    protected function performActions(): void
    {
        $processor = $this->instructionProcessorFactory->create();
        $processor->setComponent(\Ess\M2ePro\Helper\Component\Ebay::NICK);
        $processor->setMaxListingsProductsCount($this->getListingProductsLimit());

        $processor->registerHandler($this->ebayAutoActionHandler);
        $processor->registerHandler($this->ebaySynchronizationTemplateHandler);

        $processor->process();
    }

    private function getListingProductsLimit(): int
    {
        return (int)$this->configManager
            ->getGroupValue('/ebay/listing/product/instructions/cron/', 'listings_products_per_one_time');
    }
}
