<?php

namespace Ess\M2ePro\Model\Amazon\ProductType\Validator;

class SelectValidator implements ValidatorInterface
{
    /** @var string */
    private $fieldTitle = '';
    /** @var string */
    private $fieldGroup = '';
    /** @var bool */
    private $isRequired = false;
    /** @var string[]  */
    private $allowedOptions = [];
    /** @var array */
    private $errors = [];

    /**
     * @param mixed $value
     */
    public function validate($value): bool
    {
        $this->errors = [];

        $value = $this->tryConvertToString($value);
        if (empty($value)) {
            $this->errors[] = sprintf(
                '[%s] The value of "%s" is missing.',
                $this->fieldGroup,
                $this->fieldTitle
            );

            return false;
        }

        if (!array_key_exists($value, $this->allowedOptions)) {
            $message = sprintf(
                '[%s] The value of "%s" is invalid.',
                $this->fieldGroup,
                $this->fieldTitle
            );

            $this->errors[] = $message;

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

    public function setAllowedOptions(array $allowedOptions): void
    {
        $this->allowedOptions = $allowedOptions;
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
