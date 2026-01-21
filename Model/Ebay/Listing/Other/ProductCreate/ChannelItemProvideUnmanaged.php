<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Ebay\Listing\Other\ProductCreate;

use Laminas\Validator\Regex;
use Magento\Tax\Api\Data\TaxClassInterface;
use Magento\Tax\Model\ClassModel;

class ChannelItemProvideUnmanaged implements ProviderInterface
{
    private const DEFAULT_TAX_CLASS_NAME = 'Taxable Goods';

    private int $defaultAttributeSetId;
    private int $defaultTaxClassId;

    private \Magento\Catalog\Model\ProductFactory $productFactory;
    private \Magento\Catalog\Model\Product\Url $url;
    private \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder;
    private \Magento\Tax\Api\TaxClassRepositoryInterface $taxClassRepository;
    private \Ess\M2ePro\Model\Ebay\Listing\Other\ProductCreate\ItemInfoLoader $itemInfoLoader;

    public function __construct(
        \Magento\Catalog\Model\ProductFactory $productFactory,
        \Magento\Catalog\Model\Product\Url $url,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        \Magento\Tax\Api\TaxClassRepositoryInterface $taxClassRepository,
        \Ess\M2ePro\Model\Ebay\Listing\Other\ProductCreate\ItemInfoLoader $itemInfoLoader
    ) {
        $this->productFactory = $productFactory;
        $this->url = $url;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->taxClassRepository = $taxClassRepository;
        $this->itemInfoLoader = $itemInfoLoader;
    }

    public function getItem(\Ess\M2ePro\Model\Listing\Other $unmanagedProduct): ChannelItem
    {
        /** @var \Ess\M2ePro\Model\Ebay\Listing\Other $ebayUnmanagedProduct */
        $ebayUnmanagedProduct = $unmanagedProduct->getChildObject();
        $channelItemInfo = $this->itemInfoLoader->loadByListingOther($unmanagedProduct);

        if (!empty($ebayUnmanagedProduct->getOnlineVariations())) {
            return $this->buildConfigurableProductItem($unmanagedProduct, $channelItemInfo);
        }

        return $this->buildSimpleProductItem($unmanagedProduct, $channelItemInfo);
    }

    private function buildSimpleProductItem(
        \Ess\M2ePro\Model\Listing\Other $unmanagedProduct,
        \Ess\M2ePro\Model\Ebay\Listing\Other\ProductCreate\ItemInfoLoader\ChannelItemInfo $channelItemInfo
    ): ChannelItem {
        /** @var \Ess\M2ePro\Model\Ebay\Listing\Other $ebayUnmanagedProduct */
        $ebayUnmanagedProduct = $unmanagedProduct->getChildObject();

        return new ChannelItem(
            $this->getDefaultAttributeSetId(),
            \Ess\M2ePro\Model\Magento\Product::TYPE_SIMPLE_ORIGIN,
            $ebayUnmanagedProduct->getRelatedStoreId(),
            $this->getTaxClassId(),
            \Magento\Catalog\Model\Product\Visibility::VISIBILITY_BOTH,
            $ebayUnmanagedProduct->getTitle(),
            $this->truncateSku($ebayUnmanagedProduct->getSku() ?: $ebayUnmanagedProduct->getTitle()),
            $ebayUnmanagedProduct->getOnlineQty(),
            $ebayUnmanagedProduct->getOnlinePrice(),
            $ebayUnmanagedProduct->getCurrency(),
            $ebayUnmanagedProduct->getOnlineQty() > 0 ? 1 : 0,
            $channelItemInfo->description,
            [],
            [],
            [],
            $channelItemInfo->pictureUrls
        );
    }

    private function buildConfigurableProductItem(
        \Ess\M2ePro\Model\Listing\Other $unmanagedProduct,
        \Ess\M2ePro\Model\Ebay\Listing\Other\ProductCreate\ItemInfoLoader\ChannelItemInfo $channelItemInfo
    ): ChannelItem {
        /** @var \Ess\M2ePro\Model\Ebay\Listing\Other $ebayUnmanagedProduct */
        $ebayUnmanagedProduct = $unmanagedProduct->getChildObject();

        $parentSku = $this->truncateSku($ebayUnmanagedProduct->getSku() ?: $ebayUnmanagedProduct->getTitle());
        [$variations, $variationSet] = $this
            ->createChildProducts($unmanagedProduct, $parentSku, $channelItemInfo);

        return new ChannelItem(
            $this->getDefaultAttributeSetId(),
            \Ess\M2ePro\Model\Magento\Product::TYPE_CONFIGURABLE_ORIGIN,
            $ebayUnmanagedProduct->getRelatedStoreId(),
            $this->getTaxClassId(),
            \Magento\Catalog\Model\Product\Visibility::VISIBILITY_BOTH,
            $ebayUnmanagedProduct->getTitle(),
            $parentSku,
            $ebayUnmanagedProduct->getOnlineQty(),
            $ebayUnmanagedProduct->getOnlinePrice(),
            $ebayUnmanagedProduct->getCurrency(),
            $ebayUnmanagedProduct->getOnlineQty() > 0 ? 1 : 0,
            $channelItemInfo->description,
            $variationSet,
            $variations,
            [],
            $channelItemInfo->pictureUrls
        );
    }

