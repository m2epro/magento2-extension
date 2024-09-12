<?php

declare(strict_types=1);

namespace Ess\M2ePro\Ui\Template\ProductType\Component\Listing\Select;

use Magento\Framework\Data\OptionSourceInterface;

class Marketplace implements OptionSourceInterface
{
    private \Ess\M2ePro\Model\Amazon\Template\ProductType\Repository $repository;

    public function __construct(\Ess\M2ePro\Model\Amazon\Template\ProductType\Repository $repository)
    {
        $this->repository = $repository;
    }

    public function toOptionArray(): array
    {
        $options = [];

        foreach ($this->repository->getUsingMarketplaces() as $marketplace) {
            $options[] = [
                'label' => $marketplace->getTitle(),
                'value' => $marketplace->getId(),
            ];
        }

        return $options;
    }
}
