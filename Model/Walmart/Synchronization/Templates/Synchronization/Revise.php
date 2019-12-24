<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Walmart\Synchronization\Templates\Synchronization;

use Ess\M2ePro\Model\Listing\Product;
use Ess\M2ePro\Model\ResourceModel\Walmart\Template as WalmartTemplate;
use Ess\M2ePro\Model\Walmart\Listing\Product\Action\Configurator;

/**
 * Class \Ess\M2ePro\Model\Walmart\Synchronization\Templates\Synchronization\Revise
 */
class Revise extends \Ess\M2ePro\Model\Walmart\Synchronization\Templates\Synchronization\AbstractModel
{
    //########################################

    protected function getNick()
    {
        return '/synchronization/revise/';
    }

    protected function getTitle()
    {
        return 'Revise';
    }

    // ---------------------------------------

    protected function getPercentsStart()
    {
        return 65;
    }

    protected function getPercentsEnd()
    {
        return 100;
    }

    //########################################

    protected function performActions()
    {
        $this->executeQtyChanged();
        $this->executeLagTimeChanged();

        $this->executePriceChanged();
        $this->executePromotionsPriceChanged();

        $this->executeDetailsChanged();

        $this->executeNeedSynchronize();
        $this->executeTotal();
    }

    //########################################

    private function executeQtyChanged()
    {
        $this->getActualOperationHistory()->addTimePoint(__METHOD__, 'Update Quantity');

        /** @var \Ess\M2ePro\Model\Listing\Product[] $changedListingsProducts */
        $changedListingsProducts = $this->getProductChangesManager()->getInstances(
            [\Ess\M2ePro\Model\ProductChange::UPDATE_ATTRIBUTE_CODE]
        );

        $changedListingsProducts = array_merge($changedListingsProducts, $this->getPendingListingProducts());

        foreach ($changedListingsProducts as $listingProduct) {
            try {
                $isExistInRunner = $this->getRunner()->isExistProductWithAction(
                    $listingProduct,
                    \Ess\M2ePro\Model\Listing\Product::ACTION_STOP
                );

                if ($isExistInRunner) {
                    continue;
                }

                /** @var $configurator \Ess\M2ePro\Model\Walmart\Listing\Product\Action\Configurator */
                $configurator = $this->modelFactory->getObject('Walmart_Listing_Product_Action_Configurator');
                $configurator->reset();
                $configurator->allowQty();

                $isExistInRunner = $this->getRunner()->isExistProductWithCoveringConfigurator(
                    $listingProduct,
                    \Ess\M2ePro\Model\Listing\Product::ACTION_REVISE,
                    $configurator
                );

                if ($isExistInRunner) {
                    continue;
                }

                if (!$this->getInspector()->isMeetReviseQtyRequirements($listingProduct)) {
                    continue;
                }

                $this->getRunner()->addProduct(
                    $listingProduct,
                    \Ess\M2ePro\Model\Listing\Product::ACTION_REVISE,
                    $configurator
                );
            } catch (\Exception $exception) {
                $this->logError($listingProduct, $exception, false);
                continue;
            }
        }

        $this->getActualOperationHistory()->saveTimePoint(__METHOD__);
    }

    private function executeLagTimeChanged()
    {
        $this->getActualOperationHistory()->addTimePoint(__METHOD__, 'Update Lag Time');

        /** @var \Ess\M2ePro\Model\Listing\Product[] $changedListingsProducts */
        $changedListingsProducts = $this->getProductChangesManager()->getInstances(
            [\Ess\M2ePro\Model\ProductChange::UPDATE_ATTRIBUTE_CODE]
        );

        $changedListingsProducts = array_merge($changedListingsProducts, $this->getPendingListingProducts());

        foreach ($changedListingsProducts as $listingProduct) {
            try {
                $isExistInRunner = $this->getRunner()->isExistProductWithAction(
                    $listingProduct,
                    \Ess\M2ePro\Model\Listing\Product::ACTION_STOP
                );

                if ($isExistInRunner) {
                    continue;
                }

                /** @var $configurator \Ess\M2ePro\Model\Walmart\Listing\Product\Action\Configurator */
                $configurator = $this->modelFactory->getObject('Walmart_Listing_Product_Action_Configurator');
                $configurator->reset();
                $configurator->allowLagTime();

                $isExistInRunner = $this->getRunner()->isExistProductWithCoveringConfigurator(
                    $listingProduct,
                    \Ess\M2ePro\Model\Listing\Product::ACTION_REVISE,
                    $configurator
                );

                if ($isExistInRunner) {
                    continue;
                }

                if (!$this->getInspector()->isMeetReviseLagTimeRequirements($listingProduct)) {
                    continue;
                }

                $this->getRunner()->addProduct(
                    $listingProduct,
                    \Ess\M2ePro\Model\Listing\Product::ACTION_REVISE,
                    $configurator
                );
            } catch (\Exception $exception) {
                $this->logError($listingProduct, $exception, false);
                continue;
            }
        }

        $this->getActualOperationHistory()->saveTimePoint(__METHOD__);
    }

