<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Amazon\Listing\Auto\Advanced\Filter;

class Delete
{
    private \Ess\M2ePro\Model\Listing\Auto\Advanced\Filter\AttributesCache $attributesCache;

    public function __construct(
        \Ess\M2ePro\Model\Listing\Auto\Advanced\Filter\AttributesCache $attributesCache
    ) {
        $this->attributesCache = $attributesCache;
    }

    public function execute(\Ess\M2ePro\Model\Listing\Auto\Advanced\Filter $filter): void
    {
        $this->attributesCache->reset();
        $filter->delete();
    }
}
