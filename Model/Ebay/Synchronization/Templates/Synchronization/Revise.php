<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Synchronization\Templates\Synchronization;

final class Revise extends AbstractModel
{
    //########################################

    /**
     * @return string
     */
    protected function getNick()
    {
        return '/synchronization/revise/';
    }

    /**
     * @return string
     */
    protected function getTitle()
    {
        return 'Revise';
    }

    // ---------------------------------------

    /**
     * @return int
     */
    protected function getPercentsStart()
    {
        return 35;
    }

    /**
     * @return int
     */
    protected function getPercentsEnd()
    {
        return 55;
    }

    //########################################

    protected function performActions()
    {
        $this->executeQtyChanged();
        $this->executePriceChanged();

        $this->executeTitleChanged();
        $this->executeSubTitleChanged();
        $this->executeDescriptionChanged();
        $this->executeImagesChanged();

        if ($this->getHelper('Component\Ebay\PickupStore')->isFeatureEnabled()) {
            $this->executePickupStoreQtyChanged();
        }

        $this->executeNeedSynchronize();
        $this->executeTotal();
    }

    //########################################

    private function executeQtyChanged()
    {
        $this->getActualOperationHistory()->addTimePoint(__METHOD__,'Update Quantity');

        /** @var \Ess\M2ePro\Model\Listing\Product[] $changedListingsProducts */
        $changedListingsProducts = $this->getProductChangesManager()->getInstances(
            array(\Ess\M2ePro\Model\ProductChange::UPDATE_ATTRIBUTE_CODE)
        );

        foreach ($changedListingsProducts as $listingProduct) {

            try {
                /** @var $configurator \Ess\M2ePro\Model\Ebay\Listing\Product\Action\Configurator */
                $configurator = $this->modelFactory->getObject('Ebay\Listing\Product\Action\Configurator');
                $configurator->setPartialMode();
                $configurator->allowQty()->allowVariations();

                $isExistInRunner = $this->getRunner()->isExistProduct(
                    $listingProduct, \Ess\M2ePro\Model\Listing\Product::ACTION_REVISE, $configurator
                );

                if ($isExistInRunner) {
                    continue;
                }

                if (!$this->getInspector()->isMeetReviseQtyRequirements($listingProduct)) {
                    continue;
                }

                $this->getRunner()->addProduct(
                    $listingProduct, \Ess\M2ePro\Model\Listing\Product::ACTION_REVISE, $configurator
                );
            } catch (\Exception $exception) {

                $this->logError($listingProduct, $exception);
                continue;
            }
        }

        $this->getActualOperationHistory()->saveTimePoint(__METHOD__);
    }

    private function executePriceChanged()
    {
        $this->getActualOperationHistory()->addTimePoint(__METHOD__,'Update Price');

        /** @var \Ess\M2ePro\Model\Listing\Product[] $changedListingsProducts */
        $changedListingsProducts = $this->getProductChangesManager()->getInstances(
            array(\Ess\M2ePro\Model\ProductChange::UPDATE_ATTRIBUTE_CODE)
        );

        foreach ($changedListingsProducts as $listingProduct) {

            try {
                /** @var $configurator \Ess\M2ePro\Model\Ebay\Listing\Product\Action\Configurator */
                $configurator = $this->modelFactory->getObject('Ebay\Listing\Product\Action\Configurator');
                $configurator->setPartialMode();
                $configurator->allowPrice()->allowVariations();

                $isExistInRunner = $this->getRunner()->isExistProduct(
                    $listingProduct, \Ess\M2ePro\Model\Listing\Product::ACTION_REVISE, $configurator
                );

                if ($isExistInRunner) {
                    continue;
                }

                if (!$this->getInspector()->isMeetRevisePriceRequirements($listingProduct)) {
                    continue;
                }

                $this->getRunner()->addProduct(
                    $listingProduct, \Ess\M2ePro\Model\Listing\Product::ACTION_REVISE, $configurator
                );
            } catch (\Exception $exception) {

                $this->logError($listingProduct, $exception);
                continue;
            }
        }

        $this->getActualOperationHistory()->saveTimePoint(__METHOD__);
    }

    //########################################