    private function executePriceChanged()
    {
        $this->getActualOperationHistory()->addTimePoint(__METHOD__, 'Update Price');

        /** @var \Ess\M2ePro\Model\Listing\Product[] $changedListingsProducts */
        $changedListingsProducts = $this->getProductChangesManager()->getInstances(
            [\Ess\M2ePro\Model\ProductChange::UPDATE_ATTRIBUTE_CODE]
        );

        $changedListingsProducts = array_merge($changedListingsProducts, $this->getPendingListingProducts());

        foreach ($changedListingsProducts as $listingProduct) {
            try {
                $isExistInRunner = $this->getRunner()->isExistProductWithAction(
                    $listingProduct,
                    \Ess\M2ePro\Model\Listing\Product::ACTION_STOP
                );

                if ($isExistInRunner) {
                    continue;
                }

                /** @var $configurator \Ess\M2ePro\Model\Walmart\Listing\Product\Action\Configurator */
                $configurator = $this->modelFactory->getObject('Walmart_Listing_Product_Action_Configurator');
                $configurator->reset();
                $configurator->allowPrice();

                $isExistInRunner = $this->getRunner()->isExistProductWithCoveringConfigurator(
                    $listingProduct,
                    \Ess\M2ePro\Model\Listing\Product::ACTION_REVISE,
                    $configurator
                );

                if ($isExistInRunner) {
                    continue;
                }

                if (!$this->getInspector()->isMeetRevisePriceRequirements($listingProduct)) {
                    continue;
                }

                $this->getRunner()->addProduct(
                    $listingProduct,
                    \Ess\M2ePro\Model\Listing\Product::ACTION_REVISE,
                    $configurator
                );
            } catch (\Exception $exception) {
                $this->logError($listingProduct, $exception);
                continue;
            }
        }

        $this->getActualOperationHistory()->saveTimePoint(__METHOD__);
    }

    private function executePromotionsPriceChanged()
    {
        $this->getActualOperationHistory()->addTimePoint(__METHOD__, 'Update Price');

        /** @var \Ess\M2ePro\Model\Listing\Product[] $changedListingsProducts */
        $changedListingsProducts = $this->getProductChangesManager()->getInstances(
            [\Ess\M2ePro\Model\ProductChange::UPDATE_ATTRIBUTE_CODE]
        );

        $changedListingsProducts = array_merge($changedListingsProducts, $this->getPendingListingProducts());

        foreach ($changedListingsProducts as $listingProduct) {
            try {

                /** @var $configurator \Ess\M2ePro\Model\Walmart\Listing\Product\Action\Configurator */
                $configurator = $this->modelFactory->getObject('Walmart_Listing_Product_Action_Configurator');
                $configurator->reset();
                $configurator->allowPromotions();

                $isExistInRunner = $this->getRunner()->isExistProductWithCoveringConfigurator(
                    $listingProduct,
                    \Ess\M2ePro\Model\Listing\Product::ACTION_REVISE,
                    $configurator
                );

                if ($isExistInRunner) {
                    continue;
                }

                if (!$this->getInspector()->isMeetRevisePromotionsPriceRequirements($listingProduct)) {
                    continue;
                }

                $this->getRunner()->addProduct(
                    $listingProduct,
                    \Ess\M2ePro\Model\Listing\Product::ACTION_REVISE,
                    $configurator
                );
            } catch (\Exception $exception) {
                $this->logError($listingProduct, $exception, false);
                continue;
            }
        }

        $this->getActualOperationHistory()->saveTimePoint(__METHOD__);
    }

