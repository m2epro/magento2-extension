<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Listing\Product\Instruction\SynchronizationTemplate\Checker;

use Ess\M2ePro\Model\Magento\Product\ChangeProcessor\AbstractModel as ChangeProcessorAbstract;

class Active extends AbstractModel
{
    /** @var \Ess\M2ePro\Model\Ebay\Listing\Product\Action\Type\Revise\Checker */
    private $ebayReviseChecker;
    /** @var \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Factory */
    private $parentFactory;
    /** @var \Ess\M2ePro\Model\Ebay\Listing\Product\Action\ConfiguratorFactory */
    private $configuratorFactory;

    public function __construct(
        \Ess\M2ePro\Model\Ebay\Listing\Product\Action\ConfiguratorFactory $configuratorFactory,
        \Ess\M2ePro\Model\Ebay\Listing\Product\Action\Type\Revise\Checker $ebayReviseChecker,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Factory $parentFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        array $data = []
    ) {
        parent::__construct($activeRecordFactory, $helperFactory, $modelFactory, $data);
        $this->ebayReviseChecker = $ebayReviseChecker;
        $this->parentFactory = $parentFactory;
        $this->configuratorFactory = $configuratorFactory;
    }

    /**
     * @return array
     */
    protected function getStopInstructionTypes(): array
    {
        return [
            ChangeProcessorAbstract::INSTRUCTION_TYPE_PRODUCT_DATA_POTENTIALLY_CHANGED,
            \Ess\M2ePro\Model\Ebay\Template\Synchronization\ChangeProcessor::INSTRUCTION_TYPE_STOP_MODE_ENABLED,
            \Ess\M2ePro\Model\Ebay\Template\Synchronization\ChangeProcessor::INSTRUCTION_TYPE_STOP_MODE_DISABLED,
            \Ess\M2ePro\Model\Ebay\Template\Synchronization\ChangeProcessor::INSTRUCTION_TYPE_STOP_SETTINGS_CHANGED,
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
            \Ess\M2ePro\Model\ChangeTracker\Base\ChangeHolder::INSTRUCTION_TYPE_CHANGE_TRACKER_QTY,
        ];
    }

    /**
     * @return bool
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function isAllowed(): bool
    {
        if (!parent::isAllowed()) {
            return false;
        }

        if (
            !$this->input->hasInstructionWithTypes($this->getStopInstructionTypes()) &&
            !$this->input->hasInstructionWithTypes($this->getReviseInstructionTypes())
        ) {
            return false;
        }

        $listingProduct = $this->input->getListingProduct();

        if ($listingProduct->isHidden()) {
            return false;
        }

        if (!$listingProduct->isRevisable() && !$listingProduct->isStoppable()) {
            return false;
        }

        /** @var \Ess\M2ePro\Model\Ebay\Listing\Product $ebayListingProduct */
        $ebayListingProduct = $listingProduct->getChildObject();

        if (!$ebayListingProduct->isSetCategoryTemplate()) {
            return false;
        }

