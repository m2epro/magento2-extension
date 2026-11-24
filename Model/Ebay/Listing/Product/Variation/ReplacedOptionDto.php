<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Ebay\Listing\Product\Variation;

class ReplacedOptionDto
{
    public string $attribute;
    public string $option;
    public string $channelOption;

    public function __construct(
        string $attribute,
        string $option,
        string $channelOption
    ) {
        $this->attribute = $attribute;
        $this->option = $option;
        $this->channelOption = $channelOption;
    }

    public static function createFromArray(array $data): self
    {
        return new self($data['attribute'], $data['option'], $data['channel_option']);
    }

    public function toArray(): array
    {
        return [
            'attribute' => $this->attribute,
            'option' => $this->option,
            'channel_option' => $this->channelOption,
        ];
    }
}