    private function executeTitleChanged()
    {
        $this->getActualOperationHistory()->addTimePoint(__METHOD__,'Update Title');

        $attributesForProductChange = array();

        /** @var \Ess\M2ePro\Model\Ebay\Template\Description $template */
        $ebayTemplateDescriptionItems = $this->activeRecordFactory->getObject('Ebay\Template\Description')
                                                                  ->getCollection()
                                                                  ->getItems();
        foreach ($ebayTemplateDescriptionItems as $template) {
            $attributesForProductChange = array_merge($attributesForProductChange,$template->getTitleAttributes());
        }

        /** @var \Ess\M2ePro\Model\Listing\Product[] $changedListingsProducts */
        $changedListingsProducts = $this->getProductChangesManager()->getInstancesByListingProduct(
            array_unique($attributesForProductChange), true
        );

        foreach ($changedListingsProducts as $listingProduct) {

            try {
                /** @var \Ess\M2ePro\Model\Ebay\Listing\Product $ebayListingProduct */
                $ebayListingProduct = $listingProduct->getChildObject();

                $titleAttributes = $ebayListingProduct->getEbayDescriptionTemplate()->getTitleAttributes();

                if (!in_array($listingProduct->getData('changed_attribute'), $titleAttributes)) {
                    continue;
                }

                /** @var $configurator \Ess\M2ePro\Model\Ebay\Listing\Product\Action\Configurator */
                $configurator = $this->modelFactory->getObject('Ebay\Listing\Product\Action\Configurator');
                $configurator->setPartialMode();
                $configurator->allowTitle();

                $isExistInRunner = $this->getRunner()->isExistProduct(
                    $listingProduct, \Ess\M2ePro\Model\Listing\Product::ACTION_REVISE, $configurator
                );

                if ($isExistInRunner) {
                    continue;
                }

                if (!$this->getInspector()->isMeetReviseTitleRequirements($listingProduct)) {
                    continue;
                }

                $this->getRunner()->addProduct(
                    $listingProduct, \Ess\M2ePro\Model\Listing\Product::ACTION_REVISE, $configurator
                );
            } catch (\Exception $exception) {

                $this->logError($listingProduct, $exception);
                continue;
            }
        }
    }

    private function executeSubTitleChanged()
    {
        $this->getActualOperationHistory()->addTimePoint(__METHOD__,'Update Subtitle');

        $attributesForProductChange = array();

        /** @var \Ess\M2ePro\Model\Ebay\Template\Description $template */
        $ebayTemplateDescriptionItems = $this->activeRecordFactory->getObject('Ebay\Template\Description')
                                                                  ->getCollection()
                                                                  ->getItems();
        foreach ($ebayTemplateDescriptionItems as $template) {
            $attributesForProductChange = array_merge($attributesForProductChange, $template->getSubTitleAttributes());
        }

        /** @var \Ess\M2ePro\Model\Listing\Product[] $changedListingsProducts */
        $changedListingsProducts = $this->getProductChangesManager()->getInstancesByListingProduct(
            array_unique($attributesForProductChange), true
        );

        foreach ($changedListingsProducts as $listingProduct) {

            try {
                /** @var \Ess\M2ePro\Model\Ebay\Listing\Product $ebayListingProduct */
                $ebayListingProduct = $listingProduct->getChildObject();

                $subTitleAttributes = $ebayListingProduct->getEbayDescriptionTemplate()->getSubTitleAttributes();

                if (!in_array($listingProduct->getData('changed_attribute'), $subTitleAttributes)) {
                    continue;
                }

                /** @var $configurator \Ess\M2ePro\Model\Ebay\Listing\Product\Action\Configurator */
                $configurator = $this->modelFactory->getObject('Ebay\Listing\Product\Action\Configurator');
                $configurator->setPartialMode();
                $configurator->allowSubtitle();

                $isExistInRunner = $this->getRunner()->isExistProduct(
                    $listingProduct, \Ess\M2ePro\Model\Listing\Product::ACTION_REVISE, $configurator
                );

                if ($isExistInRunner) {
                    continue;
                }

                if (!$this->getInspector()->isMeetReviseSubTitleRequirements($listingProduct)) {
                    continue;
                }

                $this->getRunner()->addProduct(
                    $listingProduct, \Ess\M2ePro\Model\Listing\Product::ACTION_REVISE, $configurator
                );
            } catch (\Exception $exception) {

                $this->logError($listingProduct, $exception);
                continue;
            }
        }

        $this->getActualOperationHistory()->saveTimePoint(__METHOD__);
    }

