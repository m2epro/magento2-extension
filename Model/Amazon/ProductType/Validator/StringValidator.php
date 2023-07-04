<?php

namespace Ess\M2ePro\Model\Amazon\ProductType\Validator;

class StringValidator implements ValidatorInterface
{
    /** @var string */
    private $fieldTitle = '';
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
                'The value of "%s" is invalid.',
                $this->fieldTitle
            );

            return false;
        }

        if (empty($value)) {
            $this->errors[] = sprintf(
                'The value of "%s" is missing.',
                $this->fieldTitle
            );

            return false;
        }

        return true;
    }

    /**
     * @return array
     */
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
     * @param int $minLength
     */
    public function setMinLength(int $minLength): void
    {
        $this->minLength = $minLength;
    }

    /**
     * @param int $maxLength
     */
    public function setMaxLength(int $maxLength): void
    {
        $this->maxLength = $maxLength;
    }

    /**
     * @param mixed $value
     *
     * @return string|null
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
