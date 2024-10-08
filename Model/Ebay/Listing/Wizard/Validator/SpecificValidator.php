<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Ebay\Listing\Wizard\Validator;

use Ess\M2ePro\Model\ActiveRecord\Factory as ActiveRecordFactory;
use Ess\M2ePro\Helper\Component\Ebay\Category\Ebay as EbayHelper;
use Ess\M2ePro\Model\Magento\ProductFactory;
use Ess\M2ePro\Model\Ebay\Listing\Wizard\Product;
use Ess\M2ePro\Model\Tag\BlockingErrors;

class SpecificValidator implements ValidatorInterface
{
    /** @var array */
    private array $requiredCategorySpecificNames = [];

    private EbayHelper $ebayCategoryHelper;

    private ProductFactory $m2eProductFactory;

    private ActiveRecordFactory $activeRecordFactory;

    public function __construct(
        EbayHelper $ebayCategoryHelper,
        ProductFactory $m2eProductFactory,
        ActiveRecordFactory $activeRecordFactory
    ) {
        $this->ebayCategoryHelper = $ebayCategoryHelper;
        $this->m2eProductFactory = $m2eProductFactory;
        $this->activeRecordFactory = $activeRecordFactory;
    }

    public function validate(array $products): void
    {
        foreach ($products as $product) {
            if (!$product->getTemplateCategoryId() && !$product->getStoreCategoryId()) {
                continue;
            }
            $this->validateProduct($product);
        }
    }

    public function checkForAttributeInChildren(
        \Ess\M2ePro\Model\Magento\Product $product,
        string $attributeCode
    ): string {
        $childProducts = [];

        if ($product->isConfigurableType()) {
            $childProducts = $product->getTypeInstance()->getUsedProducts($product->getProduct());
        } elseif ($product->isGroupedType()) {
            $childProducts = $product->getTypeInstance()->getAssociatedProducts($product->getProduct());
        }

        $attributeValues = [];

        foreach ($childProducts as $childProduct) {
            $tmpProduct = $this->m2eProductFactory->create();
            $tmpProduct->loadProduct($childProduct->getId(), $childProduct->getStoreId());
            $attributeValue = $tmpProduct->getAttributeValue($attributeCode);
            if ($attributeValue && !in_array($attributeValue, $attributeValues)) {
                $attributeValues = array_merge($attributeValues, explode(',', $attributeValue));
            }
        }

        return implode(', ', $attributeValues);
    }

    /**
     * @return string[]
     */
    private function loadCategoryRequiredSpecificNames(int $categoryId, int $marketplaceId): array
    {
        $key = "{$categoryId}_$marketplaceId";
        if (array_key_exists($key, $this->requiredCategorySpecificNames)) {
            return $this->requiredCategorySpecificNames[$key];
        }

        $specifics = $this->ebayCategoryHelper
            ->getSpecifics($categoryId, $marketplaceId);

        if ($specifics === null) {
            return [];
        }

        $requiredSpecificNames = [];
        foreach ($specifics as $specific) {
            if ($specific['required']) {
                $requiredSpecificNames[] = $specific['title'];
            }
        }

        return $this->requiredCategorySpecificNames[$key] = $requiredSpecificNames;
    }

    public function loadCustomSpecific(Product $wizardProduct): \Generator
    {
        $category = $this->getCategoryTemplateModelById($wizardProduct->getTemplateCategoryId());
        foreach ($category->getSpecifics(true) as $specific) {
            if (!$specific->isCustomAttributeValueMode()) {
                continue;
            }

            yield [
                'title' => $specific->getAttributeTitle(),
                'attribute_code' => $specific->getValueCustomAttribute(),
                'category_id' => $category->getCategoryId(),
                'marketplace_id' => $wizardProduct->getWizard()->getListing()->getMarketplaceId(),
            ];
        }
    }

    private function validateProduct(Product $product)
    {
        $customAttributesSpecific = $this->loadCustomSpecific($product);

        foreach ($customAttributesSpecific as $data) {
            $categoryRequiredNames = $this->loadCategoryRequiredSpecificNames(
                (int)$data['category_id'],
                (int)$data['marketplace_id']
            );

            $attributeCode = $data['attribute_code'];
            $specificName = $data['title'];

            if (!in_array($specificName, $categoryRequiredNames, true)) {
                continue;
            }

            $magentoProduct = $product->getMagentoProduct();
            $attributeValue = $magentoProduct->getAttributeValue($attributeCode);
            $attributeValue = trim($attributeValue);

            if (empty($attributeValue)) {
                $attributeValue = $this->checkForAttributeInChildren($magentoProduct, $attributeCode);

                if (empty($attributeValue)) {
                    $product->setValidationStatus(ValidatorComposite::STATUS_INVALID);
                    $product->addErrorMessage(
                        [
                            BlockingErrors::CATEGORY_SPECIFIC_ERROR_TAG_CODE =>  (string)__(
                                'Specific "%specific_name" empty',
                                ['specific_name' => $specificName]
                            )
                        ]
                    );
                    /**
                     * @todo To check whether data from  m2epro_ebay_category_specific_validation_result table
                     * isn`t used except validation step grid. If yes, consider deletion as redundant
                     */
                }
            }
        }
    }

    private function getCategoryTemplateModelById(int $id)
    {
        return $this->activeRecordFactory->getCachedObjectLoaded(
            'Ebay_Template_Category',
            $id
        );
    }
}
