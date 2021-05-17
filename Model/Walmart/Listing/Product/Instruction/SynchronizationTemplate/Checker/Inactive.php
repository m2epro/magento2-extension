<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Walmart\Listing\Product\Instruction\SynchronizationTemplate\Checker;

use \Ess\M2ePro\Model\Walmart\Template\Synchronization\ChangeProcessor as SynchronizationChangeProcessor;
use \Ess\M2ePro\Model\Magento\Product\ChangeProcessor\AbstractModel as ChangeProcessorAbstract;

/**
 * Class \Ess\M2ePro\Model\Walmart\Listing\Product\Instruction\SynchronizationTemplate\Checker\Inactive
 */
class Inactive extends AbstractModel
{
    //########################################

    protected function getRelistInstructionTypes()
    {
        return [
            SynchronizationChangeProcessor::INSTRUCTION_TYPE_RELIST_MODE_ENABLED,
            SynchronizationChangeProcessor::INSTRUCTION_TYPE_RELIST_MODE_DISABLED,
            SynchronizationChangeProcessor::INSTRUCTION_TYPE_RELIST_SETTINGS_CHANGED,
            ChangeProcessorAbstract::INSTRUCTION_TYPE_PRODUCT_QTY_DATA_POTENTIALLY_CHANGED,
            ChangeProcessorAbstract::INSTRUCTION_TYPE_PRODUCT_STATUS_DATA_POTENTIALLY_CHANGED,
            \Ess\M2ePro\Model\Walmart\Magento\Product\ChangeProcessor::INSTRUCTION_TYPE_PROMOTIONS_DATA_CHANGED,
            \Ess\M2ePro\Model\Listing::INSTRUCTION_TYPE_PRODUCT_MOVED_FROM_OTHER,
            \Ess\M2ePro\Model\Listing::INSTRUCTION_TYPE_PRODUCT_MOVED_FROM_LISTING,
            \Ess\M2ePro\Model\Listing::INSTRUCTION_TYPE_PRODUCT_REMAP_FROM_LISTING,
            \Ess\M2ePro\Model\Walmart\Listing\Product::INSTRUCTION_TYPE_CHANNEL_QTY_CHANGED,
            \Ess\M2ePro\Model\Walmart\Listing\Product::INSTRUCTION_TYPE_CHANNEL_STATUS_CHANGED,
            \Ess\M2ePro\Model\Walmart\Listing\Product\Action\Type\ListAction\Response::INSTRUCTION_TYPE_CHECK_QTY,
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
        if (!$this->input->hasInstructionWithTypes($this->getRelistInstructionTypes()) &&
            !$this->input->hasInstructionWithTypes($this->getReviseInstructionTypes())
        ) {
            return false;
        }

        $listingProduct = $this->input->getListingProduct();

        /** @var \Ess\M2ePro\Model\Walmart\Listing\Product $walmartListingProduct */
        $walmartListingProduct = $listingProduct->getChildObject();

        $variationManager = $walmartListingProduct->getVariationManager();

        if ($variationManager->isVariationProduct()) {
            if ($variationManager->isRelationParentType()) {
                return false;
            }

            if (!$variationManager->getTypeModel()->isVariationProductMatched()) {
                return false;
            }
        }

        if ($listingProduct->isBlocked() && $walmartListingProduct->isOnlinePriceInvalid()) {
            return true;
        }

        if (!$listingProduct->isRelistable() || !$listingProduct->isStopped()) {
            return false;
        }

        return true;
    }

    //########################################

    public function process(array $params = [])
    {
        if ($this->input->hasInstructionWithTypes($this->getReviseInstructionTypes())) {
            $this->setPropertiesForRecheck($this->getPropertiesDataFromInputInstructions());
        }

        if (!$this->input->hasInstructionWithTypes($this->getRelistInstructionTypes())) {
            return;
        }

        if (!$this->isMeetRelistRequirements()) {
            if ($this->input->getScheduledAction() && !$this->input->getScheduledAction()->isForce()) {
                $this->getScheduledActionManager()->deleteAction($this->input->getScheduledAction());
            }

            return;
        }

        /** @var \Ess\M2ePro\Model\Walmart\Listing\Product $walmartListingProduct */
        $walmartListingProduct = $this->input->getListingProduct()->getChildObject();
        $walmartSynchronizationTemplate = $walmartListingProduct->getWalmartSynchronizationTemplate();

        /** @var  \Ess\M2ePro\Model\Walmart\Listing\Product\Action\Configurator $configurator */
        $configurator = $this->modelFactory->getObject('Walmart_Listing_Product_Action_Configurator');
        $configurator->disableAll()->allowQty();

        $tags = ['qty'];

        if ($walmartSynchronizationTemplate->isReviseUpdatePrice() ||
            ($this->input->getListingProduct()->isBlocked() && $walmartListingProduct->isOnlinePriceInvalid())
        ) {
            $configurator->allowPrice();
            $tags[] = 'price';
        }

        if ($walmartSynchronizationTemplate->isReviseUpdatePromotions()) {
            // Due to the fact that "promotion feed" can be sent only 6 times a day,
            // we are forced to refuse on relist action.
            $this->setPropertiesForRecheck(['promotions']);
        }

        $this->checkUpdatePriceOrPromotionsFeedsLock(
            $configurator,
            $tags,
            \Ess\M2ePro\Model\Listing\Log::ACTION_RELIST_PRODUCT_ON_COMPONENT
        );

        $scheduledAction = $this->input->getScheduledAction();
        if ($scheduledAction === null) {
            $scheduledAction = $this->activeRecordFactory->getObject('Listing_Product_ScheduledAction');
        }

        $scheduledAction->addData(
            [
                'listing_product_id' => $this->input->getListingProduct()->getId(),
                'component'          => \Ess\M2ePro\Helper\Component\Walmart::NICK,
                'action_type'        => \Ess\M2ePro\Model\Listing\Product::ACTION_RELIST,
                'tag'                => '/' . implode('/', $tags) . '/',
                'additional_data'    => $this->getHelper('Data')->jsonEncode(
                    [
                        'params'       => $params,
                        'configurator' => $configurator->getSerializedData(),
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

    public function isMeetRelistRequirements()
    {
        $listingProduct = $this->input->getListingProduct();

        /** @var \Ess\M2ePro\Model\Walmart\Listing\Product $walmartListingProduct */
        $walmartListingProduct = $listingProduct->getChildObject();
        $variationManager = $walmartListingProduct->getVariationManager();

        $walmartSynchronizationTemplate = $walmartListingProduct->getWalmartSynchronizationTemplate();

        if (!$walmartSynchronizationTemplate->isRelistMode()) {
            return false;
        }

        if ($walmartSynchronizationTemplate->isRelistFilterUserLock() &&
            $listingProduct->getStatusChanger() == \Ess\M2ePro\Model\Listing\Product::STATUS_CHANGER_USER) {
            return false;
        }

        if (!$walmartListingProduct->isExistCategoryTemplate()) {
            return false;
        }

        $variationResource = $this->activeRecordFactory->getObject('Listing_Product_Variation')->getResource();

        if ($walmartSynchronizationTemplate->isRelistStatusEnabled()) {
            if (!$listingProduct->getMagentoProduct()->isStatusEnabled()) {
                return false;
            } elseif ($variationManager->isPhysicalUnit() &&
                $variationManager->getTypeModel()->isVariationProductMatched()
            ) {
                $temp = $variationResource->isAllStatusesDisabled(
                    $listingProduct->getId(),
                    $listingProduct->getListing()->getStoreId()
                );

                if ($temp !== null && $temp) {
                    return false;
                }
            }
        }

        if ($walmartSynchronizationTemplate->isRelistIsInStock()) {
            if (!$listingProduct->getMagentoProduct()->isStockAvailability()) {
                return false;
            } elseif ($variationManager->isPhysicalUnit() &&
                $variationManager->getTypeModel()->isVariationProductMatched()
            ) {
                $temp = $variationResource->isAllDoNotHaveStockAvailabilities(
                    $listingProduct->getId(),
                    $listingProduct->getListing()->getStoreId()
                );

                if ($temp !== null && $temp) {
                    return false;
                }
            }
        }

        if ($walmartSynchronizationTemplate->isRelistWhenQtyCalculatedHasValue()) {
            $result = false;
            $productQty = (int)$walmartListingProduct->getQty(false);
            $minQty = (int)$walmartSynchronizationTemplate->getRelistWhenQtyCalculatedHasValueMin();

            if ($productQty >= $minQty) {
                $result = true;
            }

            if (!$result) {
                return false;
            }
        }

        if ($walmartSynchronizationTemplate->isRelistAdvancedRulesEnabled()) {
            $ruleModel = $this->activeRecordFactory->getObject('Magento_Product_Rule')->setData(
                [
                    'store_id' => $listingProduct->getListing()->getStoreId(),
                    'prefix'   => \Ess\M2ePro\Model\Walmart\Template\Synchronization::RELIST_ADVANCED_RULES_PREFIX
                ]
            );
            $ruleModel->loadFromSerialized($walmartSynchronizationTemplate->getRelistAdvancedRulesFilters());

            if (!$ruleModel->validate($listingProduct->getMagentoProduct()->getProduct())) {
                return false;
            }
        }

        return true;
    }

    //########################################
}
