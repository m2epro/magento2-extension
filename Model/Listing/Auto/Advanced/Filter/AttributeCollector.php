<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Listing\Auto\Advanced\Filter;

class AttributeCollector
{
    private \Ess\M2ePro\Model\ResourceModel\Listing\Auto\Advanced\Filter\CollectionFactory $autoAdvancedFilterCollectionFactory;
    private \Ess\M2ePro\Model\Listing\Auto\Advanced\Filter\AttributesCache $attributesCache;

    public function __construct(
        \Ess\M2ePro\Model\ResourceModel\Listing\Auto\Advanced\Filter\CollectionFactory $autoAdvancedFilterCollectionFactory,
        \Ess\M2ePro\Model\Listing\Auto\Advanced\Filter\AttributesCache $attributesCache
    ) {
        $this->autoAdvancedFilterCollectionFactory = $autoAdvancedFilterCollectionFactory;
        $this->attributesCache = $attributesCache;
    }

    public function execute(): array
    {
        if ($this->attributesCache->has()) {
            return $this->attributesCache->get();
        }

        $collection = $this->autoAdvancedFilterCollectionFactory->create();
        $attributes = [];
        foreach ($collection->getItems() as $advancedFilter) {
            $attributes = array_merge($attributes, $advancedFilter->getConditionAttributes());
        }

        $attributes = array_unique($attributes);

        $this->attributesCache->set($attributes);

        return $attributes;
    }
}
