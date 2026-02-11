<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Walmart\Template\Repricer;

class Diff extends \Ess\M2ePro\Model\ActiveRecord\Diff
{
    public function isDifferent(): bool
    {
        return $this->isRepricerDifferent();
    }

    public function isRepricerDifferent(): bool
    {
        $keys = [
            \Ess\M2ePro\Model\ResourceModel\Walmart\Template\Repricer::COLUMN_STRATEGY_NAME,
            \Ess\M2ePro\Model\ResourceModel\Walmart\Template\Repricer::COLUMN_MIN_PRICE_MODE,
            \Ess\M2ePro\Model\ResourceModel\Walmart\Template\Repricer::COLUMN_MIN_PRICE_ATTRIBUTE,
            \Ess\M2ePro\Model\ResourceModel\Walmart\Template\Repricer::COLUMN_MAX_PRICE_MODE,
            \Ess\M2ePro\Model\ResourceModel\Walmart\Template\Repricer::COLUMN_MAX_PRICE_ATTRIBUTE,
        ];

        return $this->isSettingsDifferent($keys);
    }
}
