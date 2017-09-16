<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Synchronization\Templates\Synchronization;

use Ess\M2ePro\Model\Amazon\Template\Synchronization as SynchronizationPolicy;

class ListActions extends \Ess\M2ePro\Model\Amazon\Synchronization\Templates\Synchronization\AbstractModel
{
    //########################################

    protected function getNick()
    {
        return '/synchronization/list/';
    }

    protected function getTitle()
    {
        return 'List';
    }

    // ---------------------------------------

    protected function getPercentsStart()
    {
        return 0;
    }

    protected function getPercentsEnd()
    {
        return 15;
    }

    //########################################

    protected function performActions()
    {
        $this->immediatelyChangedProducts();
        $this->immediatelyNotCheckedProducts();
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

                $isExistInRunner = $this->getRunner()->isExistProductWithCoveringConfigurator(
                    $listingProduct, \Ess\M2ePro\Model\Listing\Product::ACTION_LIST, $configurator
                );

                if ($isExistInRunner) {
                    continue;
                }

                if (!$this->getInspector()->isMeetListRequirements($listingProduct)) {
                    continue;
                }

                /** @var \Ess\M2ePro\Model\Amazon\Listing\Product $amazonListingProduct */
                $amazonListingProduct = $listingProduct->getChildObject();
                $amazonTemplate = $amazonListingProduct->getAmazonSynchronizationTemplate();

                if ($amazonTemplate->isListAdvancedRulesEnabled()) {

                    $templateId = $amazonTemplate->getId();
                    $storeId    = $listingProduct->getListing()->getStoreId();
                    $magentoProductId = $listingProduct->getProductId();

                    $lpForAdvancedRules[$templateId][$storeId][$magentoProductId][] = $listingProduct;

                } else {

                    $this->getRunner()->addProduct(
                        $listingProduct, \Ess\M2ePro\Model\Listing\Product::ACTION_LIST, $configurator
                    );

                    $this->setListAttemptData($listingProduct);
                }

            } catch (\Exception $exception) {

                $this->logError($listingProduct, $exception, false);
                continue;
            }
        }

        $this->processAdvancedConditions($lpForAdvancedRules);

        $this->getActualOperationHistory()->saveTimePoint(__METHOD__);
    }

    private function immediatelyNotCheckedProducts()
    {
        $this->getActualOperationHistory()->addTimePoint(__METHOD__,'Immediately when Product was not checked');
        $limit = $this->getConfigValue($this->getFullSettingsPath().'immediately_not_checked/', 'items_limit');

        /** @var $collection \Ess\M2ePro\Model\ResourceModel\Listing\Product\Collection */
        $collection = $this->amazonFactory->getObject('Listing\Product')->getCollection();
        $collection->addFieldToFilter('status', \Ess\M2ePro\Model\Listing\Product::STATUS_NOT_LISTED);
        $collection->addFieldToFilter('tried_to_list',0);

        $collection->getSelect()->limit($limit);

        $listingsProducts = $collection->getItems();

        $lpForAdvancedRules = [];

        foreach ($listingsProducts as $listingProduct) {

            /** @var $listingProduct \Ess\M2ePro\Model\Listing\Product */

            try {

                $listingProduct->getMagentoProduct()->enableCache();
                $listingProduct->setData('tried_to_list',1)->save();

                $configurator = $this->modelFactory->getObject('Amazon\Listing\Product\Action\Configurator');

                $isExistInRunner = $this->getRunner()->isExistProductWithCoveringConfigurator(
                    $listingProduct, \Ess\M2ePro\Model\Listing\Product::ACTION_LIST, $configurator
                );

                if ($isExistInRunner) {
                    continue;
                }

                if (!$this->getInspector()->isMeetListRequirements($listingProduct, false)) {
                    continue;
                }

                /** @var \Ess\M2ePro\Model\Amazon\Listing\Product $amazonListingProduct */
                $amazonListingProduct = $listingProduct->getChildObject();
                $amazonTemplate = $amazonListingProduct->getAmazonSynchronizationTemplate();

                if ($amazonTemplate->isListAdvancedRulesEnabled()) {

                    $templateId = $amazonTemplate->getId();
                    $storeId    = $listingProduct->getListing()->getStoreId();
                    $magentoProductId = $listingProduct->getProductId();

                    $lpForAdvancedRules[$templateId][$storeId][$magentoProductId][] = $listingProduct;

                } else {

                    $this->getRunner()->addProduct(
                        $listingProduct, \Ess\M2ePro\Model\Listing\Product::ACTION_LIST, $configurator
                    );

                    $this->setListAttemptData($listingProduct);
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
                $lpForAdvancedRules, SynchronizationPolicy::LIST_ADVANCED_RULES_PREFIX, 'list'
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

                $this->getRunner()->addProduct(
                    $listingProduct, \Ess\M2ePro\Model\Listing\Product::ACTION_LIST, $configurator
                );

                $this->setListAttemptData($listingProduct);

            } catch (\Exception $exception) {

                $this->logError($listingProduct, $exception, false);
                continue;
            }
        }
    }

    //########################################

    private function setListAttemptData(\Ess\M2ePro\Model\Listing\Product $listingProduct)
    {
        $additionalData = $listingProduct->getAdditionalData();
        $additionalData['last_list_attempt_date'] = $this->getHelper('Data')->getCurrentGmtDate();
        $listingProduct->setSettings('additional_data', $additionalData);

        $listingProduct->save();
    }

    //########################################
}