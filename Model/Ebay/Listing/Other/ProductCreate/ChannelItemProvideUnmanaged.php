<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Ebay\Listing\Other\ProductCreate;

use Laminas\Validator\Regex;
use Magento\Tax\Api\Data\TaxClassInterface;
use Magento\Tax\Model\ClassModel;

class ChannelItemProvideUnmanaged implements ProviderInterface
{
    private const DEFAULT_TAX_CLASS_NAME = 'Taxable Goods';

    private \Magento\Catalog\Model\ProductFactory $productFactory;
    private \Magento\Catalog\Model\Product\Url $url;
    private \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder;
    private \Magento\Tax\Api\TaxClassRepositoryInterface $taxClassRepository;

    public function __construct(
        \Magento\Catalog\Model\ProductFactory $productFactory,
        \Magento\Catalog\Model\Product\Url $url,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        \Magento\Tax\Api\TaxClassRepositoryInterface $taxClassRepository
    ) {
        $this->productFactory = $productFactory;
        $this->url = $url;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->taxClassRepository = $taxClassRepository;
    }

    public function getItem(\Ess\M2ePro\Model\Listing\Other $unmanagedProduct): ChannelItem
    {
        if (!empty($unmanagedProduct->getChildObject()->getOnlineVariations())) {
            return $this->buildConfigurableProductItem($unmanagedProduct);
        }

        return $this->buildSimpleProductItem($unmanagedProduct);
    }

    private function buildSimpleProductItem(\Ess\M2ePro\Model\Listing\Other $unmanagedProduct): ChannelItem
    {
        $stockStatus = $unmanagedProduct->getChildObject()->getOnlineQty() > 0 ? 1 : 0;

        return $this->initProductDataModel(
            \Ess\M2ePro\Model\Magento\Product::TYPE_SIMPLE_ORIGIN,
            \Magento\Catalog\Model\Product\Visibility::VISIBILITY_NOT_VISIBLE,
            $unmanagedProduct,
            $stockStatus
        );
    }

    private function buildConfigurableProductItem(\Ess\M2ePro\Model\Listing\Other $unmanagedProduct): ChannelItem
    {
        $stockStatus = $unmanagedProduct->getChildObject()->getOnlineQty() > 0 ? 1 : 0;
        $parenDataModel = $this->initProductDataModel(
            \Ess\M2ePro\Model\Magento\Product::TYPE_CONFIGURABLE_ORIGIN,
            \Magento\Catalog\Model\Product\Visibility::VISIBILITY_BOTH,
            $unmanagedProduct,
            $stockStatus
        );
        $variationsData = $this->getVariationsData(
            $parenDataModel,
            $unmanagedProduct
        );

        $parenDataModel->setVariationSet($variationsData['variationSet'] ?? []);
        $parenDataModel->setVariations($variationsData['variations'] ?? []);

        return $parenDataModel;
    }

    private function getVariationsData(
        ChannelItem $parentItem,
        \Ess\M2ePro\Model\Listing\Other $unmanagedProduct
    ): array {
        $variations = [];
        $variationSet = [];

        foreach ($unmanagedProduct->getChildObject()->getOnlineVariations() as $item) {
            $specifics = [];

            foreach ($item['specifics'] as $attributeName => $attributeValue) {
                $generatedAttributeCode = $this->generateCode($attributeName);

                $specifics[$generatedAttributeCode] = $attributeValue;

                $variationSet[$generatedAttributeCode]['attribute_code'] = $generatedAttributeCode;
                $variationSet[$generatedAttributeCode]['attribute_label'] = $attributeName;
                $variationSet[$generatedAttributeCode]['attribute_values'] = array_unique(
                    array_merge(
                        $variationSet[$generatedAttributeCode]['attribute_values'] ?? [],
                        [$attributeValue]
                    )
                );
            }

            $sku = $item['sku'] ?: $parentItem->getSku() . ' ' . implode(' ', array_values($specifics));

            $childDataArray = [
                'sku' => $sku,
                'quantity' => $item['quantity'],
                'price' => $item['price'],
                'specifics' => $item['specifics'],
            ];
            $stockStatus = $item['quantity'] > 0 ? 1 : 0;

            $childDataModel = $this->initProductDataModel(
                \Ess\M2ePro\Model\Magento\Product::TYPE_SIMPLE_ORIGIN,
                \Magento\Catalog\Model\Product\Visibility::VISIBILITY_NOT_VISIBLE,
                $unmanagedProduct,
                $stockStatus,
                $childDataArray
            );
            $childDataModel->setSpecifics($specifics);

            $variations[] = $childDataModel;
        }

        return [
            'variationSet' => $this->getVariationSet($variationSet),
            'variations' => $variations,
        ];
    }

