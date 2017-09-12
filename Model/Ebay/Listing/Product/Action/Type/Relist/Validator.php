<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Listing\Product\Action\Type\Relist;

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

        if (!$this->validateCategory()) {
            return false;
        }

        if (!$this->validateQty()) {
            return false;
        }

        if ($this->getEbayListingProduct()->isVariationsReady()) {

            if (!$this->validateVariationsOptions()) {
                return false;
            }

            if (!$this->validateVariationsFixedPrice()) {
                return false;
            }

            return true;
        }

        if ($this->getEbayListingProduct()->isListingTypeAuction()) {
            if (!$this->validateStartPrice()) {
                return false;
            }

            if (!$this->validateReservePrice()) {
                return false;
            }

            if (!$this->validateBuyItNowPrice()) {
                return false;
            }
        } else {
            if (!$this->validateFixedPrice()) {
                return false;
            }
        }

        return true;
    }

    //########################################
}