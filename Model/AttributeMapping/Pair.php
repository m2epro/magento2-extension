<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\AttributeMapping;

use Ess\M2ePro\Model\ResourceModel\AttributeMapping\Pair as PairResource;

class Pair extends \Ess\M2ePro\Model\ActiveRecord\AbstractModel
{
    public const VALUE_MODE_NONE = 0;
    public const VALUE_MODE_ATTRIBUTE = 1;
    public const VALUE_MODE_CUSTOM = 2;

    protected function _construct(): void
    {
        parent::_construct();
        $this->_init(PairResource::class);
    }

    public function create(
        string $component,
        string $type,
        int $valueMode,
        string $channelAttributeTitle,
        string $channelAttributeCode,
        string $value
    ): self {
        $this->setData(PairResource::COLUMN_COMPONENT, $component)
             ->setData(PairResource::COLUMN_TYPE, $type)
             ->setValueMode($valueMode)
             ->setData(PairResource::COLUMN_CHANNEL_ATTRIBUTE_TITLE, $channelAttributeTitle)
             ->setData(PairResource::COLUMN_CHANNEL_ATTRIBUTE_CODE, $channelAttributeCode)
             ->setValue($value);

        return $this;
    }

    public function getId(): int
    {
        return (int)parent::getId();
    }

    public function getComponent(): string
    {
        return (string)$this->getData(PairResource::COLUMN_COMPONENT);
    }

    public function getType(): string
    {
        return (string)$this->getData(PairResource::COLUMN_TYPE);
    }

    public function isValueModeNone(): bool
    {
        return $this->getValueMode() === self::VALUE_MODE_NONE;
    }

    public function isValueModeAttribute(): bool
    {
        return $this->getValueMode() === self::VALUE_MODE_ATTRIBUTE;
    }

    public function isValueModeCustom(): bool
    {
        return $this->getValueMode() === self::VALUE_MODE_CUSTOM;
    }

    public function setValueMode(int $mode): self
    {
        if (!in_array($mode, [self::VALUE_MODE_NONE, self::VALUE_MODE_ATTRIBUTE, self::VALUE_MODE_CUSTOM])) {
            throw new \LogicException(sprintf('Invalid value mode "%s"', $mode));
        }

        $this->setData(PairResource::COLUMN_VALUE_MODE, $mode);

        return $this;
    }

    public function getValueMode(): int
    {
        return (int)$this->getData(PairResource::COLUMN_VALUE_MODE);
    }

    public function getChannelAttributeTitle(): string
    {
        return (string)$this->getData(PairResource::COLUMN_CHANNEL_ATTRIBUTE_TITLE);
    }

    public function getChannelAttributeCode(): string
    {
        return (string)$this->getData(PairResource::COLUMN_CHANNEL_ATTRIBUTE_CODE);
    }

    public function setValue(string $value): self
    {
        $this->setData(PairResource::COLUMN_VALUE, $value);

        return $this;
    }

    public function getValue(): string
    {
        return (string)$this->getData(PairResource::COLUMN_VALUE);
    }
}