    //########################################

    private function executeDetailsChanged()
    {
        $this->getActualOperationHistory()->addTimePoint(__METHOD__, 'Update details');

        $attributesForProductChange = [];

        //--
        $descriptionTemplateCollection = $this->walmartFactory
                                              ->getObject('Template\Description')
                                              ->getCollection();

        /** @var \Ess\M2ePro\Model\Template\Description[] $descriptionTemplates */
        $descriptionTemplates = $descriptionTemplateCollection->getItems();

        foreach ($descriptionTemplates as $descriptionTemplate) {

            /** @var \Ess\M2ePro\Model\Walmart\Template\Description $walmartDescriptionTemplate */
            $walmartDescriptionTemplate = $descriptionTemplate->getChildObject();

            $attributesForProductChange = array_merge(
                $attributesForProductChange,
                $walmartDescriptionTemplate->getUsedAttributes()
            );
        }
        //--

        //--
        $categoriesTemplatesCollection = $this->activeRecordFactory
                                            ->getObject('Walmart_Template_Category')
                                            ->getCollection();

        /** @var \Ess\M2ePro\Model\Walmart\Template\Category[] $categoryTemplates */
        $categoryTemplates = $categoriesTemplatesCollection->getItems();

        foreach ($categoryTemplates as $template) {
            $attributesForProductChange = array_merge($attributesForProductChange, $template->getUsedAttributes());
        }
        //--

        //--
        $shippingTemplatesCollection = $this->activeRecordFactory
                                            ->getObject('Walmart_Template_SellingFormat')
                                            ->getCollection();

        /** @var \Ess\M2ePro\Model\Walmart\Template\SellingFormat[] $sellingFormatTemplates */
        $sellingFormatTemplates = $shippingTemplatesCollection->getItems();

        foreach ($sellingFormatTemplates as $template) {
            $attributesForProductChange = array_merge($attributesForProductChange, $template->getUsedAttributes());
        }
        //--

        foreach ($this->getChangedListingsProducts($attributesForProductChange) as $listingProduct) {
            try {
                $isExistInRunner = $this->getRunner()->isExistProductWithAction(
                    $listingProduct,
                    \Ess\M2ePro\Model\Listing\Product::ACTION_STOP
                );

                if ($isExistInRunner) {
                    continue;
                }

                /** @var \Ess\M2ePro\Model\Walmart\Listing\Product $walmartListingProduct */
                $walmartListingProduct = $listingProduct->getChildObject();

                $detailsAttributes = $walmartListingProduct->getWalmartDescriptionTemplate()
                                                                              ->getUsedAttributes();

                if ($walmartListingProduct->isExistCategoryTemplate()) {
                    $detailsAttributes = array_merge(
                        $detailsAttributes,
                        $walmartListingProduct->getCategoryTemplate()->getUsedAttributes()
                    );
                }

                $detailsAttributes = array_merge(
                    $detailsAttributes,
                    $walmartListingProduct->getSellingFormatTemplate()->getUsedAttributes()
                );

                if (!in_array($listingProduct->getData('changed_attribute'), $detailsAttributes)) {
                    continue;
                }

                if (!$this->getInspector()->isMeetReviseDetailsRequirements($listingProduct, false)) {
                    $walmartListingProduct->isDetailsDataChanged() &&
                    $walmartListingProduct->setData('is_details_data_changed', false)->save();
                    continue;
                }

                !$walmartListingProduct->isDetailsDataChanged() &&
                $walmartListingProduct->setData('is_details_data_changed', true)->save();
            } catch (\Exception $exception) {
                $this->logError($listingProduct, $exception, false);
                continue;
            }
        }

        $this->getActualOperationHistory()->saveTimePoint(__METHOD__);
    }

    //########################################

