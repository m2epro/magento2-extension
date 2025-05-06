<?php

namespace Ess\M2ePro\Model\Ebay\Listing\Product\Action\Type\Relist;

class Validator extends \Ess\M2ePro\Model\Ebay\Listing\Product\Action\Type\Validator
{
    public function validate()
    {
        if (!$this->getListingProduct()->isRelistable()) {
            $this->addMessage(
                'The Item either is Listed, or not Listed yet or not available',
                \Ess\M2ePro\Model\Tag\ValidatorIssues::NOT_USER_ERROR
            );

            return false;
        }

        if (!$this->validateIsVariationProductWithoutVariations()) {
            return false;
        }

        if ($this->getEbayListingProduct()->isVariationsReady()) {
            if (!$this->validateVariationsOptions()) {
                return false;
            }

            if (!$this->validateBundleMapping()) {
                return false;
            }
        }

        if (!$this->validateCategory()) {
            return false;
        }

        if (!$this->validatePrice()) {
            return false;
        }

        if (!$this->validateQty()) {
            return false;
        }

        return true;
    }
}
