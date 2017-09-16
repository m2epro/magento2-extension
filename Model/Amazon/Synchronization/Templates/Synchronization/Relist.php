<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Synchronization\Templates\Synchronization;

use Ess\M2ePro\Model\Amazon\Template\Synchronization as SynchronizationPolicy;

class Relist extends \Ess\M2ePro\Model\Amazon\Synchronization\Templates\Synchronization\AbstractModel
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

        $lpForAdvancedRules = [];

        foreach ($changedListingsProducts as $listingProduct) {

            try {

                $configurator = $this->modelFactory->getObject('Amazon\Listing\Product\Action\Configurator');
                $this->prepareConfigurator($listingProduct, $configurator);

                $isExistInRunner = $this->getRunner()->isExistProductWithCoveringConfigurator(
                    $listingProduct, \Ess\M2ePro\Model\Listing\Product::ACTION_RELIST, $configurator
                );

                if ($isExistInRunner) {
                    continue;
                }

                if (!$this->getInspector()->isMeetRelistRequirements($listingProduct)) {
                    continue;
                }

                /** @var \Ess\M2ePro\Model\Amazon\Listing\Product $amazonListingProduct */
                $amazonListingProduct = $listingProduct->getChildObject();
                $amazonTemplate = $amazonListingProduct->getAmazonSynchronizationTemplate();

                if ($amazonTemplate->isRelistAdvancedRulesEnabled()) {

                    $templateId = $amazonTemplate->getId();
                    $storeId    = $listingProduct->getListing()->getStoreId();
                    $magentoProductId = $listingProduct->getProductId();

                    $lpForAdvancedRules[$templateId][$storeId][$magentoProductId][] = $listingProduct;

                } else {

                    $this->getRunner()->addProduct(
                        $listingProduct, \Ess\M2ePro\Model\Listing\Product::ACTION_RELIST, $configurator
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
                $lpForAdvancedRules, SynchronizationPolicy::RELIST_ADVANCED_RULES_PREFIX, 'relist'
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

                /** @var $configurator \Ess\M2ePro\Model\Amazon\Listing\Product\Action\Configurator */
                $configurator = $this->modelFactory->getObject('Amazon\Listing\Product\Action\Configurator');
                $this->prepareConfigurator($listingProduct, $configurator);

                $this->getRunner()->addProduct(
                    $listingProduct, \Ess\M2ePro\Model\Listing\Product::ACTION_RELIST, $configurator
                );

            } catch (\Exception $exception) {

                $this->logError($listingProduct, $exception, false);
                continue;
            }
        }
    }

    //########################################

    private function prepareConfigurator(\Ess\M2ePro\Model\Listing\Product $listingProduct,
                                         \Ess\M2ePro\Model\Amazon\Listing\Product\Action\Configurator $configurator)
    {
        /** @var \Ess\M2ePro\Model\Amazon\Listing\Product $amazonListingProduct */
        $amazonListingProduct = $listingProduct->getChildObject();

        if (!$amazonListingProduct->getAmazonSynchronizationTemplate()->isRelistSendData()) {
            $configurator->reset();
            $configurator->allowQty();
        }
    }

    //########################################
}