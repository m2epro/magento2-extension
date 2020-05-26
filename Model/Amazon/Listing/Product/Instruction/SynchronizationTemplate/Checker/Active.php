<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Listing\Product\Instruction\SynchronizationTemplate\Checker;

use \Ess\M2ePro\Model\Magento\Product\ChangeProcessor\AbstractModel as ChangeProcessorAbstract;

/**
 * Class \Ess\M2ePro\Model\Amazon\Listing\Product\Instruction\SynchronizationTemplate\Checker\Active
 */
class Active extends AbstractModel
{
    //########################################

    protected function getStopInstructionTypes()
    {
        return [
            \Ess\M2ePro\Model\Amazon\Template\Synchronization\ChangeProcessor::INSTRUCTION_TYPE_STOP_MODE_ENABLED,
            \Ess\M2ePro\Model\Amazon\Template\Synchronization\ChangeProcessor::INSTRUCTION_TYPE_STOP_MODE_DISABLED,
            \Ess\M2ePro\Model\Amazon\Template\Synchronization\ChangeProcessor::INSTRUCTION_TYPE_STOP_SETTINGS_CHANGED,
            ChangeProcessorAbstract::INSTRUCTION_TYPE_PRODUCT_QTY_DATA_POTENTIALLY_CHANGED,
            ChangeProcessorAbstract::INSTRUCTION_TYPE_PRODUCT_STATUS_DATA_POTENTIALLY_CHANGED,
            \Ess\M2ePro\Model\Listing::INSTRUCTION_TYPE_PRODUCT_MOVED_FROM_OTHER,
            \Ess\M2ePro\Model\Listing::INSTRUCTION_TYPE_PRODUCT_MOVED_FROM_LISTING,
            \Ess\M2ePro\Model\Amazon\Listing\Product::INSTRUCTION_TYPE_CHANNEL_QTY_CHANGED,
            \Ess\M2ePro\Model\Amazon\Listing\Product::INSTRUCTION_TYPE_CHANNEL_STATUS_CHANGED,
            \Ess\M2ePro\Model\Amazon\Template\ChangeProcessor\AbstractModel::INSTRUCTION_TYPE_QTY_DATA_CHANGED,
            \Ess\M2ePro\PublicServices\Product\SqlChange::INSTRUCTION_TYPE_PRODUCT_CHANGED,
            \Ess\M2ePro\PublicServices\Product\SqlChange::INSTRUCTION_TYPE_STATUS_CHANGED,
            \Ess\M2ePro\PublicServices\Product\SqlChange::INSTRUCTION_TYPE_QTY_CHANGED,
            ChangeProcessorAbstract::INSTRUCTION_TYPE_MAGMI_PLUGIN_PRODUCT_CHANGED,
            \Ess\M2ePro\Model\Cron\Task\Listing\Product\InspectDirectChanges::INSTRUCTION_TYPE,
        ];
    }

    //########################################

    public function isAllowed()
    {
        if (!$this->input->hasInstructionWithTypes($this->getStopInstructionTypes()) &&
            !$this->input->hasInstructionWithTypes($this->getReviseInstructionTypes())
        ) {
            return false;
        }

        $listingProduct = $this->input->getListingProduct();

        if (!$listingProduct->isStoppable() && !$listingProduct->isRevisable()) {
            return false;
        }

        if ($scheduledAction = $this->input->getScheduledAction()) {
            if ($scheduledAction->isActionTypeDelete() && $scheduledAction->isForce()) {
                return false;
            }
        }

        return true;
    }

    //########################################

