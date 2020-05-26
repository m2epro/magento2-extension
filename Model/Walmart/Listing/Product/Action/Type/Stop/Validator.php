<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Walmart\Listing\Product\Action\Type\Stop;

use Ess\M2ePro\Model\Walmart\Listing\Product\Variation\Manager\Type\Relation\Child;

/**
 * Class \Ess\M2ePro\Model\Walmart\Listing\Product\Action\Type\Stop\Validator
 */
class Validator extends \Ess\M2ePro\Model\Walmart\Listing\Product\Action\Type\Validator
{
    //########################################

    /**
     * @return bool
     * @throws \Ess\M2ePro\Model\Exception
     */
    public function validate()
    {
        if (!$this->validateMagentoProductType()) {
            return false;
        }

        if (!$this->validateSku()) {
            return false;
        }

        if (!$this->validateCategory()) {
            return false;
        }

        $params = $this->getParams();
        if (empty($params['remove']) && !$this->validateMissedOnChannelBlocked()) {
            return false;
        }

        if ($this->getVariationManager()->isRelationParentType() && !$this->validateParentListingProduct()) {
            return false;
        }

        if (!$this->validatePhysicalUnitAndSimple()) {
            return false;
        }

        if (!$this->getListingProduct()->isListed() || !$this->getListingProduct()->isStoppable()) {
            if (empty($params['remove'])) {
                $this->addMessage('Item is not active or not available');
            } else {
                $removeHandler = $this->modelFactory->getObject('Walmart_Listing_Product_RemoveHandler');
                $removeHandler->setListingProduct($this->getListingProduct());
                $removeHandler->process();
            }

            return false;
        }

        return true;
    }

    //########################################
}