    private function executeDescriptionChanged()
    {
        $this->getActualOperationHistory()->addTimePoint(__METHOD__,'Update Description');

        $attributesForProductChange = array();

        /** @var \Ess\M2ePro\Model\Ebay\Template\Description $template */
        $ebayTemplateDescriptionItems = $this->activeRecordFactory->getObject('Ebay\Template\Description')
                                                                 ->getCollection()
                                                                 ->getItems();
        foreach ($ebayTemplateDescriptionItems as $template) {
            $attributesForProductChange = array_merge(
                $attributesForProductChange,
                $template->getDescriptionAttributes()
            );
        }

        /** @var \Ess\M2ePro\Model\Listing\Product[] $changedListingsProducts */
        $changedListingsProducts = $this->getProductChangesManager()->getInstancesByListingProduct(
            array_unique($attributesForProductChange), true
        );

        foreach ($changedListingsProducts as $listingProduct) {

            try {
                /** @var \Ess\M2ePro\Model\Ebay\Listing\Product $ebayListingProduct */
                $ebayListingProduct = $listingProduct->getChildObject();

                $descriptionAttributes = $ebayListingProduct->getEbayDescriptionTemplate()->getDescriptionAttributes();

                if (!in_array($listingProduct->getData('changed_attribute'), $descriptionAttributes)) {
                    continue;
                }

                /** @var $configurator \Ess\M2ePro\Model\Ebay\Listing\Product\Action\Configurator */
                $configurator = $this->modelFactory->getObject('Ebay\Listing\Product\Action\Configurator');
                $configurator->setPartialMode();
                $configurator->allowDescription();

                $isExistInRunner = $this->getRunner()->isExistProduct(
                    $listingProduct, \Ess\M2ePro\Model\Listing\Product::ACTION_REVISE, $configurator
                );

                if ($isExistInRunner) {
                    continue;
                }

                if (!$this->getInspector()->isMeetReviseDescriptionRequirements($listingProduct)) {
                    continue;
                }

                $this->getRunner()->addProduct(
                    $listingProduct, \Ess\M2ePro\Model\Listing\Product::ACTION_REVISE, $configurator
                );
            } catch (\Exception $exception) {

                $this->logError($listingProduct, $exception);
                continue;
            }
        }

        $this->getActualOperationHistory()->saveTimePoint(__METHOD__);
    }

    private function executeImagesChanged()
    {
        $this->getActualOperationHistory()->addTimePoint(__METHOD__,'Update images');

        $attributesForProductChange = array();

        /** @var \Ess\M2ePro\Model\Ebay\Template\Description $template */
        $ebayTemplateDescriptionItems = $this->activeRecordFactory->getObject('Ebay\Template\Description')
                                                                  ->getCollection()
                                                                  ->getItems();
        foreach ($ebayTemplateDescriptionItems as $template) {
            $attributesForProductChange = array_merge(
                $attributesForProductChange,
                $template->getImageMainAttributes(),
                $template->getGalleryImagesAttributes(),
                $template->getVariationImagesAttributes()
            );
        }

        $attributesForProductChange = array_unique($attributesForProductChange);

        /** @var \Ess\M2ePro\Model\Listing\Product[] $changedListingsProducts */
        $changedListingsProducts = $this->getProductChangesManager()->getInstancesByListingProduct(
            $attributesForProductChange, true
        );

        foreach ($changedListingsProducts as $listingProduct) {

            try {
                /** @var \Ess\M2ePro\Model\Ebay\Listing\Product $ebayListingProduct */
                $ebayListingProduct = $listingProduct->getChildObject();

                $imagesAttributes = array_merge(
                    $ebayListingProduct->getEbayDescriptionTemplate()->getImageMainAttributes(),
                    $ebayListingProduct->getEbayDescriptionTemplate()->getGalleryImagesAttributes()
                );

                if (!in_array($listingProduct->getData('changed_attribute'), $imagesAttributes)) {
                    continue;
                }

                /** @var $configurator \Ess\M2ePro\Model\Ebay\Listing\Product\Action\Configurator */
                $configurator = $this->modelFactory->getObject('Ebay\Listing\Product\Action\Configurator');
                $configurator->setPartialMode();
                $configurator->allowImages();

                $isExistInRunner = $this->getRunner()->isExistProduct(
                    $listingProduct, \Ess\M2ePro\Model\Listing\Product::ACTION_REVISE, $configurator
                );

                if ($isExistInRunner) {
                    continue;
                }

                if (!$this->getInspector()->isMeetReviseImagesRequirements($listingProduct)) {
                    continue;
                }

                $this->getRunner()->addProduct(
                    $listingProduct, \Ess\M2ePro\Model\Listing\Product::ACTION_REVISE, $configurator
                );
            } catch (\Exception $exception) {

                $this->logError($listingProduct, $exception);
                continue;
            }
        }

        /** @var \Ess\M2ePro\Model\Listing\Product[] $changedListingsProductsByVariationOption */
        $changedListingsProductsByVariationOption = $this->getProductChangesManager()->getInstancesByVariationOption(
            $attributesForProductChange, true
        );

        foreach ($changedListingsProductsByVariationOption as $listingProduct) {

            try {
                /** @var \Ess\M2ePro\Model\Ebay\Listing\Product $ebayListingProduct */
                $ebayListingProduct = $listingProduct->getChildObject();

                $imagesAttributes = $ebayListingProduct->getEbayDescriptionTemplate()->getVariationImagesAttributes();

                if (!in_array($listingProduct->getData('changed_attribute'), $imagesAttributes)) {
                    continue;
                }

                /** @var $configurator \Ess\M2ePro\Model\Ebay\Listing\Product\Action\Configurator */
                $configurator = $this->modelFactory->getObject('Ebay\Listing\Product\Action\Configurator');
                $configurator->setPartialMode();
                $configurator->allowVariations();

                $isExistInRunner = $this->getRunner()->isExistProduct(
                    $listingProduct, \Ess\M2ePro\Model\Listing\Product::ACTION_REVISE, $configurator
                );

                if ($isExistInRunner) {
                    continue;
                }

                if (!$this->getInspector()->isMeetReviseImagesRequirements($listingProduct)) {
                    continue;
                }

                $this->getRunner()->addProduct(
                    $listingProduct, \Ess\M2ePro\Model\Listing\Product::ACTION_REVISE, $configurator
                );
            } catch (\Exception $exception) {

                $this->logError($listingProduct, $exception);
                continue;
            }
        }

        $this->getActualOperationHistory()->saveTimePoint(__METHOD__);
    }

