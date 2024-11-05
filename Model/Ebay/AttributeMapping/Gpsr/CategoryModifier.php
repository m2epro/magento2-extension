<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Ebay\AttributeMapping\Gpsr;

class CategoryModifier
{
    private const COUNT_CATEGORIES_FOR_CYCLE = 50;

    private \Ess\M2ePro\Model\ResourceModel\Ebay\Template\Category\CollectionFactory $templateCategoryCollectionFactory;
    private \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory;
    private \Ess\M2ePro\Model\Ebay\Template\Category\AffectedListingsProductsFactory $affectedListingsProductsFactory;
    /** @var \Ess\M2ePro\Model\Ebay\AttributeMapping\Gpsr\CategoryModifier\CategoryDiffStub */
    private CategoryModifier\CategoryDiffStub $categoryDiffStub;
    private \Ess\M2ePro\Model\Ebay\Template\Category\ChangeProcessorFactory $changeProcessorFactory;

    public function __construct(
        \Ess\M2ePro\Model\ResourceModel\Ebay\Template\Category\CollectionFactory $templateCategoryCollectionFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Model\Ebay\Template\Category\AffectedListingsProductsFactory $affectedListingsProductsFactory,
        \Ess\M2ePro\Model\Ebay\AttributeMapping\Gpsr\CategoryModifier\CategoryDiffStub $categoryDiffStub,
        \Ess\M2ePro\Model\Ebay\Template\Category\ChangeProcessorFactory $changeProcessorFactory
    ) {
        $this->templateCategoryCollectionFactory = $templateCategoryCollectionFactory;
        $this->activeRecordFactory = $activeRecordFactory;
        $this->affectedListingsProductsFactory = $affectedListingsProductsFactory;
        $this->categoryDiffStub = $categoryDiffStub;
        $this->changeProcessorFactory = $changeProcessorFactory;
    }

    /**
     * @param \Ess\M2ePro\Model\Ebay\AttributeMapping\Pair[] $gpsrAttributes
     *
     * @return void
     */
    public function process(array $gpsrAttributes): void
    {
        $categoryTemplateId = 0;
        do {
            $categories = $this->getCategories($categoryTemplateId);
            foreach ($categories as $category) {
                $categoryTemplateId = (int)$category->getId();

                $isChangedCategory = $this->processCategory($category, $gpsrAttributes);
                if (!$isChangedCategory) {
                    continue;
                }

                $this->createProductInstruction($category);
            }
        } while (count($categories) === self::COUNT_CATEGORIES_FOR_CYCLE);
    }

    /**
     * @param int $fromId
     *
     * @return \Ess\M2ePro\Model\Ebay\Template\Category[]
     */
    private function getCategories(int $fromId): array
    {
        $collection = $this->templateCategoryCollectionFactory->create();
        $collection->addFieldToFilter('id', ['gt' => $fromId]);
        $collection->setOrder('id', \Magento\Framework\Data\Collection::SORT_ORDER_ASC);
        $collection->setPageSize(50);

        return array_values($collection->getItems());
    }

    /**
     * @param \Ess\M2ePro\Model\Ebay\Template\Category $category
     * @param \Ess\M2ePro\Model\Ebay\AttributeMapping\Pair[] $gpsrAttributes
     *
     * @return void
     */
    private function processCategory(\Ess\M2ePro\Model\Ebay\Template\Category $category, array $gpsrAttributes): bool
    {
        $specificsByCode = $this->getSpecificsByCode($category);

        $isChangedCategory = false;
        foreach ($gpsrAttributes as $gpsrAttribute) {
            $specific = $specificsByCode[$gpsrAttribute->channelAttributeCode] ?? null;

            if ($specific === null) {
                $specific = $this->createSpecific($category, $gpsrAttribute);
                $specific->save();

                $isChangedCategory = true;

                continue;
            }

            if ($specific->isNoneValueMode()) {
                $specific->setValueCustomAttribute($gpsrAttribute->magentoAttributeCode)
                         ->setValueCustomAttributeMode();

                $specific->save();

                $isChangedCategory = true;

                continue;
            }

            if (
                $specific->isCustomAttributeValueMode()
                && $specific->getValueCustomAttribute() !== $gpsrAttribute->magentoAttributeCode
            ) {
                $specific->setValueCustomAttribute($gpsrAttribute->magentoAttributeCode);

                $specific->save();

                $isChangedCategory = true;
            }
        }

        return $isChangedCategory;
    }

    /**
     * @param \Ess\M2ePro\Model\Ebay\Template\Category $category
     *
     * @return \Ess\M2ePro\Model\Ebay\Template\Category\Specific[]
     */
    private function getSpecificsByCode(\Ess\M2ePro\Model\Ebay\Template\Category $category): array
    {
        $result = [];
        foreach ($category->getSpecifics(true) as $specific) {
            $result[$specific->getAttributeTitle()] = $specific;
        }

        return $result;
    }

    private function createSpecific(
        \Ess\M2ePro\Model\Ebay\Template\Category $category,
        \Ess\M2ePro\Model\Ebay\AttributeMapping\Pair $gpsrAttribute
    ): \Ess\M2ePro\Model\Ebay\Template\Category\Specific {
        /** @var \Ess\M2ePro\Model\Ebay\Template\Category\Specific $specificModel */
        $specificModel = $this->activeRecordFactory->getObject('Ebay_Template_Category_Specific');
        $specificModel->setMode(\Ess\M2ePro\Model\Ebay\Template\Category\Specific::MODE_ITEM_SPECIFICS)
                      ->setAttributeTitle($gpsrAttribute->channelAttributeCode)
                      ->setValueMode(\Ess\M2ePro\Model\Ebay\Template\Category\Specific::VALUE_MODE_CUSTOM_ATTRIBUTE)
                      ->setValueCustomAttribute($gpsrAttribute->magentoAttributeCode)
                      ->setTemplateCategoryId((int)$category->getId());

        return $specificModel;
    }

    private function createProductInstruction(\Ess\M2ePro\Model\Ebay\Template\Category $category): void
    {
        $affectedListingsProducts = $this->affectedListingsProductsFactory->create();
        $affectedListingsProducts->setModel($category);

        $changeProcessor = $this->changeProcessorFactory->create();
        $changeProcessor->process(
            $this->categoryDiffStub,
            $affectedListingsProducts->getObjectsData(['id', 'status'])
        );
    }
}
