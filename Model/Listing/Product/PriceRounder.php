<?php

namespace Ess\M2ePro\Model\Listing\Product;

class PriceRounder
{
    public const PRICE_ROUNDING_NONE = 0;
    public const PRICE_ROUNDING_NEAREST_HUNDREDTH = 1;
    public const PRICE_ROUNDING_NEAREST_TENTH = 2;
    public const PRICE_ROUNDING_NEAREST_INT = 3;
    public const PRICE_ROUNDING_NEAREST_HUNDRED = 4;

    private $mode = self::PRICE_ROUNDING_NONE;

    public function setMode(int $mode): void
    {
        $validModes = [
            self::PRICE_ROUNDING_NONE,
            self::PRICE_ROUNDING_NEAREST_HUNDREDTH,
            self::PRICE_ROUNDING_NEAREST_TENTH,
            self::PRICE_ROUNDING_NEAREST_INT,
            self::PRICE_ROUNDING_NEAREST_HUNDRED,
        ];

        if (in_array($mode, $validModes)) {
            $this->mode = $mode;
        }
    }

    public function getMode(): int
    {
        return $this->mode;
    }

    public function round(float $value): float
    {
        if ($value <= 0) {
            return $value;
        }

        switch ($this->mode) {
            case self::PRICE_ROUNDING_NONE:
                $value = round($value, 2);
                break;
            case self::PRICE_ROUNDING_NEAREST_HUNDREDTH:
                $value = round($value, 1) - 0.01;
                break;
            case self::PRICE_ROUNDING_NEAREST_TENTH:
                $value = round($value) - 0.01;
                break;
            case self::PRICE_ROUNDING_NEAREST_INT:
                $value = round($value);
                break;
            case self::PRICE_ROUNDING_NEAREST_HUNDRED:
                $value = round($value, -1);
                break;
        }

        return $value;
    }
}
