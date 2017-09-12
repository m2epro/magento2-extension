<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Listing\Product\Action\Type\Revise;

use Ess\M2ePro\Model\Exception\Logic;

class Validator extends \Ess\M2ePro\Model\Ebay\Listing\Product\Action\Type\Validator
{
    //########################################

    public function validate()
    {
        $params = $this->getParams();
        if (!isset($params['out_of_stock_control_current_state']) ||
            !isset($params['out_of_stock_control_result'])) {

            throw new Logic('Miss required parameters.');
        }

        if (!$this->getListingProduct()->isRevisable()) {

            // M2ePro\TRANSLATIONS
            // Item is not Listed or not available
            $this->addMessage('Item is not Listed or not available');

            return false;
        }

        if (!$this->validateIsVariationProductWithoutVariations()) {
            return false;
        }

        if (!$this->validateCategory()) {
            return false;
        }

        if (!$params['out_of_stock_control_result'] &&
            !$this->validateQty()
        ) {
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