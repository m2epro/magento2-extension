<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Listing\Product\Instruction\SynchronizationTemplate\Checker;

use \Ess\M2ePro\Model\Magento\Product\ChangeProcessor\AbstractModel as ChangeProcessorAbstract;

/**
 * Class \Ess\M2ePro\Model\Ebay\Listing\Product\Instruction\SynchronizationTemplate\Checker\Active
 */
class Active extends AbstractModel
{
    protected $parentFactory;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Factory $parentFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        array $data = []
    ) {
        $this->parentFactory = $parentFactory;
        parent::__construct($activeRecordFactory, $helperFactory, $modelFactory, $data);
    }

    //########################################

    protected function getStopInstructionTypes()
    {
        return [
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

    //########################################

    public function process(array $params = [])
    {
        $scheduledAction = $this->input->getScheduledAction();
        if ($scheduledAction === null) {
            $scheduledAction = $this->activeRecordFactory->getObject('Listing_Product_ScheduledAction');
        }

        if ($this->input->hasInstructionWithTypes($this->getStopInstructionTypes())) {
            if (!$this->isMeetStopRequirements()) {
                if ($scheduledAction->isActionTypeStop() && !$scheduledAction->isForce()) {
                    $this->getScheduledActionManager()->deleteAction($scheduledAction);
                    $scheduledAction->unsetData();
                }
            } else {

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
                } else {
                    if ($scheduledAction->isActionTypeRevise()) {
                        $this->setPropertiesForRecheck($this->getPropertiesDataFromInputScheduledAction());
                    }
                }

                $scheduledAction->addData(
                    [
                        'listing_product_id' => $this->input->getListingProduct()->getId(),
                        'component' => \Ess\M2ePro\Helper\Component\Ebay::NICK,
                        'action_type' => $actionType,
                        'tag' => '/' . implode('/', $tags) . '/',
                        'additional_data' => $this->getHelper('Data')->jsonEncode($additionalData),
                    ]
                );

                if ($scheduledAction->getId()) {
                    $this->getScheduledActionManager()->updateAction($scheduledAction);
                } else {
                    $this->getScheduledActionManager()->addAction($scheduledAction);
                }
            }
        }

        $additionalData = $scheduledAction->getAdditionalData();

        if ($scheduledAction->isActionTypeStop() ||
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
        $configurator = $this->modelFactory->getObject('Ebay_Listing_Product_Action_Configurator');
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

        if ($this->input->hasInstructionWithTypes($this->getRevisePaymentInstructionTypes())) {
            if ($this->isMeetRevisePaymentRequirements()) {
                $configurator->allowPayment();
                $tags['payment'] = true;
            } else {
                $configurator->disallowPayment();
                unset($tags['payment']);
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
                'additional_data' => $this->getHelper('Data')->jsonEncode(
                    [
                        'params' => $params,
                        'configurator' => $configurator->getSerializedData()
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

    public function isMeetStopRequirements()
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
            } else {
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
        }

        if ($ebaySynchronizationTemplate->isStopOutOfStock()) {
            if (!$listingProduct->getMagentoProduct()->isStockAvailability()) {
                return true;
            } else {
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
                    'prefix' => \Ess\M2ePro\Model\Ebay\Template\Synchronization::STOP_ADVANCED_RULES_PREFIX
                ]
            );
            $ruleModel->loadFromSerialized($ebaySynchronizationTemplate->getStopAdvancedRulesFilters());

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

        /** @var \Ess\M2ePro\Model\Ebay\Listing\Product $ebayListingProduct */
        $ebayListingProduct = $listingProduct->getChildObject();

        $ebaySynchronizationTemplate = $ebayListingProduct->getEbaySynchronizationTemplate();

        $configurator = $this->modelFactory->getObject('Ebay_Listing_Product_Action_Configurator');
        $configurator->disableAll()->allowQty()->allowVariations();

        if (!$ebaySynchronizationTemplate->isReviseUpdateQty()) {
            return false;
        }

        $isMaxAppliedValueModeOn = $ebaySynchronizationTemplate->isReviseUpdateQtyMaxAppliedValueModeOn();
        $maxAppliedValue = $ebaySynchronizationTemplate->getReviseUpdateQtyMaxAppliedValue();

        if (!$ebayListingProduct->isVariationsReady()) {
            $productQty = $ebayListingProduct->getQty();
            $channelQty = $ebayListingProduct->getOnlineQty() - $ebayListingProduct->getOnlineQtySold();

            // Check ReviseUpdateQtyMaxAppliedValue
            if ($isMaxAppliedValueModeOn && $productQty > $maxAppliedValue && $channelQty > $maxAppliedValue) {
                return false;
            }

            if ($productQty != $channelQty) {
                return true;
            }
        } else {
            $variations = $listingProduct->getVariations(true);

            foreach ($variations as $variation) {

                /** @var \Ess\M2ePro\Model\Ebay\Listing\Product\Variation $ebayVariation */
                $ebayVariation = $variation->getChildObject();

                $productQty = $ebayVariation->getQty();
                $channelQty = $ebayVariation->getOnlineQty() - $ebayVariation->getOnlineQtySold();

                if ($productQty != $channelQty &&
                    (!$isMaxAppliedValueModeOn || $productQty <= $maxAppliedValue || $channelQty <= $maxAppliedValue)) {
                    return true;
                }
            }
        }

        return false;
    }

    // ---------------------------------------

    public function isMeetRevisePriceRequirements()
    {
        $listingProduct = $this->input->getListingProduct();

        /** @var \Ess\M2ePro\Model\Ebay\Listing\Product $ebayListingProduct */
        $ebayListingProduct = $listingProduct->getChildObject();

        $ebaySynchronizationTemplate = $ebayListingProduct->getEbaySynchronizationTemplate();

        $configurator = $this->modelFactory->getObject('Ebay_Listing_Product_Action_Configurator');
        $configurator->disableAll()->allowPrice()->allowVariations();

        if (!$ebaySynchronizationTemplate->isReviseUpdatePrice()) {
            return false;
        }

        if (!$ebayListingProduct->isVariationsReady()) {
            if ($ebayListingProduct->isListingTypeFixed()) {
                if ($ebayListingProduct->getOnlineCurrentPrice() != $ebayListingProduct->getFixedPrice()) {
                    return true;
                }
            }

            if ($ebayListingProduct->isListingTypeAuction()) {
                if ($ebayListingProduct->getOnlineStartPrice() != $ebayListingProduct->getStartPrice()) {
                    return true;
                }

                if ($ebayListingProduct->getOnlineReservePrice() != $ebayListingProduct->getReservePrice()) {
                    return true;
                }

                if ($ebayListingProduct->getOnlineBuyItNowPrice() != $ebayListingProduct->getBuyItNowPrice()) {
                    return true;
                }
            }
        } else {
            $variations = $listingProduct->getVariations(true);

            foreach ($variations as $variation) {

                /** @var \Ess\M2ePro\Model\Ebay\Listing\Product\Variation $ebayVariation */
                $ebayVariation = $variation->getChildObject();

                if ($ebayVariation->getOnlinePrice() != $ebayVariation->getPrice()) {
                    return true;
                }
            }
        }

        return false;
    }

    // ---------------------------------------

    public function isMeetReviseTitleRequirements()
    {
        $listingProduct = $this->input->getListingProduct();

        /** @var \Ess\M2ePro\Model\Ebay\Listing\Product $ebayListingProduct */
        $ebayListingProduct = $listingProduct->getChildObject();

        $ebaySynchronizationTemplate = $ebayListingProduct->getEbaySynchronizationTemplate();

        if (!$ebaySynchronizationTemplate->isReviseUpdateTitle()) {
            return false;
        }

        $actionDataBuilder = $this->modelFactory->getObject('Ebay_Listing_Product_Action_DataBuilder_Title');
        $actionDataBuilder->setListingProduct($listingProduct);

        $actionData = $actionDataBuilder->getBuilderData();

        if ($actionData['title'] == $ebayListingProduct->getOnlineTitle()) {
            return false;
        }

        return true;
    }

    // ---------------------------------------

    public function isMeetReviseSubtitleRequirements()
    {
        $listingProduct = $this->input->getListingProduct();

        /** @var \Ess\M2ePro\Model\Ebay\Listing\Product $ebayListingProduct */
        $ebayListingProduct = $listingProduct->getChildObject();

        $ebaySynchronizationTemplate = $ebayListingProduct->getEbaySynchronizationTemplate();

        if (!$ebaySynchronizationTemplate->isReviseUpdateSubtitle()) {
            return false;
        }

        $actionDataBuilder = $this->modelFactory->getObject('Ebay_Listing_Product_Action_DataBuilder_Subtitle');
        $actionDataBuilder->setListingProduct($listingProduct);

        $actionData = $actionDataBuilder->getBuilderData();

        if ($actionData['subtitle'] == $ebayListingProduct->getOnlineSubTitle()) {
            return false;
        }

        return true;
    }

    // ---------------------------------------

    public function isMeetReviseDescriptionRequirements()
    {
        $listingProduct = $this->input->getListingProduct();

        /** @var \Ess\M2ePro\Model\Ebay\Listing\Product $ebayListingProduct */
        $ebayListingProduct = $listingProduct->getChildObject();

        $ebaySynchronizationTemplate = $ebayListingProduct->getEbaySynchronizationTemplate();

        if (!$ebaySynchronizationTemplate->isReviseUpdateDescription()) {
            return false;
        }

        $actionDataBuilder = $this->modelFactory->getObject('Ebay_Listing_Product_Action_DataBuilder_Description');
        $actionDataBuilder->setListingProduct($listingProduct);

        $actionData = $actionDataBuilder->getBuilderData();

        $hashDescription = $this->getHelper('Data')->hashString($actionData['description'], 'md5');
        if ($hashDescription == $ebayListingProduct->getOnlineDescription()) {
            return false;
        }

        return true;
    }

    // ---------------------------------------

    public function isMeetReviseImagesRequirements()
    {
        $listingProduct = $this->input->getListingProduct();

        /** @var \Ess\M2ePro\Model\Ebay\Listing\Product $ebayListingProduct */
        $ebayListingProduct = $listingProduct->getChildObject();

        $ebaySynchronizationTemplate = $ebayListingProduct->getEbaySynchronizationTemplate();

        if (!$ebaySynchronizationTemplate->isReviseUpdateImages()) {
            return false;
        }

        $actionDataBuilder = $this->modelFactory->getObject('Ebay_Listing_Product_Action_DataBuilder_Images');
        $actionDataBuilder->setListingProduct($listingProduct);
        $actionDataBuilder->setIsVariationItem($ebayListingProduct->isVariationsReady());

        $hashImagesData = $this->getHelper('Data')->hashString(
            $this->getHelper('Data')->jsonEncode($actionDataBuilder->getBuilderData()),
            'md5'
        );

        if ($hashImagesData == $ebayListingProduct->getOnlineImages()) {
            return false;
        }

        return true;
    }

    // ---------------------------------------

    public function isMeetReviseCategoriesRequirements()
    {
        $listingProduct = $this->input->getListingProduct();

        /** @var \Ess\M2ePro\Model\Ebay\Listing\Product $ebayListingProduct */
        $ebayListingProduct = $listingProduct->getChildObject();

        $ebaySynchronizationTemplate = $ebayListingProduct->getEbaySynchronizationTemplate();

        if (!$ebaySynchronizationTemplate->isReviseUpdateCategories()) {
            return false;
        }

        $actionDataBuilder = $this->modelFactory->getObject('Ebay_Listing_Product_Action_DataBuilder_Categories');
        $actionDataBuilder->setListingProduct($listingProduct);

        if ($actionDataBuilder->getBuilderData() == $ebayListingProduct->getOnlineCategoriesData()) {
            return false;
        }

        return true;
    }

    // ---------------------------------------

    public function isMeetRevisePartsRequirements()
    {
        $listingProduct = $this->input->getListingProduct();

        /** @var \Ess\M2ePro\Model\Ebay\Listing\Product $ebayListingProduct */
        $ebayListingProduct = $listingProduct->getChildObject();

        $ebaySynchronizationTemplate = $ebayListingProduct->getEbaySynchronizationTemplate();

        if (!$ebaySynchronizationTemplate->isReviseUpdateParts()) {
            return false;
        }

        $actionDataBuilder = $this->modelFactory->getObject(
            'Ebay_Listing_Product_Action_DataBuilder_Parts'
        );
        $actionDataBuilder->setListingProduct($listingProduct);

        if ($actionDataBuilder->getHash() == $ebayListingProduct->getData('online_parts_data')) {
            return false;
        }

        return true;
    }

    // ---------------------------------------

    public function isMeetRevisePaymentRequirements()
    {
        $listingProduct = $this->input->getListingProduct();

        /** @var \Ess\M2ePro\Model\Ebay\Listing\Product $ebayListingProduct */
        $ebayListingProduct = $listingProduct->getChildObject();

        $ebaySynchronizationTemplate = $ebayListingProduct->getEbaySynchronizationTemplate();

        if (!$ebaySynchronizationTemplate->isReviseUpdatePayment()) {
            return false;
        }

        $actionDataBuilder = $this->modelFactory->getObject('Ebay_Listing_Product_Action_DataBuilder_Payment');
        $actionDataBuilder->setListingProduct($listingProduct);

        $hashReturnData = $this->getHelper('Data')->hashString(
            $this->getHelper('Data')->jsonEncode($actionDataBuilder->getBuilderData()),
            'md5'
        );

        if ($hashReturnData == $ebayListingProduct->getOnlinePaymentData()) {
            return false;
        }

        return true;
    }

    // ---------------------------------------

    public function isMeetReviseShippingRequirements()
    {
        $listingProduct = $this->input->getListingProduct();

        /** @var \Ess\M2ePro\Model\Ebay\Listing\Product $ebayListingProduct */
        $ebayListingProduct = $listingProduct->getChildObject();

        $ebaySynchronizationTemplate = $ebayListingProduct->getEbaySynchronizationTemplate();

        if (!$ebaySynchronizationTemplate->isReviseUpdateShipping()) {
            return false;
        }

        $actionDataBuilder = $this->modelFactory->getObject('Ebay_Listing_Product_Action_DataBuilder_Shipping');
        $actionDataBuilder->setListingProduct($listingProduct);

        $hashReturnData = $this->getHelper('Data')->hashString(
            $this->getHelper('Data')->jsonEncode($actionDataBuilder->getBuilderData()),
            'md5'
        );

        if ($hashReturnData == $ebayListingProduct->getOnlineShippingData()) {
            return false;
        }

        return true;
    }

    // ---------------------------------------

    public function isMeetReviseReturnRequirements()
    {
        $listingProduct = $this->input->getListingProduct();

        /** @var \Ess\M2ePro\Model\Ebay\Listing\Product $ebayListingProduct */
        $ebayListingProduct = $listingProduct->getChildObject();

        $ebaySynchronizationTemplate = $ebayListingProduct->getEbaySynchronizationTemplate();

        if (!$ebaySynchronizationTemplate->isReviseUpdateReturn()) {
            return false;
        }

        $actionDataBuilder = $this->modelFactory->getObject('Ebay_Listing_Product_Action_DataBuilder_ReturnPolicy');
        $actionDataBuilder->setListingProduct($listingProduct);

        $hashReturnData = $this->getHelper('Data')->hashString(
            $this->getHelper('Data')->jsonEncode($actionDataBuilder->getBuilderData()),
            'md5'
        );

        if ($hashReturnData == $ebayListingProduct->getOnlineReturnData()) {
            return false;
        }

        return true;
    }

    // ---------------------------------------

    public function isMeetReviseOtherRequirements()
    {
        $listingProduct = $this->input->getListingProduct();

        /** @var \Ess\M2ePro\Model\Ebay\Listing\Product $ebayListingProduct */
        $ebayListingProduct = $listingProduct->getChildObject();

        $ebaySynchronizationTemplate = $ebayListingProduct->getEbaySynchronizationTemplate();

        if (!$ebaySynchronizationTemplate->isReviseUpdateOther()) {
            return false;
        }

        $actionDataBuilder = $this->modelFactory->getObject('Ebay_Listing_Product_Action_DataBuilder_Other');
        $actionDataBuilder->setListingProduct($listingProduct);

        $hashOtherData = $this->getHelper('Data')->hashString(
            $this->getHelper('Data')->jsonEncode($actionDataBuilder->getBuilderData()),
            'md5'
        );

        if ($hashOtherData == $ebayListingProduct->getOnlineOtherData()) {
            return false;
        }

        return true;
    }

    //########################################
}
