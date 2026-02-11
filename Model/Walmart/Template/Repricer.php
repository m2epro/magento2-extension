<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Walmart\Template;

use Ess\M2ePro\Model\ResourceModel\Walmart\Template\Repricer as TemplateRepricerResource;

class Repricer extends \Ess\M2ePro\Model\ActiveRecord\Component\AbstractModel
{
    public const REPRICER_MIN_MAX_PRICE_MODE_NONE = 0;
    public const REPRICER_MIN_MAX_PRICE_MODE_ATTRIBUTE = 1;

    private array $cachedSources = [];

    private \Ess\M2ePro\Model\ResourceModel\Listing\CollectionFactory $listingCollectionFactory;
    private \Ess\M2ePro\Model\ResourceModel\Listing\Product\CollectionFactory $listingProductCollectionFactory;
    private \Ess\M2ePro\Model\Walmart\Template\Repricer\SourceFactory $sourceFactory;

    public function __construct(
        \Ess\M2ePro\Model\ResourceModel\Listing\CollectionFactory $listingCollectionFactory,
        \Ess\M2ePro\Model\ResourceModel\Listing\Product\CollectionFactory $listingProductCollectionFactory,
        \Ess\M2ePro\Model\Walmart\Template\Repricer\SourceFactory $sourceFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        ?\Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        ?\Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct(
            $modelFactory,
            $activeRecordFactory,
            $helperFactory,
            $context,
            $registry,
            $resource,
            $resourceCollection,
            $data
        );
        $this->listingCollectionFactory = $listingCollectionFactory;
        $this->listingProductCollectionFactory = $listingProductCollectionFactory;
        $this->sourceFactory = $sourceFactory;
    }

    public function _construct()
    {
        parent::_construct();
        $this->_init(TemplateRepricerResource::class);
    }

    public function isLocked(): bool
    {
        $listingCollection = $this->listingCollectionFactory->createWithWalmartChildMode();
        $listingCollection->addFieldToFilter(
            \Ess\M2ePro\Model\ResourceModel\Walmart\Listing::COLUMN_TEMPLATE_REPRICER_ID,
            ['eq' => $this->getId()]
        );

        if ($listingCollection->getSize() > 0) {
            return true;
        }

        $listingProductCollection = $this->listingProductCollectionFactory->createWithWalmartChildMode();
        $listingProductCollection->addFieldToFilter(
            \Ess\M2ePro\Model\ResourceModel\Walmart\Listing\Product::COLUMN_TEMPLATE_REPRICER_ID,
            ['eq' => $this->getId()]
        );

        if ($listingProductCollection->getSize() > 0) {
            return true;
        }

        return false;
    }

    public function getSource(
        \Ess\M2ePro\Model\Magento\Product $magentoProduct
    ): \Ess\M2ePro\Model\Walmart\Template\Repricer\Source {
        $productId = $magentoProduct->getProductId();

        if (!empty($this->cachedSources[$productId])) {
            return $this->cachedSources[$productId];
        }

        $this->cachedSources[$productId] = $this->sourceFactory
            ->create($this, $magentoProduct);

        return $this->cachedSources[$productId];
    }

    public function getTitle(): string
    {
        return (string)$this->getData(TemplateRepricerResource::COLUMN_TITLE);
    }

    public function getStrategyName(): string
    {
        return (string)$this->getData(TemplateRepricerResource::COLUMN_STRATEGY_NAME);
    }

    public function isMinPriceModeAttribute(): bool
    {
        return (int)$this->getData(TemplateRepricerResource::COLUMN_MIN_PRICE_MODE)
            === self::REPRICER_MIN_MAX_PRICE_MODE_ATTRIBUTE;
    }

    public function getMinPriceAttribute(): string
    {
        return (string)$this->getData(TemplateRepricerResource::COLUMN_MIN_PRICE_ATTRIBUTE);
    }

    public function isMaxPriceModeAttribute(): bool
    {
        return (int)$this->getData(TemplateRepricerResource::COLUMN_MAX_PRICE_MODE)
            === self::REPRICER_MIN_MAX_PRICE_MODE_ATTRIBUTE;
    }

    public function getMaxPriceAttribute(): string
    {
        return (string)$this->getData(TemplateRepricerResource::COLUMN_MAX_PRICE_ATTRIBUTE);
    }

    public function getRepricerAttributes(): array
    {
        $attributes = [];

        if ($this->isMinPriceModeAttribute()) {
            $attributes[] = $this->getMinPriceAttribute();
        }

        if ($this->isMaxPriceModeAttribute()) {
            $attributes[] = $this->getMaxPriceAttribute();
        }

        return array_unique($attributes);
    }
}
