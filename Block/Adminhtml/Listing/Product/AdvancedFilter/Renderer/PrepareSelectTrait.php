<?php

declare(strict_types=1);

namespace Ess\M2ePro\Block\Adminhtml\Listing\Product\AdvancedFilter\Renderer;

trait PrepareSelectTrait
{
    /**
     * @param \Ess\M2ePro\Model\Listing\Product\AdvancedFilter[] $entities
     *
     * @return array
     */
    private function createSelect(array $entities): array
    {
        $result = [''];

        $byId = [];
        foreach ($entities as $entity) {
            $byId[$entity->getId()] = $entity->getTitle();
        }

        asort($byId);

        return $result + $byId;
    }
}
