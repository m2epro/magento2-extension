<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Walmart\Dictionary;

use Ess\M2ePro\Model\ResourceModel\Walmart\Dictionary\ProductType as DictionaryResource;

class ProductType extends \Ess\M2ePro\Model\ActiveRecord\AbstractModel
{
    /**
     * @param string[] $variationAttributes
     */
    public function init(
        int $marketplaceId,
        string $nick,
        string $title,
        array $attributes,
        array $variationAttributes
    ): void {
        $this->setData(DictionaryResource::COLUMN_MARKETPLACE_ID, $marketplaceId);
        $this->setData(DictionaryResource::COLUMN_NICK, $nick);
        $this->setData(DictionaryResource::COLUMN_TITLE, $title);
        $this->setAttributes($attributes);
        $this->setVariationAttributes($variationAttributes);

        $this->markAsValid();
    }

    public function _construct()
    {
        $this->_init(\Ess\M2ePro\Model\ResourceModel\Walmart\Dictionary\ProductType::class);
    }

    public function getId(): ?int
    {
        if ($this->getDataByKey(DictionaryResource::COLUMN_ID) === null) {
            return null;
        }

        return (int)$this->getDataByKey(DictionaryResource::COLUMN_ID);
    }

    public function getTitle(): string
    {
        return $this->getDataByKey(DictionaryResource::COLUMN_TITLE);
    }

    public function getNick(): string
    {
        return $this->getDataByKey(DictionaryResource::COLUMN_NICK);
    }

    public function getMarketplaceId(): int
    {
        return (int)$this->getDataByKey(DictionaryResource::COLUMN_MARKETPLACE_ID);
    }

    public function getAttributes(): array
    {
        $attributes = $this->getDataByKey(DictionaryResource::COLUMN_ATTRIBUTES);
        if (empty($attributes)) {
            return [];
        }

        return \Ess\M2ePro\Helper\Json::decode($attributes);
    }

    public function setAttributes(array $attributes): self
    {
        $this->setData(
            DictionaryResource::COLUMN_ATTRIBUTES,
            \Ess\M2ePro\Helper\Json::encode($attributes)
        );

        return $this;
    }

    /**
     * @return string[]
     */
    public function getVariationAttributes(): array
    {
        $variationAttributes = $this->getDataByKey(DictionaryResource::COLUMN_VARIATION_ATTRIBUTES);
        if (empty($variationAttributes)) {
            return [];
        }

        return \Ess\M2ePro\Helper\Json::decode($variationAttributes);
    }

    public function setVariationAttributes(array $variationAttributes): self
    {
        $this->setData(
            DictionaryResource::COLUMN_VARIATION_ATTRIBUTES,
            \Ess\M2ePro\Helper\Json::encode($variationAttributes)
        );

        return $this;
    }

    public function isInvalid(): bool
    {
        return (bool)$this->getDataByKey(DictionaryResource::COLUMN_INVALID);
    }

    public function markAsInvalid(): self
    {
        $this->setIsInvalid(true);

        return $this;
    }

    public function markAsValid(): self
    {
        $this->setIsInvalid(false);

        return $this;
    }

    private function setIsInvalid(bool $val): void
    {
        $this->setData(DictionaryResource::COLUMN_INVALID, $val);
    }
}
