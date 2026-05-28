<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Amazon\Account\MultiLocationInventoryMapping;

class Item
{
    public string $magentoSourceCode;
    public string $amazonLocationCode;
    public string $amazonLocationTitle;

    public function __construct(
        string $magentoSourceCode,
        string $amazonLocationCode,
        string $amazonLocationTitle
    ) {
        $this->magentoSourceCode = $magentoSourceCode;
        $this->amazonLocationCode = $amazonLocationCode;
        $this->amazonLocationTitle = $amazonLocationTitle;
    }
}
