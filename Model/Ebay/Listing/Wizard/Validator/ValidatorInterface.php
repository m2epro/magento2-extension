<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Ebay\Listing\Wizard\Validator;

interface ValidatorInterface
{
    public function validate(array $products): void;
}
