<?php

namespace Ess\M2ePro\Model\Amazon\ProductType\Validator;

class BooleanValidator implements ValidatorInterface
{
    /** @var string */
    private $fieldTitle = '';
    /** @var bool */
    private $isRequired = false;
    /** @var array */
    private $errors = [];

    /**
     * @param mixed $value
     */
    public function validate($value): bool
    {
        $this->errors = [];

        if ($value === null || $value === '') {
            $this->errors[] = sprintf(
                'The value of "%s" is missing.',
                $this->fieldTitle
            );

            return false;
        }

        $value = $this->tryConvertToBooleanString($value);
        if ($value === null) {
            $this->errors[] = sprintf(
                'The value of "%s" is invalid.',
                $this->fieldTitle
            );

            return false;
        }

        return true;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function isRequiredSpecific(): bool
    {
        return $this->isRequired;
    }

    /**
     * @param string $fieldTitle
     */
    public function setFieldTitle(string $fieldTitle): void
    {
        $this->fieldTitle = $fieldTitle;
    }

    /**
     * @param bool $isRequired
     */
    public function setIsRequired(bool $isRequired): void
    {
        $this->isRequired = $isRequired;
    }

    /**
     * @param $value
     *
     * @return string|null
     */
    private function tryConvertToBooleanString($value): ?string
    {
        $value = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

        if ($value === null) {
            return null;
        }

        return $value ? 'true' : 'false';
    }
}
