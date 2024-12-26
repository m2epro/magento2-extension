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
     * @throws \Throwable
     */
    public function holdChanges(TrackerInterface $tracker): void
    {
        $this->logger->info('Start collect changes', ['tracker' => $tracker]);

        // Prepare SQL query
        $this->profiler->start();
        try {
            $trackerQuery = $tracker->getDataQuery();
        } catch (\Throwable $exception) {
            $this->processException($exception);
        }
        $this->profiler->stop();
        $this->logger->info(
            sprintf('Prepare SQL query time - <b>%s</b> sec.', $this->profiler->getTime()),
            ['tracker' => $tracker]
        );

        // Execute SQL query
        $this->profiler->start();
        try {
            $statement = $this->resource->getConnection()->query($trackerQuery);
            $statement->execute();
        } catch (\Throwable $exception) {
            $this->processException($exception);
        }
        $this->profiler->stop();
        $this->logger->info(
            sprintf('Execute SQL query time - <b>%s</b> sec.', $this->profiler->getTime()),
            ['tracker' => $tracker]
        );

        // Insert instruction
        $this->profiler->start();
        try {
            $instructionCounter = 0;
            foreach ($this->fetchInstructions($statement, $tracker) as $instructions) {
                $this->instruction->add($instructions);
                $instructionCounter += count($instructions);
            }
        } catch (\Throwable $exception) {
            $this->processException($exception);
        }
        $this->profiler->stop();
        $this->logger->info(
            sprintf('Insert instructions time - <b>%s</b> sec.', $this->profiler->getTime()),
            ['tracker' => $tracker]
        );

        $this->logger->info(
            sprintf('Added instructions: <b>%s</b>', $instructionCounter),
            ['tracker' => $tracker]
        );
    }

    private function fetchInstructions($statement, $tracker): \Generator
    {
        $instructions = [];
        $instructionCounter = 0;
        while ($row = $statement->fetch()) {
            $initiator = "{$tracker->getType()}_{$tracker->getChannel()}";

            $instructions[] = [
                'listing_product_id' => $row['listing_product_id'],
                'type' => $this->getInstructionType($tracker->getType()),
                'component' => $tracker->getChannel(),
                'initiator' => $initiator,
                'additional_data' => $row['additional_data'] ?? null,
                'priority' => 100,
                'create_date' => new \Zend_Db_Expr('NOW()'),
            ];
            $instructionCounter++;

            if ($instructionCounter % 1000 === 0) {
                yield $instructions;
                $instructions = [];
            }
        }

        yield $instructions;
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

    /**
     * @throws \Throwable
     */
    private function processException(\Throwable $exception): void
    {
        $this->logger->error($exception->getMessage(), [
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTrace()
        ]);
        $this->logger->writeLogs();

        throw $exception;
    }
}
