<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Walmart\AttributeMapping\VariationAttributes\Provider;

use Ess\M2ePro\Model\Walmart\AttributeMapping\VariationAttributes\Provider\AttributeOption as ProviderAttributeOption;

class VariationAttribute
{
    private string $channelAttributeName;
    private string $channelAttributeTitle;
    private array $channelAttributeOptions;
    private string $magentoAttributeCode;
    private string $magentoAttributeTitle;
    private array $magentoAttributeOptions;

    /**
     * @param ProviderAttributeOption[] $channelAttributeOptions
     */
    public function __construct(
        string $channelAttributeName,
        string $channelAttributeTitle,
        array $channelAttributeOptions,
        string $magentoAttributeCode,
        string $magentoAttributeTitle,
        array $magentoAttributeOptions
    ) {
        $this->channelAttributeName = $channelAttributeName;
        $this->channelAttributeTitle = $channelAttributeTitle;
        $this->channelAttributeOptions = $channelAttributeOptions;
        $this->magentoAttributeCode = $magentoAttributeCode;
        $this->magentoAttributeTitle = $magentoAttributeTitle;
        $this->magentoAttributeOptions = $magentoAttributeOptions;
    }

    public function getChannelAttributeName(): string
    {
        return $this->channelAttributeName;
    }

    public function getChannelAttributeTitle(): string
    {
        return $this->channelAttributeTitle;
    }

    /**
     * @return ProviderAttributeOption[]
     */
    public function getChannelAttributeOptions(): array
    {
        return $this->channelAttributeOptions;
    }

    public function getMagentoAttributeCode(): string
    {
        return $this->magentoAttributeCode;
    }

    public function getMagentoAttributeTitle(): string
    {
        return $this->magentoAttributeTitle;
    }

    public function getMagentoAttributeOptions(): array
    {
        return $this->magentoAttributeOptions;
    }
}
