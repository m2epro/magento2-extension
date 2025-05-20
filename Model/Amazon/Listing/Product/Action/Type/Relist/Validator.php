<?php

namespace Ess\M2ePro\Model\Amazon\Listing\Product\Action\Type\Relist;

class Validator extends \Ess\M2ePro\Model\Amazon\Listing\Product\Action\Type\Validator
{
    /**
     * @return bool
     */
    public function validate()
    {
        if (!$this->validateBlocked()) {
            return false;
        }

        if ($this->getVariationManager()->isRelationParentType() && !$this->validateParentListingProduct()) {
            return false;
        }

        if (!$this->validatePhysicalUnitAndSimple()) {
            return false;
        }

        if ($this->getAmazonListingProduct()->isAfnChannel()) {
            $this->addMessage(
                'AFN Items cannot be Relisted through M2E Pro as their Quantity is managed by Amazon.
                You may run Revise to update the Product detail, but the Quantity update will be ignored.',
                self::ERROR_FBA_ITEM_RELIST
            );

            return false;
        }

        if ($this->getVariationManager()->isPhysicalUnit() && !$this->validatePhysicalUnitMatching()) {
            return false;
        }

        if (!$this->validateSku()) {
            return false;
        }

        if (!$this->getListingProduct()->isInactive() || !$this->getListingProduct()->isRelistable()) {
            $this->addMessage(
                'The Item either is Listed, or not Listed yet or not available',
                \Ess\M2ePro\Model\Tag\ValidatorIssues::NOT_USER_ERROR
            );

            return false;
        }

        if (!$this->validateQty()) {
            return false;
        }

        if (!$this->validateRegularPrice() || !$this->validateBusinessPrice()) {
            return false;
        }

        return true;
    }
}
