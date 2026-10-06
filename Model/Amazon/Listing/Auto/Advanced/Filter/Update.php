<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Amazon\Listing\Auto\Advanced\Filter;

class Update
{
    private \Ess\M2ePro\Model\Listing\Auto\Advanced\Filter\AttributesCache $attributesCache;

    public function __construct(\Ess\M2ePro\Model\Listing\Auto\Advanced\Filter\AttributesCache $attributesCache)
    {
        $this->attributesCache = $attributesCache;
    }

    public function execute(
        \Ess\M2ePro\Model\Listing\Auto\Advanced\Filter $advancedFilter,
        string $title,
        int $addingMode,
        int $addingAddNotVisible,
        int $deletingMode,
        string $condition,
        ?int $addingProductTypeTemplateId
    ): \Ess\M2ePro\Model\Listing\Auto\Advanced\Filter {
        $advancedFilter
            ->setTitle($title)
            ->setAddingMode($addingMode)
            ->setAddingAddNotVisible($addingAddNotVisible)
            ->setDeletingMode($deletingMode)
            ->setCondition($condition);

        /** @var \Ess\M2ePro\Model\Amazon\Listing\Auto\Advanced\Filter $amazonAdvancedFilter */
        $amazonAdvancedFilter = $advancedFilter->getChildObject();
        $amazonAdvancedFilter
            ->setAddingProductTypeTemplateId($addingProductTypeTemplateId);

        $advancedFilter->save();

        $this->attributesCache->reset();

        return $advancedFilter;
    }
}
