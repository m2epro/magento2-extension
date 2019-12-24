<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Listing\Product\Action\Type\Relist;

/**
 * Class \Ess\M2ePro\Model\Ebay\Listing\Product\Action\Type\Relist\Validator
 */
class Validator extends \Ess\M2ePro\Model\Ebay\Listing\Product\Action\Type\Validator
{
    //########################################

    public function validate()
    {
        if (!$this->getListingProduct()->isRelistable()) {
            // M2ePro\TRANSLATIONS
            // The Item either is Listed, or not Listed yet or not available
            $this->addMessage('The Item either is Listed, or not Listed yet or not available');

            return false;
        }

        if (!$this->validateIsVariationProductWithoutVariations()) {
            return false;
        }

        if ($this->getEbayListingProduct()->isVariationsReady()) {
            if (!$this->validateVariationsOptions()) {
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

    //########################################
}
