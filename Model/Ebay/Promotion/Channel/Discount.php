<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Ebay\Promotion\Channel;

class Discount
{
    private string $discountId;
    private string $title;

    public function __construct(
        string $discountId,
        string $title
    ) {
        $this->discountId = $discountId;
        $this->title = $title;
    }

    public function getDiscountId(): string
    {
        return $this->discountId;
    }

    public function getTitle(): string
    {
        return $this->title;
    }
}