    private function executeNeedSynchronize()
    {
        $this->getActualOperationHistory()->addTimePoint(__METHOD__, 'Execute is need synchronize');

        $listingProductCollection = $this->walmartFactory->getObject('Listing\Product')->getCollection();
        $listingProductCollection->addFieldToFilter(
            'status',
            ['in' => [
                \Ess\M2ePro\Model\Listing\Product::STATUS_LISTED,
                \Ess\M2ePro\Model\Listing\Product::STATUS_UNKNOWN,
            ]]
        );
        $listingProductCollection->addFieldToFilter(
            'synch_status',
            \Ess\M2ePro\Model\Listing\Product::SYNCH_STATUS_NEED
        );
        $listingProductCollection->addFieldToFilter('is_variation_parent', 0);

        $tag = 'in_action';
        $modelName = $this->activeRecordFactory->getObject('Listing\Product')->getResourceName();
        $limit = $this->getConfigValue($this->getFullSettingsPath().'need_synch/', 'items_limit');

        $listingProductCollection->getSelect()->joinLeft(
            [
                'mpc' => $this->getHelper('Module_Database_Structure')->getTableNameWithPrefix('m2epro_processing_lock')
            ],
            "mpc.object_id = main_table.id AND mpc.tag='{$tag}' AND mpc.model_name = '{$modelName}'",
            []
        );
        $listingProductCollection->addFieldToFilter('mpc.id', ['null' => true]);

        $listingProductCollection->getSelect()->limit($limit);

        /** @var $listingProduct \Ess\M2ePro\Model\Listing\Product */
        foreach ($listingProductCollection->getItems() as $listingProduct) {
            try {
                $listingProduct->setData('synch_status', \Ess\M2ePro\Model\Listing\Product::SYNCH_STATUS_SKIP)->save();

                $isExistInRunner = $this->getRunner()->isExistProductWithAction(
                    $listingProduct,
                    \Ess\M2ePro\Model\Listing\Product::ACTION_STOP
                );

                if ($isExistInRunner) {
                    continue;
                }

                if (!$this->getInspector()->isMeetReviseSynchReasonsRequirements($listingProduct, false)) {
                    continue;
                }

                /** @var $configurator \Ess\M2ePro\Model\Walmart\Listing\Product\Action\Configurator */
                $configurator = $this->modelFactory->getObject('Walmart_Listing_Product_Action_Configurator');
                $configurator->reset();

                $walmartListingProduct = $listingProduct->getChildObject();
                $reasons               = $listingProduct->getSynchReasons();

                $detailsReasons = [
                    WalmartTemplate\Category::SYNCH_REASON,
                    WalmartTemplate\Description::SYNCH_REASON,
                    WalmartTemplate\SellingFormat::SYNCH_REASON_DETAILS
                ];

                if (!empty(array_intersect($detailsReasons, $reasons))) {
                    if ($this->getInspector()->isMeetReviseDetailsRequirements($listingProduct, false)) {
                        if (!$walmartListingProduct->isDetailsDataChanged()) {
                            $walmartListingProduct->setData('is_details_data_changed', true)->save();
                        }
                    } else {
                        if ($walmartListingProduct->isDetailsDataChanged()) {
                            $walmartListingProduct->setData('is_details_data_changed', false)->save();
                        }
                    }
                }

                if (in_array(WalmartTemplate\SellingFormat::SYNCH_REASON_QTY, $reasons) &&
                    $this->getInspector()->isMeetReviseQtyRequirements($listingProduct)
                ) {
                    $configurator->allowQty();
                }

                if (in_array(WalmartTemplate\SellingFormat::SYNCH_REASON_LAG_TIME, $reasons) &&
                    $this->getInspector()->isMeetReviseLagTimeRequirements($listingProduct)
                ) {
                    $configurator->allowLagTime();
                }

                if (in_array(WalmartTemplate\SellingFormat::SYNCH_REASON_PRICE, $reasons) &&
                    $this->getInspector()->isMeetRevisePriceRequirements($listingProduct)
                ) {
                    $configurator->allowPrice();
                }

                if (in_array(WalmartTemplate\SellingFormat::SYNCH_REASON_PROMOTIONS, $reasons) &&
                    $this->getInspector()->isMeetRevisePromotionsPriceRequirements($listingProduct)
                ) {
                    $configurator->allowPromotions();
                }

                $this->checkUpdatePriceOrPromotionsFeedsLock(
                    $listingProduct,
                    $configurator,
                    \Ess\M2ePro\Model\Listing\Log::ACTION_REVISE_PRODUCT_ON_COMPONENT
                );

                if (empty($configurator->getAllowedDataTypes())) {
                    continue;
                }

                $isExistInRunner = $this->getRunner()->isExistProductWithCoveringConfigurator(
                    $listingProduct,
                    \Ess\M2ePro\Model\Listing\Product::ACTION_REVISE,
                    $configurator
                );

                if ($isExistInRunner) {
                    continue;
                }

                $this->getRunner()->addProduct(
                    $listingProduct,
                    \Ess\M2ePro\Model\Listing\Product::ACTION_REVISE,
                    $configurator
                );
            } catch (\Exception $exception) {
                $this->logError($listingProduct, $exception, false);
                continue;
            }
        }

        $this->getActualOperationHistory()->saveTimePoint(__METHOD__);
    }

