<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Ebay\Listing\Wizard\Provider\Category;

use Ess\M2ePro\Model\MarketplaceFactory;
use Ess\M2ePro\Model\ResourceModel\Marketplace as MarketplaceResource;
use Ess\M2ePro\Model\AccountFactory;
use Ess\M2ePro\Model\ResourceModel\Account as AccountResource;
use Ess\M2ePro\Helper\Magento\Attribute as AttributeHelper;
use Ess\M2ePro\Helper\Component\Ebay\Category;
use Ess\M2ePro\Model\Ebay\Listing\Wizard\Product;
use Ess\M2ePro\Model\Ebay\Template\Category as TemplateCategory;
use Ess\M2ePro\Model\Ebay\Template\StoreCategory as TemplateStoreCategory;
use Ess\M2ePro\Model\ActiveRecord\Factory as ActiveRecordFactory;
use Ess\M2ePro\Helper\Factory as HelperFactory;

class Details
{
    private Category $componentEbayCategory;

    private AttributeHelper $magentoAttributeHelper;

    private AccountFactory $accountFactory;

    private AccountResource $accountResource;

    private MarketplaceFactory $marketplaceModelFactory;

    private MarketplaceResource $marketplaceResource;

    private ActiveRecordFactory $activeRecordFactory;

    private HelperFactory $helperFactory;

    public function __construct(
        AttributeHelper $magentoAttributeHelper,
        Category $componentEbayCategory,
        MarketplaceResource $marketplaceResource,
        MarketplaceFactory $marketplaceModelFactory,
        AccountFactory $accountFactory,
        AccountResource $accountResource,
        ActiveRecordFactory $activeRecordFactory,
        HelperFactory $helperFactory
    ) {
        $this->magentoAttributeHelper = $magentoAttributeHelper;
        $this->componentEbayCategory = $componentEbayCategory;
        $this->marketplaceResource = $marketplaceResource;
        $this->marketplaceModelFactory = $marketplaceModelFactory;
        $this->accountFactory = $accountFactory;
        $this->accountResource = $accountResource;
        $this->activeRecordFactory = $activeRecordFactory;
        $this->helperFactory = $helperFactory;
    }

    /**
     * @param Product[] $wizardProducts
     *
     * @return array
     *
     */
    public function getCategoriesDetails(array $wizardProducts, int $accountId, int $marketplaceId): array
    {
        $categoriesDetails = [];

        foreach ($wizardProducts as $product) {
            $details = [];

            if ($product->getTemplateCategoryId()) {
                $details[Category::TYPE_EBAY_MAIN] = $this->getSingleCategoryDetails(
                    $accountId,
                    $marketplaceId,
                    Category::TYPE_EBAY_MAIN,
                    (int)$product->getTemplateCategoryId()
                );
            }

            if ($product->getTemplateCategorySecondaryId()) {
                $details[Category::TYPE_EBAY_SECONDARY] = $this->getSingleCategoryDetails(
                    $accountId,
                    $marketplaceId,
                    Category::TYPE_EBAY_SECONDARY,
                    (int)$product->getTemplateCategorySecondaryId()
                );
            }

            if ($product->getStoreCategoryId()) {
                $details[Category::TYPE_STORE_MAIN] = $this->getSingleCategoryDetails(
                    $accountId,
                    $marketplaceId,
                    Category::TYPE_STORE_MAIN,
                    (int)$product->getStoreCategoryId()
                );
            }

            if ($product->getStoreCategorySecondaryId()) {
                $details[Category::TYPE_STORE_SECONDARY]  = $this->getSingleCategoryDetails(
                    $accountId,
                    $marketplaceId,
                    Category::TYPE_STORE_SECONDARY,
                    (int)$product->getStoreCategorySecondaryId()
                );
            }

            $details['listing_product_ids'] = [$product->getId()];

            $categoriesDetails[$product->getId()] = $details;
        }

        return $categoriesDetails;
    }

