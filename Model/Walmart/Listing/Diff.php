<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Walmart\Listing;

class Diff extends \Ess\M2ePro\Model\ActiveRecord\Diff
{
    public function isConditionDifferent(): bool
    {
        return $this->isSettingsDifferent([
            \Ess\M2ePro\Model\ResourceModel\Walmart\Listing::COLUMN_CONDITION_MODE,
            \Ess\M2ePro\Model\ResourceModel\Walmart\Listing::COLUMN_CONDITION_VALUE,
            \Ess\M2ePro\Model\ResourceModel\Walmart\Listing::COLUMN_CONDITION_CUSTOM_ATTRIBUTE,
        ]);
    }
}
