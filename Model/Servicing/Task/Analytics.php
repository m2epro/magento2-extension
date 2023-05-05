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

    /** In bytes. It is equal 1 Mb */
    private const REQUEST_SIZE_MAX = 1048576;

    /** @var \Ess\M2ePro\Model\Servicing\Task\Analytics\Registry */
    private $registry;
    /** @var \Ess\M2ePro\Model\Servicing\Task\Analytics\EntityManager\Serializer */
    private $serializer;
    /** @var \Ess\M2ePro\Helper\Module\Exception */
    private $helperException;
    /** @var \Ess\M2ePro\Model\Servicing\Task\Analytics\EntityManagerFactory */
    private $entityManagerFactory;

    /**
     * @param \Ess\M2ePro\Model\Servicing\Task\Analytics\Registry $registry
     * @param \Ess\M2ePro\Model\Servicing\Task\Analytics\EntityManager\Serializer $serializer
     * @param \Ess\M2ePro\Helper\Module\Exception $helperException
     * @param \Ess\M2ePro\Model\Servicing\Task\Analytics\EntityManagerFactory $entityManagerFactory
     */
    public function __construct(
        \Ess\M2ePro\Model\Servicing\Task\Analytics\Registry $registry,
        \Ess\M2ePro\Model\Servicing\Task\Analytics\EntityManager\Serializer $serializer,
        \Ess\M2ePro\Helper\Module\Exception $helperException,
        \Ess\M2ePro\Model\Servicing\Task\Analytics\EntityManagerFactory $entityManagerFactory
    ) {
        $this->registry = $registry;
        $this->serializer = $serializer;
        $this->helperException = $helperException;
        $this->entityManagerFactory = $entityManagerFactory;
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
            return $this->collectAnalytics();
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
        $entities = [];

        foreach ($this->getEntitiesTypes() as $component => $entitiesTypes) {
            foreach ($entitiesTypes as $entityType) {
                $manager = $this->entityManagerFactory->create(
                    ['component' => $component, 'entityType' => $entityType]
                );

                $progress[$manager->getEntityKey()] = false;

                if ($manager->isCompleted()) {
                    $progress[$manager->getEntityKey()] = true;
                    continue;
                }

                $iteration = 0;
                foreach ($manager->getEntities() as $item) {
                    /** @var \Ess\M2ePro\Model\ActiveRecord\AbstractModel $item */

                    if ($iteration && $iteration % 10 === 0 && $this->isEntitiesPackFull($entities)) {
                        break 3;
                    }

                    $entities[] = $this->serializer->serializeData($item, $manager);
                    $manager->setLastProcessedId($item->getId());
                    $iteration++;
                }
            }
        }

        if (!in_array(false, $progress)) {
            $this->registry->markFinished();
        }

        return [
            'analytics' => [
                'entities' => $entities,
                'planned_at' => $this->registry->getPlannedAt(),
                'started_at' => $this->registry->getStartedAt(),
                'finished_at' => $this->registry->getFinishedAt(),
            ],
        ];
    }

    //----------------------------------------

    /**
     * @return array[]
     */
    private function getEntitiesTypes(): array
    {
        return [
            \Ess\M2ePro\Helper\Component\Amazon::NICK => [
                'Account',
                'Listing',
                'Template_Synchronization',
                'Template_SellingFormat',
                'Amazon_Template_ProductTaxCode',
                'Amazon_Template_Shipping',
            ],
            \Ess\M2ePro\Helper\Component\Ebay::NICK => [
                'Account',
                'Listing',
                'Template_Synchronization',
                'Template_Description',
                'Template_SellingFormat',
                'Ebay_Template_ReturnPolicy',
                'Ebay_Template_Shipping',
                'Ebay_Template_Category',
            ],
            \Ess\M2ePro\Helper\Component\Walmart::NICK => [
                'Account',
                'Listing',
                'Template_Synchronization',
                'Template_Description',
                'Template_SellingFormat',
                'Walmart_Template_Category',
            ],
        ];
    }

    /**
     * @param $entities
     *
     * @return bool
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    private function isEntitiesPackFull(&$entities): bool
    {
        $dataSize = strlen(\Ess\M2ePro\Helper\Json::encode($entities));

        return $dataSize > self::REQUEST_SIZE_MAX;
    }
}
