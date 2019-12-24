<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Walmart\Synchronization\Templates\Synchronization;

use Ess\M2ePro\Model\Walmart\Template\Synchronization as SynchronizationPolicy;

/**
 * Class \Ess\M2ePro\Model\Walmart\Synchronization\Templates\Synchronization\Stop
 */
class Stop extends \Ess\M2ePro\Model\Walmart\Synchronization\Templates\Synchronization\AbstractModel
{
    //########################################

    protected function getNick()
    {
        return '/synchronization/stop/';
    }

    protected function getTitle()
    {
        return 'Stop';
    }

    // ---------------------------------------

    protected function getPercentsStart()
    {
        return 40;
    }

    protected function getPercentsEnd()
    {
        return 65;
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
                /** @var $configurator \Ess\M2ePro\Model\Walmart\Listing\Product\Action\Configurator */
                $configurator = $this->modelFactory->getObject('Walmart_Listing_Product_Action_Configurator');

                $isExistInRunner = $this->getRunner()->isExistProductWithCoveringConfigurator(
                    $listingProduct,
                    \Ess\M2ePro\Model\Listing\Product::ACTION_STOP,
                    $configurator
                );

                if ($isExistInRunner) {
                    continue;
                }

                if (!$this->getInspector()->isMeetStopGeneralRequirements($listingProduct)) {
                    continue;
                }

                if ($this->getInspector()->isMeetStopRequirements($listingProduct)) {
                    $this->getRunner()->addProduct(
                        $listingProduct,
                        \Ess\M2ePro\Model\Listing\Product::ACTION_STOP,
                        $configurator
                    );
                    continue;
                }

                /** @var \Ess\M2ePro\Model\Walmart\Listing\Product $walmartListingProduct */
                $walmartListingProduct = $listingProduct->getChildObject();
                $walmartTemplate = $walmartListingProduct->getWalmartSynchronizationTemplate();

                if ($walmartTemplate->isStopAdvancedRulesEnabled()) {
                    $templateId = $walmartTemplate->getId();
                    $storeId    = $listingProduct->getListing()->getStoreId();
                    $magentoProductId = $listingProduct->getProductId();

                    $lpForAdvancedRules[$templateId][$storeId][$magentoProductId][] = $listingProduct;
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
                SynchronizationPolicy::STOP_ADVANCED_RULES_PREFIX,
                'stop'
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

                $this->getRunner()->addProduct(
                    $listingProduct,
                    \Ess\M2ePro\Model\Listing\Product::ACTION_STOP,
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
