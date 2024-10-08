<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Ebay\Listing\Wizard\Validator;

use Ess\M2ePro\Model\Ebay\Listing\Wizard\Product;
use Ess\M2ePro\Model\ResourceModel\Ebay\Listing\Wizard\Product as WizardProductResource;

class ValidatorComposite implements ValidatorInterface
{
    public const STATUS_INVALID = 0;
    public const STATUS_VALID = 1;

    /**
     * @var ValidatorInterface[]
     */
    private array $validators;

    private WizardProductResource $wizardProductResource;

    /**
     * @param ValidatorInterface[] $validators
     */
    public function __construct(
        array $validators,
        WizardProductResource $wizardProductResource
    ) {
        $this->validators = $validators;
        $this->wizardProductResource = $wizardProductResource;
    }

    /**
     * @param Product[] $products
     *
     * @return void
     */
    public function validate(array $products): void
    {
        $this->initProductsValidationState($products);

        foreach ($this->validators as $validator) {
            $validator->validate($products);
        }

        $this->saveProducts($products);
    }

    private function initProductsValidationState(array $products): void
    {
        /**
         * Reset all previous validation iteration data or init status for products to be validated for the first time
         */
        foreach ($products as $product) {
            $product->setValidationStatus(self::STATUS_VALID);
            $product->setValidationErrors([]);
        }
    }

    private function saveProducts(array $products): void
    {
        foreach ($products as $product) {
            $this->wizardProductResource->save($product);
        }
    }
}
