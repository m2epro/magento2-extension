<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Ebay\Bundle\Options\Mapping;

use Ess\M2ePro\Model\Ebay\Bundle\Options as OptionsMapping;

class ObserverHandler
{
    private OptionsMapping\Mapping\Repository $mappingRepository;
    private OptionsMapping\MappingFactory $mappingFactory;
    private OptionsMapping\Mapping\ObserverHandler\OptionDifferenceFactory $optionDifferentFactory;

    public function __construct(
        OptionsMapping\Mapping\Repository $mappingRepository,
        OptionsMapping\MappingFactory $mappingFactory,
        OptionsMapping\Mapping\ObserverHandler\OptionDifferenceFactory $optionDifferentFactory
    ) {
        $this->mappingRepository = $mappingRepository;
        $this->mappingFactory = $mappingFactory;
        $this->optionDifferentFactory = $optionDifferentFactory;
    }

    public function handle(array $beforeNames, array $afterNames): void
    {
        $differences = $this->getOptionDifferent($beforeNames, $afterNames);
        foreach ($differences as $difference) {
            $this->processOptionDifference($difference);
        }
    }

    private function processOptionDifference(
        OptionsMapping\Mapping\ObserverHandler\OptionDifference $optionDifference
    ): void {
        if (!$optionDifference->isUpdated()) {
            return;
        }

        $mapping = $this->mappingRepository->findByTitle($optionDifference->getOldTitle());
        if ($mapping === null) {
            return;
        }

        $newMapping = $this->mappingFactory->create();
        $newMapping->init($optionDifference->getNewTitle(), $mapping->getAttributeCode());
        $this->mappingRepository->create($newMapping);
    }

    /**
     * @param array<int, string> $before
     * @param array<int, string> $after
     *
     * @return OptionsMapping\Mapping\ObserverHandler\OptionDifference[]
     */
    private function getOptionDifferent(array $before, array $after): array
    {
        $beforeOptionIds = array_keys($before);

        $result = [];
        foreach ($beforeOptionIds as $optionId) {
            $oldTitle = $before[$optionId];
            $newTitle = $after[$optionId] ?? null;

            if ($newTitle === null) {
                $result[] = $this->optionDifferentFactory->createDeletedDiff($oldTitle);
                continue;
            }

            if ($oldTitle !== $newTitle) {
                $result[] = $this->optionDifferentFactory->createUpdatedDiff($oldTitle, $newTitle);
            }

            unset($after[$optionId]);
        }

        if (count($after) > 0) {
            foreach ($after as $newTitle) {
                $result[] = $this->optionDifferentFactory->createAddedDiff($newTitle);
            }
        }

        return $result;
    }
}
