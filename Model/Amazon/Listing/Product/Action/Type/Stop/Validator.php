<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Listing\Product\Action\Type\Stop;

/**
 * Class \Ess\M2ePro\Model\Amazon\Listing\Product\Action\Type\Stop\Validator
 */
class Validator extends \Ess\M2ePro\Model\Amazon\Listing\Product\Action\Type\Validator
{
    //########################################

    /**
     * @return bool
     * @throws \Ess\M2ePro\Model\Exception
     */
    public function validate()
    {
        $params = $this->getParams();

        if (empty($params['remove']) && !$this->validateBlocked()) {
            return false;
        }

        if ($this->getVariationManager()->isRelationParentType() && !$this->validateParentListingProduct()) {
            return false;
        }

        if (!$this->validatePhysicalUnitAndSimple()) {
            return false;
        }

        if ($this->getAmazonListingProduct()->isAfnChannel()) {
            if (!empty($params['remove'])) {
                $this->addMessage('Stop Action for FBA Items is impossible as their Quantity is unknown.');
                $this->getListingProduct()->delete();
                $this->getListingProduct()->isDeleted(true);
            } else {
                $this->addMessage('Stop Action for FBA Items is impossible as their Quantity is unknown. You can run
                 Revise Action for such Items, but the Quantity value will be ignored.');
            }

            return false;
        }

        if (!$this->getListingProduct()->isListed() || !$this->getListingProduct()->isStoppable()) {
            if (empty($params['remove'])) {
                $this->addMessage('Item is not active or not available');
            } else {
                /** @var \Ess\M2ePro\Model\Amazon\Listing\Product\RemoveHandler $removeHandler */
                $removeHandler = $this->modelFactory->getObject('Amazon_Listing_Product_RemoveHandler');
                $removeHandler->setListingProduct($this->getListingProduct());
                $removeHandler->process();
            }

            return false;
        }

        if (!$this->validateSku()) {
            return false;
        }

        return true;
    }

    //########################################
}
