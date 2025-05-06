<?php

namespace Ess\M2ePro\Model\Ebay\Listing\Product\Action\Type\Stop;

class Validator extends \Ess\M2ePro\Model\Ebay\Listing\Product\Action\Type\Validator
{
    public function validate()
    {
        if (!$this->getListingProduct()->isStoppable()) {
            $params = $this->getParams();

            if (empty($params['remove'])) {
                $this->addMessage(
                    'Item is not Listed or not available',
                    \Ess\M2ePro\Model\Tag\ValidatorIssues::NOT_USER_ERROR
                );
            } else {
                $removeHandler = $this->modelFactory->getObject('Listing_Product_RemoveHandler');
                $removeHandler->setListingProduct($this->getListingProduct());
                $removeHandler->process();
            }

            return false;
        }

        return true;
    }
}
