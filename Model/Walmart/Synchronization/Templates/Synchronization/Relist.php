<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Walmart\Synchronization\Templates\Synchronization;

use Ess\M2ePro\Model\Walmart\Template\Synchronization as SynchronizationPolicy;

/**
 * Class \Ess\M2ePro\Model\Walmart\Synchronization\Templates\Synchronization\Relist
 */
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
        $this->getActualOperationHistory()->addTimePoint(__METHOD__, 'Immediately when Product was changed');

        /** @var \Ess\M2ePro\Model\Listing\Product[] $changedListingsProducts */
        $changedListingsProducts = $this->getProductChangesManager()->getInstances(
            [\Ess\M2ePro\Model\ProductChange::UPDATE_ATTRIBUTE_CODE]
        );

        $changedListingsProducts = array_merge($changedListingsProducts, $this->getPendingListingProducts());

        $lpForAdvancedRules = [];

        foreach ($changedListingsProducts as $listingProduct) {
            try {
                $configurator = $this->modelFactory->getObject('Walmart_Listing_Product_Action_Configurator');
                $configurator->reset();
                $configurator->allowQty();
                $configurator->allowLagTime();

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
                    $listingProduct,
                    \Ess\M2ePro\Model\Listing\Product::ACTION_RELIST,
                    $configurator
                );

                if ($isExistInRunner) {
                    continue;
                }

                if (!$this->getInspector()->isMeetRelistRequirements($listingProduct)) {
                    continue;
                }

                /** @var \Ess\M2ePro\Model\Walmart\Listing\Product $walmartListingProduct */
                $walmartListingProduct = $listingProduct->getChildObject();
                $walmartTemplate = $walmartListingProduct->getWalmartSynchronizationTemplate();

                if ($walmartTemplate->isRelistAdvancedRulesEnabled()) {
                    $templateId = $walmartTemplate->getId();
                    $storeId    = $listingProduct->getListing()->getStoreId();
                    $magentoProductId = $listingProduct->getProductId();

                    $lpForAdvancedRules[$templateId][$storeId][$magentoProductId][] = $listingProduct;
                } else {
                    $this->getRunner()->addProduct(
                        $listingProduct,
                        \Ess\M2ePro\Model\Listing\Product::ACTION_RELIST,
                        $configurator
                    );
                }
            } catch (\Exception $exception) {
                $this->logError($listingProduct, $exception, false);
                continue;
            }
        }

        $this->processAdvancedConditions($lpForAdvancedRules);

        $this->getActualOperationHistory()->saveTimePoint(__METHOD__);
    }

    //########################################

    private function processAdvancedConditions($lpForAdvancedRules)
    {
        $affectedListingProducts = [];

        try {
            $affectedListingProducts = $this->getInspector()->getMeetAdvancedRequirementsProducts(
                $lpForAdvancedRules,
                SynchronizationPolicy::RELIST_ADVANCED_RULES_PREFIX,
                'relist'
            );
        } catch (\Exception $exception) {
            foreach ($lpForAdvancedRules as $templateId => $productsByTemplate) {
                foreach ($productsByTemplate as $storeId => $productsByStore) {
                    foreach ($productsByStore as $magentoProductId => $productsByMagentoProduct) {
                        foreach ($productsByMagentoProduct as $lProduct) {
                            $this->logError($lProduct, $exception, false);
                        }
                    }
                }
            }
        }

        foreach ($affectedListingProducts as $listingProduct) {
            /** @var \Ess\M2ePro\Model\Listing\Product $listingProduct */

            try {

                /** @var $configurator \Ess\M2ePro\Model\Walmart\Listing\Product\Action\Configurator */
                $configurator = $this->modelFactory->getObject('Walmart_Listing_Product_Action_Configurator');
                $configurator->reset();
                $configurator->allowQty();

                $this->getRunner()->addProduct(
                    $listingProduct,
                    \Ess\M2ePro\Model\Listing\Product::ACTION_RELIST,
                    $configurator
                );
            } catch (\Exception $exception) {
                $this->logError($listingProduct, $exception, false);
                continue;
            }
        }
    }

    //########################################
}