    public function getSingleCategoryDetails(
        int $accountId,
        int $marketplaceId,
        int $categoryType,
        int $templateId
    ): array {
        $details = [
            'path' => '',
            'interface_path' => '',
            'template_id' => null,
            'is_custom_template' => null,
            'mode' => 0,
            'value' => ''
        ];

        $categoryHelper = $this->componentEbayCategory;

        $template = $this->getTemplate($categoryType, $templateId);

        if ($template->getId()) {
            $mode = $template->getCategoryMode();
            $details['mode'] = $mode;

            switch ($mode) {
                case TemplateCategory::CATEGORY_MODE_EBAY:
                    $details['value'] = $template->getCategoryId();
                    $details['path'] = $categoryHelper->isEbayCategoryType($categoryType)
                        ? $this->getEbayCategoryPath($template->getCategoryId(), $marketplaceId)
                        : $this->getStoreCategoryPath($template->getCategoryId(), $accountId);

                    $details['interface_path'] = $details['path'] . ' (' . $template->getCategoryId() . ')';
                    break;
                case \Ess\M2ePro\Model\Ebay\Template\Category::CATEGORY_MODE_ATTRIBUTE:
                    $details['value'] = $template->getCategoryAttribute();
                    $details['path'] = $template['category_path'] ?? '';

                    $details['interface_path'] = $details['path'];

                    break;
            }

            if ($categoryType == Category::TYPE_EBAY_MAIN) {
                $details['is_custom_template'] = $template->getIsCustomTemplate();
                $details['template_id'] = $template->getId();
            }
        }

        return $details;
    }

    private function getEbayCategoryPath($value, $marketplaceId, $includeTitle = true)
    {
        $marketplaceModel = $this->marketplaceModelFactory->create();

        $this->marketplaceResource->load($marketplaceModel, $marketplaceId);

        if ($marketplaceModel->getId()) {
            $category = $marketplaceModel->getChildObject()
                                         ->getCategory((int)$value);
        }

        if (!$category) {
            return '';
        }

        $category['path'] = str_replace(' > ', '>', $category['path']);

        return $category['path'] . ($includeTitle ? '>' . $category['title'] : '');
    }

    private function getStoreCategoryPath($value, $accountId, $delimiter = '>')
    {
        $accountModel = $this->accountFactory->create();
        $this->accountResource->load($accountModel, $accountId);
        if ($accountModel->getId()) {
            $categories = $accountModel->getChildObject()->getEbayStoreCategories();

            $pathData = [];

            while (true) {
                $currentCategory = null;

                foreach ($categories as $category) {
                    if ($category['category_id'] == $value) {
                        $currentCategory = $category;
                        break;
                    }
                }

                if ($currentCategory === null) {
                    break;
                }

                $pathData[] = $currentCategory['title'];

                if ($currentCategory['parent_id'] == 0) {
                    break;
                }

                $value = $currentCategory['parent_id'];
            }

            array_reverse($pathData);

            return implode($delimiter, $pathData);
        }

        return '';
    }

    private function __()
    {
        return $this->getHelper('Module\Translation')->translate(func_get_args());
    }

    /**
     * @param $helperName
     * @param array $arguments
     *
     * @return \Magento\Framework\App\Helper\AbstractHelper
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    private function getHelper($helperName)
    {
        return $this->helperFactory->getObject($helperName);
    }

    private function getTemplate(int $categoryType, int $templateId)
    {
        if (in_array($categoryType, [Category::TYPE_EBAY_MAIN, Category::TYPE_EBAY_SECONDARY])) {
            $modelType = 'Ebay_Template_Category';
        } elseif (in_array($categoryType, [Category::TYPE_STORE_MAIN, Category::TYPE_STORE_SECONDARY])) {
            $modelType = 'Ebay_Template_StoreCategory';
        }

        $template = $this->activeRecordFactory->getObject($modelType);

        return $template->load($templateId);
    }
}