    //########################################

    private function executePickupStoreQtyChanged()
    {
        $this->getActualOperationHistory()->addTimePoint(__METHOD__,'Update Pickup Store Quantity');

        /** @var \Ess\M2ePro\Model\Listing\Product[] $changedListingsProducts */
        $changedListingsProducts = $this->getProductChangesManager()->getInstances(
            array(\Ess\M2ePro\Model\ProductChange::UPDATE_ATTRIBUTE_CODE)
        );

        foreach ($changedListingsProducts as $listingProduct) {

            try {
                /** @var \Ess\M2ePro\Model\Ebay\Listing\Product $ebayListingProduct */
                $ebayListingProduct = $listingProduct->getChildObject();
                if (!$ebayListingProduct->getEbayAccount()->isPickupStoreEnabled()) {
                    continue;
                }

                $ebaySynchronizationTemplate = $ebayListingProduct->getEbaySynchronizationTemplate();
                if (!$ebaySynchronizationTemplate->isReviseWhenChangeQty()) {
                    continue;
                }

                /** @var \Ess\M2ePro\Model\Ebay\Listing\Product\PickupStore\State\Updater $pickupStoreStateUpdater */
                $pickupStoreStateUpdater = $this->modelFactory->getObject(
                    'Ebay\Listing\Product\PickupStore\State\Updater'
                );
                $pickupStoreStateUpdater->setListingProduct($listingProduct);

                if ($ebaySynchronizationTemplate->isReviseUpdateQtyMaxAppliedValueModeOn()) {
                    $pickupStoreStateUpdater->setMaxAppliedQtyValue(
                        $ebaySynchronizationTemplate->getReviseUpdateQtyMaxAppliedValue()
                    );
                }

                $pickupStoreStateUpdater->process();
            } catch (\Exception $exception) {

                $this->logError($listingProduct, $exception);
                continue;
            }
        }

        $this->getActualOperationHistory()->saveTimePoint(__METHOD__);
    }

    //########################################

