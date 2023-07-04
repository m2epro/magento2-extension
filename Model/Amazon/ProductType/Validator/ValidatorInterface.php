<?php

namespace Ess\M2ePro\Model\Amazon\ProductType\Validator;

interface ValidatorInterface
{
    /**
     * @param mixed $value
     *
     * @return bool
     */
    public function validate($value): bool;

    public function getErrors(): array;

    public function isRequiredSpecific(): bool;
}