    public function process(array $params = [])
    {
        /** @var \Ess\M2ePro\Model\Listing\Product\ScheduledAction $scheduledAction */
        $scheduledAction = $this->input->getScheduledAction();
        if ($scheduledAction === null) {
            $scheduledAction = $this->activeRecordFactory->getObject('Listing_Product_ScheduledAction');
        }

        $variationManager = $this->input->getListingProduct()->getChildObject()->getVariationManager();

        if ($this->input->hasInstructionWithTypes($this->getStopInstructionTypes())) {
            if (!$this->isMeetStopRequirements()) {
                if ($scheduledAction->isActionTypeStop() && !$scheduledAction->isForce()) {
                    $this->getScheduledActionManager()->deleteAction($scheduledAction);
                    $scheduledAction->unsetData();
                }
            } else {
                if ($scheduledAction->isActionTypeRevise()) {
                    $this->setPropertiesForRecheck($this->getPropertiesDataFromInputScheduledAction());
                }

                $scheduledAction->addData(
                    [
                        'listing_product_id' => $this->input->getListingProduct()->getId(),
                        'component'          => \Ess\M2ePro\Helper\Component\Amazon::NICK,
                        'action_type'        => \Ess\M2ePro\Model\Listing\Product::ACTION_STOP,
                        'additional_data'    => $this->getHelper('Data')->jsonEncode(['params' => $params,]),
                    ]
                );

                if ($scheduledAction->getId()) {
                    $this->getScheduledActionManager()->updateAction($scheduledAction);
                } else {
                    $this->getScheduledActionManager()->addAction($scheduledAction);
                }
            }
        }

        if ($scheduledAction->isActionTypeStop()) {
            if ($this->input->hasInstructionWithTypes($this->getReviseInstructionTypes())) {
                $this->setPropertiesForRecheck($this->getPropertiesDataFromInputInstructions());
            }

            return;
        }

        /** @var \Ess\M2ePro\Model\Amazon\Listing\Product\Action\Configurator $configurator */
        $configurator = $this->modelFactory->getObject('Amazon_Listing_Product_Action_Configurator');
        $configurator->disableAll();

        $tags = [];

        if ($scheduledAction->isActionTypeRevise()) {
            if ($scheduledAction->isForce()) {
                return;
            }

            $additionalData = $scheduledAction->getAdditionalData();

            if (isset($additionalData['configurator'])) {
                $configurator->setUnserializedData($additionalData['configurator']);
            } else {
                $configurator->enableAll();
            }

            $tags = explode('/', $scheduledAction->getTag());
        }

        $tags = array_flip($tags);

        if ($variationManager->isRelationParentType() &&
            $this->input->getListingProduct()->getChildObject()->getSku() === null
        ) {
            return false;
        }

        if ($this->input->hasInstructionWithTypes($this->getReviseQtyInstructionTypes())) {
            if ($this->isMeetReviseQtyRequirements()) {
                $configurator->allowQty();
                $tags['qty'] = true;
            } else {
                $configurator->disallowQty();
                unset($tags['qty']);
            }
        }

        if ($this->input->hasInstructionWithTypes($this->getRevisePriceRegularInstructionTypes())) {
            if ($this->isMeetRevisePriceRegularRequirements()) {
                $configurator->allowRegularPrice();
                $tags['price_regular'] = true;
            } else {
                $configurator->disallowRegularPrice();
                unset($tags['price_regular']);
            }
        }

        if ($this->input->hasInstructionWithTypes($this->getRevisePriceBusinessInstructionTypes())) {
            if ($this->isMeetRevisePriceBusinessRequirements()) {
                $configurator->allowBusinessPrice();
                $tags['price_business'] = true;
            } else {
                $configurator->disallowBusinessPrice();
                unset($tags['price_business']);
            }
        }

        if ($this->input->hasInstructionWithTypes($this->getReviseDetailsInstructionTypes())) {
            if ($this->isMeetReviseDetailsRequirements()) {
                $configurator->allowDetails();
                $tags['details'] = true;
            } else {
                $configurator->disallowDetails();
                unset($tags['details']);
            }
        }

        if ($this->input->hasInstructionWithTypes($this->getReviseImagesInstructionTypes())) {
            if ($this->isMeetReviseImagesRequirements()) {
                $configurator->allowImages();
                $tags['images'] = true;
            } else {
                $configurator->disallowImages();
                unset($tags['images']);
            }
        }

        if (empty($configurator->getAllowedDataTypes())) {
            if ($scheduledAction->getId()) {
                $this->getScheduledActionManager()->deleteAction($scheduledAction);
            }

            return;
        }

        $tags = array_keys($tags);

        $scheduledAction->addData(
            [
                'listing_product_id' => $this->input->getListingProduct()->getId(),
                'component'          => \Ess\M2ePro\Helper\Component\Amazon::NICK,
                'action_type'        => \Ess\M2ePro\Model\Listing\Product::ACTION_REVISE,
                'tag'                => '/'.implode('/', $tags).'/',
                'additional_data'    => $this->getHelper('Data')->jsonEncode(
                    [
                        'params'       => $params,
                        'configurator' => $configurator->getSerializedData()
                    ]
                ),
            ]
        );

        if ($scheduledAction->getId()) {
            $this->getScheduledActionManager()->updateAction($scheduledAction);
        } else {
            $this->getScheduledActionManager()->addAction($scheduledAction);
        }
    }