    private function executeNeedSynchronize()
    {
        $this->getActualOperationHistory()->addTimePoint(__METHOD__,'Execute is need synchronize');

        $listingProductCollection = $this->ebayFactory->getObject('Listing\Product')->getCollection();
        $listingProductCollection->addFieldToFilter('status', \Ess\M2ePro\Model\Listing\Product::STATUS_LISTED);
        $listingProductCollection->addFieldToFilter(
            'synch_status',\Ess\M2ePro\Model\Listing\Product::SYNCH_STATUS_NEED
        );

        $tag = 'in_action';
        $modelName = $this->activeRecordFactory->getObject('Listing\Product')->getResourceName();

        $listingProductCollection->getSelect()->joinLeft(
            array('mpc' => $this->resourceConnection->getTableName('m2epro_processing_lock')),
            "mpc.object_id = main_table.id AND mpc.tag='{$tag}' AND mpc.model_name = '{$modelName}'",
            array()
        );
        $listingProductCollection->addFieldToFilter('mpc.id', array('null' => true));

        $listingProductCollection->getSelect()->limit(100);

        foreach ($listingProductCollection->getItems() as $listingProduct) {

            try {
                /** @var $listingProduct \Ess\M2ePro\Model\Listing\Product */
                $listingProduct->setData('synch_status', \Ess\M2ePro\Model\Listing\Product::SYNCH_STATUS_SKIP)->save();

                /** @var $configurator \Ess\M2ePro\Model\Ebay\Listing\Product\Action\Configurator */
                $configurator = $this->modelFactory->getObject('Ebay\Listing\Product\Action\Configurator');

                $isExistInRunner = $this->getRunner()->isExistProduct(
                    $listingProduct, \Ess\M2ePro\Model\Listing\Product::ACTION_REVISE, $configurator
                );

                if ($isExistInRunner) {
                    continue;
                }

                if (!$this->getInspector()->isMeetReviseSynchReasonsRequirements($listingProduct)) {
                    continue;
                }

                $this->getRunner()->addProduct(
                    $listingProduct, \Ess\M2ePro\Model\Listing\Product::ACTION_REVISE, $configurator
                );
            } catch (\Exception $exception) {

                $this->logError($listingProduct, $exception);
                continue;
            }
        }

        $this->getActualOperationHistory()->saveTimePoint(__METHOD__);
    }

    private function executeTotal()
    {
        $this->getActualOperationHistory()->addTimePoint(__METHOD__,'Execute Revise all');

        $lastListingProductProcessed = $this->getConfigValue(
            $this->getFullSettingsPath().'total/','last_listing_product_id'
        );

        if (is_null($lastListingProductProcessed)) {
            return;
        }

        $itemsPerCycle = 100;

        $collection = $this->ebayFactory->getObject('Listing\Product')
            ->getCollection()
            ->addFieldToFilter('id',array('gt' => $lastListingProductProcessed))
            ->addFieldToFilter('status', \Ess\M2ePro\Model\Listing\Product::STATUS_LISTED);

        $collection->getSelect()->limit($itemsPerCycle);
        $collection->getSelect()->order('id ASC');

        /* @var $listingProduct \Ess\M2ePro\Model\Listing\Product */
        foreach ($collection->getItems() as $listingProduct) {

            try {
                /** @var $configurator \Ess\M2ePro\Model\Ebay\Listing\Product\Action\Configurator */
                $configurator = $this->modelFactory->getObject('Ebay\Listing\Product\Action\Configurator');

                $isExistInRunner = $this->getRunner()->isExistProduct(
                    $listingProduct, \Ess\M2ePro\Model\Listing\Product::ACTION_REVISE, $configurator
                );

                if ($isExistInRunner) {
                    continue;
                }

                if (!$this->getInspector()->isMeetReviseGeneralRequirements($listingProduct)) {
                    continue;
                }

                $this->getRunner()->addProduct(
                    $listingProduct, \Ess\M2ePro\Model\Listing\Product::ACTION_REVISE, $configurator
                );
            } catch (\Exception $exception) {

                $this->logError($listingProduct, $exception);
                continue;
            }
        }

        $lastListingProduct = $collection->getLastItem()->getId();

        if ($collection->count() < $itemsPerCycle) {

            $this->setConfigValue(
                $this->getFullSettingsPath().'total/', 'end_date',
                $this->getHelper('Data')->getCurrentGmtDate()
            );

            $lastListingProduct = NULL;
        }

        $this->setConfigValue(
            $this->getFullSettingsPath().'total/', 'last_listing_product_id',
            $lastListingProduct
        );

        $this->getActualOperationHistory()->saveTimePoint(__METHOD__);
    }

    //########################################
}