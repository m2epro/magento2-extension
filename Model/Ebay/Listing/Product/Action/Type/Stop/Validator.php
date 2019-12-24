<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Listing\Product\Action\Type\Stop;

/**
 * Class \Ess\M2ePro\Model\Ebay\Listing\Product\Action\Type\Stop\Validator
 */
class Validator extends \Ess\M2ePro\Model\Ebay\Listing\Product\Action\Type\Validator
{
    //########################################

    public function validate()
    {
        if (!$this->getListingProduct()->isStoppable()) {
            $params = $this->getParams();

            if (empty($params['remove'])) {
                // M2ePro\TRANSLATIONS
                // Item is not Listed or not available
                $this->addMessage('Item is not Listed or not available');
            } else {
                $this->getListingProduct()->setData('status', \Ess\M2ePro\Model\Listing\Product::STATUS_STOPPED);
                $this->getListingProduct()->save();

                $this->getListingProduct()->delete();
            }

            return false;
        }

        return true;
    }

    //########################################
}
