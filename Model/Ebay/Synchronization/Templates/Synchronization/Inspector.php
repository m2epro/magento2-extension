<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Synchronization\Templates\Synchronization;

use Ess\M2ePro\Model\Listing\Product as ListingProduct;
use Ess\M2ePro\Model\Ebay\Template\Synchronization as SynchronizationPolicy;

class Inspector extends \Ess\M2ePro\Model\Synchronization\Templates\Synchronization\Inspector
{
    private $ebayFactory;
    private $magentoProductCollectionFactory;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $magentoProductCollectionFactory,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $ebayFactory
    )
    {
        parent::__construct($activeRecordFactory, $helperFactory, $modelFactory);

        $this->magentoProductCollectionFactory = $magentoProductCollectionFactory;
        $this->ebayFactory = $ebayFactory;
    }

    //########################################

    /**
     * @param \Ess\M2ePro\Model\Listing\Product $listingProduct
     * @param bool $needSynchRulesCheckIfLocked
     * @return bool
     * @throws \Ess\M2ePro\Model\Exception\Logic
     * @throws \Exception
     */
    public function isMeetListRequirements(\Ess\M2ePro\Model\Listing\Product $listingProduct,
                                           $needSynchRulesCheckIfLocked = true)
    {
        if (!$listingProduct->isNotListed()) {
            return false;
        }

        if (!$listingProduct->isListable()) {
            return false;
        }

        /** @var \Ess\M2ePro\Model\Ebay\Listing\Product $ebayListingProduct */
        $ebayListingProduct = $listingProduct->getChildObject();

        $ebaySynchronizationTemplate = $ebayListingProduct->getEbaySynchronizationTemplate();

        if (!$ebaySynchronizationTemplate->isListMode()) {
            return false;
        }

        $additionalData = $listingProduct->getAdditionalData();

        if (!$ebayListingProduct->isSetCategoryTemplate()) {
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

        $log = $this->getHelper('Module\Log');

        if ($ebaySynchronizationTemplate->isListStatusEnabled()) {

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
            } else if ($ebayListingProduct->isVariationsReady()) {

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

        if ($ebaySynchronizationTemplate->isListIsInStock()) {

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
            } else if ($ebayListingProduct->isVariationsReady()) {

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

        if ($ebaySynchronizationTemplate->isListWhenQtyMagentoHasValue()) {

            $result = false;
            $productQty = (int)$listingProduct->getMagentoProduct()->getQty(true);

            $typeQty = (int)$ebaySynchronizationTemplate->getListWhenQtyMagentoHasValueType();
            $minQty = (int)$ebaySynchronizationTemplate->getListWhenQtyMagentoHasValueMin();
            $maxQty = (int)$ebaySynchronizationTemplate->getListWhenQtyMagentoHasValueMax();

            $note = '';

            if ($typeQty == \Ess\M2ePro\Model\Ebay\Template\Synchronization::LIST_QTY_LESS) {
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

            if ($typeQty == \Ess\M2ePro\Model\Ebay\Template\Synchronization::LIST_QTY_MORE) {
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

            if ($typeQty == \Ess\M2ePro\Model\Ebay\Template\Synchronization::LIST_QTY_BETWEEN) {
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

        if ($ebaySynchronizationTemplate->isListWhenQtyCalculatedHasValue()) {

            $result = false;
            $productQty = (int)$ebayListingProduct->getQty();

            $typeQty = (int)$ebaySynchronizationTemplate->getListWhenQtyCalculatedHasValueType();
            $minQty = (int)$ebaySynchronizationTemplate->getListWhenQtyCalculatedHasValueMin();
            $maxQty = (int)$ebaySynchronizationTemplate->getListWhenQtyCalculatedHasValueMax();

            $note = '';

            if ($typeQty == \Ess\M2ePro\Model\Ebay\Template\Synchronization::LIST_QTY_LESS) {
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

            if ($typeQty == \Ess\M2ePro\Model\Ebay\Template\Synchronization::LIST_QTY_MORE) {
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

            if ($typeQty == \Ess\M2ePro\Model\Ebay\Template\Synchronization::LIST_QTY_BETWEEN) {
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
     * @return bool
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function isMeetRelistRequirements(\Ess\M2ePro\Model\Listing\Product $listingProduct,
                                             $needSynchRulesCheckIfLocked = true)
    {
        if ($listingProduct->isListed()) {
            return false;
        }

        if (!$listingProduct->isRelistable() && !$listingProduct->isHidden()) {
            return false;
        }

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

        if ($listingProduct->isSetProcessingLock('in_action')) {
            if ($needSynchRulesCheckIfLocked) {
                $this->activeRecordFactory->getObject('Listing\Product')
                     ->getResource()->setNeedSynchRulesCheck(array($listingProduct->getId()));
            }
            return false;
        }

        $variationResource = $this->activeRecordFactory->getObject('Listing\Product\Variation')->getResource();

        // Check filters
        // ---------------------------------------
        if ($ebaySynchronizationTemplate->isRelistStatusEnabled()) {

            if (!$listingProduct->getMagentoProduct()->isStatusEnabled()) {
                return false;
            } else if ($ebayListingProduct->isVariationsReady()) {

                $temp = $variationResource->isAllStatusesDisabled(
                    $listingProduct->getId(),
                    $listingProduct->getListing()->getStoreId()
                );

                if (!is_null($temp) && $temp) {
                    return false;
                }
            }
        }

        if ($ebaySynchronizationTemplate->isRelistIsInStock()) {

            if (!$listingProduct->getMagentoProduct()->isStockAvailability()) {
                return false;
            } else if ($ebayListingProduct->isVariationsReady()) {

                $temp = $variationResource->isAllDoNotHaveStockAvailabilities(
                    $listingProduct->getId(),
                    $listingProduct->getListing()->getStoreId()
                );

                if (!is_null($temp) && $temp) {
                    return false;
                }
            }
        }

        if ($ebaySynchronizationTemplate->isRelistWhenQtyMagentoHasValue()) {

            $result = false;
            $productQty = (int)$listingProduct->getMagentoProduct()->getQty(true);

            $typeQty = (int)$ebaySynchronizationTemplate->getRelistWhenQtyMagentoHasValueType();
            $minQty = (int)$ebaySynchronizationTemplate->getRelistWhenQtyMagentoHasValueMin();
            $maxQty = (int)$ebaySynchronizationTemplate->getRelistWhenQtyMagentoHasValueMax();

            if ($typeQty == \Ess\M2ePro\Model\Ebay\Template\Synchronization::RELIST_QTY_LESS &&
                $productQty <= $minQty) {
                $result = true;
            }

            if ($typeQty == \Ess\M2ePro\Model\Ebay\Template\Synchronization::RELIST_QTY_MORE &&
                $productQty >= $minQty) {
                $result = true;
            }

            if ($typeQty == \Ess\M2ePro\Model\Ebay\Template\Synchronization::RELIST_QTY_BETWEEN &&
                $productQty >= $minQty && $productQty <= $maxQty) {
                $result = true;
            }

            if (!$result) {
                return false;
            }
        }

        if ($ebaySynchronizationTemplate->isRelistWhenQtyCalculatedHasValue()) {

            $result = false;
            $productQty = (int)$ebayListingProduct->getQty();

            $typeQty = (int)$ebaySynchronizationTemplate->getRelistWhenQtyCalculatedHasValueType();
            $minQty = (int)$ebaySynchronizationTemplate->getRelistWhenQtyCalculatedHasValueMin();
            $maxQty = (int)$ebaySynchronizationTemplate->getRelistWhenQtyCalculatedHasValueMax();

            if ($typeQty == \Ess\M2ePro\Model\Ebay\Template\Synchronization::RELIST_QTY_LESS &&
                $productQty <= $minQty) {
                $result = true;
            }

            if ($typeQty == \Ess\M2ePro\Model\Ebay\Template\Synchronization::RELIST_QTY_MORE &&
                $productQty >= $minQty) {
                $result = true;
            }

            if ($typeQty == \Ess\M2ePro\Model\Ebay\Template\Synchronization::RELIST_QTY_BETWEEN &&
                $productQty >= $minQty && $productQty <= $maxQty) {
                $result = true;
            }

            if (!$result) {
                return false;
            }
        }
        // ---------------------------------------

        return true;
    }

    // ---------------------------------------

    /**
     * @param \Ess\M2ePro\Model\Listing\Product $listingProduct
     * @param bool $needSynchRulesCheckIfLocked
     * @return bool
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function isMeetStopGeneralRequirements(\Ess\M2ePro\Model\Listing\Product $listingProduct,
                                                  $needSynchRulesCheckIfLocked = true)
    {
        if (!$listingProduct->isListed()) {
            return false;
        }

        if (!$listingProduct->isStoppable() || $listingProduct->isHidden()) {
            return false;
        }

        /** @var \Ess\M2ePro\Model\Ebay\Listing\Product $ebayListingProduct */
        $ebayListingProduct = $listingProduct->getChildObject();

        if (!$ebayListingProduct->isSetCategoryTemplate()) {
            return false;
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
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function isMeetStopRequirements(\Ess\M2ePro\Model\Listing\Product $listingProduct,
                                           $needSynchRulesCheckIfLocked = true)
    {
        /** @var \Ess\M2ePro\Model\Ebay\Listing\Product $ebayListingProduct */
        $ebayListingProduct = $listingProduct->getChildObject();
        $ebaySynchronizationTemplate = $ebayListingProduct->getEbaySynchronizationTemplate();

        $variationResource = $this->activeRecordFactory->getObject('Listing\Product\Variation')->getResource();

        if ($ebaySynchronizationTemplate->isStopStatusDisabled()) {

            if (!$listingProduct->getMagentoProduct()->isStatusEnabled()) {
                return true;
            } else if ($ebayListingProduct->isVariationsReady()) {

                $temp = $variationResource->isAllStatusesDisabled(
                    $listingProduct->getId(),
                    $listingProduct->getListing()->getStoreId()
                );

                if (!is_null($temp) && $temp) {
                    return true;
                }
            }
        }

        if ($ebaySynchronizationTemplate->isStopOutOfStock()) {

            if (!$listingProduct->getMagentoProduct()->isStockAvailability()) {
                return true;
            } else if ($ebayListingProduct->isVariationsReady()) {

                $temp = $variationResource->isAllDoNotHaveStockAvailabilities(
                    $listingProduct->getId(),
                    $listingProduct->getListing()->getStoreId()
                );

                if (!is_null($temp) && $temp) {
                    return true;
                }
            }
        }

        if ($ebaySynchronizationTemplate->isStopWhenQtyMagentoHasValue()) {

            $productQty = (int)$listingProduct->getMagentoProduct()->getQty(true);

            $typeQty = (int)$ebaySynchronizationTemplate->getStopWhenQtyMagentoHasValueType();
            $minQty = (int)$ebaySynchronizationTemplate->getStopWhenQtyMagentoHasValueMin();
            $maxQty = (int)$ebaySynchronizationTemplate->getStopWhenQtyMagentoHasValueMax();

            if ($typeQty == \Ess\M2ePro\Model\Ebay\Template\Synchronization::STOP_QTY_LESS &&
                $productQty <= $minQty) {
                return true;
            }

            if ($typeQty == \Ess\M2ePro\Model\Ebay\Template\Synchronization::STOP_QTY_MORE &&
                $productQty >= $minQty) {
                return true;
            }

            if ($typeQty == \Ess\M2ePro\Model\Ebay\Template\Synchronization::STOP_QTY_BETWEEN &&
                $productQty >= $minQty && $productQty <= $maxQty) {
                return true;
            }
        }

        if ($ebaySynchronizationTemplate->isStopWhenQtyCalculatedHasValue()) {

            $productQty = (int)$ebayListingProduct->getQty();

            $typeQty = (int)$ebaySynchronizationTemplate->getStopWhenQtyCalculatedHasValueType();
            $minQty = (int)$ebaySynchronizationTemplate->getStopWhenQtyCalculatedHasValueMin();
            $maxQty = (int)$ebaySynchronizationTemplate->getStopWhenQtyCalculatedHasValueMax();

            if ($typeQty == \Ess\M2ePro\Model\Ebay\Template\Synchronization::STOP_QTY_LESS &&
                $productQty <= $minQty) {
                return true;
            }

            if ($typeQty == \Ess\M2ePro\Model\Ebay\Template\Synchronization::STOP_QTY_MORE &&
                $productQty >= $minQty) {
                return true;
            }

            if ($typeQty == \Ess\M2ePro\Model\Ebay\Template\Synchronization::STOP_QTY_BETWEEN &&
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
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function isMeetReviseGeneralRequirements(\Ess\M2ePro\Model\Listing\Product $listingProduct,
                                                    $needSynchRulesCheckIfLocked = true)
    {
        if (!$listingProduct->isListed()) {
            return false;
        }

        if (!$listingProduct->isRevisable() || $listingProduct->isHidden()) {
            return false;
        }

        /** @var \Ess\M2ePro\Model\Ebay\Listing\Product $ebayListingProduct */
        $ebayListingProduct = $listingProduct->getChildObject();

        if (!$ebayListingProduct->isSetCategoryTemplate()) {
            return false;
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
     * @return bool
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function isMeetReviseQtyRequirements(\Ess\M2ePro\Model\Listing\Product $listingProduct,
                                                $needSynchRulesCheckIfLocked = true)
    {
        if (!$this->isMeetReviseGeneralRequirements($listingProduct, $needSynchRulesCheckIfLocked)) {
            return false;
        }

        /** @var \Ess\M2ePro\Model\Ebay\Listing\Product $ebayListingProduct */
        $ebayListingProduct = $listingProduct->getChildObject();

        $ebaySynchronizationTemplate = $ebayListingProduct->getEbaySynchronizationTemplate();

        if (!$ebaySynchronizationTemplate->isReviseWhenChangeQty()) {
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

            if ($productQty > 0 && $productQty != $channelQty) {
                return true;
            }

        } else {

            $totalQty = 0;
            $hasChange = false;

            $variations = $listingProduct->getVariations(true);

            foreach ($variations as $variation) {

                /** @var \Ess\M2ePro\Model\Ebay\Listing\Product\Variation $ebayVariation */
                $ebayVariation = $variation->getChildObject();

                $productQty = $ebayVariation->getQty();
                $channelQty = $ebayVariation->getOnlineQty() - $ebayVariation->getOnlineQtySold();

                if ($productQty != $channelQty) {
                    // Check ReviseUpdateQtyMaxAppliedValue
                    (!$isMaxAppliedValueModeOn || $productQty <= $maxAppliedValue || $channelQty <= $maxAppliedValue) &&
                    $hasChange = true;
                }

                $totalQty += $productQty;
            }

            if ($totalQty > 0 && $hasChange) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param \Ess\M2ePro\Model\Listing\Product $listingProduct
     * @param bool $needSynchRulesCheckIfLocked
     * @return bool
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function isMeetRevisePriceRequirements(\Ess\M2ePro\Model\Listing\Product $listingProduct,
                                                  $needSynchRulesCheckIfLocked = true)
    {
        if (!$this->isMeetReviseGeneralRequirements($listingProduct, $needSynchRulesCheckIfLocked)) {
            return false;
        }

        /** @var \Ess\M2ePro\Model\Ebay\Listing\Product $ebayListingProduct */
        $ebayListingProduct = $listingProduct->getChildObject();

        $ebaySynchronizationTemplate = $ebayListingProduct->getEbaySynchronizationTemplate();

        if (!$ebaySynchronizationTemplate->isReviseWhenChangePrice()) {
            return false;
        }

        if (!$ebayListingProduct->isVariationsReady()) {

            if ($ebayListingProduct->isListingTypeFixed()) {

                $needRevise = $this->checkRevisePricesRequirements(
                    $ebaySynchronizationTemplate,
                    $ebayListingProduct->getOnlineCurrentPrice(),
                    $ebayListingProduct->getFixedPrice()
                );

                if ($needRevise) {
                    return true;
                }
            }

            if ($ebayListingProduct->isListingTypeAuction()) {

                $needRevise = $this->checkRevisePricesRequirements(
                    $ebaySynchronizationTemplate,
                    $ebayListingProduct->getOnlineStartPrice(),
                    $ebayListingProduct->getStartPrice()
                );

                if ($needRevise) {
                    return true;
                }

                $needRevise = $this->checkRevisePricesRequirements(
                    $ebaySynchronizationTemplate,
                    $ebayListingProduct->getOnlineReservePrice(),
                    $ebayListingProduct->getReservePrice()
                );

                if ($needRevise) {
                    return true;
                }

                $needRevise = $this->checkRevisePricesRequirements(
                    $ebaySynchronizationTemplate,
                    $ebayListingProduct->getOnlineBuyItNowPrice(),
                    $ebayListingProduct->getBuyItNowPrice()
                );

                if ($needRevise) {
                    return true;
                }
            }

        } else {

            $variations = $listingProduct->getVariations(true);

            foreach ($variations as $variation) {

                /** @var \Ess\M2ePro\Model\Ebay\Listing\Product\Variation $ebayVariation */
                $ebayVariation = $variation->getChildObject();

                $needRevise = $this->checkRevisePricesRequirements(
                    $ebaySynchronizationTemplate,
                    $ebayVariation->getOnlinePrice(),
                    $ebayVariation->getPrice()
                );

                if ($needRevise) {
                    return true;
                }
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
    public function isMeetReviseTitleRequirements(\Ess\M2ePro\Model\Listing\Product $listingProduct,
                                                  $needSynchRulesCheckIfLocked = true)
    {
        if (!$this->isMeetReviseGeneralRequirements($listingProduct, $needSynchRulesCheckIfLocked)) {
            return false;
        }

        /** @var \Ess\M2ePro\Model\Ebay\Listing\Product $ebayListingProduct */
        $ebayListingProduct = $listingProduct->getChildObject();

        if (!$ebayListingProduct->getEbaySynchronizationTemplate()->isReviseWhenChangeTitle()) {
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
    public function isMeetReviseSubTitleRequirements(\Ess\M2ePro\Model\Listing\Product $listingProduct,
                                                     $needSynchRulesCheckIfLocked = true)
    {
        if (!$this->isMeetReviseGeneralRequirements($listingProduct, $needSynchRulesCheckIfLocked)) {
            return false;
        }

        /** @var \Ess\M2ePro\Model\Ebay\Listing\Product $ebayListingProduct */
        $ebayListingProduct = $listingProduct->getChildObject();

        if (!$ebayListingProduct->getEbaySynchronizationTemplate()->isReviseWhenChangeSubTitle()) {
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
    public function isMeetReviseDescriptionRequirements(\Ess\M2ePro\Model\Listing\Product $listingProduct,
                                                        $needSynchRulesCheckIfLocked = true)
    {
        if (!$this->isMeetReviseGeneralRequirements($listingProduct, $needSynchRulesCheckIfLocked)) {
            return false;
        }

        /** @var \Ess\M2ePro\Model\Ebay\Listing\Product $ebayListingProduct */
        $ebayListingProduct = $listingProduct->getChildObject();

        if (!$ebayListingProduct->getEbaySynchronizationTemplate()->isReviseWhenChangeDescription()) {
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
    public function isMeetReviseImagesRequirements(\Ess\M2ePro\Model\Listing\Product $listingProduct,
                                                   $needSynchRulesCheckIfLocked = true)
    {
        if (!$this->isMeetReviseGeneralRequirements($listingProduct, $needSynchRulesCheckIfLocked)) {
            return false;
        }

        /** @var \Ess\M2ePro\Model\Ebay\Listing\Product $ebayListingProduct */
        $ebayListingProduct = $listingProduct->getChildObject();

        if (!$ebayListingProduct->getEbaySynchronizationTemplate()->isReviseWhenChangeImages()) {
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
    public function isMeetReviseSpecificsRequirements(\Ess\M2ePro\Model\Listing\Product $listingProduct,
                                                      $needSynchRulesCheckIfLocked = true)
    {
        if (!$this->isMeetReviseGeneralRequirements($listingProduct, $needSynchRulesCheckIfLocked)) {
            return false;
        }

        /** @var \Ess\M2ePro\Model\Ebay\Listing\Product $ebayListingProduct */
        $ebayListingProduct = $listingProduct->getChildObject();

        if (!$ebayListingProduct->getEbaySynchronizationTemplate()->isReviseWhenChangeSpecifics()) {
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
    public function isMeetReviseShippingServicesRequirements(\Ess\M2ePro\Model\Listing\Product $listingProduct,
                                                             $needSynchRulesCheckIfLocked = true)
    {
        if (!$this->isMeetReviseGeneralRequirements($listingProduct, $needSynchRulesCheckIfLocked)) {
            return false;
        }

        /** @var \Ess\M2ePro\Model\Ebay\Listing\Product $ebayListingProduct */
        $ebayListingProduct = $listingProduct->getChildObject();

        if (!$ebayListingProduct->getEbaySynchronizationTemplate()->isReviseWhenChangeShippingServices()) {
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
    public function isMeetReviseSynchReasonsRequirements(\Ess\M2ePro\Model\Listing\Product $listingProduct,
                                                         $needSynchRulesCheckIfLocked = true)
    {
        $reasons = $listingProduct->getSynchReasons();
        if (empty($reasons)) {
            return false;
        }

        if (!$this->isMeetReviseGeneralRequirements($listingProduct, $needSynchRulesCheckIfLocked)) {
            return false;
        }

        /** @var \Ess\M2ePro\Model\Ebay\Listing\Product $ebayListingProduct */
        $ebayListingProduct = $listingProduct->getChildObject();

        $synchronizationTemplate = $ebayListingProduct->getSynchronizationTemplate();
        $ebaySynchronizationTemplate = $ebayListingProduct->getEbaySynchronizationTemplate();

        foreach ($reasons as $reason) {

            if ($reason == 'otherCategoryTemplate') {
                $reason = 'categoryTemplate';
            }

            $method = 'isRevise'.ucfirst($reason);

            if (method_exists($synchronizationTemplate, $method)) {
                if ($synchronizationTemplate->$method()) {
                    return true;
                }

                continue;
            }

            if (method_exists($ebaySynchronizationTemplate, $method)) {
                if ($ebaySynchronizationTemplate->$method()) {
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

            /** @var \Ess\M2ePro\Model\Ebay\Template\Synchronization $ebayTemplate */
            $template = $this->ebayFactory->getCachedObjectLoaded('Template\Synchronization', $templateId);
            $ebayTemplate = $template->getChildObject();

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

                $templateData = $ebayTemplate->getData($ruleFiltersDataKeyPrefix . '_advanced_rules_filters');
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
        /** @var \Ess\M2ePro\Model\Ebay\Listing\Product $ebayListingProduct */
        $ebayListingProduct = $listingProduct->getChildObject();
        $ebayTemplate = $ebayListingProduct->getEbaySynchronizationTemplate();

        if (!$ebayTemplate->isListAdvancedRulesEnabled()) {
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
        /** @var \Ess\M2ePro\Model\Ebay\Listing\Product $ebayListingProduct */
        $ebayListingProduct = $listingProduct->getChildObject();
        $ebayTemplate = $ebayListingProduct->getEbaySynchronizationTemplate();

        if (!$ebayTemplate->isRelistAdvancedRulesEnabled()) {
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
        /** @var \Ess\M2ePro\Model\Ebay\Listing\Product $ebayListingProduct */
        $ebayListingProduct = $listingProduct->getChildObject();
        $ebayTemplate = $ebayListingProduct->getEbaySynchronizationTemplate();

        if (!$ebayTemplate->isStopAdvancedRulesEnabled()) {
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
        /** @var \Ess\M2ePro\Model\Ebay\Listing\Product $ebayListingProduct */
        $ebayListingProduct = $listingProduct->getChildObject();

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

        $ebayTemplate = $ebayListingProduct->getEbaySynchronizationTemplate();
        $templateData = $ebayTemplate->getData($ruleFiltersDataKeyPrefix . '_advanced_rules_filters');
        $templateData && $ruleModel->loadFromSerialized($templateData);

        $ruleModel->setAttributesFilterToCollection($tempCollection);

        return $tempCollection->getSize();
    }

    //########################################

    private function checkRevisePricesRequirements(
        \Ess\M2ePro\Model\Ebay\Template\Synchronization $ebaySynchronizationTemplate,
        $onlinePrice, $productPrice
    ) {
        if ((float)$onlinePrice == (float)$productPrice) {
            return false;
        }

        if ((float)$onlinePrice <= 0) {
            return true;
        }

        if ($ebaySynchronizationTemplate->isReviseUpdatePriceMaxAllowedDeviationModeOff()) {
            return true;
        }

        $deviation = round(abs($onlinePrice - $productPrice) / $onlinePrice * 100, 2);

        return $deviation > $ebaySynchronizationTemplate->getReviseUpdatePriceMaxAllowedDeviation();
    }

    //########################################
}