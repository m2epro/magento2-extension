<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Walmart\Synchronization\Templates\Synchronization;

use Ess\M2ePro\Model\Listing\Product as ListingProduct;
use Ess\M2ePro\Model\Walmart\Template\Synchronization as SynchronizationPolicy;

/**
 * Class \Ess\M2ePro\Model\Walmart\Synchronization\Templates\Synchronization\Inspector
 */
class Inspector extends \Ess\M2ePro\Model\Synchronization\Templates\Synchronization\Inspector
{
    private $walmartFactory;
    private $magentoProductCollectionFactory;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $magentoProductCollectionFactory,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Walmart\Factory $walmartFactory
    ) {
        parent::__construct($activeRecordFactory, $helperFactory, $modelFactory);

        $this->magentoProductCollectionFactory = $magentoProductCollectionFactory;
        $this->walmartFactory = $walmartFactory;
    }

    //########################################

    /**
     * @param \Ess\M2ePro\Model\Listing\Product $listingProduct
     * @param bool $needSynchRulesCheckIfLocked
     * @return bool
     * @throws \Ess\M2ePro\Model\Exception
     * @throws \Ess\M2ePro\Model\Exception\Logic
     * @throws \Exception
     */
    public function isMeetListRequirements(ListingProduct $listingProduct, $needSynchRulesCheckIfLocked = true)
    {
        if (!$listingProduct->isListable() || !$listingProduct->isNotListed()) {
            return false;
        }

        /** @var \Ess\M2ePro\Model\Walmart\Listing\Product $walmartListingProduct */
        $walmartListingProduct = $listingProduct->getChildObject();
        $variationManager = $walmartListingProduct->getVariationManager();

        if ($variationManager->isVariationProduct()) {
            if ($variationManager->isPhysicalUnit() &&
                !$variationManager->getTypeModel()->isVariationProductMatched()
            ) {
                return false;
            }

            if ($variationManager->isRelationParentType()) {
                return false;
            }
        }

        $walmartSynchronizationTemplate = $walmartListingProduct->getWalmartSynchronizationTemplate();

        if (!$walmartSynchronizationTemplate->isListMode()) {
            return false;
        }

        if (!$walmartListingProduct->isExistCategoryTemplate()) {
            return false;
        }

        if ($listingProduct->isSetProcessingLock('in_action')) {
            if ($needSynchRulesCheckIfLocked) {
                $this->activeRecordFactory->getObject('Listing\Product')
                    ->getResource()->setNeedSynchRulesCheck([$listingProduct->getId()]);
            }
            return false;
        }

        $variationResource = $this->activeRecordFactory->getObject('Listing_Product_Variation')->getResource();

        $additionalData = $listingProduct->getAdditionalData();

        $log = $this->getHelper('Module\Log');

        if ($walmartSynchronizationTemplate->isListStatusEnabled()) {
            if (!$listingProduct->getMagentoProduct()->isStatusEnabled()) {
                $note = $log->encodeDescription(
                    'Product was not automatically Listed according to the List Rules in Synchronization Policy.
                     Status of Magento Product is Disabled (%date%) though in Synchronization Rules “Product Status”
                     is set to Enabled.',
                    ['date' => $this->getHelper('Data')->getCurrentGmtDate()]
                );
                $additionalData['synch_template_list_rules_note'] = $note;

                $listingProduct->setSettings('additional_data', $additionalData)->save();

                return false;
            } elseif ($variationManager->isPhysicalUnit() &&
                       $variationManager->getTypeModel()->isVariationProductMatched()
            ) {
                $temp = $variationResource->isAllStatusesDisabled(
                    $listingProduct->getId(),
                    $listingProduct->getListing()->getStoreId()
                );

                if ($temp !== null && $temp) {
                    $note = $log->encodeDescription(
                        'Product was not automatically Listed according to the List Rules in Synchronization Policy.
                         Status of Magento Product Variation is Disabled (%date%) though in Synchronization Rules
                         “Product Status“ is set to Enabled.',
                        ['date' => $this->getHelper('Data')->getCurrentGmtDate()]
                    );
                    $additionalData['synch_template_list_rules_note'] = $note;

                    $listingProduct->setSettings('additional_data', $additionalData)->save();

                    return false;
                }
            }
        }

        if ($walmartSynchronizationTemplate->isListIsInStock()) {
            if (!$listingProduct->getMagentoProduct()->isStockAvailability()) {
                $note = $log->encodeDescription(
                    'Product was not automatically Listed according to the List Rules in Synchronization Policy.
                     Stock Availability of Magento Product is Out of Stock though in
                     Synchronization Rules “Stock Availability” is set to In Stock.',
                    ['date' => $this->getHelper('Data')->getCurrentGmtDate()]
                );
                $additionalData['synch_template_list_rules_note'] = $note;

                $listingProduct->setSettings('additional_data', $additionalData)->save();

                return false;
            } elseif ($variationManager->isPhysicalUnit() &&
                       $variationManager->getTypeModel()->isVariationProductMatched()
            ) {
                $temp = $variationResource->isAllDoNotHaveStockAvailabilities(
                    $listingProduct->getId(),
                    $listingProduct->getListing()->getStoreId()
                );

                if ($temp !== null && $temp) {
                    $note = $log->encodeDescription(
                        'Product was not automatically Listed according to the List Rules in Synchronization Policy.
                         Stock Availability of Magento Product Variation is Out of Stock though
                         in Synchronization Rules “Stock Availability” is set to In Stock.',
                        ['date' => $this->getHelper('Data')->getCurrentGmtDate()]
                    );
                    $additionalData['synch_template_list_rules_note'] = $note;

                    $listingProduct->setSettings('additional_data', $additionalData)->save();

                    return false;
                }
            }
        }

        if ($walmartSynchronizationTemplate->isListWhenQtyMagentoHasValue()) {
            $result = false;

            if ($variationManager->isRelationParentType()) {
                $productQty = (int)$listingProduct->getMagentoProduct()->getQty(true);
            } else {
                $productQty = (int)$walmartListingProduct->getQty(true);
            }

            $typeQty = (int)$walmartSynchronizationTemplate->getListWhenQtyMagentoHasValueType();
            $minQty = (int)$walmartSynchronizationTemplate->getListWhenQtyMagentoHasValueMin();
            $maxQty = (int)$walmartSynchronizationTemplate->getListWhenQtyMagentoHasValueMax();

            $note = '';

            if ($typeQty == \Ess\M2ePro\Model\Walmart\Template\Synchronization::LIST_QTY_LESS) {
                if ($productQty <= $minQty) {
                    $result = true;
                } else {
                    $note = $log->encodeDescription(
                        'Product was not automatically Listed according to the List Rules in Synchronization Policy.
                         Quantity of Magento Product is %product_qty% though in Synchronization Rules
                         “Magento Quantity“ is set to less then  %template_min_qty%.',
                        [
                            '!template_min_qty' => $minQty,
                            '!product_qty' => $productQty,
                            '!date' => $this->getHelper('Data')->getCurrentGmtDate()
                        ]
                    );
                }
            }

            if ($typeQty == \Ess\M2ePro\Model\Walmart\Template\Synchronization::LIST_QTY_MORE) {
                if ($productQty >= $minQty) {
                    $result = true;
                } else {
                    $note = $log->encodeDescription(
                        'Product was not automatically Listed according to the List Rules in Synchronization Policy.
                         Quantity of Magento Product is %product_qty% though in Synchronization Rules
                         “Magento Quantity” is set to more then  %template_min_qty%.',
                        [
                            '!template_min_qty' => $minQty,
                            '!product_qty' => $productQty,
                            '!date' => $this->getHelper('Data')->getCurrentGmtDate()
                        ]
                    );
                }
            }

            if ($typeQty == \Ess\M2ePro\Model\Walmart\Template\Synchronization::LIST_QTY_BETWEEN) {
                if ($productQty >= $minQty && $productQty <= $maxQty) {
                    $result = true;
                } else {
                    $note = $log->encodeDescription(
                        'Product was not automatically Listed according to the List Rules in Synchronization Policy.
                         Quantity of Magento Product is %product_qty% though in Synchronization Rules
                         “Magento Quantity” is set between  %template_min_qty% and %template_max_qty%.',
                        [
                            '!template_min_qty' => $minQty,
                            '!template_max_qty' => $maxQty,
                            '!product_qty' => $productQty,
                            '!date' => $this->getHelper('Data')->getCurrentGmtDate()
                        ]
                    );
                }
            }

            if (!$result) {
                if (!empty($note)) {
                    $additionalData['synch_template_list_rules_note'] = $note;
                    $listingProduct->setSettings('additional_data', $additionalData)->save();
                }

                return false;
            }
        }

        if ($walmartSynchronizationTemplate->isListWhenQtyCalculatedHasValue() &&
            !$variationManager->isRelationParentType()
        ) {
            $result = false;
            $productQty = (int)$walmartListingProduct->getQty(false);

            $typeQty = (int)$walmartSynchronizationTemplate->getListWhenQtyCalculatedHasValueType();
            $minQty = (int)$walmartSynchronizationTemplate->getListWhenQtyCalculatedHasValueMin();
            $maxQty = (int)$walmartSynchronizationTemplate->getListWhenQtyCalculatedHasValueMax();

            $note = '';

            if ($typeQty == \Ess\M2ePro\Model\Walmart\Template\Synchronization::LIST_QTY_LESS) {
                if ($productQty <= $minQty) {
                    $result = true;
                } else {
                    $note = $log->encodeDescription(
                        'Product was not automatically Listed according to the List Rules in Synchronization Policy.
                         Quantity of Magento Product is %product_qty% though in Synchronization Rules
                         “Calculated Quantity” is set to less then %template_min_qty%.',
                        [
                            '!template_min_qty' => $minQty,
                            '!product_qty' => $productQty,
                            '!date' => $this->getHelper('Data')->getCurrentGmtDate()
                        ]
                    );
                }
            }

            if ($typeQty == \Ess\M2ePro\Model\Walmart\Template\Synchronization::LIST_QTY_MORE) {
                if ($productQty >= $minQty) {
                    $result = true;
                } else {
                    $note = $log->encodeDescription(
                        'Product was not automatically Listed according to the List Rules in Synchronization Policy.
                         Quantity of Magento Product is %product_qty% though in Synchronization Rules
                         “Calculated Quantity” is set to more then  %template_min_qty%.',
                        [
                            '!template_min_qty' => $minQty,
                            '!product_qty' => $productQty,
                            '!date' => $this->getHelper('Data')->getCurrentGmtDate()
                        ]
                    );
                }
            }

            if ($typeQty == \Ess\M2ePro\Model\Walmart\Template\Synchronization::LIST_QTY_BETWEEN) {
                if ($productQty >= $minQty && $productQty <= $maxQty) {
                    $result = true;
                } else {
                    $note = $log->encodeDescription(
                        'Product was not automatically Listed according to the List Rules in Synchronization Policy.
                         Quantity of Magento Product is %product_qty% though in Synchronization Rules
                         “Calculated Quantity” is set between  %template_min_qty% and %template_max_qty%.',
                        [
                            '!template_min_qty' => $minQty,
                            '!template_max_qty' => $maxQty,
                            '!product_qty' => $productQty,
                            '!date' => $this->getHelper('Data')->getCurrentGmtDate()
                        ]
                    );
                }
            }

            if (!$result) {
                if (!empty($note)) {
                    $additionalData['synch_template_list_rules_note'] = $note;
                    $listingProduct->setSettings('additional_data', $additionalData)->save();
                }

                return false;
            }
        }

        if ($listingProduct->getSynchStatus() != \Ess\M2ePro\Model\Listing\Product::SYNCH_STATUS_NEED &&
            $this->isTriedToList($listingProduct) &&
            $this->isChangeInitiatorOnlyInspector($listingProduct)
        ) {
            return false;
        }

        return true;
    }

    /**
     * @param \Ess\M2ePro\Model\Listing\Product $listingProduct
     * @param bool $needSynchRulesCheckIfLocked
     * @return bool
     * @throws \Ess\M2ePro\Model\Exception
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function isMeetRelistRequirements(ListingProduct $listingProduct, $needSynchRulesCheckIfLocked = true)
    {
        if (!$listingProduct->isStopped() || $listingProduct->isBlocked()) {
            return false;
        }

        if (!$listingProduct->isRelistable()) {
            return false;
        }

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

        $walmartSynchronizationTemplate = $walmartListingProduct->getWalmartSynchronizationTemplate();

        if (!$walmartSynchronizationTemplate->isRelistMode()) {
            return false;
        }

        if ($walmartSynchronizationTemplate->isRelistFilterUserLock() &&
            $listingProduct->getStatusChanger() == \Ess\M2ePro\Model\Listing\Product::STATUS_CHANGER_USER) {
            return false;
        }

        if ($listingProduct->isSetProcessingLock('in_action')) {
            if ($needSynchRulesCheckIfLocked) {
                $this->activeRecordFactory->getObject('Listing\Product')
                    ->getResource()->setNeedSynchRulesCheck([$listingProduct->getId()]);
            }
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

        if ($walmartSynchronizationTemplate->isRelistWhenQtyMagentoHasValue()) {
            $result = false;
            $productQty = (int)$walmartListingProduct->getQty(true);

            $typeQty = (int)$walmartSynchronizationTemplate->getRelistWhenQtyMagentoHasValueType();
            $minQty = (int)$walmartSynchronizationTemplate->getRelistWhenQtyMagentoHasValueMin();
            $maxQty = (int)$walmartSynchronizationTemplate->getRelistWhenQtyMagentoHasValueMax();

            if ($typeQty == \Ess\M2ePro\Model\Walmart\Template\Synchronization::RELIST_QTY_LESS &&
                $productQty <= $minQty) {
                $result = true;
            }

            if ($typeQty == \Ess\M2ePro\Model\Walmart\Template\Synchronization::RELIST_QTY_MORE &&
                $productQty >= $minQty) {
                $result = true;
            }

            if ($typeQty == \Ess\M2ePro\Model\Walmart\Template\Synchronization::RELIST_QTY_BETWEEN &&
                $productQty >= $minQty && $productQty <= $maxQty) {
                $result = true;
            }

            if (!$result) {
                return false;
            }
        }

        if ($walmartSynchronizationTemplate->isRelistWhenQtyCalculatedHasValue()) {
            $result = false;
            $productQty = (int)$walmartListingProduct->getQty(false);

            $typeQty = (int)$walmartSynchronizationTemplate->getRelistWhenQtyCalculatedHasValueType();
            $minQty = (int)$walmartSynchronizationTemplate->getRelistWhenQtyCalculatedHasValueMin();
            $maxQty = (int)$walmartSynchronizationTemplate->getRelistWhenQtyCalculatedHasValueMax();

            if ($typeQty == \Ess\M2ePro\Model\Walmart\Template\Synchronization::RELIST_QTY_LESS &&
                $productQty <= $minQty) {
                $result = true;
            }

            if ($typeQty == \Ess\M2ePro\Model\Walmart\Template\Synchronization::RELIST_QTY_MORE &&
                $productQty >= $minQty) {
                $result = true;
            }

            if ($typeQty == \Ess\M2ePro\Model\Walmart\Template\Synchronization::RELIST_QTY_BETWEEN &&
                $productQty >= $minQty && $productQty <= $maxQty) {
                $result = true;
            }

            if (!$result) {
                return false;
            }
        }

        return true;
    }

    // ---------------------------------------

    /**
     * @param \Ess\M2ePro\Model\Listing\Product $listingProduct
     * @param bool $needSynchRulesCheckIfLocked
     * @return bool
     * @throws \Ess\M2ePro\Model\Exception
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function isMeetStopGeneralRequirements(ListingProduct $listingProduct, $needSynchRulesCheckIfLocked = true)
    {
        if (!$listingProduct->isListed() || $listingProduct->isBlocked()) {
            return false;
        }

        if (!$listingProduct->isStoppable()) {
            return false;
        }

        /** @var \Ess\M2ePro\Model\Walmart\Listing\Product $walmartListingProduct */
        $walmartListingProduct = $listingProduct->getChildObject();
        $variationManager = $walmartListingProduct->getVariationManager();

        if ($variationManager->isVariationProduct()) {
            if ($variationManager->isRelationParentType()) {
                return false;
            }
        }

        if ($listingProduct->isSetProcessingLock('in_action')) {
            if ($needSynchRulesCheckIfLocked) {
                $this->activeRecordFactory->getObject('Listing\Product')
                    ->getResource()->setNeedSynchRulesCheck([$listingProduct->getId()]);
            }
            return false;
        }

        return true;
    }

    /**
     * @param \Ess\M2ePro\Model\Listing\Product $listingProduct
     * @param bool $needSynchRulesCheckIfLocked
     * @return bool
     * @throws \Ess\M2ePro\Model\Exception
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function isMeetStopRequirements(ListingProduct $listingProduct, $needSynchRulesCheckIfLocked = true)
    {
        /** @var \Ess\M2ePro\Model\Walmart\Listing\Product $walmartListingProduct */
        $walmartListingProduct = $listingProduct->getChildObject();
        $variationManager = $walmartListingProduct->getVariationManager();

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

        if ($walmartSynchronizationTemplate->isStopWhenQtyMagentoHasValue()) {
            $productQty = (int)$walmartListingProduct->getQty(true);

            $typeQty = (int)$walmartSynchronizationTemplate->getStopWhenQtyMagentoHasValueType();
            $minQty = (int)$walmartSynchronizationTemplate->getStopWhenQtyMagentoHasValueMin();
            $maxQty = (int)$walmartSynchronizationTemplate->getStopWhenQtyMagentoHasValueMax();

            if ($typeQty ==\Ess\M2ePro\Model\Walmart\Template\Synchronization::STOP_QTY_LESS &&
                $productQty <= $minQty) {
                return true;
            }

            if ($typeQty ==\Ess\M2ePro\Model\Walmart\Template\Synchronization::STOP_QTY_MORE &&
                $productQty >= $minQty) {
                return true;
            }

            if ($typeQty ==\Ess\M2ePro\Model\Walmart\Template\Synchronization::STOP_QTY_BETWEEN &&
                $productQty >= $minQty && $productQty <= $maxQty) {
                return true;
            }
        }

        if ($walmartSynchronizationTemplate->isStopWhenQtyCalculatedHasValue()) {
            $productQty = (int)$walmartListingProduct->getQty(false);

            $typeQty = (int)$walmartSynchronizationTemplate->getStopWhenQtyCalculatedHasValueType();
            $minQty = (int)$walmartSynchronizationTemplate->getStopWhenQtyCalculatedHasValueMin();
            $maxQty = (int)$walmartSynchronizationTemplate->getStopWhenQtyCalculatedHasValueMax();

            if ($typeQty ==\Ess\M2ePro\Model\Walmart\Template\Synchronization::STOP_QTY_LESS &&
                $productQty <= $minQty) {
                return true;
            }

            if ($typeQty ==\Ess\M2ePro\Model\Walmart\Template\Synchronization::STOP_QTY_MORE &&
                $productQty >= $minQty) {
                return true;
            }

            if ($typeQty ==\Ess\M2ePro\Model\Walmart\Template\Synchronization::STOP_QTY_BETWEEN &&
                $productQty >= $minQty && $productQty <= $maxQty) {
                return true;
            }
        }

        return false;
    }

    // ---------------------------------------

    /**
     * @param \Ess\M2ePro\Model\Listing\Product $listingProduct
     * @param bool $needSynchRulesCheckIfLocked
     * @return bool
     * @throws \Ess\M2ePro\Model\Exception
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function isMeetReviseGeneralRequirements(ListingProduct $listingProduct, $needSynchRulesCheckIfLocked = true)
    {
        /** @var \Ess\M2ePro\Model\Walmart\Listing\Product $walmartListingProduct */
        $walmartListingProduct = $listingProduct->getChildObject();

        if (!$listingProduct->isListed() || $listingProduct->isBlocked()) {
            return false;
        }

        if (!$listingProduct->isRevisable()) {
            return false;
        }

        $variationManager = $walmartListingProduct->getVariationManager();

        if ($variationManager->isVariationProduct()) {
            if ($variationManager->isRelationParentType()) {
                return false;
            }

            if ($variationManager->isPhysicalUnit() &&
                !$variationManager->getTypeModel()->isVariationProductMatched()
            ) {
                return false;
            }
        }

        if ($listingProduct->isSetProcessingLock('in_action')) {
            if ($needSynchRulesCheckIfLocked) {
                $this->activeRecordFactory->getObject('Listing\Product')
                                          ->getResource()->setNeedSynchRulesCheck([$listingProduct->getId()]);
            }
            return false;
        }

        return true;
    }

    // ---------------------------------------

    /**
     * @param \Ess\M2ePro\Model\Listing\Product $listingProduct
     * @param bool $needSynchRulesCheckIfLocked
     * @return bool
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function isMeetReviseQtyRequirements(ListingProduct $listingProduct, $needSynchRulesCheckIfLocked = true)
    {
        if (!$this->isMeetReviseGeneralRequirements($listingProduct, $needSynchRulesCheckIfLocked)) {
            return false;
        }

        /** @var \Ess\M2ePro\Model\Walmart\Listing\Product $walmartListingProduct */
        $walmartListingProduct = $listingProduct->getChildObject();

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

    /**
     * @param \Ess\M2ePro\Model\Listing\Product $listingProduct
     * @param bool $needSynchRulesCheckIfLocked
     * @return bool
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function isMeetReviseLagTimeRequirements(ListingProduct $listingProduct, $needSynchRulesCheckIfLocked = true)
    {
        if (!$this->isMeetReviseGeneralRequirements($listingProduct, $needSynchRulesCheckIfLocked)) {
            return false;
        }

        /** @var \Ess\M2ePro\Model\Walmart\Listing\Product $walmartListingProduct */
        $walmartListingProduct = $listingProduct->getChildObject();

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

    /**
     * @param \Ess\M2ePro\Model\Listing\Product $listingProduct
     * @param bool $needSynchRulesCheckIfLocked
     * @return bool
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function isMeetRevisePriceRequirements(
        ListingProduct $listingProduct,
        $needSynchRulesCheckIfLocked = true
    ) {
        /** @var \Ess\M2ePro\Model\Walmart\Listing\Product $walmartListingProduct */
        $walmartListingProduct = $listingProduct->getChildObject();

        if (!$this->isMeetReviseGeneralRequirements($listingProduct, $needSynchRulesCheckIfLocked)) {
            return false;
        }

        $walmartSynchronizationTemplate = $walmartListingProduct->getWalmartSynchronizationTemplate();

        if (!$walmartSynchronizationTemplate->isReviseUpdatePrice()) {
            return false;
        }

        $currentPrice = $walmartListingProduct->getPrice();
        $onlinePrice  = $walmartListingProduct->getOnlinePrice();

        $isChanged = $walmartSynchronizationTemplate->isPriceChangedOverAllowedDeviation($onlinePrice, $currentPrice);
        if ($isChanged) {
            return true;
        }

        return false;
    }

    /**
     * @param \Ess\M2ePro\Model\Listing\Product $listingProduct
     * @param bool $needSynchRulesCheckIfLocked
     * @return bool
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function isMeetRevisePromotionsPriceRequirements(
        ListingProduct $listingProduct,
        $needSynchRulesCheckIfLocked = true
    ) {
        if (!$this->isMeetReviseGeneralRequirements($listingProduct, $needSynchRulesCheckIfLocked)) {
            return false;
        }

        /** @var \Ess\M2ePro\Model\Walmart\Listing\Product $walmartListingProduct */
        $walmartListingProduct = $listingProduct->getChildObject();

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
            $onlinePromotions = ['promotion_prices' => []];
        }

        return $promotionsActionDataBuilder->getRequestData() != $onlinePromotions;
    }

    // ---------------------------------------

    /**
     * @param \Ess\M2ePro\Model\Listing\Product $listingProduct
     * @param bool $needSynchRulesCheckIfLocked
     * @return bool
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function isMeetReviseDetailsRequirements(
        ListingProduct $listingProduct,
        $needSynchRulesCheckIfLocked = true
    ) {
        if (!$this->isMeetReviseGeneralRequirements($listingProduct, $needSynchRulesCheckIfLocked)) {
            return false;
        }

        /** @var \Ess\M2ePro\Model\Walmart\Listing\Product $walmartListingProduct */
        $walmartListingProduct = $listingProduct->getChildObject();

        if (!$walmartListingProduct->isExistCategoryTemplate()) {
            return false;
        }

        /** @var \Ess\M2ePro\Model\Walmart\Listing\Product\Action\DataBuilder\Details $detailsActionDataBuilder */
        $detailsActionDataBuilder = $this->modelFactory
            ->getObject('Walmart_Listing_Product_Action_DataBuilder_Details');
        $detailsActionDataBuilder->setListingProduct($listingProduct);

        $currentDetailsData = $detailsActionDataBuilder->getRequestData();

        $currentStartDate = $currentDetailsData['start_date'];
        unset($currentDetailsData['start_date']);

        $currentEndDate = $currentDetailsData['end_date'];
        unset($currentDetailsData['end_date']);

        if ($currentDetailsData != $walmartListingProduct->getOnlineDetailsData()) {
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

    /**
     * @param \Ess\M2ePro\Model\Listing\Product $listingProduct
     * @param bool $needSynchRulesCheckIfLocked
     * @return bool
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function isMeetReviseSynchReasonsRequirements(
        ListingProduct $listingProduct,
        $needSynchRulesCheckIfLocked = true
    ) {
        $reasons = $listingProduct->getSynchReasons();
        if (empty($reasons)) {
            return false;
        }

        if (!$this->isMeetReviseGeneralRequirements($listingProduct, $needSynchRulesCheckIfLocked)) {
            return false;
        }

        return true;
    }

    //########################################

    /**
     * @param \Ess\M2ePro\Model\Listing\Product[] $lpForAdvancedRules
     * input format $lpForAdvancedRules[$templateId][$storeId][$magentoProductId][] = $listingProduct;
     * @param string $ruleModelPrefix
     * @param string $ruleFiltersDataKeyPrefix
     * @return array
     * @throws \Ess\M2ePro\Model\Exception\Logic
     * @throws \Exception
     */
    public function getMeetAdvancedRequirementsProducts(
        array $lpForAdvancedRules,
        $ruleModelPrefix,
        $ruleFiltersDataKeyPrefix
    ) {
        $resultProducts = [];

        foreach ($lpForAdvancedRules as $templateId => $productsByTemplate) {

            /** @var \Ess\M2ePro\Model\Walmart\Template\Synchronization $walmartTemplate */
            $template = $this->walmartFactory->getCachedObjectLoaded('Template\Synchronization', $templateId);
            $walmartTemplate = $template->getChildObject();

            foreach ($productsByTemplate as $storeId => $productsByStore) {
                /** @var $tempCollection \Magento\Catalog\Model\ResourceModel\Product\Collection */
                $tempCollection = $this->magentoProductCollectionFactory->create();
                $tempCollection->addFieldToFilter('entity_id', ['in' => array_keys($productsByStore)]);
                $tempCollection->setStoreId($storeId);

                $ruleModel = $this->activeRecordFactory->getObject('Magento_Product_Rule')->setData(
                    [
                        'store_id' => $storeId,
                        'prefix'   => $ruleModelPrefix
                    ]
                );

                $templateData = $walmartTemplate->getData($ruleFiltersDataKeyPrefix . '_advanced_rules_filters');
                $templateData && $ruleModel->loadFromSerialized($templateData);

                $ruleModel->setAttributesFilterToCollection($tempCollection);

                foreach ($tempCollection->getItems() as $magentoProduct) {
                    /**@var \Magento\Catalog\Model\Product $magentoProduct */

                    if (isset($productsByStore[$magentoProduct->getId()])) {
                        $resultProducts = array_merge($resultProducts, $productsByStore[$magentoProduct->getId()]);
                    }
                }
            }
        }

        return $resultProducts;
    }

    // ---------------------------------------

    /**
     * @param \Ess\M2ePro\Model\Listing\Product $listingProduct
     * @return bool
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function isMeetAdvancedListRequirements(ListingProduct $listingProduct)
    {
        /** @var \Ess\M2ePro\Model\Walmart\Listing\Product $walmartListingProduct */
        $walmartListingProduct = $listingProduct->getChildObject();
        $walmartTemplate = $walmartListingProduct->getWalmartSynchronizationTemplate();

        if (!$walmartTemplate->isListAdvancedRulesEnabled()) {
            return false;
        }

        return $this->isMeetAdvancedRequirements(
            $listingProduct,
            SynchronizationPolicy::LIST_ADVANCED_RULES_PREFIX,
            'list'
        );
    }

    /**
     * @param \Ess\M2ePro\Model\Listing\Product $listingProduct
     * @return bool
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function isMeetAdvancedRelistRequirements(ListingProduct $listingProduct)
    {
        /** @var \Ess\M2ePro\Model\Walmart\Listing\Product $walmartListingProduct */
        $walmartListingProduct = $listingProduct->getChildObject();
        $walmartTemplate = $walmartListingProduct->getWalmartSynchronizationTemplate();

        if (!$walmartTemplate->isRelistAdvancedRulesEnabled()) {
            return false;
        }

        return $this->isMeetAdvancedRequirements(
            $listingProduct,
            SynchronizationPolicy::RELIST_ADVANCED_RULES_PREFIX,
            'relist'
        );
    }

    /**
     * @param \Ess\M2ePro\Model\Listing\Product $listingProduct
     * @return bool
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function isMeetAdvancedStopRequirements(ListingProduct $listingProduct)
    {
        /** @var \Ess\M2ePro\Model\Walmart\Listing\Product $walmartListingProduct */
        $walmartListingProduct = $listingProduct->getChildObject();
        $walmartTemplate = $walmartListingProduct->getWalmartSynchronizationTemplate();

        if (!$walmartTemplate->isStopAdvancedRulesEnabled()) {
            return false;
        }

        return $this->isMeetAdvancedRequirements(
            $listingProduct,
            SynchronizationPolicy::STOP_ADVANCED_RULES_PREFIX,
            'stop'
        );
    }

    //todo
    /**
     * @param \Ess\M2ePro\Model\Listing\Product $listingProduct
     * @param string $ruleModelPrefix
     * @param string $ruleFiltersDataKeyPrefix
     * @return bool
     * @throws \Ess\M2ePro\Model\Exception\Logic
     * @throws \Exception
     */
    private function isMeetAdvancedRequirements(
        ListingProduct $listingProduct,
        $ruleModelPrefix,
        $ruleFiltersDataKeyPrefix
    ) {
        /** @var \Ess\M2ePro\Model\Walmart\Listing\Product $walmartListingProduct */
        $walmartListingProduct = $listingProduct->getChildObject();

        /** @var $tempCollection \Magento\Catalog\Model\ResourceModel\Product\Collection */
        $tempCollection = $this->magentoProductCollectionFactory->create();
        $tempCollection->addFieldToFilter('entity_id', $listingProduct->getProductId());
        $tempCollection->setStoreId($listingProduct->getListing()->getStoreId());

        $ruleModel = $this->activeRecordFactory->getObject('Magento_Product_Rule')->setData(
            [
                'store_id' => $listingProduct->getListing()->getStoreId(),
                'prefix'   => $ruleModelPrefix
            ]
        );

        $walmartTemplate = $walmartListingProduct->getWalmartSynchronizationTemplate();
        $templateData = $walmartTemplate->getData($ruleFiltersDataKeyPrefix . '_advanced_rules_filters');
        $templateData && $ruleModel->loadFromSerialized($templateData);

        $ruleModel->setAttributesFilterToCollection($tempCollection);

        return $tempCollection->getSize();
    }

    //########################################
}
