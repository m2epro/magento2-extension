<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Amazon\Order\Item\CustomizationDetails;

class TextPrintingCustomization
{
    public string $label;
    public string $value;

    public function __construct(
        string $label,
        string $value
    ) {
        $this->label = $label;
        $this->value = $value;
    }
}