        return true;
    }

    /**
     * @param array $params
     *
     * @return void
     * @throws \Ess\M2ePro\Model\Exception
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function process(array $params = []): void
    {
        $scheduledAction = $this->input->getScheduledAction();
        if ($scheduledAction === null) {
            /** @var \Ess\M2ePro\Model\Listing\Product\ScheduledAction $scheduledAction */
            $scheduledAction = $this->activeRecordFactory->getObject('Listing_Product_ScheduledAction');
        }

        if ($this->input->hasInstructionWithTypes($this->getStopInstructionTypes())) {
            if ($this->isMeetStopRequirements()) {
                /** @var \Ess\M2ePro\Model\Ebay\Listing\Product $ebayListingProduct */
                $ebayListingProduct = $this->input->getListingProduct()->getChildObject();

                $actionType = \Ess\M2ePro\Model\Listing\Product::ACTION_STOP;

                $additionalData = [
                    'params' => $params,
                ];

                $tags = [];

                if ($ebayListingProduct->isOutOfStockControlEnabled()) {
                    $actionType = \Ess\M2ePro\Model\Listing\Product::ACTION_REVISE;
                    $additionalData['params']['replaced_action'] = \Ess\M2ePro\Model\Listing\Product::ACTION_STOP;

                    $configurator = $this->modelFactory->getObject('Ebay_Listing_Product_Action_Configurator');
                    $configurator->disableAll()->allowQty()->allowVariations();

                    $tags[] = 'qty';

                    $additionalData['configurator'] = $configurator->getSerializedData();
                } elseif ($scheduledAction->isActionTypeRevise()) {
                    $this->setPropertiesForRecheck($this->getPropertiesDataFromInputScheduledAction());
                }

                $scheduledAction->addData(
                    [
                        'listing_product_id' => $this->input->getListingProduct()->getId(),
                        'component' => \Ess\M2ePro\Helper\Component\Ebay::NICK,
                        'action_type' => $actionType,
                        'tag' => '/' . implode('/', $tags) . '/',
                        'additional_data' => \Ess\M2ePro\Helper\Json::encode($additionalData),
                    ]
                );

                if ($scheduledAction->getId()) {
                    $this->getScheduledActionManager()->updateAction($scheduledAction);
                } else {
                    $this->getScheduledActionManager()->addAction($scheduledAction);
                }
            } elseif ($scheduledAction->isActionTypeStop() && !$scheduledAction->isForce()) {
                $this->getScheduledActionManager()->deleteAction($scheduledAction);
                $scheduledAction->unsetData();
            }
        }

        $additionalData = $scheduledAction->getAdditionalData();

        if (
            $scheduledAction->isActionTypeStop() ||
            ($scheduledAction->isActionTypeRevise() &&
                isset($additionalData['params']['replaced_action']) &&
                $additionalData['params']['replaced_action'] == \Ess\M2ePro\Model\Listing\Product::ACTION_STOP)
        ) {
            if ($this->input->hasInstructionWithTypes($this->getReviseInstructionTypes())) {
                $this->setPropertiesForRecheck($this->getPropertiesDataFromInputInstructions());
            }

            return;
        }

        /** @var \Ess\M2ePro\Model\Ebay\Listing\Product\Action\Configurator $configurator */
        $configurator = $this->configuratorFactory->create();
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

        if ($this->input->hasInstructionWithTypes($this->getReviseQtyInstructionTypes())) {
            if ($this->isMeetReviseQtyRequirements()) {
                $configurator->allowQty()->allowVariations();
                $tags['qty'] = true;
            } else {
                $configurator->disallowQty();
                unset($tags['qty']);
            }
        }

        if ($this->input->hasInstructionWithTypes($this->getRevisePriceInstructionTypes())) {
            if ($this->isMeetRevisePriceRequirements()) {
                $configurator->allowPrice()->allowVariations();
                $tags['price'] = true;
            } else {
                $configurator->disallowPrice();
                unset($tags['price']);
            }
        }

        if ($this->input->hasInstructionWithTypes($this->getReviseTitleInstructionTypes())) {
            if ($this->isMeetReviseTitleRequirements()) {
                $configurator->allowTitle();
                $tags['title'] = true;
            } else {
                $configurator->disallowTitle();
                unset($tags['title']);
            }
        }

        if ($this->input->hasInstructionWithTypes($this->getReviseSubtitleInstructionTypes())) {
            if ($this->isMeetReviseSubtitleRequirements()) {
                $configurator->allowSubtitle();
                $tags['subtitle'] = true;
            } else {
                $configurator->disallowSubtitle();
                unset($tags['subtitle']);
            }
        }

        if ($this->input->hasInstructionWithTypes($this->getReviseDescriptionInstructionTypes())) {
            if ($this->isMeetReviseDescriptionRequirements()) {
                $configurator->allowDescription();
                $tags['description'] = true;
            } else {
                $configurator->disallowDescription();
                unset($tags['description']);
            }
        }

        if ($this->input->hasInstructionWithTypes($this->getReviseImagesInstructionTypes())) {
            if ($this->isMeetReviseImagesRequirements()) {
                $configurator->allowImages();

                if ($this->input->hasInstructionWithTypes($this->getReviseVariationImagesInstructionTypes())) {
                    $configurator->allowVariations();
                }

                $tags['images'] = true;
            } else {
                $configurator->disallowImages();
                unset($tags['images']);
            }
        }

        if ($this->input->hasInstructionWithTypes($this->getReviseCategoriesInstructionTypes())) {
            if ($this->isMeetReviseCategoriesRequirements()) {
                $configurator->allowCategories();
                $tags['categories'] = true;
            } else {
                $configurator->disallowCategories();
                unset($tags['categories']);
            }
        }

        if ($this->input->hasInstructionWithTypes($this->getRevisePartsInstructionTypes())) {
            if ($this->isMeetRevisePartsRequirements()) {
                $configurator->allowParts();
                $tags['parts'] = true;
            } else {
                $configurator->disallowParts();
                unset($tags['parts']);
            }
        }

        if ($this->input->hasInstructionWithTypes($this->getReviseShippingInstructionTypes())) {
            if ($this->isMeetReviseShippingRequirements()) {
                $configurator->allowShipping();
                $tags['shipping'] = true;
            } else {
                $configurator->disallowShipping();
                unset($tags['shipping']);
            }
        }

        if ($this->input->hasInstructionWithTypes($this->getReviseReturnInstructionTypes())) {
            if ($this->isMeetReviseReturnRequirements()) {
                $configurator->allowReturn();
                $tags['return'] = true;
            } else {
                $configurator->disallowReturn();
                unset($tags['return']);
            }
        }

        if ($this->input->hasInstructionWithTypes($this->getReviseOtherInstructionTypes())) {
            if ($this->isMeetReviseOtherRequirements()) {
                $configurator->allowOther();
                $tags['other'] = true;
            } else {
                $configurator->disallowOther();
                unset($tags['other']);
            }
        }

        $types = $configurator->getAllowedDataTypes();
        if (empty($types) || (count($types) == 1 && $configurator->isVariationsAllowed())) {
            if ($scheduledAction->getId()) {
                $this->getScheduledActionManager()->deleteAction($scheduledAction);
            }

            return;
        }

        $tags = array_keys($tags);

        $scheduledAction->addData(
            [
                'listing_product_id' => $this->input->getListingProduct()->getId(),
                'component' => \Ess\M2ePro\Helper\Component\Ebay::NICK,
                'action_type' => \Ess\M2ePro\Model\Listing\Product::ACTION_REVISE,
                'tag' => '/' . implode('/', $tags) . '/',
                'additional_data' => \Ess\M2ePro\Helper\Json::encode(
                    [
                        'params' => $params,
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

    /**
     * @return bool
     * @throws \Ess\M2ePro\Model\Exception
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function isMeetStopRequirements(): bool
    {
        $listingProduct = $this->input->getListingProduct();

        /** @var \Ess\M2ePro\Model\Ebay\Listing\Product $ebayListingProduct */
        $ebayListingProduct = $listingProduct->getChildObject();

        $ebaySynchronizationTemplate = $ebayListingProduct->getEbaySynchronizationTemplate();

        if (!$ebaySynchronizationTemplate->isStopMode()) {
            return false;
        }

        if (!$ebayListingProduct->isSetCategoryTemplate()) {
            return false;
        }

        $variationResource = $this->parentFactory->getObject(
            \Ess\M2ePro\Helper\Component\Ebay::NICK,
            'Listing_Product_Variation'
        )->getResource();

        if ($ebaySynchronizationTemplate->isStopStatusDisabled()) {
            if (!$listingProduct->getMagentoProduct()->isStatusEnabled()) {
                return true;
            }

            if ($ebayListingProduct->isVariationsReady()) {
                $temp = $variationResource->isAllStatusesDisabled(
                    $listingProduct->getId(),
                    $listingProduct->getListing()->getStoreId()
                );

                if ($temp !== null && $temp) {
                    return true;
                }
            }
        }

        if ($ebaySynchronizationTemplate->isStopOutOfStock()) {
            if (!$listingProduct->getMagentoProduct()->isStockAvailability()) {
                return true;
            }

            if ($ebayListingProduct->isVariationsReady()) {
                $temp = $variationResource->isAllDoNotHaveStockAvailabilities(
                    $listingProduct->getId(),
                    $listingProduct->getListing()->getStoreId()
                );

                if ($temp !== null && $temp) {
                    return true;
                }
            }
        }

        if ($ebaySynchronizationTemplate->isStopWhenQtyCalculatedHasValue()) {
            $productQty = (int)$ebayListingProduct->getQty();
            $minQty = (int)$ebaySynchronizationTemplate->getStopWhenQtyCalculatedHasValueMin();

            if ($productQty <= $minQty) {
                return true;
            }
        }

        if ($ebaySynchronizationTemplate->isStopAdvancedRulesEnabled()) {
            $ruleModel = $this->activeRecordFactory->getObject('Magento_Product_Rule')->setData(
                [
                    'store_id' => $listingProduct->getListing()->getStoreId(),
                    'prefix' => \Ess\M2ePro\Model\Ebay\Template\Synchronization::STOP_ADVANCED_RULES_PREFIX,
                ]
            );
            $ruleModel->loadFromSerialized($ebaySynchronizationTemplate->getStopAdvancedRulesFilters());

            if ($ruleModel->validate($listingProduct->getMagentoProduct()->getProduct())) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return bool
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function isMeetReviseQtyRequirements(): bool
    {
        return $this->ebayReviseChecker->isNeedReviseForQty(
            $this->input->getListingProduct()->getChildObject()
        );
    }

    /**
     * @return bool
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function isMeetRevisePriceRequirements(): bool
    {
        return $this->ebayReviseChecker->isNeedReviseForPrice(
            $this->input->getListingProduct()->getChildObject()
        );
    }

    /**
     * @return bool
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function isMeetReviseTitleRequirements(): bool
    {
        return $this->ebayReviseChecker->isNeedReviseForTitle(
            $this->input->getListingProduct()->getChildObject()
        );
    }

    /**
     * @return bool
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function isMeetReviseSubtitleRequirements(): bool
    {
        return $this->ebayReviseChecker->isNeedReviseForTitle(
            $this->input->getListingProduct()->getChildObject()
        );
    }

    /**
     * @return bool
     * @throws \Ess\M2ePro\Model\Exception
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function isMeetReviseDescriptionRequirements(): bool
    {
        return $this->ebayReviseChecker->isNeedReviseForDescription(
            $this->input->getListingProduct()->getChildObject()
        );
    }

    /**
     * @return bool
     * @throws \Ess\M2ePro\Model\Exception
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function isMeetReviseImagesRequirements(): bool
    {
        return $this->ebayReviseChecker->isNeedReviseForImages(
            $this->input->getListingProduct()->getChildObject()
        );
    }

    /**
     * @return bool
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function isMeetReviseCategoriesRequirements(): bool
    {
        return $this->ebayReviseChecker->isNeedReviseForCategories(
            $this->input->getListingProduct()->getChildObject()
        );
    }

    /**
     * @return bool
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function isMeetRevisePartsRequirements(): bool
    {
        return $this->ebayReviseChecker->isNeedReviseForParts(
            $this->input->getListingProduct()->getChildObject()
        );
    }

    /**
     * @return bool
     * @throws \Ess\M2ePro\Model\Exception
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function isMeetReviseShippingRequirements(): bool
    {
        return $this->ebayReviseChecker->isNeedReviseForShipping(
            $this->input->getListingProduct()->getChildObject()
        );
    }

    /**
     * @return bool
     * @throws \Ess\M2ePro\Model\Exception
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function isMeetReviseReturnRequirements(): bool
    {
        return $this->ebayReviseChecker->isNeedReviseForReturn(
            $this->input->getListingProduct()->getChildObject()
        );
    }

    /**
     * @return bool
     * @throws \Ess\M2ePro\Model\Exception
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function isMeetReviseOtherRequirements(): bool
    {
        return $this->ebayReviseChecker->isNeedReviseForOther(
            $this->input->getListingProduct()->getChildObject()
        );
    }
}
