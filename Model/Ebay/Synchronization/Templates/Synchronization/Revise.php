<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Synchronization\Templates\Synchronization;

class Revise extends AbstractModel
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
        return 80;
    }

    /**
     * @return int
     */
    protected function getPercentsEnd()
    {
        return 100;
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
        $this->executeSpecificsChanged();
        $this->executeShippingServicesChanged();

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

                $isExistInRunner = $this->getRunner()->isExistProductWithAction(
                    $listingProduct, \Ess\M2ePro\Model\Listing\Product::ACTION_STOP
                );

                if ($isExistInRunner) {
                    continue;
                }

                /** @var $configurator \Ess\M2ePro\Model\Ebay\Listing\Product\Action\Configurator */
                $configurator = $this->modelFactory->getObject('Ebay\Listing\Product\Action\Configurator');
                $configurator->reset();
                $configurator->allowQty();
                $configurator->allowVariations();

                $isExistInRunner = $this->getRunner()->isExistProductWithCoveringConfigurator(
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

                $this->logError($listingProduct, $exception, false);
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

                $isExistInRunner = $this->getRunner()->isExistProductWithAction(
                    $listingProduct, \Ess\M2ePro\Model\Listing\Product::ACTION_STOP
                );

                if ($isExistInRunner) {
                    continue;
                }

                /** @var $configurator \Ess\M2ePro\Model\Ebay\Listing\Product\Action\Configurator */
                $configurator = $this->modelFactory->getObject('Ebay\Listing\Product\Action\Configurator');
                $configurator->reset();
                $configurator->allowPrice();
                $configurator->allowVariations();

                $isExistInRunner = $this->getRunner()->isExistProductWithCoveringConfigurator(
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

                $this->logError($listingProduct, $exception, false);
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

        $descriptionTemplateCollection = $this->ebayFactory->getObject('Template\Description')->getCollection();

        /** @var \Ess\M2ePro\Model\Template\Description[] $descriptionTemplates */
        $descriptionTemplates = $descriptionTemplateCollection->getItems();

        foreach ($descriptionTemplates as $descriptionTemplate) {
            /** @var \Ess\M2ePro\Model\Ebay\Template\Description $ebayDescriptionTemplate */
            $ebayDescriptionTemplate = $descriptionTemplate->getChildObject();

            $attributesForProductChange = array_merge(
                $attributesForProductChange, $ebayDescriptionTemplate->getTitleAttributes()
            );
        }

        /** @var \Ess\M2ePro\Model\Listing\Product[] $changedListingsProducts */
        $changedListingsProducts = $this->getProductChangesManager()->getInstancesByListingProduct(
            array_unique($attributesForProductChange), true
        );

        foreach ($changedListingsProducts as $listingProduct) {

            try {

                $isExistInRunner = $this->getRunner()->isExistProductWithAction(
                    $listingProduct, \Ess\M2ePro\Model\Listing\Product::ACTION_STOP
                );

                if ($isExistInRunner) {
                    continue;
                }

                /** @var \Ess\M2ePro\Model\Ebay\Listing\Product $ebayListingProduct */
                $ebayListingProduct = $listingProduct->getChildObject();

                $titleAttributes = $ebayListingProduct->getEbayDescriptionTemplate()->getTitleAttributes();

                if (!in_array($listingProduct->getData('changed_attribute'), $titleAttributes)) {
                    continue;
                }

                /** @var $configurator \Ess\M2ePro\Model\Ebay\Listing\Product\Action\Configurator */
                $configurator = $this->modelFactory->getObject('Ebay\Listing\Product\Action\Configurator');
                $configurator->reset();
                $configurator->allowTitle();

                $isExistInRunner = $this->getRunner()->isExistProductWithCoveringConfigurator(
                    $listingProduct, \Ess\M2ePro\Model\Listing\Product::ACTION_REVISE, $configurator
                );

                if ($isExistInRunner) {
                    continue;
                }

                if (!$this->getInspector()->isMeetReviseTitleRequirements($listingProduct, false)) {
                    continue;
                }

                $this->getRunner()->addProduct(
                    $listingProduct, \Ess\M2ePro\Model\Listing\Product::ACTION_REVISE, $configurator
                );
            } catch (\Exception $exception) {

                $this->logError($listingProduct, $exception, false);
                continue;
            }
        }
    }

    private function executeSubTitleChanged()
    {
        $this->getActualOperationHistory()->addTimePoint(__METHOD__,'Update Subtitle');

        $attributesForProductChange = array();

        $descriptionTemplateCollection = $this->ebayFactory->getObject('Template\Description')->getCollection();

        /** @var \Ess\M2ePro\Model\Template\Description[] $descriptionTemplates */
        $descriptionTemplates = $descriptionTemplateCollection->getItems();

        foreach ($descriptionTemplates as $descriptionTemplate) {
            /** @var \Ess\M2ePro\Model\Ebay\Template\Description $ebayDescriptionTemplate */
            $ebayDescriptionTemplate = $descriptionTemplate->getChildObject();

            $attributesForProductChange = array_merge(
                $attributesForProductChange, $ebayDescriptionTemplate->getSubTitleAttributes()
            );
        }

        /** @var \Ess\M2ePro\Model\Listing\Product[] $changedListingsProducts */
        $changedListingsProducts = $this->getProductChangesManager()->getInstancesByListingProduct(
            array_unique($attributesForProductChange), true
        );

        foreach ($changedListingsProducts as $listingProduct) {

            try {

                $isExistInRunner = $this->getRunner()->isExistProductWithAction(
                    $listingProduct, \Ess\M2ePro\Model\Listing\Product::ACTION_STOP
                );

                if ($isExistInRunner) {
                    continue;
                }

                /** @var \Ess\M2ePro\Model\Ebay\Listing\Product $ebayListingProduct */
                $ebayListingProduct = $listingProduct->getChildObject();

                $subTitleAttributes = $ebayListingProduct->getEbayDescriptionTemplate()->getSubTitleAttributes();

                if (!in_array($listingProduct->getData('changed_attribute'), $subTitleAttributes)) {
                    continue;
                }

                /** @var $configurator \Ess\M2ePro\Model\Ebay\Listing\Product\Action\Configurator */
                $configurator = $this->modelFactory->getObject('Ebay\Listing\Product\Action\Configurator');
                $configurator->reset();
                $configurator->allowSubtitle();

                $isExistInRunner = $this->getRunner()->isExistProductWithCoveringConfigurator(
                    $listingProduct, \Ess\M2ePro\Model\Listing\Product::ACTION_REVISE, $configurator
                );

                if ($isExistInRunner) {
                    continue;
                }

                if (!$this->getInspector()->isMeetReviseSubTitleRequirements($listingProduct, false)) {
                    continue;
                }

                $this->getRunner()->addProduct(
                    $listingProduct, \Ess\M2ePro\Model\Listing\Product::ACTION_REVISE, $configurator
                );
            } catch (\Exception $exception) {

                $this->logError($listingProduct, $exception, false);
                continue;
            }
        }

        $this->getActualOperationHistory()->saveTimePoint(__METHOD__);
    }

    private function executeDescriptionChanged()
    {
        $this->getActualOperationHistory()->addTimePoint(__METHOD__,'Update Description');

        $attributesForProductChange = array();

        $descriptionTemplateCollection = $this->ebayFactory->getObject('Template\Description')->getCollection();

        /** @var \Ess\M2ePro\Model\Template\Description[] $descriptionTemplates */
        $descriptionTemplates = $descriptionTemplateCollection->getItems();

        foreach ($descriptionTemplates as $descriptionTemplate) {
            /** @var \Ess\M2ePro\Model\Ebay\Template\Description $ebayDescriptionTemplate */
            $ebayDescriptionTemplate = $descriptionTemplate->getChildObject();

            $attributesForProductChange = array_merge(
                $attributesForProductChange, $ebayDescriptionTemplate->getDescriptionAttributes()
            );
        }

        /** @var \Ess\M2ePro\Model\Listing\Product[] $changedListingsProducts */
        $changedListingsProducts = $this->getProductChangesManager()->getInstancesByListingProduct(
            array_unique($attributesForProductChange), true
        );

        foreach ($changedListingsProducts as $listingProduct) {

            try {

                $isExistInRunner = $this->getRunner()->isExistProductWithAction(
                    $listingProduct, \Ess\M2ePro\Model\Listing\Product::ACTION_STOP
                );

                if ($isExistInRunner) {
                    continue;
                }

                /** @var \Ess\M2ePro\Model\Ebay\Listing\Product $ebayListingProduct */
                $ebayListingProduct = $listingProduct->getChildObject();

                $descriptionAttributes = $ebayListingProduct->getEbayDescriptionTemplate()->getDescriptionAttributes();

                if (!in_array($listingProduct->getData('changed_attribute'), $descriptionAttributes)) {
                    continue;
                }

                /** @var $configurator \Ess\M2ePro\Model\Ebay\Listing\Product\Action\Configurator */
                $configurator = $this->modelFactory->getObject('Ebay\Listing\Product\Action\Configurator');
                $configurator->reset();
                $configurator->allowDescription();

                $isExistInRunner = $this->getRunner()->isExistProductWithCoveringConfigurator(
                    $listingProduct, \Ess\M2ePro\Model\Listing\Product::ACTION_REVISE, $configurator
                );

                if ($isExistInRunner) {
                    continue;
                }

                if (!$this->getInspector()->isMeetReviseDescriptionRequirements($listingProduct, false)) {
                    continue;
                }

                $this->getRunner()->addProduct(
                    $listingProduct, \Ess\M2ePro\Model\Listing\Product::ACTION_REVISE, $configurator
                );
            } catch (\Exception $exception) {

                $this->logError($listingProduct, $exception, false);
                continue;
            }
        }

        $this->getActualOperationHistory()->saveTimePoint(__METHOD__);
    }

    private function executeImagesChanged()
    {
        $this->getActualOperationHistory()->addTimePoint(__METHOD__,'Update images');

        $attributesForProductChange = array();

        $descriptionTemplateCollection = $this->ebayFactory->getObject('Template\Description')->getCollection();

        /** @var \Ess\M2ePro\Model\Template\Description[] $descriptionTemplates */
        $descriptionTemplates = $descriptionTemplateCollection->getItems();

        foreach ($descriptionTemplates as $descriptionTemplate) {
            /** @var \Ess\M2ePro\Model\Ebay\Template\Description $ebayDescriptionTemplate */
            $ebayDescriptionTemplate = $descriptionTemplate->getChildObject();

            $attributesForProductChange = array_merge(
                $attributesForProductChange,
                $ebayDescriptionTemplate->getImageMainAttributes(),
                $ebayDescriptionTemplate->getGalleryImagesAttributes()
            );
        }

        $attributesForProductChange = array_unique($attributesForProductChange);

        /** @var \Ess\M2ePro\Model\Listing\Product[] $changedListingsProducts */
        $changedListingsProducts = $this->getProductChangesManager()->getInstancesByListingProduct(
            $attributesForProductChange, true
        );

        foreach ($changedListingsProducts as $listingProduct) {

            try {

                $isExistInRunner = $this->getRunner()->isExistProductWithAction(
                    $listingProduct, \Ess\M2ePro\Model\Listing\Product::ACTION_STOP
                );

                if ($isExistInRunner) {
                    continue;
                }

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
                $configurator->reset();
                $configurator->allowImages();

                $isExistInRunner = $this->getRunner()->isExistProductWithCoveringConfigurator(
                    $listingProduct, \Ess\M2ePro\Model\Listing\Product::ACTION_REVISE, $configurator
                );

                if ($isExistInRunner) {
                    continue;
                }

                if (!$this->getInspector()->isMeetReviseImagesRequirements($listingProduct, false)) {
                    continue;
                }

                $this->getRunner()->addProduct(
                    $listingProduct, \Ess\M2ePro\Model\Listing\Product::ACTION_REVISE, $configurator
                );
            } catch (\Exception $exception) {

                $this->logError($listingProduct, $exception, false);
                continue;
            }
        }

        foreach ($descriptionTemplates as $descriptionTemplate) {
            /** @var \Ess\M2ePro\Model\Ebay\Template\Description $ebayDescriptionTemplate */
            $ebayDescriptionTemplate = $descriptionTemplate->getChildObject();

            $attributesForProductChange = array_merge(
                $attributesForProductChange,
                $ebayDescriptionTemplate->getVariationImagesAttributes()
            );
        }

        /** @var \Ess\M2ePro\Model\Listing\Product[] $changedListingsProductsByVariationOption */
        $changedListingsProductsByVariationOption = $this->getProductChangesManager()->getInstancesByVariationOption(
            array_unique($attributesForProductChange), true
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
                $configurator->reset();
                $configurator->allowVariations();

                $isExistInRunner = $this->getRunner()->isExistProductWithCoveringConfigurator(
                    $listingProduct, \Ess\M2ePro\Model\Listing\Product::ACTION_REVISE, $configurator
                );

                if ($isExistInRunner) {
                    continue;
                }

                if (!$this->getInspector()->isMeetReviseImagesRequirements($listingProduct, false)) {
                    continue;
                }

                $this->getRunner()->addProduct(
                    $listingProduct, \Ess\M2ePro\Model\Listing\Product::ACTION_REVISE, $configurator
                );
            } catch (\Exception $exception) {

                $this->logError($listingProduct, $exception, false);
                continue;
            }
        }

        $this->getActualOperationHistory()->saveTimePoint(__METHOD__);
    }

    private function executeSpecificsChanged()
    {
        $this->getActualOperationHistory()->addTimePoint(__METHOD__,'Update specifics');

        /** @var \Ess\M2ePro\Model\Listing\Product[] $changedListingsProducts */
        $changedListingsProducts = $this->getProductChangesManager()->getInstances(
            array(\Ess\M2ePro\Model\ProductChange::UPDATE_ATTRIBUTE_CODE)
        );

        $attributesForProductChange = array();

        foreach ($changedListingsProducts as $listingProduct) {
            /** @var \Ess\M2ePro\Model\Ebay\Listing\Product $ebayListingProduct */
            $ebayListingProduct = $listingProduct->getChildObject();

            if ($ebayListingProduct->isSetCategoryTemplate()) {
                $attributesForProductChange = array_merge(
                    $attributesForProductChange,
                    $ebayListingProduct->getCategoryTemplate()->getTrackingAttributes()
                );
            }
        }
        $attributesForProductChange = array_unique($attributesForProductChange);

        /** @var \Ess\M2ePro\Model\Listing\Product[] $changedListingsProducts */
        $changedListingsProducts = $this->getProductChangesManager()->getInstancesByListingProduct(
            $attributesForProductChange, true
        );

        foreach ($changedListingsProducts as $listingProduct) {

            try {

                $isExistInRunner = $this->getRunner()->isExistProductWithAction(
                    $listingProduct, \Ess\M2ePro\Model\Listing\Product::ACTION_STOP
                );

                if ($isExistInRunner) {
                    continue;
                }

                /** @var \Ess\M2ePro\Model\Ebay\Listing\Product $ebayListingProduct */
                $ebayListingProduct = $listingProduct->getChildObject();

                if (!$ebayListingProduct->isSetCategoryTemplate()) {
                    continue;
                }

                $specificsAttributes = $ebayListingProduct->getCategoryTemplate()->getTrackingAttributes();

                if (!in_array($listingProduct->getData('changed_attribute'), $specificsAttributes)) {
                    continue;
                }

                /** @var $configurator \Ess\M2ePro\Model\Ebay\Listing\Product\Action\Configurator */
                $configurator = $this->modelFactory->getObject('Ebay\Listing\Product\Action\Configurator');
                $configurator->reset();
                $configurator->allowSpecifics();

                $isExistInRunner = $this->getRunner()->isExistProductWithCoveringConfigurator(
                    $listingProduct, \Ess\M2ePro\Model\Listing\Product::ACTION_REVISE, $configurator
                );

                if ($isExistInRunner) {
                    continue;
                }

                if (!$this->getInspector()->isMeetReviseSpecificsRequirements($listingProduct, false)) {
                    continue;
                }

                $this->getRunner()->addProduct(
                    $listingProduct, \Ess\M2ePro\Model\Listing\Product::ACTION_REVISE, $configurator
                );
            } catch (\Exception $exception) {

                $this->logError($listingProduct, $exception, false);
                continue;
            }
        }

        $this->getActualOperationHistory()->saveTimePoint(__METHOD__);
    }

    private function executeShippingServicesChanged()
    {
        $this->getActualOperationHistory()->addTimePoint(__METHOD__,'Update shipping services');

        /** @var \Ess\M2ePro\Model\Listing\Product[] $changedListingsProducts */
        $changedListingsProducts = $this->getProductChangesManager()->getInstances(
            array(\Ess\M2ePro\Model\ProductChange::UPDATE_ATTRIBUTE_CODE)
        );

        $attributesForProductChange = array();

        foreach ($changedListingsProducts as $listingProduct) {
            /** @var \Ess\M2ePro\Model\Ebay\Listing\Product $ebayListingProduct */
            $ebayListingProduct = $listingProduct->getChildObject();

            $attributesForProductChange = array_merge(
                $attributesForProductChange,
                $ebayListingProduct->getShippingTemplate()->getTrackingAttributes()
            );
        }
        $attributesForProductChange = array_unique($attributesForProductChange);

        /** @var \Ess\M2ePro\Model\Listing\Product[] $changedListingsProducts */
        $changedListingsProducts = $this->getProductChangesManager()->getInstancesByListingProduct(
            $attributesForProductChange, true
        );

        foreach ($changedListingsProducts as $listingProduct) {

            try {

                $isExistInRunner = $this->getRunner()->isExistProductWithAction(
                    $listingProduct, \Ess\M2ePro\Model\Listing\Product::ACTION_STOP
                );

                if ($isExistInRunner) {
                    continue;
                }

                /** @var \Ess\M2ePro\Model\Ebay\Listing\Product $ebayListingProduct */
                $ebayListingProduct = $listingProduct->getChildObject();

                $servicesAttributes = $ebayListingProduct->getShippingTemplate()->getTrackingAttributes();

                if (!in_array($listingProduct->getData('changed_attribute'), $servicesAttributes)) {
                    continue;
                }

                /** @var $configurator \Ess\M2ePro\Model\Ebay\Listing\Product\Action\Configurator */
                $configurator = $this->modelFactory->getObject('Ebay\Listing\Product\Action\Configurator');
                $configurator->reset();
                $configurator->allowShippingServices();

                $isExistInRunner = $this->getRunner()->isExistProductWithCoveringConfigurator(
                    $listingProduct, \Ess\M2ePro\Model\Listing\Product::ACTION_REVISE, $configurator
                );

                if ($isExistInRunner) {
                    continue;
                }

                if (!$this->getInspector()->isMeetReviseShippingServicesRequirements($listingProduct)) {
                    continue;
                }

                $this->getRunner()->addProduct(
                    $listingProduct, \Ess\M2ePro\Model\Listing\Product::ACTION_REVISE, $configurator
                );
            } catch (\Exception $exception) {

                $this->logError($listingProduct, $exception, false);
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

                $this->logError($listingProduct, $exception, false);
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
        $limit = $this->getConfigValue($this->getFullSettingsPath().'need_synch/', 'items_limit');

        $listingProductCollection->getSelect()->joinLeft(
            array('mpc' => $this->resourceConnection->getTableName('m2epro_processing_lock')),
            "mpc.object_id = main_table.id AND mpc.tag='{$tag}' AND mpc.model_name = '{$modelName}'",
            array()
        );
        $listingProductCollection->addFieldToFilter('mpc.id', array('null' => true));

        $listingProductCollection->getSelect()->limit($limit);

        foreach ($listingProductCollection->getItems() as $listingProduct) {

            try {
                /** @var $listingProduct \Ess\M2ePro\Model\Listing\Product */
                $listingProduct->setData('synch_status', \Ess\M2ePro\Model\Listing\Product::SYNCH_STATUS_SKIP)->save();

                $isExistInRunner = $this->getRunner()->isExistProductWithAction(
                    $listingProduct, \Ess\M2ePro\Model\Listing\Product::ACTION_STOP
                );

                if ($isExistInRunner) {
                    continue;
                }

                /** @var $configurator \Ess\M2ePro\Model\Ebay\Listing\Product\Action\Configurator */
                $configurator = $this->modelFactory->getObject('Ebay\Listing\Product\Action\Configurator');

                $isExistInRunner = $this->getRunner()->isExistProductWithCoveringConfigurator(
                    $listingProduct, \Ess\M2ePro\Model\Listing\Product::ACTION_REVISE, $configurator
                );

                if ($isExistInRunner) {
                    continue;
                }

                if (!$this->getInspector()->isMeetReviseSynchReasonsRequirements($listingProduct, false)) {
                    continue;
                }

                $this->getRunner()->addProduct(
                    $listingProduct, \Ess\M2ePro\Model\Listing\Product::ACTION_REVISE, $configurator
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
        $this->getActualOperationHistory()->addTimePoint(__METHOD__,'Execute Revise all');

        $lastListingProductProcessed = $this->getConfigValue(
            $this->getFullSettingsPath().'total/','last_listing_product_id'
        );

        if (is_null($lastListingProductProcessed)) {
            return;
        }

        $itemsPerCycle = $this->getConfigValue($this->getFullSettingsPath().'total/', 'items_limit');

        $collection = $this->ebayFactory->getObject('Listing\Product')
            ->getCollection()
            ->addFieldToFilter('id',array('gt' => $lastListingProductProcessed))
            ->addFieldToFilter('status', \Ess\M2ePro\Model\Listing\Product::STATUS_LISTED);

        $collection->getSelect()->limit($itemsPerCycle);
        $collection->getSelect()->order('id ASC');

        /* @var $listingProduct \Ess\M2ePro\Model\Listing\Product */
        foreach ($collection->getItems() as $listingProduct) {

            try {

                $isExistInRunner = $this->getRunner()->isExistProductWithAction(
                    $listingProduct, \Ess\M2ePro\Model\Listing\Product::ACTION_STOP
                );

                if ($isExistInRunner) {
                    continue;
                }

                /** @var $configurator \Ess\M2ePro\Model\Ebay\Listing\Product\Action\Configurator */
                $configurator = $this->modelFactory->getObject('Ebay\Listing\Product\Action\Configurator');

                $isExistInRunner = $this->getRunner()->isExistProductWithCoveringConfigurator(
                    $listingProduct, \Ess\M2ePro\Model\Listing\Product::ACTION_REVISE, $configurator
                );

                if ($isExistInRunner) {
                    continue;
                }

                if (!$this->getInspector()->isMeetReviseGeneralRequirements($listingProduct, false)) {
                    continue;
                }

                $this->getRunner()->addProduct(
                    $listingProduct, \Ess\M2ePro\Model\Listing\Product::ACTION_REVISE, $configurator
                );
            } catch (\Exception $exception) {

                $this->logError($listingProduct, $exception, false);
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