    private function executeTotal()
    {
        $this->getActualOperationHistory()->addTimePoint(__METHOD__, 'Execute revise all');

        $lastListingProductProcessed = $this->getConfigValue(
            $this->getFullSettingsPath().'total/',
            'last_listing_product_id'
        );

        if ($lastListingProductProcessed === null) {
            return;
        }

        $itemsPerCycle = $this->getConfigValue($this->getFullSettingsPath().'total/', 'items_limit');

        $collection = $this->walmartFactory->getObject('Listing\Product')
            ->getCollection()
            ->addFieldToFilter('id', ['gt' => $lastListingProductProcessed])
            ->addFieldToFilter('status', \Ess\M2ePro\Model\Listing\Product::STATUS_LISTED)
            ->addFieldToFilter('is_variation_parent', 0);

        $collection->getSelect()->limit($itemsPerCycle);
        $collection->getSelect()->order('id ASC');

        /** @var $listingProduct \Ess\M2ePro\Model\Listing\Product */
        foreach ($collection->getItems() as $listingProduct) {
            try {
                $isExistInRunner = $this->getRunner()->isExistProductWithAction(
                    $listingProduct,
                    \Ess\M2ePro\Model\Listing\Product::ACTION_STOP
                );

                if ($isExistInRunner) {
                    continue;
                }

                /** @var $configurator \Ess\M2ePro\Model\Walmart\Listing\Product\Action\Configurator */
                $configurator = $this->modelFactory->getObject('Walmart_Listing_Product_Action_Configurator');

                $isExistInRunner = $this->getRunner()->isExistProductWithCoveringConfigurator(
                    $listingProduct,
                    \Ess\M2ePro\Model\Listing\Product::ACTION_REVISE,
                    $configurator
                );

                if ($isExistInRunner) {
                    continue;
                }

                if (!$this->getInspector()->isMeetReviseGeneralRequirements($listingProduct, false)) {
                    continue;
                }

                $this->getRunner()->addProduct(
                    $listingProduct,
                    \Ess\M2ePro\Model\Listing\Product::ACTION_REVISE,
                    $configurator
                );
            } catch (\Exception $exception) {
                $this->logError($listingProduct, $exception, false);
                continue;
            }
        }

        $lastListingProduct = $collection->getLastItem()->getId();

        if ($collection->getSize() < $itemsPerCycle) {
            $this->setConfigValue(
                $this->getFullSettingsPath().'total/',
                'end_date',
                $this->getHelper('Data')->getCurrentGmtDate()
            );

            $lastListingProduct = null;
        }

        $this->setConfigValue(
            $this->getFullSettingsPath().'total/',
            'last_listing_product_id',
            $lastListingProduct
        );

        $this->getActualOperationHistory()->saveTimePoint(__METHOD__);
    }

    //########################################

