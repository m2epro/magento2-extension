<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Walmart\Synchronization\Templates\Synchronization;

class Relist extends \Ess\M2ePro\Model\Walmart\Synchronization\Templates\Synchronization\AbstractModel
{
    //########################################

    protected function getNick()
    {
        return '/synchronization/relist/';
    }

    protected function getTitle()
    {
        return 'Relist';
    }

    // ---------------------------------------

    protected function getPercentsStart()
    {
        return 15;
    }

    protected function getPercentsEnd()
    {
        return 40;
    }

    //########################################

    protected function performActions()
    {
        $this->immediatelyChangedProducts();
    }

    //########################################

    private function immediatelyChangedProducts()
    {
        $this->getActualOperationHistory()->addTimePoint(__METHOD__,'Immediately when Product was changed');

        /** @var \Ess\M2ePro\Model\Listing\Product[] $changedListingsProducts */
        $changedListingsProducts = $this->getProductChangesManager()->getInstances(
            array(\Ess\M2ePro\Model\ProductChange::UPDATE_ATTRIBUTE_CODE)
        );

        $changedListingsProducts = array_merge($changedListingsProducts, $this->getPendingListingProducts());

        foreach ($changedListingsProducts as $listingProduct) {

            try {

                $configurator = $this->modelFactory->getObject('Walmart\Listing\Product\Action\Configurator');
                $configurator->reset();
                $configurator->allowQty();

                /** @var \Ess\M2ePro\Model\Walmart\Listing\Product $walmartListingProduct */
                $walmartListingProduct = $listingProduct->getChildObject();
                $walmartSynchronizationTemplate = $walmartListingProduct->getWalmartSynchronizationTemplate();

                if ($walmartSynchronizationTemplate->isReviseUpdatePrice() ||
                    ($listingProduct->isBlocked() && $walmartListingProduct->isOnlinePriceInvalid())
                ) {
                    $configurator->allowPrice();
                }

                if ($walmartSynchronizationTemplate->isReviseUpdatePromotions()) {
                    $configurator->allowPromotions();
                }

                $isExistInRunner = $this->getRunner()->isExistProductWithCoveringConfigurator(
                    $listingProduct, \Ess\M2ePro\Model\Listing\Product::ACTION_RELIST, $configurator
                );

                if ($isExistInRunner) {
                    continue;
                }

                if (!$this->getInspector()->isMeetRelistRequirements($listingProduct)) {
                    continue;
                }

                $this->getRunner()->addProduct(
                    $listingProduct, \Ess\M2ePro\Model\Listing\Product::ACTION_RELIST, $configurator
                );

            } catch (\Exception $exception) {

                $this->logError($listingProduct, $exception, false);
                continue;
            }
        }

        $this->getActualOperationHistory()->saveTimePoint(__METHOD__);
    }

    //########################################
}