    //########################################

    public function isMeetStopRequirements()
    {
        $listingProduct = $this->input->getListingProduct();

        /** @var \Ess\M2ePro\Model\Amazon\Listing\Product $amazonListingProduct */
        $amazonListingProduct = $listingProduct->getChildObject();
        $variationManager = $amazonListingProduct->getVariationManager();
        if ($variationManager->isRelationParentType()) {
            return false;
        }

        $amazonSynchronizationTemplate = $amazonListingProduct->getAmazonSynchronizationTemplate();

        /** @var \Ess\M2ePro\Model\ResourceModel\Listing\Product\Variation $variationResource */
        $variationResource = $this->activeRecordFactory->getObject('Listing_Product_Variation')->getResource();

        if (!$amazonSynchronizationTemplate->isStopMode()) {
            return false;
        }

        if ($amazonSynchronizationTemplate->isStopStatusDisabled()) {
            if (!$listingProduct->getMagentoProduct()->isStatusEnabled()) {
                return true;
            } elseif ($variationManager->isPhysicalUnit() &&
                $variationManager->getTypeModel()->isVariationProductMatched()
            ) {
                $temp = $variationResource->isAllStatusesDisabled(
                    $listingProduct->getId(),
                    $listingProduct->getListing()->getStoreId()
                );

                if ($temp !== null && $temp) {
                    return true;
                }
            }
        }

        if ($amazonSynchronizationTemplate->isStopOutOfStock()) {
            if (!$listingProduct->getMagentoProduct()->isStockAvailability()) {
                return true;
            } elseif ($variationManager->isPhysicalUnit() &&
                $variationManager->getTypeModel()->isVariationProductMatched()
            ) {
                $temp = $variationResource->isAllDoNotHaveStockAvailabilities(
                    $listingProduct->getId(),
                    $listingProduct->getListing()->getStoreId()
                );

                if ($temp !== null && $temp) {
                    return true;
                }
            }
        }

        if ($amazonSynchronizationTemplate->isStopWhenQtyMagentoHasValue()) {
            $productQty = (int)$amazonListingProduct->getQty(true);

            $typeQty = (int)$amazonSynchronizationTemplate->getStopWhenQtyMagentoHasValueType();
            $minQty = (int)$amazonSynchronizationTemplate->getStopWhenQtyMagentoHasValueMin();
            $maxQty = (int)$amazonSynchronizationTemplate->getStopWhenQtyMagentoHasValueMax();

            if ($typeQty == \Ess\M2ePro\Model\Template\Synchronization::QTY_MODE_LESS &&
                $productQty <= $minQty) {
                return true;
            }

            if ($typeQty == \Ess\M2ePro\Model\Template\Synchronization::QTY_MODE_MORE &&
                $productQty >= $minQty) {
                return true;
            }

            if ($typeQty == \Ess\M2ePro\Model\Template\Synchronization::QTY_MODE_BETWEEN &&
                $productQty >= $minQty && $productQty <= $maxQty) {
                return true;
            }
        }

        if ($amazonSynchronizationTemplate->isStopWhenQtyCalculatedHasValue()) {
            $productQty = (int)$amazonListingProduct->getQty(false);

            $typeQty = (int)$amazonSynchronizationTemplate->getStopWhenQtyCalculatedHasValueType();
            $minQty = (int)$amazonSynchronizationTemplate->getStopWhenQtyCalculatedHasValueMin();
            $maxQty = (int)$amazonSynchronizationTemplate->getStopWhenQtyCalculatedHasValueMax();

            if ($typeQty == \Ess\M2ePro\Model\Template\Synchronization::QTY_MODE_LESS &&
                $productQty <= $minQty) {
                return true;
            }

            if ($typeQty == \Ess\M2ePro\Model\Template\Synchronization::QTY_MODE_MORE &&
                $productQty >= $minQty) {
                return true;
            }

            if ($typeQty == \Ess\M2ePro\Model\Template\Synchronization::QTY_MODE_BETWEEN &&
                $productQty >= $minQty && $productQty <= $maxQty) {
                return true;
            }
        }

        if ($amazonSynchronizationTemplate->isStopAdvancedRulesEnabled()) {
            /** @var \Ess\M2ePro\Model\Magento\Product\Rule $ruleModel */
            $ruleModel = $this->activeRecordFactory->getObject('Magento_Product_Rule')->setData(
                [
                    'store_id' => $listingProduct->getListing()->getStoreId(),
                    'prefix'   => \Ess\M2ePro\Model\Amazon\Template\Synchronization::STOP_ADVANCED_RULES_PREFIX
                ]
            );
            $ruleModel->loadFromSerialized($amazonSynchronizationTemplate->getStopAdvancedRulesFilters());

            if ($ruleModel->validate($listingProduct->getMagentoProduct()->getProduct())) {
                return true;
            }
        }

        return false;
    }

