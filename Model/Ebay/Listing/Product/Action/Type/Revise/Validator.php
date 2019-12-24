<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Listing\Product\Action\Type\Revise;

use Ess\M2ePro\Model\Exception\Logic;

/**
 * Class \Ess\M2ePro\Model\Ebay\Listing\Product\Action\Type\Revise\Validator
 */
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

        if (!$params['out_of_stock_control_result'] && !$this->validateQty()) {
            return false;
        }

        return true;
    }

    //########################################
}
