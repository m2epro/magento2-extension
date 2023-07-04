<?php

namespace Ess\M2ePro\Model\Amazon\ProductType\Validator;

class ValidatorBuilder
{
    private const STRING_VALIDATOR_TYPE = 'string';
    private const BOOLEAN_VALIDATOR_TYPE = 'boolean';
    private const NUMBER_VALIDATOR_TYPE = 'number';
    private const INTEGER_VALIDATOR_TYPE = 'integer';

    /** @var array */
    private $validatorData;

    /**
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function __construct(array $validatorData)
    {
        $this->validateData($validatorData);
        $this->validatorData = $validatorData;
    }

    /**
     * @return ValidatorInterface
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function build(): ValidatorInterface
    {
        $validatorType = $this->validatorData['type'];
        $allowedOptions = $this->validatorData['options'] ?? [];

        if ($validatorType === self::STRING_VALIDATOR_TYPE && empty($allowedOptions)) {
                return $this->buildStringValidator();
        }

        if ($validatorType === self::STRING_VALIDATOR_TYPE && !empty($allowedOptions)) {
            return $this->buildSelectValidator();
        }

        if ($validatorType === self::BOOLEAN_VALIDATOR_TYPE && !empty($allowedOptions)) {
            return $this->buildBooleanValidator();
        }

        if ($validatorType === self::NUMBER_VALIDATOR_TYPE) {
            return $this->buildNumberValidator();
        }

        if ($validatorType === self::INTEGER_VALIDATOR_TYPE) {
            return $this->buildIntegerValidator();
        }

        $message = sprintf('Undefined validator type "%s"', $validatorType);
        throw new \Ess\M2ePro\Model\Exception\Logic($message);
    }

    private function buildStringValidator(): StringValidator
    {
        $validator = new StringValidator();
        $validator->setFieldTitle($this->validatorData['title']);

        $validationRules = $this->validatorData['validation_rules'] ?? [];
        if (array_key_exists('is_required', $validationRules)) {
            $validator->setIsRequired((bool)$validationRules['is_required']);
        }
        if (array_key_exists('min_length', $validationRules)) {
            $validator->setMinLength((int)$validationRules['min_length']);
        }
        if (array_key_exists('max_length', $validationRules)) {
            $validator->setMaxLength((int)$validationRules['max_length']);
        }

        return $validator;
    }

    private function buildSelectValidator(): SelectValidator
    {
        $validator = new SelectValidator();
        $validator->setFieldTitle($this->validatorData['title']);
        $validator->setAllowedOptions($this->validatorData['options']);

        $validationRules = $this->validatorData['validation_rules'] ?? [];
        if (array_key_exists('is_required', $validationRules)) {
            $validator->setIsRequired((bool)$validationRules['is_required']);
        }

        return $validator;
    }

    private function buildBooleanValidator(): BooleanValidator
    {
        $validator = new BooleanValidator();
        $validator->setFieldTitle($this->validatorData['title']);

        $validationRules = $this->validatorData['validation_rules'] ?? [];
        if (array_key_exists('is_required', $validationRules)) {
            $validator->setIsRequired((bool)$validationRules['is_required']);
        }

        return $validator;
    }

    private function buildNumberValidator(): NumberValidator
    {
        $validator = new NumberValidator();
        $validator->setFieldTitle($this->validatorData['title']);

        $validationRules = $this->validatorData['validation_rules'] ?? [];
        if (array_key_exists('is_required', $validationRules)) {
            $validator->setIsRequired((bool)$validationRules['is_required']);
        }
        if (array_key_exists('minimum', $validationRules)) {
            $validator->setMinimum((int)$validationRules['minimum']);
        }
        if (array_key_exists('maximum', $validationRules)) {
            $validator->setMaximum((int)$validationRules['maximum']);
        }

        return $validator;
    }

    private function buildIntegerValidator(): IntegerValidator
    {
        $validator = new IntegerValidator();
        $validator->setFieldTitle($this->validatorData['title']);

        $validationRules = $this->validatorData['validation_rules'] ?? [];
        if (array_key_exists('is_required', $validationRules)) {
            $validator->setIsRequired((bool)$validationRules['is_required']);
        }
        if (array_key_exists('minimum', $validationRules)) {
            $validator->setMinimum((int)$validationRules['minimum']);
        }
        if (array_key_exists('maximum', $validationRules)) {
            $validator->setMaximum((int)$validationRules['maximum']);
        }

        return $validator;
    }

    /**
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    private function validateData(array $validatorData): void
    {
        if (empty($validatorData)) {
            $this->throwException('Validator data is empty');
        }

        if (!array_key_exists('type', $validatorData)) {
            $this->throwException('Validator type is not set');
        }

        if (!array_key_exists('title', $validatorData)) {
            $this->throwException('Empty validator title');
        }
    }

    /**
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    private function throwException(string $message): void
    {
        throw new \Ess\M2ePro\Model\Exception\Logic($message);
    }
}
