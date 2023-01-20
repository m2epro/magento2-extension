<?php

namespace Ess\M2ePro\Model\ChangeTracker\Base;

class ChangeHolder
{
    public const INSTRUCTION_TYPE_CHANGE_TRACKER_QTY = 'change_tracker_qty_changed';
    public const INSTRUCTION_TYPE_CHANGE_TRACKER_PRICE = 'change_tracker_price_changed';

    /** @var \Magento\Framework\App\ResourceConnection */
    private $resource;
    /** @var \Ess\M2ePro\Model\ResourceModel\Listing\Product\Instruction */
    private $instruction;
    /** @var \Ess\M2ePro\Model\ChangeTracker\Common\Helpers\Profiler */
    private $profiler;
    /** @var \Ess\M2ePro\Model\ChangeTracker\Common\Helpers\TrackerLogger */
    private $logger;

    /**
     * @param \Magento\Framework\App\ResourceConnection $resource
     * @param \Ess\M2ePro\Model\ResourceModel\Listing\Product\Instruction $instruction
     * @param \Ess\M2ePro\Model\ChangeTracker\Common\Helpers\Profiler $profiler
     * @param \Ess\M2ePro\Model\ChangeTracker\Common\Helpers\TrackerLogger $logger
     */
    public function __construct(
        \Magento\Framework\App\ResourceConnection $resource,
        \Ess\M2ePro\Model\ResourceModel\Listing\Product\Instruction $instruction,
        \Ess\M2ePro\Model\ChangeTracker\Common\Helpers\Profiler $profiler,
        \Ess\M2ePro\Model\ChangeTracker\Common\Helpers\TrackerLogger $logger
    ) {
        $this->resource = $resource;
        $this->instruction = $instruction;
        $this->profiler = $profiler;
        $this->logger = $logger;
    }

    /**
     * @param \Ess\M2ePro\Model\ChangeTracker\Base\TrackerInterface $tracker
     *
     * @return void
     * @throws \Ess\M2ePro\Model\Exception\Logic
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Zend_Db_Statement_Exception
     */
    public function holdChanges(TrackerInterface $tracker): void
    {
        $this->logger->info("Hold tracker. Type: {$tracker->getType()}, channel: {$tracker->getChannel()}");
        $this->profiler->start();
        $trackerQuery = $tracker->getDataQuery();
        $this->profiler->stop();
        $this->logger->info('Build data query: ' . $this->profiler->logString());

        $this->profiler->start();
        $statement = $this->resource->getConnection()->query($trackerQuery);
        $statement->execute();
        $this->profiler->stop();
        $this->logger->info('Executed data query: ' . $this->profiler->logString());

        $this->profiler->start();
        $instructions = [];
        $instructionCounter = 0;
        while ($row = $statement->fetch()) {
            $initiator = "{$tracker->getType()}_{$tracker->getChannel()}";

            $instructions[] = [
                'listing_product_id' => $row['listing_product_id'],
                'type' => $this->getInstructionType($tracker->getType()),
                'component' => $tracker->getChannel(),
                'initiator' => $initiator,
                'priority' => 100,
                'create_date' => new \Zend_Db_Expr('NOW()'),
            ];
            $instructionCounter++;

            if ($instructionCounter % 1000 === 0) {
                $this->instruction->add($instructions);
                $instructions = [];
            }
        }

        $this->instruction->add($instructions);
        $this->profiler->stop();
        $this->logger->info('Insert instruction: ' . $this->profiler->logString());
        $this->logger->info('Added instructions: ' . $instructionCounter);
    }

    /**
     * @param string $trackerType
     *
     * @return string
     */
    private function getInstructionType(string $trackerType): string
    {
        if ($trackerType === TrackerInterface::TYPE_INVENTORY) {
            return self::INSTRUCTION_TYPE_CHANGE_TRACKER_QTY;
        }

        if ($trackerType === TrackerInterface::TYPE_PRICE) {
            return self::INSTRUCTION_TYPE_CHANGE_TRACKER_PRICE;
        }

        throw new \RuntimeException('Unknown change tracker type ' . $trackerType);
    }
}
