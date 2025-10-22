<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\ChangeTracker;

class ChangeHolder
{
    public const INSTRUCTION_TYPE_CHANGE_TRACKER_QTY = 'change_tracker_qty_changed';
    public const INSTRUCTION_TYPE_CHANGE_TRACKER_PRICE = 'change_tracker_price_changed';

    private \Ess\M2ePro\Model\ResourceModel\Listing\Product\Instruction $instruction;
    private Common\Helpers\Profiler $profiler;
    private Common\Helpers\TrackerLogger $logger;
    private \Magento\Framework\ObjectManagerInterface $objectManager;

    public function __construct(
        \Ess\M2ePro\Model\ResourceModel\Listing\Product\Instruction $instruction,
        \Ess\M2ePro\Model\ChangeTracker\Common\Helpers\Profiler $profiler,
        \Ess\M2ePro\Model\ChangeTracker\Common\Helpers\TrackerLogger $logger,
        \Magento\Framework\ObjectManagerInterface $objectManager
    ) {
        $this->instruction = $instruction;
        $this->profiler = $profiler;
        $this->logger = $logger;
        $this->objectManager = $objectManager;
    }

    /**
     * @throws \Throwable
     */
    public function holdChanges(TrackerInterface $tracker): void
    {
        $this->logger->info('Start collect changes', ['tracker' => $tracker]);

        // Prepare SQL query
        $this->profiler->start();
        try {
            $trackerQuery = $tracker->getDataQuery();

            $message = sprintf('Data query %s %s', $tracker->getType(), $tracker->getChannel());
            $this->logger->debug($message, [
                'query' => (string)$trackerQuery,
                'type' => $tracker->getType(),
                'channel' => $tracker->getChannel(),
                'tracker' => $tracker,
            ]);
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
            $statement = $this->createDatabaseConnection()->query($trackerQuery);
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
                $instructionCounter += \count($instructions);
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

    private function fetchInstructions($statement, TrackerInterface $tracker): \Generator
    {
        $instructions = [];
        $instructionCounter = 0;
        while ($row = $statement->fetch()) {
            $instructionData = $tracker->processQueryRow($row);
            if ($instructionData === null) {
                continue;
            }

            $instructions[] = $instructionData;
            $instructionCounter++;

            if ($instructionCounter % 1000 === 0) {
                yield $instructions;
                $instructions = [];
            }
        }

        yield $instructions;
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
            'trace' => $exception->getTrace(),
        ]);
        $this->logger->writeLogs();

        throw $exception;
    }

    private function createDatabaseConnection(): \Magento\Framework\DB\Adapter\AdapterInterface
    {
        $resource = $this->objectManager->create(\Magento\Framework\App\ResourceConnection::class);

        return $resource->getConnection();
    }
}