    // ---------------------------------------

    public function isMeetReviseQtyRequirements()
    {
        $listingProduct = $this->input->getListingProduct();

        /** @var \Ess\M2ePro\Model\Amazon\Listing\Product $amazonListingProduct */
        $amazonListingProduct = $listingProduct->getChildObject();
        $variationManager = $amazonListingProduct->getVariationManager();
        if ($variationManager->isRelationParentType()) {
            return false;
        }

        $amazonSynchronizationTemplate = $amazonListingProduct->getAmazonSynchronizationTemplate();
        if (!$amazonSynchronizationTemplate->isReviseUpdateQty() || $amazonListingProduct->isAfnChannel()) {
            return false;
        }

        $currentHandlingTime = $amazonListingProduct->getListingSource()->getHandlingTime();
        $onlineHandlingTime  = $amazonListingProduct->getOnlineHandlingTime();

        if ($currentHandlingTime != $onlineHandlingTime) {
            return true;
        }

        $currentRestockDate = $amazonListingProduct->getListingSource()->getRestockDate();
        $onlineRestockDate  = $amazonListingProduct->getOnlineRestockDate();

        if ($currentRestockDate != $onlineRestockDate) {
            return true;
        }

        $isMaxAppliedValueModeOn = $amazonSynchronizationTemplate->isReviseUpdateQtyMaxAppliedValueModeOn();
        $maxAppliedValue = $amazonSynchronizationTemplate->getReviseUpdateQtyMaxAppliedValue();

        $productQty = $amazonListingProduct->getQty();
        $channelQty = $amazonListingProduct->getOnlineQty();

        if ($isMaxAppliedValueModeOn && $productQty > $maxAppliedValue && $channelQty > $maxAppliedValue) {
            return false;
        }

        if ($productQty != $channelQty) {
            return true;
        }

        return false;
    }

    // ---------------------------------------

    public function isMeetRevisePriceRegularRequirements()
    {
        $listingProduct = $this->input->getListingProduct();

        /** @var \Ess\M2ePro\Model\Amazon\Listing\Product $amazonListingProduct */
        $amazonListingProduct = $listingProduct->getChildObject();
        $variationManager = $amazonListingProduct->getVariationManager();
        if ($variationManager->isRelationParentType()) {
            return false;
        }

        if (!$amazonListingProduct->isAllowedForRegularCustomers()) {
            return false;
        }

        if ($this->getHelper('Component_Amazon_Repricing')->isEnabled() &&
            $amazonListingProduct->isRepricingManaged()) {
            return false;
        }

        $amazonSynchronizationTemplate = $amazonListingProduct->getAmazonSynchronizationTemplate();

        if (!$amazonSynchronizationTemplate->isReviseUpdatePrice()) {
            return false;
        }

        $currentPrice = $amazonListingProduct->getRegularPrice();
        $onlinePrice  = $amazonListingProduct->getOnlineRegularPrice();

        $isChanged = $amazonSynchronizationTemplate->isPriceChangedOverAllowedDeviation($onlinePrice, $currentPrice);
        if ($isChanged) {
            return true;
        }

        $currentSalePriceInfo = $amazonListingProduct->getRegularSalePriceInfo();
        if ($currentSalePriceInfo !== false) {
            $currentSalePrice          = $currentSalePriceInfo['price'];
            $currentSalePriceStartDate = $currentSalePriceInfo['start_date'];
            $currentSalePriceEndDate   = $currentSalePriceInfo['end_date'];
        } else {
            $currentSalePrice          = 0;
            $currentSalePriceStartDate = null;
            $currentSalePriceEndDate   = null;
        }

        $onlineSalePrice = $amazonListingProduct->getOnlineRegularSalePrice();

        if (!$currentSalePrice && !$onlineSalePrice) {
            return false;
        }

        if (($currentSalePrice === null && $onlineSalePrice !== null) ||
            ($currentSalePrice !== null && $onlineSalePrice === null)
        ) {
            return true;
        }

        $isChanged = $amazonSynchronizationTemplate->isPriceChangedOverAllowedDeviation(
            $onlineSalePrice,
            $currentSalePrice
        );

        if ($isChanged) {
            return true;
        }

        $onlineSalePriceStartDate = $amazonListingProduct->getOnlineRegularSalePriceStartDate();
        $onlineSalePriceEndDate   = $amazonListingProduct->getOnlineRegularSalePriceEndDate();

        if ($currentSalePriceStartDate != $onlineSalePriceStartDate ||
            $currentSalePriceEndDate   != $onlineSalePriceEndDate
        ) {
            return true;
        }

        return false;
    }

