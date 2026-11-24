<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Ebay\Listing\Product\Variation\OptionsConfigurationLoader;

class Variation
{
    public string $attribute;
    /**
     * @var Option[]
     */
    public array $options;
    /**
     * @var string[]
     */
    public array $availableOptions;

    /**
     * @param Option[] $options
     * @param string[] $availableOptions
     */
    public function __construct(
        string $attribute,
        array $options,
        array $availableOptions
    ) {
        $this->attribute = $attribute;
        $this->options = $options;
        $this->availableOptions = $availableOptions;
    }
}