    /**
     * Generate code from label, copy\pasted from core magento for consistency
     */
    private function generateCode(string $label): string
    {
        $code = substr(
            preg_replace(
                '/[^a-z_0-9]/',
                '_',
                $this->url->formatUrlKey($label)
            ),
            0,
            30
        );
        $validatorAttrCode = new Regex(['pattern' => '/^[a-z][a-z_0-9]{0,29}[a-z0-9]$/']);
        if (!$validatorAttrCode->isValid($code)) {
            // md5() here is not for cryptographic use.
            // phpcs:ignore Magento2.Security.InsecureFunction
            $code = 'attr_' . ($code ?: substr(hash('md5', (string)time()), 0, 8));
        }

        return $code;
    }

    private function initProductDataModel(
        string $productType,
        int $visibility,
        \Ess\M2ePro\Model\Listing\Other $unmanagedProduct,
        int $stockStatus,
        ?array $variationItemData = null
    ): ChannelItem {
        return new ChannelItem(
            (int)$this->productFactory->create()->getDefaultAttributeSetId(),
            $productType,
            $unmanagedProduct->getChildObject()->getRelatedStoreId(),
            (int)$this->getTaxClass(self::DEFAULT_TAX_CLASS_NAME)->getClassId(),
            $visibility,
            $this->getItemTitle(
                $unmanagedProduct,
                $variationItemData
            ),
            $unmanagedProduct->getChildObject()->getTitle(),
            $this->getItemSku(
                $unmanagedProduct,
                $variationItemData
            ),
            $variationItemData['quantity'] ?? $unmanagedProduct->getChildObject()->getOnlineQty(),
            $variationItemData['price'] ?? $unmanagedProduct->getChildObject()->getOnlinePrice(),
            $unmanagedProduct->getChildObject()->getCurrency(),
            $stockStatus
        );
    }

    private function getTaxClass(string $name): ?TaxClassInterface
    {
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter(ClassModel::KEY_NAME, $name)
            ->create();
        $searchResults = $this->taxClassRepository
            ->getList($searchCriteria)
            ->getItems();

        return array_shift($searchResults);
    }

    private function getItemTitle(
        \Ess\M2ePro\Model\Listing\Other $unmanagedProduct,
        ?array $variationItemData = null
    ): string {
        $title = $unmanagedProduct->getChildObject()->getTitle();
        if (!$variationItemData) {
            return $title;
        }

        return $title
            . ' '
            . implode(' ', array_values($variationItemData['specifics']));
    }

    private function getItemSku(
        \Ess\M2ePro\Model\Listing\Other $unmanagedProduct,
        ?array $variationItemData = null
    ): string {
        if (!isset($variationItemData['sku'])) {
            return substr(
                $unmanagedProduct->getChildObject()->getSku() ?: $unmanagedProduct->getChildObject()->getTitle(),
                0,
                64
            );
        }

        return substr(
            $variationItemData['sku'],
            0,
            64
        );
    }

    private function getVariationSet(array $specifics): array
    {
        $variationSet = [];
        foreach ($specifics as $specific) {
            $variationSet[] = new ChannelAttributeItem(
                $specific['attribute_code'],
                $specific['attribute_label'],
                $specific['attribute_values']
            );
        }

        return $variationSet;
    }
}
