<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Listing\Product\Action\Type\Relist;

/**
 * Class \Ess\M2ePro\Model\Amazon\Listing\Product\Action\Type\Relist\Validator
 */
class Validator extends \Ess\M2ePro\Model\Amazon\Listing\Product\Action\Type\Validator
{
    //########################################

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
                You may run Revise to update the Product detail, but the Quantity update will be ignored.'
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
                'The Item either is Listed, or not Listed yet or not available'
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

    //########################################
}
