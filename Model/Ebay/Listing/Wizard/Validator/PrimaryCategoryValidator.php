<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Ebay\Listing\Wizard\Validator;

use Ess\M2ePro\Model\Tag\BlockingErrors;

class PrimaryCategoryValidator implements ValidatorInterface
{
    public function validate(array $products): void
    {
        foreach ($products as $product) {
            if ($product->getTemplateCategoryId() == null && $product->getStoreCategoryId() == null) {
                $product->setValidationStatus(ValidatorComposite::STATUS_INVALID);
                $product->addErrorMessage(
                    [BlockingErrors::PRIMARY_CATEGORY_ERROR_TAG_CODE => (string)__('Primary category is not set')]
                );
            }
        }
    }
}
