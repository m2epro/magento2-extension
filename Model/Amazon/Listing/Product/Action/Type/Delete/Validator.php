<?php

namespace Ess\M2ePro\Model\Amazon\Listing\Product\Action\Type\Delete;

class Validator extends \Ess\M2ePro\Model\Amazon\Listing\Product\Action\Type\Validator
{
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

        if ($this->getListingProduct()->isNotListed()) {
            if (empty($params['remove'])) {
                $this->addMessage(
                    'Item is not Listed or not available',
                    \Ess\M2ePro\Model\Tag\ValidatorIssues::NOT_USER_ERROR
                );
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
}
