<?php

namespace Ess\M2ePro\Model\Amazon\ProductType\Validator;

class StringValidator implements ValidatorInterface
{
    /** @var string */
    private $fieldTitle = '';
    /** @var string */
    private $fieldGroup = '';
    /** @var bool */
    private $isRequired = false;
    /** @var int */
    private $minLength = 0;
    /** @var int */
    private $maxLength = 1000;
    /** @var array */
    private $errors = [];

    /**
     * @param mixed $value
     */
    public function validate($value): bool
    {
        $value = $this->tryConvertToString($value);
        if ($value === null) {
            $this->errors[] = sprintf(
                '[%s] The value of "%s" is invalid.',
                $this->fieldGroup,
                $this->fieldTitle
            );

            return false;
        }

        if (empty($value)) {
            $this->errors[] = sprintf(
                '[%s] The value of "%s" is missing.',
                $this->fieldGroup,
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

    public function setFieldTitle(string $fieldTitle): void
    {
        $this->fieldTitle = $fieldTitle;
    }

    public function setFieldGroup(string $fieldGroup): void
    {
        $this->fieldGroup = $fieldGroup;
    }

    public function setIsRequired(bool $isRequired): void
    {
        $this->isRequired = $isRequired;
    }

    public function setMinLength(int $minLength): void
    {
        $this->minLength = $minLength;
    }

    public function setMaxLength(int $maxLength): void
    {
        $this->maxLength = $maxLength;
    }

    /**
     * @param mixed $value
     */
    private function tryConvertToString($value): ?string
    {
        if (
            is_string($value)
            || is_numeric($value)
            || $value === null
        ) {
            $value = (string)$value;

            return trim($value);
        }

        return null;
    }
}