    private function createChildProducts(
        \Ess\M2ePro\Model\Listing\Other $unmanagedProduct,
        string $parentSku,
        \Ess\M2ePro\Model\Ebay\Listing\Other\ProductCreate\ItemInfoLoader\ChannelItemInfo $channelItemInfo
    ): array {
        /** @var \Ess\M2ePro\Model\Ebay\Listing\Other $ebayUnmanagedProduct */
        $ebayUnmanagedProduct = $unmanagedProduct->getChildObject();

        $variations = [];
        $variationSet = [];
        foreach ($ebayUnmanagedProduct->getOnlineVariations() as $variation) {
            $specifics = [];
            foreach ($variation['specifics'] as $attributeName => $attributeValue) {
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

            $childSku = $variation['sku'];
            if (empty($childSku)) {
                $childSku = $parentSku . ' ' . implode(' ', array_values($specifics));
            }

            $childTitle = sprintf(
                '%s %s',
                $ebayUnmanagedProduct->getTitle(),
                implode(' ', array_values($variation['specifics']))
            );

            $childDataModel = new ChannelItem(
                $this->getDefaultAttributeSetId(),
                \Ess\M2ePro\Model\Magento\Product::TYPE_SIMPLE_ORIGIN,
                $ebayUnmanagedProduct->getRelatedStoreId(),
                $this->getTaxClassId(),
                \Magento\Catalog\Model\Product\Visibility::VISIBILITY_NOT_VISIBLE,
                $childTitle,
                $this->truncateSku($childSku),
                $variation['quantity'],
                $variation['price'],
                $ebayUnmanagedProduct->getCurrency(),
                $variation['quantity'] > 0 ? 1 : 0,
                '',
                [],
                [],
                $specifics,
                $this->findImagesInChannelInfo($channelItemInfo, $variation)
            );

            $variations[] = $childDataModel;
        }

        $variationSet = $this->getVariationSet($variationSet);

        return [$variations, $variationSet];
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

    private function getTaxClassId(): int
    {
        /** @psalm-suppress RedundantPropertyInitializationCheck */
        if (!isset($this->defaultTaxClassId)) {
            $searchCriteria = $this->searchCriteriaBuilder
                ->addFilter(ClassModel::KEY_NAME, self::DEFAULT_TAX_CLASS_NAME)
                ->create();
            $searchResults = $this->taxClassRepository
                ->getList($searchCriteria)
                ->getItems();

            $taxClass = array_shift($searchResults);

            $this->defaultTaxClassId = (int)$taxClass->getId();
        }

        return $this->defaultTaxClassId;
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

    private function getDefaultAttributeSetId(): int
    {
        /** @psalm-suppress RedundantPropertyInitializationCheck */
        if (!isset($this->defaultAttributeSetId)) {
            $this->defaultAttributeSetId = (int)$this->productFactory->create()->getDefaultAttributeSetId();
        }

        return $this->defaultAttributeSetId;
    }

    private function truncateSku(string $sku): string
    {
        return substr($sku, 0, 64);
    }

    private function findImagesInChannelInfo(
        ItemInfoLoader\ChannelItemInfo $channelItemInfo,
        array $variation
    ): array {
        if (empty($channelItemInfo->variations)) {
            return [];
        }

        $searchSpecificHash = $this->makeSpecificHash($variation['specifics']);
        foreach ($channelItemInfo->variations as $variationInfo) {
            $specificHash = $this->makeSpecificHash($variationInfo->specifics);
            if ($searchSpecificHash === $specificHash) {
                return $variationInfo->images;
            }
        }

        return [];
    }

    private function makeSpecificHash(array $variationSpecifics): string
    {
        ksort($variationSpecifics);

        return implode('::', array_values($variationSpecifics));
    }
}