    public function isMeetRevisePriceBusinessRequirements()
    {
        $listingProduct = $this->input->getListingProduct();

        /** @var \Ess\M2ePro\Model\Amazon\Listing\Product $amazonListingProduct */
        $amazonListingProduct = $listingProduct->getChildObject();
        $variationManager = $amazonListingProduct->getVariationManager();
        if ($variationManager->isRelationParentType()) {
            return false;
        }

        if (!$amazonListingProduct->isAllowedForBusinessCustomers()) {
            return false;
        }

        $amazonSynchronizationTemplate = $amazonListingProduct->getAmazonSynchronizationTemplate();

        if (!$amazonSynchronizationTemplate->isReviseUpdatePrice()) {
            return false;
        }

        $currentPrice = $amazonListingProduct->getBusinessPrice();
        $onlinePrice  = $amazonListingProduct->getOnlineBusinessPrice();

        if ($amazonSynchronizationTemplate->isPriceChangedOverAllowedDeviation($onlinePrice, $currentPrice)) {
            return true;
        }

        $currentDiscounts = $amazonListingProduct->getBusinessDiscounts();
        $onlineDiscounts  = $amazonListingProduct->getOnlineBusinessDiscounts();

        // amazon does not support disabling discounts, so revise should not be allowed
        if (empty($currentDiscounts)) {
            return false;
        }

        if (count($currentDiscounts) != count($onlineDiscounts)) {
            return true;
        }

        foreach ($currentDiscounts as $qty => $currentDiscount) {
            if (!isset($onlineDiscounts[$qty])) {
                return true;
            }

            $onlineDiscount = $onlineDiscounts[$qty];

            $isChanged = $amazonSynchronizationTemplate->isPriceChangedOverAllowedDeviation(
                $onlineDiscount,
                $currentDiscount
            );

            if ($isChanged) {
                return true;
            }
        }

        return false;
    }

    // ---------------------------------------

    public function isMeetReviseDetailsRequirements()
    {
        $listingProduct = $this->input->getListingProduct();

        /** @var \Ess\M2ePro\Model\Amazon\Listing\Product $amazonListingProduct */
        $amazonListingProduct = $listingProduct->getChildObject();

        $amazonSynchronizationTemplate = $amazonListingProduct->getAmazonSynchronizationTemplate();
        if (!$amazonSynchronizationTemplate->isReviseWhenChangeDetails()) {
            return false;
        }

        /** @var \Ess\M2ePro\Model\Amazon\Listing\Product\Action\DataBuilder\Details $detailsActionDataBuilder */
        $detailsActionDataBuilder = $this->modelFactory->getObject('Amazon_Listing_Product_Action_DataBuilder_Details');
        $detailsActionDataBuilder->setListingProduct($listingProduct);

        if ($detailsActionDataBuilder->getBuilderData() != $amazonListingProduct->getOnlineDetailsData()) {
            return true;
        }

        return false;
    }

    // ---------------------------------------

    public function isMeetReviseImagesRequirements()
    {
        $listingProduct = $this->input->getListingProduct();

        /** @var \Ess\M2ePro\Model\Amazon\Listing\Product $amazonListingProduct */
        $amazonListingProduct = $listingProduct->getChildObject();

        $amazonSynchronizationTemplate = $amazonListingProduct->getAmazonSynchronizationTemplate();
        if (!$amazonSynchronizationTemplate->isReviseWhenChangeImages()) {
            return false;
        }

        /** @var \Ess\M2ePro\Model\Amazon\Listing\Product\Action\DataBuilder\Images $actionDataBuilder */
        $actionDataBuilder = $this->modelFactory->getObject('Amazon_Listing_Product_Action_DataBuilder_Images');
        $actionDataBuilder->setListingProduct($listingProduct);

        if ($actionDataBuilder->getBuilderData() != $amazonListingProduct->getOnlineImagesData()) {
            return true;
        }

        return false;
    }

    //########################################
}
