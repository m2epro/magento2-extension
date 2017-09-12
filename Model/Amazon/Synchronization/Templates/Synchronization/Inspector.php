<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Synchronization\Templates\Synchronization;

use Ess\M2ePro\Model\Listing\Product as ListingProduct;
use Ess\M2ePro\Model\Amazon\Template\Synchronization as SynchronizationPolicy;

class Inspector extends \Ess\M2ePro\Model\Synchronization\Templates\Synchronization\Inspector
{
    private $amazonFactory;
    private $magentoProductCollectionFactory;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $magentoProductCollectionFactory,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory
    )
    {
        parent::__construct($activeRecordFactory, $helperFactory, $modelFactory);

        $this->magentoProductCollectionFactory = $magentoProductCollectionFactory;
        $this->amazonFactory = $amazonFactory;
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
        if (!$listingProduct->isNotListed() || $listingProduct->isBlocked()) {
            return false;
        }

        if (!$listingProduct->isListable()) {
            return false;
        }

        /** @var \Ess\M2ePro\Model\Amazon\Listing\Product $amazonListingProduct */
        $amazonListingProduct = $listingProduct->getChildObject();
        $variationManager = $amazonListingProduct->getVariationManager();

        $searchGeneralId   = $amazonListingProduct->getListingSource()->getSearchGeneralId();
        $searchWorldwideId = $amazonListingProduct->getListingSource()->getSearchWorldwideId();

        if ($variationManager->isVariationProduct()) {

            if ($variationManager->isPhysicalUnit() &&
                !$variationManager->getTypeModel()->isVariationProductMatched()
            ) {
                return false;
            }

            if ($variationManager->isRelationParentType() && $amazonListingProduct->getGeneralId()) {
                return false;
            }

            if ($variationManager->isRelationChildType()) {
                if (!$amazonListingProduct->getGeneralId() && !$amazonListingProduct->isGeneralIdOwner()) {
                    return false;
                }
            }

            if ($variationManager->isIndividualType()) {
                if (!$amazonListingProduct->getGeneralId() &&
                    ($listingProduct->getMagentoProduct()->isSimpleTypeWithCustomOptions() ||
                        $listingProduct->getMagentoProduct()->isBundleType() ||
                        $listingProduct->getMagentoProduct()->isDownloadableTypeWithSeparatedLinks())
                ) {
                    return false;
                }
            }

            if ($variationManager->isRelationParentType() &&
                empty($searchGeneralId) &&
                !$amazonListingProduct->isGeneralIdOwner()
            ) {
                return false;
            }
        }

        if (!$amazonListingProduct->getGeneralId() && !$amazonListingProduct->isGeneralIdOwner() &&
            empty($searchGeneralId) && empty($searchWorldwideId)
        ) {
            return false;
        }

        $amazonSynchronizationTemplate = $amazonListingProduct->getAmazonSynchronizationTemplate();

        if (!$amazonSynchronizationTemplate->isListMode()) {
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

        if ($amazonSynchronizationTemplate->isListStatusEnabled()) {

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

        if ($amazonSynchronizationTemplate->isListIsInStock()) {

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

        if ($amazonSynchronizationTemplate->isListWhenQtyMagentoHasValue()) {

            $result = false;

            if ($variationManager->isRelationParentType()) {
                $productQty = (int)$listingProduct->getMagentoProduct()->getQty(true);
            } else {
                $productQty = (int)$amazonListingProduct->getQty(true);
            }

            $typeQty = (int)$amazonSynchronizationTemplate->getListWhenQtyMagentoHasValueType();
            $minQty = (int)$amazonSynchronizationTemplate->getListWhenQtyMagentoHasValueMin();
            $maxQty = (int)$amazonSynchronizationTemplate->getListWhenQtyMagentoHasValueMax();

            $note = '';

            if ($typeQty == \Ess\M2ePro\Model\Amazon\Template\Synchronization::LIST_QTY_LESS) {
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

            if ($typeQty == \Ess\M2ePro\Model\Amazon\Template\Synchronization::LIST_QTY_MORE) {
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

            if ($typeQty == \Ess\M2ePro\Model\Amazon\Template\Synchronization::LIST_QTY_BETWEEN) {
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

        if ($amazonSynchronizationTemplate->isListWhenQtyCalculatedHasValue() &&
            !$variationManager->isRelationParentType()
        ) {

            $result = false;
            $productQty = (int)$amazonListingProduct->getQty(false);

            $typeQty = (int)$amazonSynchronizationTemplate->getListWhenQtyCalculatedHasValueType();
            $minQty = (int)$amazonSynchronizationTemplate->getListWhenQtyCalculatedHasValueMin();
            $maxQty = (int)$amazonSynchronizationTemplate->getListWhenQtyCalculatedHasValueMax();

            $note = '';

            if ($typeQty == \Ess\M2ePro\Model\Amazon\Template\Synchronization::LIST_QTY_LESS) {
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

            if ($typeQty == \Ess\M2ePro\Model\Amazon\Template\Synchronization::LIST_QTY_MORE) {
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

            if ($typeQty == \Ess\M2ePro\Model\Amazon\Template\Synchronization::LIST_QTY_BETWEEN) {
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

        /** @var \Ess\M2ePro\Model\Amazon\Listing\Product $amazonListingProduct */
        $amazonListingProduct = $listingProduct->getChildObject();
        $variationManager = $amazonListingProduct->getVariationManager();

        if ($variationManager->isVariationProduct()) {

            if ($variationManager->isRelationParentType()) {
                return false;
            }

            if (!$variationManager->getTypeModel()->isVariationProductMatched()) {
                return false;
            }
        }

        $amazonSynchronizationTemplate = $amazonListingProduct->getAmazonSynchronizationTemplate();

        if (!$amazonSynchronizationTemplate->isRelistMode()) {
            return false;
        }

        if ($amazonSynchronizationTemplate->isRelistFilterUserLock() &&
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

        $variationResource = $this->activeRecordFactory->getObject('Listing\Product\Variation')->getResource();

        if ($amazonSynchronizationTemplate->isRelistStatusEnabled()) {

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

        if ($amazonSynchronizationTemplate->isRelistIsInStock()) {

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

        if ($amazonSynchronizationTemplate->isRelistWhenQtyMagentoHasValue()) {

            $result = false;
            $productQty = (int)$amazonListingProduct->getQty(true);

            $typeQty = (int)$amazonSynchronizationTemplate->getRelistWhenQtyMagentoHasValueType();
            $minQty = (int)$amazonSynchronizationTemplate->getRelistWhenQtyMagentoHasValueMin();
            $maxQty = (int)$amazonSynchronizationTemplate->getRelistWhenQtyMagentoHasValueMax();

            if ($typeQty == \Ess\M2ePro\Model\Amazon\Template\Synchronization::RELIST_QTY_LESS &&
                $productQty <= $minQty) {
                $result = true;
            }

            if ($typeQty == \Ess\M2ePro\Model\Amazon\Template\Synchronization::RELIST_QTY_MORE &&
                $productQty >= $minQty) {
                $result = true;
            }

            if ($typeQty == \Ess\M2ePro\Model\Amazon\Template\Synchronization::RELIST_QTY_BETWEEN &&
                $productQty >= $minQty && $productQty <= $maxQty) {
                $result = true;
            }

            if (!$result) {
                return false;
            }
        }

        if ($amazonSynchronizationTemplate->isRelistWhenQtyCalculatedHasValue()) {

            $result = false;
            $productQty = (int)$amazonListingProduct->getQty(false);

            $typeQty = (int)$amazonSynchronizationTemplate->getRelistWhenQtyCalculatedHasValueType();
            $minQty = (int)$amazonSynchronizationTemplate->getRelistWhenQtyCalculatedHasValueMin();
            $maxQty = (int)$amazonSynchronizationTemplate->getRelistWhenQtyCalculatedHasValueMax();

            if ($typeQty == \Ess\M2ePro\Model\Amazon\Template\Synchronization::RELIST_QTY_LESS &&
                $productQty <= $minQty) {
                $result = true;
            }

            if ($typeQty == \Ess\M2ePro\Model\Amazon\Template\Synchronization::RELIST_QTY_MORE &&
                $productQty >= $minQty) {
                $result = true;
            }

            if ($typeQty == \Ess\M2ePro\Model\Amazon\Template\Synchronization::RELIST_QTY_BETWEEN &&
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

        /** @var \Ess\M2ePro\Model\Amazon\Listing\Product $amazonListingProduct */
        $amazonListingProduct = $listingProduct->getChildObject();
        $variationManager = $amazonListingProduct->getVariationManager();

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
        /** @var \Ess\M2ePro\Model\Amazon\Listing\Product $amazonListingProduct */
        $amazonListingProduct = $listingProduct->getChildObject();
        $variationManager = $amazonListingProduct->getVariationManager();

        $amazonSynchronizationTemplate = $amazonListingProduct->getAmazonSynchronizationTemplate();
        $variationResource = $this->activeRecordFactory->getObject('Listing\Product\Variation')->getResource();

        if ($amazonSynchronizationTemplate->isStopStatusDisabled()) {

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

        if ($amazonSynchronizationTemplate->isStopOutOfStock()) {

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

        if ($amazonSynchronizationTemplate->isStopWhenQtyMagentoHasValue()) {

            $productQty = (int)$amazonListingProduct->getQty(true);

            $typeQty = (int)$amazonSynchronizationTemplate->getStopWhenQtyMagentoHasValueType();
            $minQty = (int)$amazonSynchronizationTemplate->getStopWhenQtyMagentoHasValueMin();
            $maxQty = (int)$amazonSynchronizationTemplate->getStopWhenQtyMagentoHasValueMax();

            if ($typeQty ==\Ess\M2ePro\Model\Amazon\Template\Synchronization::STOP_QTY_LESS &&
                $productQty <= $minQty) {
                return true;
            }

            if ($typeQty ==\Ess\M2ePro\Model\Amazon\Template\Synchronization::STOP_QTY_MORE &&
                $productQty >= $minQty) {
                return true;
            }

            if ($typeQty ==\Ess\M2ePro\Model\Amazon\Template\Synchronization::STOP_QTY_BETWEEN &&
                $productQty >= $minQty && $productQty <= $maxQty) {
                return true;
            }
        }

        if ($amazonSynchronizationTemplate->isStopWhenQtyCalculatedHasValue()) {

            $productQty = (int)$amazonListingProduct->getQty(false);

            $typeQty = (int)$amazonSynchronizationTemplate->getStopWhenQtyCalculatedHasValueType();
            $minQty = (int)$amazonSynchronizationTemplate->getStopWhenQtyCalculatedHasValueMin();
            $maxQty = (int)$amazonSynchronizationTemplate->getStopWhenQtyCalculatedHasValueMax();

            if ($typeQty ==\Ess\M2ePro\Model\Amazon\Template\Synchronization::STOP_QTY_LESS &&
                $productQty <= $minQty) {
                return true;
            }

            if ($typeQty ==\Ess\M2ePro\Model\Amazon\Template\Synchronization::STOP_QTY_MORE &&
                $productQty >= $minQty) {
                return true;
            }

            if ($typeQty ==\Ess\M2ePro\Model\Amazon\Template\Synchronization::STOP_QTY_BETWEEN &&
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
        /** @var \Ess\M2ePro\Model\Amazon\Listing\Product $amazonListingProduct */
        $amazonListingProduct = $listingProduct->getChildObject();

        if (!$amazonListingProduct->isAfnChannel() && (!$listingProduct->isListed() || $listingProduct->isBlocked())) {
            return false;
        }

        if (!$listingProduct->isRevisable()) {
            return false;
        }

        $variationManager = $amazonListingProduct->getVariationManager();

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
                    ->getResource()->setNeedSynchRulesCheck(array($listingProduct->getId()));
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

        /** @var \Ess\M2ePro\Model\Amazon\Listing\Product $amazonListingProduct */
        $amazonListingProduct = $listingProduct->getChildObject();

        $amazonSynchronizationTemplate = $amazonListingProduct->getAmazonSynchronizationTemplate();

        if (!$amazonSynchronizationTemplate->isReviseWhenChangeQty() || $amazonListingProduct->isAfnChannel()) {
            return false;
        }

        $isMaxAppliedValueModeOn = $amazonSynchronizationTemplate->isReviseUpdateQtyMaxAppliedValueModeOn();
        $maxAppliedValue = $amazonSynchronizationTemplate->getReviseUpdateQtyMaxAppliedValue();

        $productQty = $amazonListingProduct->getQty();
        $channelQty = $amazonListingProduct->getOnlineQty();

        if ($isMaxAppliedValueModeOn && $productQty > $maxAppliedValue && $channelQty > $maxAppliedValue) {
            return false;
        }

        if ($productQty > 0 && $productQty != $channelQty) {
            return true;
        }

        return false;
    }

    public function isMeetReviseQtyDetailsRequirements(ListingProduct $listingProduct,
                                                       $needSynchRulesCheckIfLocked = true)
    {
        if (!$this->isMeetReviseGeneralRequirements($listingProduct, $needSynchRulesCheckIfLocked)) {
            return false;
        }

        /** @var \Ess\M2ePro\Model\Amazon\Listing\Product $amazonListingProduct */
        $amazonListingProduct = $listingProduct->getChildObject();

        $amazonSynchronizationTemplate = $amazonListingProduct->getAmazonSynchronizationTemplate();

        if (!$amazonSynchronizationTemplate->isReviseWhenChangeQty() || $amazonListingProduct->isAfnChannel()) {
            return false;
        }

        return true;
    }

    // ---------------------------------------

    /**
     * In Amazon component we use separate revise configurator options for regular and business prices,
     * because of business price should be revised for repriced products.
     *
     * @param \Ess\M2ePro\Model\Listing\Product $listingProduct
     * @param bool $needSynchRulesCheckIfLocked
     * @return bool
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function isMeetRevisePriceRequirements(ListingProduct $listingProduct, $needSynchRulesCheckIfLocked = true)
    {
        throw new \Ess\M2ePro\Model\Exception\Logic(
            'You must use ->isMeetReviseRegularPriceRequirements() or ->isMeetReviseBusinessPriceRequirements() method'
        );
    }

    /**
     * @param \Ess\M2ePro\Model\Listing\Product $listingProduct
     * @param bool $needSynchRulesCheckIfLocked
     * @return bool
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function isMeetReviseRegularPriceRequirements(ListingProduct $listingProduct,
                                                         $needSynchRulesCheckIfLocked = true)
    {
        /** @var \Ess\M2ePro\Model\Amazon\Listing\Product $amazonListingProduct */
        $amazonListingProduct = $listingProduct->getChildObject();

        if (!$this->isMeetReviseGeneralRequirements($listingProduct, $needSynchRulesCheckIfLocked)) {
            return false;
        }

        if (!$amazonListingProduct->isAllowedForRegularCustomers()) {
            return false;
        }

        if ($this->getHelper('Component\Amazon\Repricing')->isEnabled() &&
            $amazonListingProduct->isRepricingEnabled()) {
            return false;
        }

        $amazonSynchronizationTemplate = $amazonListingProduct->getAmazonSynchronizationTemplate();

        if (!$amazonSynchronizationTemplate->isReviseWhenChangePrice()) {
            return false;
        }

        $currentPrice = $amazonListingProduct->getRegularPrice();
        $onlinePrice  = $amazonListingProduct->getOnlineRegularPrice();

        $needRevise = $this->checkRevisePricesRequirements($amazonSynchronizationTemplate, $onlinePrice, $currentPrice);
        if ($needRevise) {
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

        if ((is_null($currentSalePrice) && !is_null($onlineSalePrice)) ||
            (!is_null($currentSalePrice) && is_null($onlineSalePrice))
        ) {
            return true;
        }

        $needRevise = $this->checkRevisePricesRequirements(
            $amazonSynchronizationTemplate, $onlineSalePrice, $currentSalePrice
        );

        if ($needRevise) {
            return true;
        }

        $onlineSalePriceStartDate  = $amazonListingProduct->getOnlineRegularSalePriceStartDate();
        $onlineSalePriceEndDate    = $amazonListingProduct->getOnlineRegularSalePriceEndDate();

        if ($currentSalePriceStartDate != $onlineSalePriceStartDate ||
            $currentSalePriceEndDate   != $onlineSalePriceEndDate
        ) {
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
    public function isMeetReviseBusinessPriceRequirements(ListingProduct $listingProduct,
                                                          $needSynchRulesCheckIfLocked = true)
    {
        /** @var \Ess\M2ePro\Model\Amazon\Listing\Product $amazonListingProduct */
        $amazonListingProduct = $listingProduct->getChildObject();

        if (!$this->isMeetReviseGeneralRequirements($listingProduct, $needSynchRulesCheckIfLocked)) {
            return false;
        }

        if (!$amazonListingProduct->isAllowedForBusinessCustomers()) {
            return false;
        }

        $amazonSynchronizationTemplate = $amazonListingProduct->getAmazonSynchronizationTemplate();

        if (!$amazonSynchronizationTemplate->isReviseWhenChangePrice()) {
            return false;
        }

        $currentPrice = $amazonListingProduct->getBusinessPrice();
        $onlinePrice  = $amazonListingProduct->getOnlineBusinessPrice();

        if ($this->checkRevisePricesRequirements($amazonSynchronizationTemplate, $onlinePrice, $currentPrice)) {
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

            $needRevise = $this->checkRevisePricesRequirements(
                $amazonSynchronizationTemplate, $onlineDiscount, $currentDiscount
            );

            if ($needRevise) {
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
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function isMeetReviseDetailsRequirements(ListingProduct $listingProduct,
                                                    $needSynchRulesCheckIfLocked = true)
    {
        if (!$this->isMeetReviseGeneralRequirements($listingProduct, $needSynchRulesCheckIfLocked)) {
            return false;
        }

        /** @var \Ess\M2ePro\Model\Amazon\Listing\Product $amazonListingProduct */
        $amazonListingProduct = $listingProduct->getChildObject();

        $amazonSynchronizationTemplate = $amazonListingProduct->getAmazonSynchronizationTemplate();
        if (!$amazonSynchronizationTemplate->isReviseWhenChangeDetails()) {
            return false;
        }

        return true;
    }

    /**
     * @param \Ess\M2ePro\Model\Listing\Product $listingProduct
     * @param bool $needSynchRulesCheckIfLocked
     * @return bool
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function isMeetReviseImagesRequirements(ListingProduct $listingProduct,
                                                   $needSynchRulesCheckIfLocked = true)
    {
        if (!$this->isMeetReviseGeneralRequirements($listingProduct, $needSynchRulesCheckIfLocked)) {
            return false;
        }

        /** @var \Ess\M2ePro\Model\Amazon\Listing\Product $amazonListingProduct */
        $amazonListingProduct = $listingProduct->getChildObject();

        $amazonSynchronizationTemplate = $amazonListingProduct->getAmazonSynchronizationTemplate();
        if (!$amazonSynchronizationTemplate->isReviseWhenChangeImages()) {
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

        /** @var \Ess\M2ePro\Model\Amazon\Listing\Product $amazonListingProduct */
        $amazonListingProduct = $listingProduct->getChildObject();

        $synchronizationTemplate = $amazonListingProduct->getSynchronizationTemplate();
        $amazonSynchronizationTemplate = $amazonListingProduct->getAmazonSynchronizationTemplate();

        foreach ($reasons as $reason) {

            $method = 'isRevise'.ucfirst($reason);

            if (method_exists($synchronizationTemplate, $method)) {
                if ($synchronizationTemplate->$method()) {
                    return true;
                }

                continue;
            }

            if (method_exists($amazonSynchronizationTemplate, $method)) {
                if ($amazonSynchronizationTemplate->$method()) {
                    return true;
                }

                continue;
            }
        }

        return false;
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
    public function getMeetAdvancedRequirementsProducts(array $lpForAdvancedRules,
                                                        $ruleModelPrefix,
                                                        $ruleFiltersDataKeyPrefix)
    {
        $resultProducts = [];

        foreach ($lpForAdvancedRules as $templateId => $productsByTemplate) {

            /** @var \Ess\M2ePro\Model\Amazon\Template\Synchronization $amazonTemplate */
            $template = $this->amazonFactory->getCachedObjectLoaded('Template\Synchronization', $templateId);
            $amazonTemplate = $template->getChildObject();

            foreach ($productsByTemplate as $storeId => $productsByStore) {

                /* @var $tempCollection \Magento\Catalog\Model\ResourceModel\Product\Collection */
                $tempCollection = $this->magentoProductCollectionFactory->create();
                $tempCollection->addFieldToFilter('entity_id', ['in' => array_keys($productsByStore)]);
                $tempCollection->setStoreId($storeId);

                $ruleModel = $this->activeRecordFactory->getObject('Magento\Product\Rule')->setData(
                    [
                        'store_id' => $storeId,
                        'prefix'   => $ruleModelPrefix
                    ]
                );

                $templateData = $amazonTemplate->getData($ruleFiltersDataKeyPrefix . '_advanced_rules_filters');
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
        /** @var \Ess\M2ePro\Model\Amazon\Listing\Product $amazonListingProduct */
        $amazonListingProduct = $listingProduct->getChildObject();
        $amazonTemplate = $amazonListingProduct->getAmazonSynchronizationTemplate();

        if (!$amazonTemplate->isListAdvancedRulesEnabled()) {
            return false;
        }

        return $this->isMeetAdvancedRequirements(
            $listingProduct, SynchronizationPolicy::LIST_ADVANCED_RULES_PREFIX, 'list'
        );
    }

    /**
     * @param \Ess\M2ePro\Model\Listing\Product $listingProduct
     * @return bool
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function isMeetAdvancedRelistRequirements(ListingProduct $listingProduct)
    {
        /** @var \Ess\M2ePro\Model\Amazon\Listing\Product $amazonListingProduct */
        $amazonListingProduct = $listingProduct->getChildObject();
        $amazonTemplate = $amazonListingProduct->getAmazonSynchronizationTemplate();

        if (!$amazonTemplate->isRelistAdvancedRulesEnabled()) {
            return false;
        }

        return $this->isMeetAdvancedRequirements(
            $listingProduct, SynchronizationPolicy::RELIST_ADVANCED_RULES_PREFIX, 'relist'
        );
    }

    /**
     * @param \Ess\M2ePro\Model\Listing\Product $listingProduct
     * @return bool
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function isMeetAdvancedStopRequirements(ListingProduct $listingProduct)
    {
        /** @var \Ess\M2ePro\Model\Amazon\Listing\Product $amazonListingProduct */
        $amazonListingProduct = $listingProduct->getChildObject();
        $amazonTemplate = $amazonListingProduct->getAmazonSynchronizationTemplate();

        if (!$amazonTemplate->isStopAdvancedRulesEnabled()) {
            return false;
        }

        return $this->isMeetAdvancedRequirements(
            $listingProduct, SynchronizationPolicy::STOP_ADVANCED_RULES_PREFIX, 'stop'
        );
    }

    /**
     * @param \Ess\M2ePro\Model\Listing\Product $listingProduct
     * @param string $ruleModelPrefix
     * @param string $ruleFiltersDataKeyPrefix
     * @return bool
     * @throws \Ess\M2ePro\Model\Exception\Logic
     * @throws \Exception
     */
    private function isMeetAdvancedRequirements(ListingProduct $listingProduct,
                                                $ruleModelPrefix,
                                                $ruleFiltersDataKeyPrefix)
    {
        /** @var \Ess\M2ePro\Model\Amazon\Listing\Product $amazonListingProduct */
        $amazonListingProduct = $listingProduct->getChildObject();

        /* @var $tempCollection \Magento\Catalog\Model\ResourceModel\Product\Collection */
        $tempCollection = $this->magentoProductCollectionFactory->create();
        $tempCollection->addFieldToFilter('entity_id', $listingProduct->getProductId());
        $tempCollection->setStoreId($listingProduct->getListing()->getStoreId());

        $ruleModel = $this->activeRecordFactory->getObject('Magento\Product\Rule')->setData(
            [
                'store_id' => $listingProduct->getListing()->getStoreId(),
                'prefix'   => $ruleModelPrefix
            ]
        );

        $amazonTemplate = $amazonListingProduct->getAmazonSynchronizationTemplate();
        $templateData = $amazonTemplate->getData($ruleFiltersDataKeyPrefix . '_advanced_rules_filters');
        $templateData && $ruleModel->loadFromSerialized($templateData);

        $ruleModel->setAttributesFilterToCollection($tempCollection);

        return $tempCollection->getSize();
    }

    //########################################

    private function checkRevisePricesRequirements(
        \Ess\M2ePro\Model\Amazon\Template\Synchronization $amazonSynchronizationTemplate,
        $onlinePrice, $productPrice
    ) {
        if ((float)$onlinePrice == (float)$productPrice) {
            return false;
        }

        if ((float)$onlinePrice <= 0) {
            return true;
        }

        if ($amazonSynchronizationTemplate->isReviseUpdatePriceMaxAllowedDeviationModeOff()) {
            return true;
        }

        $deviation = round(abs($onlinePrice - $productPrice) / $onlinePrice * 100, 2);

        return $deviation > $amazonSynchronizationTemplate->getReviseUpdatePriceMaxAllowedDeviation();
    }

    //########################################
}