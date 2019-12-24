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

        if (!$this->validateCategory()) {
            return false;
        }

        $params = $this->getParams();
        if (empty($params['remove']) && !$this->validateMissedOnChannelBlocked()) {
            return false;
        }

        if ($this->getVariationManager()->isRelationParentType() && !$this->validateParentListingProductFlags()) {
            return false;
        }

        if (!$this->validatePhysicalUnitAndSimple()) {
            return false;
        }

        if (!$this->getListingProduct()->isListed() || !$this->getListingProduct()->isStoppable()) {
            if (empty($params['remove'])) {
                // M2ePro\TRANSLATIONS
                // Item is not Listed or not available
                $this->addMessage('Item is not active or not available');
            } else {
                $variationManager = $this->getWalmartListingProduct()->getVariationManager();

                if ($variationManager->isRelationChildType()) {

                    /** @var \Ess\M2ePro\Model\Walmart\Listing\Product $parentWalmartListingProduct */
                    $parentWalmartListingProduct = $variationManager
                        ->getTypeModel()
                        ->getWalmartParentListingProduct();

                    $this->parentWalmartListingProductForProcess = $parentWalmartListingProduct;

                    /** @var Child $childTypeModel */
                    $childTypeModel = $variationManager->getTypeModel();

                    if ($childTypeModel->isVariationProductMatched()) {
                        $parentWalmartListingProduct->getVariationManager()->getTypeModel()->addRemovedProductOptions(
                            $variationManager->getTypeModel()->getProductOptions()
                        );
                    }
                }

                if (!$this->getListingProduct()->isNotListed()) {
                    $this->getListingProduct()->setData(
                        'status',
                        \Ess\M2ePro\Model\Listing\Product::STATUS_NOT_LISTED
                    )->save();
                }

                $this->getListingProduct()->delete();
                $this->getListingProduct()->isDeleted(true);
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
