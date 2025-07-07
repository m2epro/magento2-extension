<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Ebay\Listing\Wizard;

use Ess\M2ePro\Helper\Component\Ebay\Category;

class RememberedCategoriesService
{
    private const REMEMBERED_CATEGORIES_SAME_MODE = 'ebay_remembered_categories_same_mode';

    private \Ess\M2ePro\Model\Ebay\Template\CategoryFactory $templateCategoryFactory;
    private \Ess\M2ePro\Model\Ebay\Template\StoreCategoryFactory $templateStoreCategoryFactory;

    public function __construct(
        \Ess\M2ePro\Model\Ebay\Template\CategoryFactory $templateCategoryFactory,
        \Ess\M2ePro\Model\Ebay\Template\StoreCategoryFactory $templateStoreCategoryFactory
    ) {
        $this->templateCategoryFactory = $templateCategoryFactory;
        $this->templateStoreCategoryFactory = $templateStoreCategoryFactory;
    }

    /**
     * @return array<int, array{
     *      mode: int,
     *      value: int|string,
     *      path: string,
     *      template_id: int,
     *      is_custom_template: int
     *  }>
     */
    public function getPreviousEbayCategoryChoice(array $additionalData): array
    {
        $categoryIds = $additionalData[self::REMEMBERED_CATEGORIES_SAME_MODE] ?? null;
        if (!$categoryIds) {
            return [];
        }

        $result = [];
        if (!empty($categoryIds[Category::TYPE_EBAY_MAIN])) {
            $template = $this->getCategoryTemplate($categoryIds[Category::TYPE_EBAY_MAIN]);
            if ($template) {
                $result[Category::TYPE_EBAY_MAIN] = $this->getCategoryData($template);
            }
        }

        if (!empty($categoryIds[Category::TYPE_EBAY_SECONDARY])) {
            $template = $this->getCategoryTemplate($categoryIds[Category::TYPE_EBAY_SECONDARY]);
            if ($template) {
                $result[Category::TYPE_EBAY_SECONDARY] = $this->getCategoryData($template);
            }
        }

        if (!empty($categoryIds[Category::TYPE_STORE_MAIN])) {
            $template = $this->getStoreCategoryTemplate($categoryIds[Category::TYPE_STORE_MAIN]);
            if ($template) {
                $result[Category::TYPE_STORE_MAIN] = $this->getCategoryData($template);
            }
        }

        if (!empty($categoryIds[Category::TYPE_STORE_SECONDARY])) {
            $template = $this->getStoreCategoryTemplate($categoryIds[Category::TYPE_STORE_SECONDARY]);
            if ($template) {
                $result[Category::TYPE_STORE_SECONDARY] = $this->getCategoryData($template);
            }
        }

        return $result;
    }

    public function updateEbayCategoryChoiceForSameMode(
        array $additionalData,
        \Ess\M2ePro\Model\Ebay\Listing\Wizard\TemplateCategoryLinkProcessorResult $categoryLinkResult
    ): array {
        $additionalData[self::REMEMBERED_CATEGORIES_SAME_MODE] = [
            Category::TYPE_EBAY_MAIN => $categoryLinkResult->getCategoryId(),
            Category::TYPE_EBAY_SECONDARY => $categoryLinkResult->getSecondaryCategoryId(),
            Category::TYPE_STORE_MAIN => $categoryLinkResult->getStoreCategoryId(),
            Category::TYPE_STORE_SECONDARY => $categoryLinkResult->getSecondaryStoreCategoryId(),
        ];

        return $additionalData;
    }

    private function getCategoryData(\Ess\M2ePro\Model\Ebay\Template\CategoryInterface $category): array
    {
        return [
            'mode' => $category->getCategoryMode(),
            'value' => $category->getCategoryValue(),
            'path' => $category->getCategoryPath(),
            'template_id' => (int)$category->getId(),
            'is_custom_template' => $category->getIsCustomTemplate(),
        ];
    }

    private function getCategoryTemplate(int $id): ?\Ess\M2ePro\Model\Ebay\Template\Category
    {
        try {
            $template = $this->templateCategoryFactory->create();

            return $template->load($id);
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function getStoreCategoryTemplate(int $id): ?\Ess\M2ePro\Model\Ebay\Template\StoreCategory
    {
        try {
            $template = $this->templateStoreCategoryFactory->create();

            return $template->load($id);
        } catch (\Throwable $e) {
            return null;
        }
    }
}