    /**
     * @param array $trackingAttributes
     * @return \Ess\M2ePro\Model\Listing\Product[]
     */
    private function getChangedListingsProducts(array $trackingAttributes)
    {
        $filteredChangedListingsProducts = [];

        /** @var \Ess\M2ePro\Model\Listing\Product[] $changedListingsProducts */
        $changedListingsProducts = $this->getProductChangesManager()->getInstancesByListingProduct(
            array_unique($trackingAttributes),
            true
        );

        foreach ($changedListingsProducts as $changedListingProduct) {
            /** @var \Ess\M2ePro\Model\Walmart\Listing\Product $walmartListingProduct */
            $walmartListingProduct = $changedListingProduct->getChildObject();

            if ($walmartListingProduct->getVariationManager()->isRelationParentType()) {
                continue;
            }

            $magentoProduct = $changedListingProduct->getMagentoProduct();

            if ($magentoProduct->isConfigurableType() || $magentoProduct->isGroupedType()) {
                continue;
            }

            $filteredChangedListingsProducts[$changedListingProduct->getId()] = $changedListingProduct;
        }

        /** @var \Ess\M2ePro\Model\Listing\Product[] $changedListingsProducts */
        $changedListingsProducts = $this->getProductChangesManager()->getInstancesByVariationOption(
            array_unique($trackingAttributes),
            true
        );

        foreach ($changedListingsProducts as $changedListingProduct) {
            $magentoProduct = $changedListingProduct->getMagentoProduct();

            if ($magentoProduct->isSimpleTypeWithCustomOptions() ||
                $magentoProduct->isBundleType() ||
                $magentoProduct->isDownloadableTypeWithSeparatedLinks()) {
                continue;
            }

            $filteredChangedListingsProducts[$changedListingProduct->getId()] = $changedListingProduct;
        }

        return $filteredChangedListingsProducts;
    }

    //########################################

    protected function checkUpdatePriceOrPromotionsFeedsLock(
        Product $listingProduct,
        Configurator $configurator,
        $action
    ) {
        if (count($configurator->getAllowedDataTypes()) !== 1) {
            return;
        }

        if (!$configurator->isPriceAllowed() && !$configurator->isPromotionsAllowed()) {
            return;
        }

        if (!$this->isLockedForUpdatePriceOrPromotions($listingProduct)) {
            return;
        }

        if ($configurator->isPriceAllowed()) {
            $message = $this->modelFactory->getObject('Connector_Connection_Response_Message');
            $message->initFromPreparedData(
                'Item Price cannot yet be submitted. Walmart allows updating the Price information no sooner than
                24 hours after the relevant product is listed on their website.',
                \Ess\M2ePro\Model\Connector\Connection\Response\Message::TYPE_WARNING
            );

            $configurator->disallowPrice();
        } else {
            $message = $this->modelFactory->getObject('Connector_Connection_Response_Message');
            $message->initFromPreparedData(
                'Item Promotion Price cannot yet be submitted. Walmart allows updating the Promotion Price
                information no sooner than 24 hours after the relevant product is listed on their website.',
                \Ess\M2ePro\Model\Connector\Connection\Response\Message::TYPE_WARNING
            );

            $configurator->disallowPromotions();
        }

        $logger = $this->modelFactory->getObject('Walmart_Listing_Product_Action_Logger');
        $logger->setAction($action);
        $logger->setActionId($this->activeRecordFactory->getObject('Listing\Log')->getResource()->getNextActionId());
        $logger->setInitiator(\Ess\M2ePro\Helper\Data::INITIATOR_EXTENSION);
        $logger->logListingProductMessage($listingProduct, $message);
    }

    protected function isLockedForUpdatePriceOrPromotions(Product $listingProduct)
    {
        /** @var \Ess\M2ePro\Model\Walmart\Listing\Product $walmartListingProduct */
        $walmartListingProduct = $listingProduct->getChildObject();

        if ($walmartListingProduct->getListDate() === null) {
            return false;
        }

        try {
            $borderDate = new \DateTime($walmartListingProduct->getListDate(), new \DateTimeZone('UTC'));
            $borderDate->modify('+24 hours');
        } catch (\Exception $exception) {
            return false;
        }

        if ($borderDate < new \DateTime('now', new \DateTimeZone('UTC'))) {
            return false;
        }

        return true;
    }

    //########################################
}
