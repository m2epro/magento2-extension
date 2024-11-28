<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Ebay\AttributeMapping;

class Pair
{
    public ?int $id;
    public string $type;
    public int $mode;
    public string $channelAttributeTitle;
    public string $channelAttributeCode;
    public ?string $value;

    public function __construct(
        ?int $id,
        string $type,
        int $mode,
        string $channelAttributeTitle,
        string $channelAttributeCode,
        ?string $value
    ) {
        $this->id = $id;
        $this->type = $type;
        $this->mode = $mode;
        $this->channelAttributeTitle = $channelAttributeTitle;
        $this->channelAttributeCode = $channelAttributeCode;
        $this->value = $value;
    }

    public function isValueModeNone(): bool
    {
        return $this->mode === \Ess\M2ePro\Model\AttributeMapping\Pair::VALUE_MODE_NONE;
    }

    public function isValueModeAttribute(): bool
    {
        return $this->mode === \Ess\M2ePro\Model\AttributeMapping\Pair::VALUE_MODE_ATTRIBUTE;
    }

    public function isValueModeCustom(): bool
    {
        return $this->mode === \Ess\M2ePro\Model\AttributeMapping\Pair::VALUE_MODE_CUSTOM;
    }
}
