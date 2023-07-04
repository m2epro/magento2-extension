<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Servicing\Task;

class Analytics implements \Ess\M2ePro\Model\Servicing\TaskInterface
{
    public const NAME = 'analytics';

    private const REQUEST_MAX_SIZE_ONE_MB_IN_BYTES = 1024 * 1024;

    /** @var \Ess\M2ePro\Model\Servicing\Task\Analytics\Registry */
    private $registry;
    /** @var \Ess\M2ePro\Helper\Module\Exception */
    private $helperException;
    /** @var \Ess\M2ePro\Model\Servicing\Task\Analytics\ProgressManagerFactory */
    private $progressManagerFactory;
    /** @var \Ess\M2ePro\Model\Servicing\Task\Analytics\CollectorFactory */
    private $collectorFactory;

    public function __construct(
        \Ess\M2ePro\Model\Servicing\Task\Analytics\Registry $registry,
        \Ess\M2ePro\Helper\Module\Exception $helperException,
        \Ess\M2ePro\Model\Servicing\Task\Analytics\ProgressManagerFactory $progressManagerFactory,
        \Ess\M2ePro\Model\Servicing\Task\Analytics\CollectorFactory $collectorFactory
    ) {
        $this->registry = $registry;
        $this->helperException = $helperException;
        $this->progressManagerFactory = $progressManagerFactory;
        $this->collectorFactory = $collectorFactory;
    }

    //----------------------------------------

    /**
     * @return string
     */
    public function getServerTaskName(): string
    {
        return self::NAME;
    }

    //----------------------------------------

    /**
     * @return bool
     */
    public function isAllowed(): bool
    {
        return $this->registry->isPlannedNow();
    }

    //----------------------------------------

    /**
     * @return array[]
     */
    public function getRequestData(): array
    {
        try {
            return [
                'analytics' => [
                    'entities' => $this->collectAnalytics(),
                    'planned_at' => $this->registry->getPlannedAt(),
                    'started_at' => $this->registry->getStartedAt(),
                    'finished_at' => $this->registry->getFinishedAt(),
                ],
            ];
        } catch (\Throwable $e) {
            $this->helperException->process($e);

            return ['analytics' => []];
        }
    }

    /**
     * @param array $data
     *
     * @return void
     */
    public function processResponseData(array $data): void
    {
    }

    //----------------------------------------

    /**
     * @return array[]
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    private function collectAnalytics(): array
    {
        if (!$this->registry->getStartedAt()) {
            $this->registry->markStarted();
        }

        $progress = [];
        $collectedData = [];

        foreach ($this->getCollectors() as $collectorClassName) {
            $collector = $this->collectorFactory->create($collectorClassName);

            $collectorId = $collector->getComponent() . '::' . $collector->getEntityName();
            $progressManager = $this->progressManagerFactory->create($collectorId);

            $progress[$collectorId] = false;

            if (!$progressManager->isInProcess()) {
                $lastEntityId = $collector->getLastEntityId();
                $progressManager->start($lastEntityId);
            }

            if ($progressManager->isCompleted()) {
                $progress[$collectorId] = true;

                continue;
            }

            $iteration = 0;

            foreach ($collector->getRows($progressManager->getCurrent(), $progressManager->getLastId()) as $row) {
                $collectedData[] = [
                    'component' => $collector->getComponent(),
                    'entity' => $collector->getEntityName(),
                    'id' => $row->id,
                    'data' => \Ess\M2ePro\Helper\Json::encode($row->data),
                ];

                if ($iteration % 10 === 0 && $this->isEntitiesPackFull($collectedData)) {
                    break 2;
                }

                $progressManager->setCurrent($row->id);
                $iteration++;
            }
        }

        if (!in_array(false, $progress)) {
            $this->registry->markFinished();
        }

        return $collectedData;
    }

    //----------------------------------------

    /**
     * @return string[]
     */
    private function getCollectors(): array
    {
        return [
            \Ess\M2ePro\Model\Servicing\Task\Analytics\Collector\Amazon\Account::class,
            \Ess\M2ePro\Model\Servicing\Task\Analytics\Collector\Ebay\Account::class,
            \Ess\M2ePro\Model\Servicing\Task\Analytics\Collector\Walmart\Account::class,
            \Ess\M2ePro\Model\Servicing\Task\Analytics\Collector\Amazon\Listing::class,
            \Ess\M2ePro\Model\Servicing\Task\Analytics\Collector\Ebay\Listing::class,
            \Ess\M2ePro\Model\Servicing\Task\Analytics\Collector\Walmart\Listing::class,
            \Ess\M2ePro\Model\Servicing\Task\Analytics\Collector\Amazon\TemplateSynchronization::class,
            \Ess\M2ePro\Model\Servicing\Task\Analytics\Collector\Ebay\TemplateSynchronization::class,
            \Ess\M2ePro\Model\Servicing\Task\Analytics\Collector\Walmart\TemplateSynchronization::class,
            \Ess\M2ePro\Model\Servicing\Task\Analytics\Collector\Amazon\TemplateSellingFormat::class,
            \Ess\M2ePro\Model\Servicing\Task\Analytics\Collector\Ebay\TemplateSellingFormat::class,
            \Ess\M2ePro\Model\Servicing\Task\Analytics\Collector\Walmart\TemplateSellingFormat::class,
            \Ess\M2ePro\Model\Servicing\Task\Analytics\Collector\Amazon\AmazonTemplateProductTaxCode::class,
            \Ess\M2ePro\Model\Servicing\Task\Analytics\Collector\Amazon\AmazonTemplateShipping::class,
            \Ess\M2ePro\Model\Servicing\Task\Analytics\Collector\Ebay\EbayTemplateShipping::class,
            \Ess\M2ePro\Model\Servicing\Task\Analytics\Collector\Ebay\TemplateDescription::class,
            \Ess\M2ePro\Model\Servicing\Task\Analytics\Collector\Walmart\TemplateDescription::class,
            \Ess\M2ePro\Model\Servicing\Task\Analytics\Collector\Ebay\EbayTemplateReturnPolicy::class,
            \Ess\M2ePro\Model\Servicing\Task\Analytics\Collector\Ebay\EbayTemplateCategory::class,
            \Ess\M2ePro\Model\Servicing\Task\Analytics\Collector\Walmart\WalmartTemplateCategory::class,
        ];
    }

    /**
     * @param $entities
     *
     * @return bool
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    private function isEntitiesPackFull($entities): bool
    {
        $dataSize = strlen(\Ess\M2ePro\Helper\Json::encode($entities));

        return $dataSize > self::REQUEST_MAX_SIZE_ONE_MB_IN_BYTES;
    }
}
