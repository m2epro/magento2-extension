<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Listing\Product\Instruction\SynchronizationTemplate\Checker;

use \Ess\M2ePro\Model\Magento\Product\ChangeProcessor\AbstractModel as ChangeProcessorAbstract;

/**
 * Class \Ess\M2ePro\Model\Ebay\Listing\Product\Instruction\SynchronizationTemplate\Checker\Inactive
 */
class Inactive extends AbstractModel
{
    protected $parentFactory;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Factory $parentFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        array $data = []
    ) {
        $this->parentFactory = $parentFactory;
        parent::__construct($activeRecordFactory, $helperFactory, $modelFactory, $data);
    }

    //########################################

    protected function getRelistInstructionTypes()
    {
        return [
            \Ess\M2ePro\Model\Ebay\Template\Synchronization\ChangeProcessor::INSTRUCTION_TYPE_RELIST_MODE_ENABLED,
            \Ess\M2ePro\Model\Ebay\Template\Synchronization\ChangeProcessor::INSTRUCTION_TYPE_RELIST_MODE_DISABLED,
            \Ess\M2ePro\Model\Ebay\Template\Synchronization\ChangeProcessor::INSTRUCTION_TYPE_RELIST_SETTINGS_CHANGED,
            ChangeProcessorAbstract::INSTRUCTION_TYPE_PRODUCT_QTY_DATA_POTENTIALLY_CHANGED,
            ChangeProcessorAbstract::INSTRUCTION_TYPE_PRODUCT_STATUS_DATA_POTENTIALLY_CHANGED,
            \Ess\M2ePro\Model\Listing::INSTRUCTION_TYPE_PRODUCT_MOVED_FROM_OTHER,
            \Ess\M2ePro\Model\Listing::INSTRUCTION_TYPE_PRODUCT_MOVED_FROM_LISTING,
            \Ess\M2ePro\Model\Listing::INSTRUCTION_TYPE_PRODUCT_REMAP_FROM_LISTING,
            \Ess\M2ePro\Model\Ebay\Listing\Product::INSTRUCTION_TYPE_CHANNEL_QTY_CHANGED,
            \Ess\M2ePro\Model\Ebay\Listing\Product::INSTRUCTION_TYPE_CHANNEL_STATUS_CHANGED,
            \Ess\M2ePro\Model\Ebay\Template\ChangeProcessor\ChangeProcessorAbstract::INSTRUCTION_TYPE_QTY_DATA_CHANGED,
            \Ess\M2ePro\PublicServices\Product\SqlChange::INSTRUCTION_TYPE_PRODUCT_CHANGED,
            \Ess\M2ePro\PublicServices\Product\SqlChange::INSTRUCTION_TYPE_STATUS_CHANGED,
            \Ess\M2ePro\PublicServices\Product\SqlChange::INSTRUCTION_TYPE_QTY_CHANGED,
            \Ess\M2ePro\Model\Magento\Product\ChangeProcessor\AbstractModel::
            INSTRUCTION_TYPE_MAGMI_PLUGIN_PRODUCT_CHANGED,
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

        if (!$listingProduct->isRelistable() && !$listingProduct->isHidden()) {
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

        /** @var \Ess\M2ePro\Model\Ebay\Listing\Product $ebayListingProduct */
        $ebayListingProduct = $this->input->getListingProduct()->getChildObject();
        $ebaySynchronizationTemplate = $ebayListingProduct->getEbaySynchronizationTemplate();

        $configurator = $this->modelFactory->getObject('Ebay_Listing_Product_Action_Configurator');
        $configurator->disableAll()->allowQty()->allowVariations();

        $tags = ['qty'];

        if ($ebaySynchronizationTemplate->isReviseUpdatePrice()) {
            $configurator->allowPrice();
            $tags[] = 'price';
        }

        $scheduledAction = $this->input->getScheduledAction();
        if ($scheduledAction === null) {
            $scheduledAction = $this->activeRecordFactory->getObject('Listing_Product_ScheduledAction');
        }

        $actionType = \Ess\M2ePro\Model\Listing\Product::ACTION_RELIST;

        if ($this->input->getListingProduct()->isHidden()) {
            $actionType = \Ess\M2ePro\Model\Listing\Product::ACTION_REVISE;
            $params['replaced_action'] = \Ess\M2ePro\Model\Listing\Product::ACTION_RELIST;
        }

        $scheduledAction->addData(
            [
                'listing_product_id' => $this->input->getListingProduct()->getId(),
                'component' => \Ess\M2ePro\Helper\Component\Ebay::NICK,
                'action_type' => $actionType,
                'tag' => '/' . implode('/', $tags) . '/',
                'additional_data' => $this->getHelper('Data')->jsonEncode(
                    [
                        'params' => $params,
                        'configurator' => $configurator->getSerializedData(),
                    ]
                )
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

        /** @var \Ess\M2ePro\Model\Ebay\Listing\Product $ebayListingProduct */
        $ebayListingProduct = $listingProduct->getChildObject();

        $ebaySynchronizationTemplate = $ebayListingProduct->getEbaySynchronizationTemplate();

        // Correct synchronization
        // ---------------------------------------
        if (!$ebaySynchronizationTemplate->isRelistMode()) {
            return false;
        }

        if ($listingProduct->isStopped() &&
            $ebaySynchronizationTemplate->isRelistFilterUserLock() &&
            $listingProduct->getStatusChanger() == \Ess\M2ePro\Model\Listing\Product::STATUS_CHANGER_USER
        ) {
            return false;
        }

        if (!$ebayListingProduct->isSetCategoryTemplate()) {
            return false;
        }

        // ---------------------------------------

        $variationResource = $this->parentFactory->getObject(
            \Ess\M2ePro\Helper\Component\Ebay::NICK,
            'Listing_Product_Variation'
        )->getResource();

        // Check filters
        // ---------------------------------------
        if ($ebaySynchronizationTemplate->isRelistStatusEnabled()) {
            if (!$listingProduct->getMagentoProduct()->isStatusEnabled()) {
                return false;
            } else {
                if ($ebayListingProduct->isVariationsReady()) {
                    $temp = $variationResource->isAllStatusesDisabled(
                        $listingProduct->getId(),
                        $listingProduct->getListing()->getStoreId()
                    );

                    if ($temp !== null && $temp) {
                        return false;
                    }
                }
            }
        }

        if ($ebaySynchronizationTemplate->isRelistIsInStock()) {
            if (!$listingProduct->getMagentoProduct()->isStockAvailability()) {
                return false;
            } else {
                if ($ebayListingProduct->isVariationsReady()) {
                    $temp = $variationResource->isAllDoNotHaveStockAvailabilities(
                        $listingProduct->getId(),
                        $listingProduct->getListing()->getStoreId()
                    );

                    if ($temp !== null && $temp) {
                        return false;
                    }
                }
            }
        }

        if ($ebaySynchronizationTemplate->isRelistWhenQtyCalculatedHasValue()) {
            $result = false;
            $productQty = (int)$ebayListingProduct->getQty();
            $minQty = (int)$ebaySynchronizationTemplate->getRelistWhenQtyCalculatedHasValueMin();

            if ($productQty >= $minQty) {
                $result = true;
            }

            if (!$result) {
                return false;
            }
        }

        if ($ebaySynchronizationTemplate->isRelistAdvancedRulesEnabled()) {
            $ruleModel = $this->activeRecordFactory->getObject('Magento_Product_Rule')->setData(
                [
                    'store_id' => $listingProduct->getListing()->getStoreId(),
                    'prefix' => \Ess\M2ePro\Model\Ebay\Template\Synchronization::RELIST_ADVANCED_RULES_PREFIX
                ]
            );
            $ruleModel->loadFromSerialized($ebaySynchronizationTemplate->getRelistAdvancedRulesFilters());

            if (!$ruleModel->validate($listingProduct->getMagentoProduct()->getProduct())) {
                return false;
            }
        }

        return true;
    }

    //########################################
}
