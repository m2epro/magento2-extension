<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Walmart\Connector\Product\Stop;

class Responser extends \Ess\M2ePro\Model\Walmart\Connector\Product\Responser
{
    /** @var \Ess\M2ePro\Model\Listing\Product $parentForProcessing */
    protected $parentForProcessing = null;

    // ########################################

    protected function getSuccessfulMessage()
    {
        // M2ePro\TRANSLATIONS
        // Item was successfully Stopped
        return 'Item was successfully Stopped';
    }

    // ########################################

    public function eventAfterExecuting()
    {
        if (!empty($this->params['params']['remove'])) {
            $variationManager = $this->getWalmartListingProduct()->getVariationManager();
            $parentWalmartListingProduct = null;

            if ($variationManager->isRelationChildType()) {

                /** @var \Ess\M2ePro\Model\Walmart\Listing\Product $parentWalmartListingProduct */
                $parentWalmartListingProduct = $variationManager
                    ->getTypeModel()
                    ->getWalmartParentListingProduct();

                /**
                 *@var \Ess\M2ePro\Model\Walmart\Listing\Product\Variation\Manager\Type\Relation\Child $childTypeModel
                 */
                $childTypeModel = $variationManager->getTypeModel();

                if ($childTypeModel->isVariationProductMatched()) {
                    $parentWalmartListingProduct->getVariationManager()->getTypeModel()->addRemovedProductOptions(
                        $variationManager->getTypeModel()->getProductOptions()
                    );
                }
            }

            if (!$this->listingProduct->isNotListed()) {
                $this->listingProduct->setData('status', \Ess\M2ePro\Model\Listing\Product::STATUS_NOT_LISTED)->save();
            }

            $this->listingProduct->delete();
            $this->listingProduct->isDeleted(true);

            if ($parentWalmartListingProduct !== null) {
                /**
                 *@var \Ess\M2ePro\Model\Walmart\Listing\Product\Variation\Manager\Type\Relation\Parent $parentTypeModel
                 */
                $parentTypeModel = $parentWalmartListingProduct->getVariationManager()->getTypeModel();
                $parentTypeModel->getProcessor()->process();
            }
        }

        if ($this->listingProduct->isDeleted()) {
            return;
        }

        parent::eventAfterExecuting();
    }

    protected function processParentProcessor()
    {
        if (empty($this->params['params']['remove'])) {
            parent::processParentProcessor();
            return;
        }

        if (is_null($this->parentForProcessing)) {
            return;
        }

        /** @var \Ess\M2ePro\Model\Walmart\Listing\Product $walmartListingProduct */
        $walmartListingProduct = $this->parentForProcessing->getChildObject();
        $walmartListingProduct->getVariationManager()->getTypeModel()->getProcessor()->process();
    }

    // ########################################

    /**
     * @return \Ess\M2ePro\Model\Walmart\Listing\Product
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    private function getWalmartListingProduct()
    {
        return $this->listingProduct->getChildObject();
    }

    // ########################################
}