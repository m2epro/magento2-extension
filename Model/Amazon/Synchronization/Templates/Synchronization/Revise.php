<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Synchronization\Templates\Synchronization;

final class Revise extends \Ess\M2ePro\Model\Amazon\Synchronization\Templates\Synchronization\AbstractModel
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
        $this->executePriceChanged();

        $this->executeDetailsChanged();
        $this->executeImagesChanged();

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

                /** @var $configurator \Ess\M2ePro\Model\Amazon\Listing\Product\Action\Configurator */
                $configurator = $this->modelFactory->getObject('Amazon\Listing\Product\Action\Configurator');
                $configurator->setPartialMode();
                $configurator->allowQty();

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

                /** @var $configurator \Ess\M2ePro\Model\Amazon\Listing\Product\Action\Configurator */
                $configurator = $this->modelFactory->getObject('Amazon\Listing\Product\Action\Configurator');
                $configurator->setPartialMode();
                $configurator->allowPrice();

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

    private function executeDetailsChanged()
    {
        $this->getActualOperationHistory()->addTimePoint(__METHOD__,'Update details');

        $attributesForProductChange = array();
        $descriptionTemplateCollection = $this->amazonFactory
                                              ->getObject('Template\Description')
                                              ->getCollection();

        /** @var \Ess\M2ePro\Model\Template\Description[] $descriptionTemplates */
        $descriptionTemplates = $descriptionTemplateCollection->getItems();

        foreach ($descriptionTemplates as $descriptionTemplate) {

            /** @var \Ess\M2ePro\Model\Amazon\Template\Description $amazonDescriptionTemplate */
            $amazonDescriptionTemplate = $descriptionTemplate->getChildObject();

            $attributes = $amazonDescriptionTemplate->getDefinitionTemplate()->getUsedDetailsAttributes();

            $specifics = $amazonDescriptionTemplate->getSpecifics(true);
            foreach ($specifics as $specific) {
                $attributes = array_merge($attributes, $specific->getUsedAttributes());
            }

            $attributesForProductChange = array_merge($attributesForProductChange,$attributes);
        }

        $listingCollection = $this->amazonFactory->getObject('Listing')->getCollection();

        /** @var \Ess\M2ePro\Model\Listing[] $listings */
        $listings = $listingCollection->getItems();

        foreach ($listings as $listing) {

            /** @var \Ess\M2ePro\Model\Amazon\Listing $amazonListing */
            $amazonListing = $listing->getChildObject();

            $attributesForProductChange = array_merge(
                $attributesForProductChange,
                $amazonListing->getConditionNoteAttributes(),
                $amazonListing->getGiftWrapAttributes(),
                $amazonListing->getGiftMessageAttributes()
            );
        }

        foreach ($this->getChangedListingsProducts($attributesForProductChange) as $listingProduct) {

            try {

                $isExistInRunner = $this->getRunner()->isExistProductWithAction(
                    $listingProduct, \Ess\M2ePro\Model\Listing\Product::ACTION_STOP
                );

                if ($isExistInRunner) {
                    continue;
                }

                /** @var \Ess\M2ePro\Model\Amazon\Listing\Product $amazonListingProduct */
                $amazonListingProduct = $listingProduct->getChildObject();

                $detailsAttributes = array_merge(
                    $amazonListingProduct->getAmazonListing()->getConditionNoteAttributes(),
                    $amazonListingProduct->getAmazonListing()->getGiftWrapAttributes(),
                    $amazonListingProduct->getAmazonListing()->getGiftMessageAttributes()
                );

                if ($amazonListingProduct->isExistDescriptionTemplate()) {
                    $descriptionTemplateDetailsAttributes = $amazonListingProduct->getAmazonDescriptionTemplate()
                        ->getDefinitionTemplate()
                        ->getUsedDetailsAttributes();

                    $specifics = $amazonListingProduct->getAmazonDescriptionTemplate()->getSpecifics(true);
                    foreach ($specifics as $specific) {
                        $descriptionTemplateDetailsAttributes = array_merge(
                            $descriptionTemplateDetailsAttributes, $specific->getUsedAttributes()
                        );
                    }

                    $detailsAttributes = array_merge(
                        $detailsAttributes,
                        $descriptionTemplateDetailsAttributes
                    );
                }

                if (!in_array($listingProduct->getData('changed_attribute'), $detailsAttributes)) {
                    continue;
                }

                /** @var $configurator \Ess\M2ePro\Model\Amazon\Listing\Product\Action\Configurator */
                $configurator = $this->modelFactory->getObject('Amazon\Listing\Product\Action\Configurator');
                $configurator->setPartialMode();
                $configurator->allowDetails();

                $isExistInRunner = $this->getRunner()->isExistProductWithCoveringConfigurator(
                    $listingProduct, \Ess\M2ePro\Model\Listing\Product::ACTION_REVISE, $configurator
                );

                if ($isExistInRunner) {
                    continue;
                }

                if (!$this->getInspector()->isMeetReviseDetailsRequirements($listingProduct)) {
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
        $descriptionTemplateCollection = $this->amazonFactory
                                              ->getObject('Template\Description')
                                              ->getCollection();

        /** @var \Ess\M2ePro\Model\Template\Description[] $descriptionTemplates */
        $descriptionTemplates = $descriptionTemplateCollection->getItems();

        foreach ($descriptionTemplates as $descriptionTemplate) {

            /** @var \Ess\M2ePro\Model\Amazon\Template\Description $amazonDescriptionTemplate */
            $amazonDescriptionTemplate = $descriptionTemplate->getChildObject();

            $attributesForProductChange = array_merge(
                $attributesForProductChange,
                $amazonDescriptionTemplate->getDefinitionTemplate()->getUsedImagesAttributes()
            );
        }

        $listingCollection = $this->amazonFactory->getObject('Listing')->getCollection();

        /** @var \Ess\M2ePro\Model\Listing[] $listings */
        $listings = $listingCollection->getItems();

        foreach ($listings as $listing) {

            /** @var \Ess\M2ePro\Model\Amazon\Listing $amazonListing */
            $amazonListing = $listing->getChildObject();

            $attributesForProductChange = array_merge(
                $attributesForProductChange,
                $amazonListing->getImageMainAttributes(),
                $amazonListing->getGalleryImagesAttributes()
            );
        }

        foreach ($this->getChangedListingsProducts($attributesForProductChange) as $listingProduct) {

            try {

                $isExistInRunner = $this->getRunner()->isExistProductWithAction(
                    $listingProduct, \Ess\M2ePro\Model\Listing\Product::ACTION_STOP
                );

                if ($isExistInRunner) {
                    continue;
                }

                /** @var \Ess\M2ePro\Model\Amazon\Listing\Product $amazonListingProduct */
                $amazonListingProduct = $listingProduct->getChildObject();

                $amazonListing = $amazonListingProduct->getAmazonListing();

                $imagesAttributes = array_merge(
                    $amazonListing->getImageMainAttributes(),
                    $amazonListing->getGalleryImagesAttributes()
                );

                if ($amazonListingProduct->isExistDescriptionTemplate()) {
                    $amazonDescriptionTemplate = $amazonListingProduct->getAmazonDescriptionTemplate();
                    $imagesAttributes = array_merge(
                        $imagesAttributes,
                        $amazonDescriptionTemplate->getDefinitionTemplate()->getUsedImagesAttributes()
                    );
                }

                if (!in_array($listingProduct->getData('changed_attribute'), $imagesAttributes)) {
                    continue;
                }

                /** @var $configurator \Ess\M2ePro\Model\Amazon\Listing\Product\Action\Configurator */
                $configurator = $this->modelFactory->getObject('Amazon\Listing\Product\Action\Configurator');
                $configurator->setPartialMode();
                $configurator->allowImages();

                $isExistInRunner = $this->getRunner()->isExistProductWithCoveringConfigurator(
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

        $listingProductCollection = $this->amazonFactory->getObject('Listing\Product')->getCollection();
        $listingProductCollection->addFieldToFilter(
            'status',
            array('in' => array(
                \Ess\M2ePro\Model\Listing\Product::STATUS_LISTED,
                \Ess\M2ePro\Model\Listing\Product::STATUS_UNKNOWN,
            ))
        );
        $listingProductCollection->addFieldToFilter(
            'synch_status',\Ess\M2ePro\Model\Listing\Product::SYNCH_STATUS_NEED
        );
        $listingProductCollection->addFieldToFilter('is_variation_parent', 0);

        $tag = 'in_action';
        $modelName = $this->activeRecordFactory->getObject('Listing\Product')->getResourceName();

        $listingProductCollection->getSelect()->joinLeft(
            array('mpc' => $this->resourceConnection->getTableName('m2epro_processing_lock')),
            "mpc.object_id = main_table.id AND mpc.tag='{$tag}' AND mpc.model_name = '{$modelName}'",
            array()
        );
        $listingProductCollection->addFieldToFilter('mpc.id', array('null' => true));

        $listingProductCollection->getSelect()->limit(100);

        /** @var $listingProduct \Ess\M2ePro\Model\Listing\Product */
        foreach ($listingProductCollection->getItems() as $listingProduct) {

            try {
                $listingProduct->setData('synch_status',\Ess\M2ePro\Model\Listing\Product::SYNCH_STATUS_SKIP)->save();

                $isExistInRunner = $this->getRunner()->isExistProductWithAction(
                    $listingProduct, \Ess\M2ePro\Model\Listing\Product::ACTION_STOP
                );

                if ($isExistInRunner) {
                    continue;
                }

                /** @var $configurator \Ess\M2ePro\Model\Amazon\Listing\Product\Action\Configurator */
                $configurator = $this->modelFactory->getObject('Amazon\Listing\Product\Action\Configurator');

                $isExistInRunner = $this->getRunner()->isExistProductWithCoveringConfigurator(
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

                $this->logError($listingProduct, $exception, false);
                continue;
            }
        }

        $this->getActualOperationHistory()->saveTimePoint(__METHOD__);
    }

    private function executeTotal()
    {
        $this->getActualOperationHistory()->addTimePoint(__METHOD__,'Execute revise all');

        $lastListingProductProcessed = $this->getConfigValue(
            $this->getFullSettingsPath().'total/','last_listing_product_id'
        );

        if (is_null($lastListingProductProcessed)) {
            return;
        }

        $itemsPerCycle = 100;

        $collection = $this->amazonFactory->getObject('Listing\Product')
            ->getCollection()
            ->addFieldToFilter('id',array('gt' => $lastListingProductProcessed))
            ->addFieldToFilter('status', \Ess\M2ePro\Model\Listing\Product::STATUS_LISTED)
            ->addFieldToFilter('is_variation_parent', 0);

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

                /** @var $configurator \Ess\M2ePro\Model\Amazon\Listing\Product\Action\Configurator */
                $configurator = $this->modelFactory->getObject('Amazon\Listing\Product\Action\Configurator');

                $isExistInRunner = $this->getRunner()->isExistProductWithCoveringConfigurator(
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

    /**
     * @param array $trackingAttributes
     * @return \Ess\M2ePro\Model\Listing\Product[]
     */
    private function getChangedListingsProducts(array $trackingAttributes)
    {
        $filteredChangedListingsProducts = array();

        /** @var \Ess\M2ePro\Model\Listing\Product[] $changedListingsProducts */
        $changedListingsProducts = $this->getProductChangesManager()->getInstancesByListingProduct(
            array_unique($trackingAttributes), true
        );

        foreach ($changedListingsProducts as $changedListingProduct) {
            /** @var \Ess\M2ePro\Model\Amazon\Listing\Product $amazonListingProduct */
            $amazonListingProduct = $changedListingProduct->getChildObject();

            if ($amazonListingProduct->getVariationManager()->isRelationParentType()) {
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
            array_unique($trackingAttributes), true
        );

        foreach ($changedListingsProducts as $changedListingProduct) {
            $magentoProduct = $changedListingProduct->getMagentoProduct();

            if ($magentoProduct->isSimpleTypeWithCustomOptions() || $magentoProduct->isBundleType()) {
                continue;
            }

            $filteredChangedListingsProducts[$changedListingProduct->getId()] = $changedListingProduct;
        }

        return $filteredChangedListingsProducts;
    }

    //########################################
}