<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Walmart\Synchronization\Templates\Synchronization;

use Ess\M2ePro\Model\Listing\Product as ListingProduct;
use Ess\M2ePro\Model\Walmart\Template\Synchronization as SynchronizationPolicy;

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
    )
    {
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
                    ->getResource()->setNeedSynchRulesCheck(array($listingProduct->getId()));
            }
            return false;
        }

        $variationResource = $this->activeRecordFactory->getObject('Listing\Product\Variation')->getResource();

        $additionalData = $listingProduct->getAdditionalData();

        $log = $this->getHelper('Module\Log');

        if ($walmartSynchronizationTemplate->isListStatusEnabled()) {

            if (!$listingProduct->getMagentoProduct()->isStatusEnabled()) {
                // M2ePro\TRANSLATIONS
                // Product was not automatically Listed according to the List Rules in Synchronization Policy. Status of Magento Product is Disabled (%date%) though in Synchronization Rules “Product Status” is set to Enabled.
                $note = $log->encodeDescription(
                    'Product was not automatically Listed according to the List Rules in Synchronization Policy.
                     Status of Magento Product is Disabled (%date%) though in Synchronization Rules “Product Status”
                     is set to Enabled.',
                    array('date' => $this->getHelper('Data')->getCurrentGmtDate())
                );
                $additionalData['synch_template_list_rules_note'] = $note;

                $listingProduct->setSettings('additional_data', $additionalData)->save();

                return false;
            } else if ($variationManager->isPhysicalUnit() &&
                       $variationManager->getTypeModel()->isVariationProductMatched()
            ) {
                $temp = $variationResource->isAllStatusesDisabled(
                    $listingProduct->getId(),
                    $listingProduct->getListing()->getStoreId()
                );

                if (!is_null($temp) && $temp) {
                    // M2ePro\TRANSLATIONS
                    // Product was not automatically Listed according to the List Rules in Synchronization Policy. Status of Magento Product Variation is Disabled (%date%) though in Synchronization Rules “Product Status“ is set to Enabled.
                    $note = $log->encodeDescription(
                        'Product was not automatically Listed according to the List Rules in Synchronization Policy.
                         Status of Magento Product Variation is Disabled (%date%) though in Synchronization Rules
                         “Product Status“ is set to Enabled.',
                        array('date' => $this->getHelper('Data')->getCurrentGmtDate())
                    );
                    $additionalData['synch_template_list_rules_note'] = $note;

                    $listingProduct->setSettings('additional_data', $additionalData)->save();

                    return false;
                }
            }
        }

        if ($walmartSynchronizationTemplate->isListIsInStock()) {

            if (!$listingProduct->getMagentoProduct()->isStockAvailability()) {
                // M2ePro\TRANSLATIONS
                // Product was not automatically Listed according to the List Rules in Synchronization Policy. Stock Availability of Magento Product is Out of Stock though in Synchronization Rules “Stock Availability” is set to In Stock.
                $note = $log->encodeDescription(
                    'Product was not automatically Listed according to the List Rules in Synchronization Policy.
                     Stock Availability of Magento Product is Out of Stock though in
                     Synchronization Rules “Stock Availability” is set to In Stock.',
                    array('date' => $this->getHelper('Data')->getCurrentGmtDate())
                );
                $additionalData['synch_template_list_rules_note'] = $note;

                $listingProduct->setSettings('additional_data', $additionalData)->save();

                return false;
            } else if ($variationManager->isPhysicalUnit() &&
                       $variationManager->getTypeModel()->isVariationProductMatched()
            ) {
                $temp = $variationResource->isAllDoNotHaveStockAvailabilities(
                    $listingProduct->getId(),
                    $listingProduct->getListing()->getStoreId()
                );

                if (!is_null($temp) && $temp) {
                    // M2ePro\TRANSLATIONS
                    // Product was not automatically Listed according to the List Rules in Synchronization Policy. Stock Availability of Magento Product Variation is Out of Stock though in Synchronization Rules “Stock Availability” is set to In Stock.
                    $note = $log->encodeDescription(
                        'Product was not automatically Listed according to the List Rules in Synchronization Policy.
                         Stock Availability of Magento Product Variation is Out of Stock though
                         in Synchronization Rules “Stock Availability” is set to In Stock.',
                        array('date' => $this->getHelper('Data')->getCurrentGmtDate())
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
                    // M2ePro\TRANSLATIONS
                    // Product was not automatically Listed according to the List Rules in Synchronization Policy. Quantity of Magento Product is %product_qty% though in Synchronization Rules “Magento Quantity“ is set to less then  %template_min_qty%.
                    $note = $log->encodeDescription(
                        'Product was not automatically Listed according to the List Rules in Synchronization Policy.
                         Quantity of Magento Product is %product_qty% though in Synchronization Rules
                         “Magento Quantity“ is set to less then  %template_min_qty%.',
                        array(
                            '!template_min_qty' => $minQty,
                            '!product_qty' => $productQty,
                            '!date' => $this->getHelper('Data')->getCurrentGmtDate()
                        )
                    );
                }
            }

            if ($typeQty == \Ess\M2ePro\Model\Walmart\Template\Synchronization::LIST_QTY_MORE) {
                if ($productQty >= $minQty) {
                    $result = true;
                } else {
                    // M2ePro\TRANSLATIONS
                    // Product was not automatically Listed according to the List Rules in Synchronization Policy. Quantity of Magento Product is %product_qty% though in Synchronization Rules “Magento Quantity” is set to more then  %template_min_qty%.
                    $note = $log->encodeDescription(
                        'Product was not automatically Listed according to the List Rules in Synchronization Policy.
                         Quantity of Magento Product is %product_qty% though in Synchronization Rules
                         “Magento Quantity” is set to more then  %template_min_qty%.',
                        array(
                            '!template_min_qty' => $minQty,
                            '!product_qty' => $productQty,
                            '!date' => $this->getHelper('Data')->getCurrentGmtDate()
                        )
                    );
                }
            }

            if ($typeQty == \Ess\M2ePro\Model\Walmart\Template\Synchronization::LIST_QTY_BETWEEN) {
                if ($productQty >= $minQty && $productQty <= $maxQty) {
                    $result = true;
                } else {
                    // M2ePro\TRANSLATIONS
                    // Product was not automatically Listed according to the List Rules in Synchronization Policy. Quantity of Magento Product is %product_qty% though in Synchronization Rules “Magento Quantity” is set between  %template_min_qty% and %template_max_qty%.
                    $note = $log->encodeDescription(
                        'Product was not automatically Listed according to the List Rules in Synchronization Policy.
                         Quantity of Magento Product is %product_qty% though in Synchronization Rules
                         “Magento Quantity” is set between  %template_min_qty% and %template_max_qty%.',
                        array(
                            '!template_min_qty' => $minQty,
                            '!template_max_qty' => $maxQty,
                            '!product_qty' => $productQty,
                            '!date' => $this->getHelper('Data')->getCurrentGmtDate()
                        )
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
                    // M2ePro\TRANSLATIONS
                    // Product was not automatically Listed according to the List Rules in Synchronization Policy. Quantity of Magento Product is %product_qty% though in Synchronization Rules “Calculated Quantity” is set to less then %template_min_qty%.
                    $note = $log->encodeDescription(
                        'Product was not automatically Listed according to the List Rules in Synchronization Policy.
                         Quantity of Magento Product is %product_qty% though in Synchronization Rules
                         “Calculated Quantity” is set to less then %template_min_qty%.',
                        array(
                            '!template_min_qty' => $minQty,
                            '!product_qty' => $productQty,
                            '!date' => $this->getHelper('Data')->getCurrentGmtDate()
                        )
                    );
                }
            }

            if ($typeQty == \Ess\M2ePro\Model\Walmart\Template\Synchronization::LIST_QTY_MORE) {
                if ($productQty >= $minQty) {
                    $result = true;
                } else {
                    // M2ePro\TRANSLATIONS
                    // Product was not automatically Listed according to the List Rules in Synchronization Policy. Quantity of Magento Product is %product_qty% though in Synchronization Rules “Calculated Quantity” is set to more then  %template_min_qty%.
                    $note = $log->encodeDescription(
                        'Product was not automatically Listed according to the List Rules in Synchronization Policy.
                         Quantity of Magento Product is %product_qty% though in Synchronization Rules
                         “Calculated Quantity” is set to more then  %template_min_qty%.',
                        array(
                            '!template_min_qty' => $minQty,
                            '!product_qty' => $productQty,
                            '!date' => $this->getHelper('Data')->getCurrentGmtDate()
                        )
                    );
                }
            }

            if ($typeQty == \Ess\M2ePro\Model\Walmart\Template\Synchronization::LIST_QTY_BETWEEN) {
                if ($productQty >= $minQty && $productQty <= $maxQty) {
                    $result = true;
                } else {
                    // M2ePro\TRANSLATIONS
                    // Product was not automatically Listed according to the List Rules in Synchronization Policy. Quantity of Magento Product is %product_qty% though in Synchronization Rules “Calculated Quantity” is set between  %template_min_qty% and %template_max_qty%.
                    $note = $log->encodeDescription(
                        'Product was not automatically Listed according to the List Rules in Synchronization Policy.
                         Quantity of Magento Product is %product_qty% though in Synchronization Rules
                         “Calculated Quantity” is set between  %template_min_qty% and %template_max_qty%.',
                        array(
                            '!template_min_qty' => $minQty,
                            '!template_max_qty' => $maxQty,
                            '!product_qty' => $productQty,
                            '!date' => $this->getHelper('Data')->getCurrentGmtDate()
                        )
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
                    ->getResource()->setNeedSynchRulesCheck(array($listingProduct->getId()));
            }
            return false;
        }

        if (!$walmartListingProduct->isExistCategoryTemplate()) {
            return false;
        }

        $variationResource = $this->activeRecordFactory->getObject('Listing\Product\Variation')->getResource();

        if ($walmartSynchronizationTemplate->isRelistStatusEnabled()) {

            if (!$listingProduct->getMagentoProduct()->isStatusEnabled()) {
                return false;
            } else if ($variationManager->isPhysicalUnit() &&
                       $variationManager->getTypeModel()->isVariationProductMatched()
            ) {
                $temp = $variationResource->isAllStatusesDisabled(
                    $listingProduct->getId(),
                    $listingProduct->getListing()->getStoreId()
                );

                if (!is_null($temp) && $temp) {
                    return false;
                }
            }
        }

        if ($walmartSynchronizationTemplate->isRelistIsInStock()) {

            if (!$listingProduct->getMagentoProduct()->isStockAvailability()) {
                return false;
            } else if ($variationManager->isPhysicalUnit() &&
                       $variationManager->getTypeModel()->isVariationProductMatched()
            ) {
                $temp = $variationResource->isAllDoNotHaveStockAvailabilities(
                    $listingProduct->getId(),
                    $listingProduct->getListing()->getStoreId()
                );

                if (!is_null($temp) && $temp) {
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
                    ->getResource()->setNeedSynchRulesCheck(array($listingProduct->getId()));
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
        $variationResource = $this->activeRecordFactory->getObject('Listing\Product\Variation')->getResource();

        if (!$walmartSynchronizationTemplate->isStopMode()) {
            return false;
        }

        if (!$walmartListingProduct->isExistCategoryTemplate()) {
            return false;
        }

        if ($walmartSynchronizationTemplate->isStopStatusDisabled()) {

            if (!$listingProduct->getMagentoProduct()->isStatusEnabled()) {
                return true;
            } else if ($variationManager->isPhysicalUnit() &&
                       $variationManager->getTypeModel()->isVariationProductMatched()
            ) {
                $temp = $variationResource->isAllStatusesDisabled(
                    $listingProduct->getId(),
                    $listingProduct->getListing()->getStoreId()
                );

                if (!is_null($temp) && $temp) {
                    return true;
                }
            }
        }

        if ($walmartSynchronizationTemplate->isStopOutOfStock()) {

            if (!$listingProduct->getMagentoProduct()->isStockAvailability()) {
                return true;
            } else if ($variationManager->isPhysicalUnit() &&
                       $variationManager->getTypeModel()->isVariationProductMatched()
            ) {
                $temp = $variationResource->isAllDoNotHaveStockAvailabilities(
                    $listingProduct->getId(),
                    $listingProduct->getListing()->getStoreId()
                );

                if (!is_null($temp) && $temp) {
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

        $currentLagTime = $walmartListingProduct->getSellingFormatTemplateSource()->getLagTime();
        $onlineLagTime  = $walmartListingProduct->getOnlineLagTime();

        if ($currentLagTime != $onlineLagTime) {
            return true;
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

    /**
     * @param \Ess\M2ePro\Model\Listing\Product $listingProduct
     * @param bool $needSynchRulesCheckIfLocked
     * @return bool
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function isMeetRevisePriceRequirements(
        ListingProduct $listingProduct,
        $needSynchRulesCheckIfLocked = true
    )
    {
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
    public function isMeetRevisePromotionsPriceRequirements(ListingProduct $listingProduct,
                                                          $needSynchRulesCheckIfLocked = true)
    {
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
            ->getObject('Walmart\Listing\Product\Action\DataBuilder\Promotions');
        $promotionsActionDataBuilder->setListingProduct($listingProduct);

        return $promotionsActionDataBuilder->getRequestData() != $walmartListingProduct->getOnlinePromotions();
    }

    // ---------------------------------------

    /**
     * @param \Ess\M2ePro\Model\Listing\Product $listingProduct
     * @param bool $needSynchRulesCheckIfLocked
     * @return bool
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function isMeetReviseDetailsRequirements(ListingProduct $listingProduct,
                                                    $needSynchRulesCheckIfLocked = true)
    {
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
            ->getObject('Walmart\Listing\Product\Action\DataBuilder\Details');
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
    public function isMeetReviseSynchReasonsRequirements(ListingProduct $listingProduct,
        $needSynchRulesCheckIfLocked = true)
    {
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
}