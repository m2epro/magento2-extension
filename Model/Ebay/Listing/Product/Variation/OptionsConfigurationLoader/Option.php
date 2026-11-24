<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Ebay\Listing\Product\Variation\OptionsConfigurationLoader;

class Option
{
    public string $option;
    public string $channelOption;
    public bool $isReplaced;

    public function __construct(
        string $option,
        string $channelOption,
        bool $isReplaced
    ) {
        $this->option = $option;
        $this->channelOption = $channelOption;
        $this->isReplaced = $isReplaced;
    }
}
