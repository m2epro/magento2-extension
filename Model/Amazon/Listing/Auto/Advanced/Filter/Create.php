<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Amazon\Listing\Auto\Advanced\Filter;

class Create
{
    private \Ess\M2ePro\Model\Listing\Auto\Advanced\FilterFactory $autoAdvancedFilterFactory;
    private \Ess\M2ePro\Model\Amazon\Listing\Auto\Advanced\FilterFactory $amazonAdvancedFilterFactory;
    private \Ess\M2ePro\Model\Listing\Auto\Advanced\Filter\AttributesCache $attributesCache;

    public function __construct(
        \Ess\M2ePro\Model\Listing\Auto\Advanced\FilterFactory $autoAdvancedFilterFactory,
        \Ess\M2ePro\Model\Amazon\Listing\Auto\Advanced\FilterFactory $amazonAdvancedFilterFactory,
        \Ess\M2ePro\Model\Listing\Auto\Advanced\Filter\AttributesCache $attributesCache
    ) {
        $this->autoAdvancedFilterFactory = $autoAdvancedFilterFactory;
        $this->amazonAdvancedFilterFactory = $amazonAdvancedFilterFactory;
        $this->attributesCache = $attributesCache;
    }

    public function execute(
        int $listingId,
        string $title,
        int $addingMode,
        int $addingAddNotVisible,
        int $deletingMode,
        string $condition,
        ?int $addingProductTypeTemplateId
    ): \Ess\M2ePro\Model\Listing\Auto\Advanced\Filter {
        $parent = $this->autoAdvancedFilterFactory
            ->createWithComponentModeAmazon()
            ->setListingId($listingId)
            ->setTitle($title)
            ->setAddingMode($addingMode)
            ->setAddingAddNotVisible($addingAddNotVisible)
            ->setDeletingMode($deletingMode)
            ->setCondition($condition);

        $child = $this->amazonAdvancedFilterFactory
            ->create()
            ->setAddingProductTypeTemplateId($addingProductTypeTemplateId);

        $parent->save();
        $child->setListingAutoAdvancedFilterId((int)$parent->getId())->save();

        $parent->setChildObject($child);

        $this->attributesCache->reset();

        return $parent;
    }
}
