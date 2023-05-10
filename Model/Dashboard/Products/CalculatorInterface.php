<?php

namespace Ess\M2ePro\Model\Dashboard\Products;

interface CalculatorInterface
{
    public function getCountOfActiveProducts(): int;

    public function getCountOfInactiveProducts(): int;
}
