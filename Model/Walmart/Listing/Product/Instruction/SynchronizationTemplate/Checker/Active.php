<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Walmart\Listing\Product\Instruction\SynchronizationTemplate\Checker;

use \Ess\M2ePro\Model\Magento\Product\ChangeProcessor\AbstractModel as ChangeProcessorAbstract;

/**
 * Class \Ess\M2ePro\Model\Walmart\Listing\Product\Instruction\SynchronizationTemplate\Checker\Active
 */
class Active extends AbstractModel
{
    //########################################

    protected function getStopInstructionTypes()
    {
        return [
            \Ess\M2ePro\Model\Walmart\Template\Synchronization\ChangeProcessor::INSTRUCTION_TYPE_STOP_MODE_ENABLED,
            \Ess\M2ePro\Model\Walmart\Template\Synchronization\ChangeProcessor::INSTRUCTION_TYPE_STOP_MODE_DISABLED,
            \Ess\M2ePro\Model\Walmart\Template\Synchronization\ChangeProcessor::INSTRUCTION_TYPE_STOP_SETTINGS_CHANGED,
            ChangeProcessorAbstract::INSTRUCTION_TYPE_PRODUCT_QTY_DATA_POTENTIALLY_CHANGED,
            ChangeProcessorAbstract::INSTRUCTION_TYPE_PRODUCT_STATUS_DATA_POTENTIALLY_CHANGED,
            \Ess\M2ePro\Model\Listing::INSTRUCTION_TYPE_PRODUCT_MOVED_FROM_OTHER,
            \Ess\M2ePro\Model\Listing::INSTRUCTION_TYPE_PRODUCT_MOVED_FROM_LISTING,
            \Ess\M2ePro\Model\Listing::INSTRUCTION_TYPE_PRODUCT_REMAP_FROM_LISTING,
            \Ess\M2ePro\Model\Walmart\Listing\Product::INSTRUCTION_TYPE_CHANNEL_QTY_CHANGED,
            \Ess\M2ePro\Model\Walmart\Listing\Product::INSTRUCTION_TYPE_CHANNEL_STATUS_CHANGED,
            \Ess\M2ePro\Model\Walmart\Template\ChangeProcessor\ChangeProcessorAbstract::INSTRUCTION_TYPE_QTY_DATA_CHANGED,
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

        /** @var \Ess\M2ePro\Model\Walmart\Listing\Product $walmartListingProduct */
        $walmartListingProduct = $listingProduct->getChildObject();
        if (!$walmartListingProduct->isExistCategoryTemplate()) {
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
                        'component'          => \Ess\M2ePro\Helper\Component\Walmart::NICK,
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

        /** @var \Ess\M2ePro\Model\Walmart\Listing\Product\Action\Configurator $configurator */
        $configurator = $this->modelFactory->getObject('Walmart_Listing_Product_Action_Configurator');
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

        if ($this->input->hasInstructionWithTypes($this->getReviseLagTimeInstructionTypes())) {
            if ($this->isMeetReviseLagTime()) {
                $configurator->allowLagTime();
                $tags['lag_time'] = true;
            } else {
                $configurator->disallowLagTime();
                unset($tags['lag_time']);
            }
        }

        if ($this->input->hasInstructionWithTypes($this->getRevisePriceInstructionTypes())) {
            if ($this->isMeetRevisePriceRequirements()) {
                $configurator->allowPrice();
                $tags['price'] = true;
            } else {
                $configurator->disallowPrice();
                unset($tags['price']);
            }
        }

        if ($this->input->hasInstructionWithTypes($this->getRevisePromotionsInstructionTypes())) {
            if ($this->isMeetRevisePromotionsRequirements()) {
                $configurator->allowPromotions();
                $tags['promotions'] = true;
            } else {
                $configurator->disallowPromotions();
                unset($tags['promotions']);
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

        $this->checkUpdatePriceOrPromotionsFeedsLock(
            $configurator,
            $tags,
            \Ess\M2ePro\Model\Listing\Log::ACTION_REVISE_PRODUCT_ON_COMPONENT
        );

        $types = $configurator->getAllowedDataTypes();
        if (empty($types)) {
            if ($scheduledAction->getId()) {
                $this->getScheduledActionManager()->deleteAction($scheduledAction);
            }

            return;
        }

        $tags = array_keys($tags);

        $scheduledAction->addData(
            [
                'listing_product_id' => $this->input->getListingProduct()->getId(),
                'component'          => \Ess\M2ePro\Helper\Component\Walmart::NICK,
                'action_type'        => \Ess\M2ePro\Model\Listing\Product::ACTION_REVISE,
                'tag'                => '/' . implode('/', $tags) . '/',
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

        /** @var \Ess\M2ePro\Model\Walmart\Listing\Product $walmartListingProduct */
        $walmartListingProduct = $listingProduct->getChildObject();
        $variationManager = $walmartListingProduct->getVariationManager();
        if ($variationManager->isRelationParentType()) {
            return false;
        }

        $walmartSynchronizationTemplate = $walmartListingProduct->getWalmartSynchronizationTemplate();
        $variationResource = $this->activeRecordFactory->getObject('Listing_Product_Variation')->getResource();

        if (!$walmartSynchronizationTemplate->isStopMode()) {
            return false;
        }

        if (!$walmartListingProduct->isExistCategoryTemplate()) {
            return false;
        }

        if ($walmartSynchronizationTemplate->isStopStatusDisabled()) {
            if (!$listingProduct->getMagentoProduct()->isStatusEnabled()) {
                return true;
            } elseif ($variationManager->isPhysicalUnit() &&
                $variationManager->getTypeModel()->isVariationProductMatched()) {
                $temp = $variationResource->isAllStatusesDisabled(
                    $listingProduct->getId(),
                    $listingProduct->getListing()->getStoreId()
                );

                if ($temp !== null && $temp) {
                    return true;
                }
            }
        }

        if ($walmartSynchronizationTemplate->isStopOutOfStock()) {
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

        if ($walmartSynchronizationTemplate->isStopWhenQtyCalculatedHasValue()) {
            $productQty = (int)$walmartListingProduct->getQty(false);
            $minQty = (int)$walmartSynchronizationTemplate->getStopWhenQtyCalculatedHasValueMin();

            if ($productQty <= $minQty) {
                return true;
            }
        }

        if ($walmartSynchronizationTemplate->isStopAdvancedRulesEnabled()) {
            $ruleModel = $this->activeRecordFactory->getObject('Magento_Product_Rule')->setData(
                [
                    'store_id' => $listingProduct->getListing()->getStoreId(),
                    'prefix'   => \Ess\M2ePro\Model\Walmart\Template\Synchronization::STOP_ADVANCED_RULES_PREFIX
                ]
            );
            $ruleModel->loadFromSerialized($walmartSynchronizationTemplate->getStopAdvancedRulesFilters());

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

        /** @var \Ess\M2ePro\Model\Walmart\Listing\Product $walmartListingProduct */
        $walmartListingProduct = $listingProduct->getChildObject();
        $variationManager = $walmartListingProduct->getVariationManager();
        if ($variationManager->isRelationParentType()) {
            return false;
        }

        $walmartSynchronizationTemplate = $walmartListingProduct->getWalmartSynchronizationTemplate();
        if (!$walmartSynchronizationTemplate->isReviseUpdateQty()) {
            return false;
        }

        $isMaxAppliedValueModeOn = $walmartSynchronizationTemplate->isReviseUpdateQtyMaxAppliedValueModeOn();
        $maxAppliedValue = $walmartSynchronizationTemplate->getReviseUpdateQtyMaxAppliedValue();

        $productQty = $walmartListingProduct->getQty();
        $channelQty = $walmartListingProduct->getOnlineQty();

        if ($isMaxAppliedValueModeOn && $productQty > $maxAppliedValue && $channelQty > $maxAppliedValue) {
            return false;
        }

        if ($productQty != $channelQty) {
            return true;
        }

        return false;
    }

    // ---------------------------------------

    public function isMeetReviseLagTime()
    {
        $listingProduct = $this->input->getListingProduct();

        /** @var \Ess\M2ePro\Model\Walmart\Listing\Product $walmartListingProduct */
        $walmartListingProduct = $listingProduct->getChildObject();
        $variationManager = $walmartListingProduct->getVariationManager();
        if ($variationManager->isRelationParentType()) {
            return false;
        }

        $walmartSynchronizationTemplate = $walmartListingProduct->getWalmartSynchronizationTemplate();
        if (!$walmartSynchronizationTemplate->isReviseUpdateQty()) {
            return false;
        }

        $currentLagTime = $walmartListingProduct->getSellingFormatTemplateSource()->getLagTime();
        $onlineLagTime  = $walmartListingProduct->getOnlineLagTime();

        if ($currentLagTime != $onlineLagTime) {
            return true;
        }

        return false;
    }

    // ---------------------------------------

    public function isMeetRevisePriceRequirements()
    {
        $listingProduct = $this->input->getListingProduct();

        /** @var \Ess\M2ePro\Model\Walmart\Listing\Product $walmartListingProduct */
        $walmartListingProduct = $listingProduct->getChildObject();
        $variationManager = $walmartListingProduct->getVariationManager();
        if ($variationManager->isRelationParentType()) {
            return false;
        }

        $walmartSynchronizationTemplate = $walmartListingProduct->getWalmartSynchronizationTemplate();
        if (!$walmartSynchronizationTemplate->isReviseUpdatePrice()) {
            return false;
        }

        $currentPrice = $walmartListingProduct->getPrice();
        $onlinePrice  = $walmartListingProduct->getOnlinePrice();

        if ($walmartListingProduct->getPrice() != $walmartListingProduct->getOnlinePrice()) {
            return true;
        }

        return false;
    }

    // ---------------------------------------

    public function isMeetRevisePromotionsRequirements()
    {
        $listingProduct = $this->input->getListingProduct();

        /** @var \Ess\M2ePro\Model\Walmart\Listing\Product $walmartListingProduct */
        $walmartListingProduct = $listingProduct->getChildObject();
        $variationManager = $walmartListingProduct->getVariationManager();
        if ($variationManager->isRelationParentType()) {
            return false;
        }

        $walmartSynchronizationTemplate = $walmartListingProduct->getWalmartSynchronizationTemplate();
        if (!$walmartSynchronizationTemplate->isReviseUpdatePromotions()) {
            return false;
        }

        /** @var \Ess\M2ePro\Model\Walmart\Listing\Product\Action\DataBuilder\Promotions $promotionsActionDataBuilder */
        $promotionsActionDataBuilder = $this->modelFactory
            ->getObject('Walmart_Listing_Product_Action_DataBuilder_Promotions');
        $promotionsActionDataBuilder->setListingProduct($listingProduct);

        $onlinePromotions = $walmartListingProduct->getOnlinePromotions();

        if (empty($onlinePromotions)) {
            $onlinePromotions = $hashDetailsData = $this->getHelper('Data')->hashString(
                $this->getHelper('Data')->jsonEncode(['promotion_prices' => []]),
                'md5'
            );
        }

        $hashPromotionsData = $this->getHelper('Data')->hashString(
            $this->getHelper('Data')->jsonEncode($promotionsActionDataBuilder->getBuilderData()),
            'md5'
        );

        return $hashPromotionsData != $onlinePromotions;
    }

    // ---------------------------------------

    public function isMeetReviseDetailsRequirements()
    {
        /** @var \Ess\M2ePro\Model\Walmart\Listing\Product $walmartListingProduct */
        $walmartListingProduct = $this->input->getListingProduct()->getChildObject();

        $walmartSynchronizationTemplate = $walmartListingProduct->getWalmartSynchronizationTemplate();
        if (!$walmartSynchronizationTemplate->isReviseUpdateDetails()) {
            return false;
        }

        /** @var \Ess\M2ePro\Model\Walmart\Listing\Product\Action\DataBuilder\Details $detailsActionDataBuilder */
        $detailsActionDataBuilder = $this->modelFactory
            ->getObject('Walmart_Listing_Product_Action_DataBuilder_Details');
        $detailsActionDataBuilder->setListingProduct($this->input->getListingProduct());

        $currentDetailsData = $detailsActionDataBuilder->getBuilderData();

        $currentStartDate = $currentDetailsData['start_date'];
        unset($currentDetailsData['start_date']);

        $currentEndDate = $currentDetailsData['end_date'];
        unset($currentDetailsData['end_date']);

        $hashDetailsData = $this->getHelper('Data')->hashString(
            $this->getHelper('Data')->jsonEncode($currentDetailsData),
            'md5'
        );

        if ($hashDetailsData != $walmartListingProduct->getOnlineDetailsData()) {
            return true;
        }

        if ($currentStartDate != $walmartListingProduct->getOnlineStartDate()) {
            return true;
        }

        if ($currentEndDate != $walmartListingProduct->getOnlineEndDate()) {
            return true;
        }

        return false;
    }

    //########################################
}
