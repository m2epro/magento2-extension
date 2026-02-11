<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Walmart\Template\Repricer;

class Builder extends \Ess\M2ePro\Model\ActiveRecord\AbstractBuilder
{
    protected function prepareData(): array
    {
        return $this->rawData;
    }

    public function getDefaultData(): array
    {
        return [
            'id' => '',
            'title' => '',
            'account_id' => '',

            'min_price_mode' => \Ess\M2ePro\Model\Walmart\Template\Repricer::REPRICER_MIN_MAX_PRICE_MODE_NONE,
            'min_price_attribute' => '',
            'max_price_mode' => \Ess\M2ePro\Model\Walmart\Template\Repricer::REPRICER_MIN_MAX_PRICE_MODE_NONE,
            'max_price_attribute' => '',
            'strategy_name' => '',
        ];
    }
}
