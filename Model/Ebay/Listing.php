<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay;

/**
 * @method \Ess\M2ePro\Model\Listing getParentObject()
 */
class Listing extends \Ess\M2ePro\Model\ActiveRecord\Component\Child\Ebay\AbstractModel
{
    const ADDING_MODE_ADD_AND_ASSIGN_CATEGORY = 2;

    const PARTS_COMPATIBILITY_MODE_EPIDS  = 'epids';
    const PARTS_COMPATIBILITY_MODE_KTYPES = 'ktypes';

    const CREATE_LISTING_SESSION_DATA = 'ebay_listing_create';

    /**
     * @var \Ess\M2ePro\Model\Ebay\Template\Category
     */
    private $autoGlobalAddingCategoryTemplateModel = null;

    /**
     * @var \Ess\M2ePro\Model\Ebay\Template\Category
     */
    protected $autoGlobalAddingCategorySecondaryTemplateModel = null;

    /**
     * @var \Ess\M2ePro\Model\Ebay\Template\StoreCategory
     */
    protected $autoGlobalAddingStoreCategoryTemplateModel = null;

    /**
     * @var \Ess\M2ePro\Model\Ebay\Template\StoreCategory
     */
    protected $autoGlobalAddingStoreCategorySecondaryTemplateModel = null;

    /**
     * @var \Ess\M2ePro\Model\Ebay\Template\Category
     */
    private $autoWebsiteAddingCategoryTemplateModel = null;

    /**
     * @var \Ess\M2ePro\Model\Ebay\Template\Category
     */
    protected $autoWebsiteAddingCategorySecondaryTemplateModel = null;

    /**
     * @var \Ess\M2ePro\Model\Ebay\Template\StoreCategory
     */
    protected $autoWebsiteAddingStoreCategoryTemplateModel = null;

    /**
     * @var \Ess\M2ePro\Model\Ebay\Template\StoreCategory
     */
    protected $autoWebsiteAddingStoreCategorySecondaryTemplateModel = null;

    /**
     * @var \Ess\M2ePro\Model\Ebay\Template\Manager[]
     */
    private $templateManagers = [];

    // ---------------------------------------

    /**
     * @var \Ess\M2ePro\Model\Template\SellingFormat
     */
    private $sellingFormatTemplateModel = null;

    /**
     * @var \Ess\M2ePro\Model\Template\Synchronization
     */
    private $synchronizationTemplateModel = null;

    /**
     * @var \Ess\M2ePro\Model\Template\Description
     */
    private $descriptionTemplateModel = null;

    /**
     * @var \Ess\M2ePro\Model\Ebay\Template\Payment
     */
    private $paymentTemplateModel = null;

    /**
     * @var \Ess\M2ePro\Model\Ebay\Template\ReturnPolicy
     */
    private $returnTemplateModel = null;

    /**
     * @var \Ess\M2ePro\Model\Ebay\Template\Shipping
     */
    private $shippingTemplateModel = null;

    //########################################

    public function _construct()
    {
        parent::_construct();
        $this->_init('Ess\M2ePro\Model\ResourceModel\Ebay\Listing');
    }

    //########################################

    public function save()
    {
        $this->getHelper('Data_Cache_Permanent')->removeTagValues('listing');

        return parent::save();
    }

    //########################################

    public function delete()
    {
        if ($this->isLocked()) {
            return false;
        }

        $this->templateManagers = [];

        $this->autoGlobalAddingCategoryTemplateModel = null;
        $this->autoGlobalAddingCategorySecondaryTemplateModel = null;
        $this->autoGlobalAddingStoreCategoryTemplateModel = null;
        $this->autoGlobalAddingStoreCategorySecondaryTemplateModel = null;

        $this->autoWebsiteAddingCategoryTemplateModel = null;
        $this->autoWebsiteAddingCategorySecondaryTemplateModel = null;
        $this->autoWebsiteAddingStoreCategoryTemplateModel = null;
        $this->autoWebsiteAddingStoreCategorySecondaryTemplateModel = null;

        $this->sellingFormatTemplateModel = null;
        $this->synchronizationTemplateModel = null;
        $this->descriptionTemplateModel = null;
        $this->paymentTemplateModel = null;
        $this->returnTemplateModel = null;
        $this->shippingTemplateModel = null;

        $this->getHelper('Data_Cache_Permanent')->removeTagValues('listing');

        return parent::delete();
    }

    //########################################

    /**
     * @return \Ess\M2ePro\Model\Ebay\Template\Category
     */
    public function getAutoGlobalAddingCategoryTemplate()
    {
        if ($this->autoGlobalAddingCategoryTemplateModel === null) {
            try {
                $this->autoGlobalAddingCategoryTemplateModel = $this->activeRecordFactory->getCachedObjectLoaded(
                    'Ebay_Template_Category',
                    (int)$this->getAutoGlobalAddingTemplateCategoryId()
                );
            } catch (\Exception $exception) {
                return $this->autoGlobalAddingCategoryTemplateModel;
            }
        }

        return $this->autoGlobalAddingCategoryTemplateModel;
    }

    /**
     * @param \Ess\M2ePro\Model\Ebay\Template\Category $instance
     */
    public function setAutoGlobalAddingCategoryTemplate(\Ess\M2ePro\Model\Ebay\Template\Category $instance)
    {
        $this->autoGlobalAddingCategoryTemplateModel = $instance;
    }

    /**
     * @return \Ess\M2ePro\Model\Ebay\Template\Category
     */
    public function getAutoGlobalAddingCategorySecondaryTemplate()
    {
        if ($this->autoGlobalAddingCategorySecondaryTemplateModel === null) {
            try {
                $this->autoGlobalAddingCategorySecondaryTemplateModel =
                    $this->activeRecordFactory->getCachedObjectLoaded(
                        'Ebay_Template_Category',
                        (int)$this->getAutoGlobalAddingTemplateCategorySecondaryId(),
                        null,
                        ['template']
                    );
            } catch (\Exception $exception) {
                return $this->autoGlobalAddingCategorySecondaryTemplateModel;
            }
        }

        return $this->autoGlobalAddingCategorySecondaryTemplateModel;
    }

    /**
     * @param \Ess\M2ePro\Model\Ebay\Template\Category $instance
     */
    public function setAutoGlobalAddingCategorySecondaryTemplate(\Ess\M2ePro\Model\Ebay\Template\Category $instance)
    {
        $this->autoGlobalAddingCategorySecondaryTemplateModel = $instance;
    }

    // ---------------------------------------

    /**
     * @return \Ess\M2ePro\Model\Ebay\Template\StoreCategory
     */
    public function getAutoGlobalAddingStoreCategoryTemplate()
    {
        if ($this->autoGlobalAddingStoreCategoryTemplateModel === null) {
            try {
                $this->autoGlobalAddingStoreCategoryTemplateModel = $this->activeRecordFactory->getCachedObjectLoaded(
                    'Ebay_Template_StoreCategory',
                    (int)$this->getAutoGlobalAddingTemplateStoreCategoryId(),
                    null,
                    ['template']
                );
            } catch (\Exception $exception) {
                return $this->autoGlobalAddingStoreCategoryTemplateModel;
            }
        }

        return $this->autoGlobalAddingStoreCategoryTemplateModel;
    }

    /**
     * @param \Ess\M2ePro\Model\Ebay\Template\StoreCategory $instance
     */
    public function setAutoGlobalAddingStoreCategoryTemplate(\Ess\M2ePro\Model\Ebay\Template\StoreCategory $instance)
    {
        $this->autoGlobalAddingStoreCategoryTemplateModel = $instance;
    }

    /**
     * @return \Ess\M2ePro\Model\Ebay\Template\StoreCategory
     */
    public function getAutoGlobalAddingStoreCategorySecondaryTemplate()
    {
        if ($this->autoGlobalAddingStoreCategorySecondaryTemplateModel === null) {
            try {
                $this->autoGlobalAddingStoreCategorySecondaryTemplateModel =
                    $this->activeRecordFactory->getCachedObjectLoaded(
                        'Ebay_Template_StoreCategory',
                        (int)$this->getAutoGlobalAddingTemplateStoreCategorySecondaryId(),
                        null,
                        ['template']
                    );
            } catch (\Exception $exception) {
                return $this->autoGlobalAddingStoreCategorySecondaryTemplateModel;
            }
        }

        return $this->autoGlobalAddingStoreCategorySecondaryTemplateModel;
    }

    /**
     * @param \Ess\M2ePro\Model\Ebay\Template\StoreCategory $instance
     */
    public function setAutoGlobalAddingStoreCategorySecondaryTemplate(
        \Ess\M2ePro\Model\Ebay\Template\StoreCategory $instance
    ) {
        $this->autoGlobalAddingStoreCategorySecondaryTemplateModel = $instance;
    }

    // ---------------------------------------

    /**
     * @return \Ess\M2ePro\Model\Ebay\Template\Category
     */
    public function getAutoWebsiteAddingCategoryTemplate()
    {
        if ($this->autoWebsiteAddingCategoryTemplateModel === null) {
            try {
                $this->autoWebsiteAddingCategoryTemplateModel = $this->activeRecordFactory->getCachedObjectLoaded(
                    'Ebay_Template_Category',
                    (int)$this->getAutoWebsiteAddingTemplateCategoryId()
                );
            } catch (\Exception $exception) {
                return $this->autoWebsiteAddingCategoryTemplateModel;
            }
        }

        return $this->autoWebsiteAddingCategoryTemplateModel;
    }

    /**
     * @param \Ess\M2ePro\Model\Ebay\Template\Category $instance
     */
    public function setAutoWebsiteAddingCategoryTemplate(\Ess\M2ePro\Model\Ebay\Template\Category $instance)
    {
        $this->autoWebsiteAddingCategoryTemplateModel = $instance;
    }

    // ---------------------------------------

    /**
     * @return \Ess\M2ePro\Model\Ebay\Template\Category
     */
    public function getAutoWebsiteAddingCategorySecondaryTemplate()
    {
        if ($this->autoWebsiteAddingCategorySecondaryTemplateModel === null) {
            try {
                $this->autoWebsiteAddingCategorySecondaryTemplateModel =
                    $this->activeRecordFactory->getCachedObjectLoaded(
                        'Ebay_Template_Category',
                        (int)$this->getAutoWebsiteAddingTemplateCategorySecondaryId(),
                        null,
                        ['template']
                    );
            } catch (\Exception $exception) {
                return $this->autoWebsiteAddingCategorySecondaryTemplateModel;
            }
        }

        return $this->autoWebsiteAddingCategorySecondaryTemplateModel;
    }

    /**
     * @param \Ess\M2ePro\Model\Ebay\Template\Category $instance
     */
    public function setAutoWebsiteAddingCategorySecondaryTemplate(\Ess\M2ePro\Model\Ebay\Template\Category $instance)
    {
        $this->autoWebsiteAddingCategorySecondaryTemplateModel = $instance;
    }

    // ---------------------------------------

    /**
     * @return \Ess\M2ePro\Model\Ebay\Template\StoreCategory
     */
    public function getAutoWebsiteAddingStoreCategoryTemplate()
    {
        if ($this->autoWebsiteAddingStoreCategoryTemplateModel === null) {
            try {
                $this->autoWebsiteAddingStoreCategoryTemplateModel = $this->activeRecordFactory->getCachedObjectLoaded(
                    'Ebay_Template_StoreCategory',
                    (int)$this->getAutoWebsiteAddingTemplateStoreCategoryId(),
                    null,
                    ['template']
                );
            } catch (\Exception $exception) {
                return $this->autoWebsiteAddingStoreCategoryTemplateModel;
            }
        }

        return $this->autoWebsiteAddingStoreCategoryTemplateModel;
    }

    /**
     * @param \Ess\M2ePro\Model\Ebay\Template\StoreCategory $instance
     */
    public function setAutoWebsiteAddingStoreCategoryTemplate(\Ess\M2ePro\Model\Ebay\Template\StoreCategory $instance)
    {
        $this->autoWebsiteAddingStoreCategoryTemplateModel = $instance;
    }

    /**
     * @return \Ess\M2ePro\Model\Ebay\Template\StoreCategory
     */
    public function getAutoWebsiteAddingStoreCategorySecondaryTemplate()
    {
        if ($this->autoWebsiteAddingStoreCategorySecondaryTemplateModel === null) {
            try {
                $this->autoWebsiteAddingStoreCategorySecondaryTemplateModel =
                    $this->activeRecordFactory->getCachedObjectLoaded(
                        'Ebay_Template_StoreCategory',
                        (int)$this->getAutoWebsiteAddingTemplateStoreCategorySecondaryId(),
                        null,
                        ['template']
                    );
            } catch (\Exception $exception) {
                return $this->autoWebsiteAddingStoreCategorySecondaryTemplateModel;
            }
        }

        return $this->autoWebsiteAddingStoreCategorySecondaryTemplateModel;
    }

    /**
     * @param \Ess\M2ePro\Model\Ebay\Template\StoreCategory $instance
     */
    public function setAutoWebsiteAddingStoreCategorySecondaryTemplate(
        \Ess\M2ePro\Model\Ebay\Template\StoreCategory $instance
    ) {
        $this->autoWebsiteAddingStoreCategorySecondaryTemplateModel = $instance;
    }

    //########################################

    /**
     * @return \Ess\M2ePro\Model\Account
     */
    public function getAccount()
    {
        return $this->getParentObject()->getAccount();
    }

    /**
     * @return \Ess\M2ePro\Model\Ebay\Account
     */
    public function getEbayAccount()
    {
        return $this->getAccount()->getChildObject();
    }

    // ---------------------------------------

    /**
     * @return \Ess\M2ePro\Model\Marketplace
     */
    public function getMarketplace()
    {
        return $this->getParentObject()->getMarketplace();
    }

    /**
     * @return \Ess\M2ePro\Model\Ebay\Marketplace
     */
    public function getEbayMarketplace()
    {
        return $this->getMarketplace()->getChildObject();
    }

    //########################################

    /**
     * @param $template
     * @return \Ess\M2ePro\Model\Ebay\Template\Manager
     */
    public function getTemplateManager($template)
    {
        if (!isset($this->templateManagers[$template])) {
            /** @var \Ess\M2ePro\Model\Ebay\Template\Manager $manager */
            $manager = $this->modelFactory->getObject('Ebay_Template_Manager')->setOwnerObject($this);
            $this->templateManagers[$template] = $manager->setTemplate($template);
        }

        return $this->templateManagers[$template];
    }

    // ---------------------------------------

    /**
     * @return \Ess\M2ePro\Model\Template\SellingFormat
     */
    public function getSellingFormatTemplate()
    {
        if ($this->sellingFormatTemplateModel === null) {
            $template = \Ess\M2ePro\Model\Ebay\Template\Manager::TEMPLATE_SELLING_FORMAT;
            $this->sellingFormatTemplateModel = $this->getTemplateManager($template)->getResultObject();
        }

        return $this->sellingFormatTemplateModel;
    }

    /**
     * @param \Ess\M2ePro\Model\Template\SellingFormat $instance
     */
    public function setSellingFormatTemplate(\Ess\M2ePro\Model\Template\SellingFormat $instance)
    {
        $this->sellingFormatTemplateModel = $instance;
    }

    // ---------------------------------------

    /**
     * @return \Ess\M2ePro\Model\Template\Synchronization
     */
    public function getSynchronizationTemplate()
    {
        if ($this->synchronizationTemplateModel === null) {
            $template = \Ess\M2ePro\Model\Ebay\Template\Manager::TEMPLATE_SYNCHRONIZATION;
            $this->synchronizationTemplateModel = $this->getTemplateManager($template)->getResultObject();
        }

        return $this->synchronizationTemplateModel;
    }

    /**
     * @param \Ess\M2ePro\Model\Template\Synchronization $instance
     */
    public function setSynchronizationTemplate(\Ess\M2ePro\Model\Template\Synchronization $instance)
    {
        $this->synchronizationTemplateModel = $instance;
    }

    // ---------------------------------------

    /**
     * @return \Ess\M2ePro\Model\Template\Description
     */
    public function getDescriptionTemplate()
    {
        if ($this->descriptionTemplateModel === null) {
            $template = \Ess\M2ePro\Model\Ebay\Template\Manager::TEMPLATE_DESCRIPTION;
            $this->descriptionTemplateModel = $this->getTemplateManager($template)->getResultObject();
        }

        return $this->descriptionTemplateModel;
    }

    /**
     * @param \Ess\M2ePro\Model\Template\Description $instance
     */
    public function setDescriptionTemplate(\Ess\M2ePro\Model\Template\Description $instance)
    {
        $this->descriptionTemplateModel = $instance;
    }

    // ---------------------------------------

    /**
     * @return \Ess\M2ePro\Model\Ebay\Template\Payment
     */
    public function getPaymentTemplate()
    {
        if ($this->paymentTemplateModel === null) {
            $template = \Ess\M2ePro\Model\Ebay\Template\Manager::TEMPLATE_PAYMENT;
            $this->paymentTemplateModel = $this->getTemplateManager($template)->getResultObject();
        }

        return $this->paymentTemplateModel;
    }

    /**
     * @param \Ess\M2ePro\Model\Ebay\Template\Payment $instance
     */
    public function setPaymentTemplate(\Ess\M2ePro\Model\Ebay\Template\Payment $instance)
    {
        $this->paymentTemplateModel = $instance;
    }

    // ---------------------------------------

    /**
     * @return \Ess\M2ePro\Model\Ebay\Template\ReturnPolicy
     */
    public function getReturnTemplate()
    {
        if ($this->returnTemplateModel === null) {
            $template = \Ess\M2ePro\Model\Ebay\Template\Manager::TEMPLATE_RETURN_POLICY;
            $this->returnTemplateModel = $this->getTemplateManager($template)->getResultObject();
        }

        return $this->returnTemplateModel;
    }

    /**
     * @param \Ess\M2ePro\Model\Ebay\Template\ReturnPolicy $instance
     */
    public function setReturnTemplate(\Ess\M2ePro\Model\Ebay\Template\ReturnPolicy $instance)
    {
        $this->returnTemplateModel = $instance;
    }

    // ---------------------------------------

    /**
     * @return \Ess\M2ePro\Model\Ebay\Template\Shipping
     */
    public function getShippingTemplate()
    {
        if ($this->shippingTemplateModel === null) {
            $template = \Ess\M2ePro\Model\Ebay\Template\Manager::TEMPLATE_SHIPPING;
            $this->shippingTemplateModel = $this->getTemplateManager($template)->getResultObject();
        }

        return $this->shippingTemplateModel;
    }

    /**
     * @param \Ess\M2ePro\Model\Ebay\Template\Shipping $instance
     */
    public function setShippingTemplate(\Ess\M2ePro\Model\Ebay\Template\Shipping $instance)
    {
        $this->shippingTemplateModel = $instance;
    }

    // ---------------------------------------

    /**
     * @return \Ess\M2ePro\Model\Ebay\Template\SellingFormat
     */
    public function getEbaySellingFormatTemplate()
    {
        return $this->getSellingFormatTemplate()->getChildObject();
    }

    /**
     * @return \Ess\M2ePro\Model\Ebay\Template\Synchronization
     */
    public function getEbaySynchronizationTemplate()
    {
        return $this->getSynchronizationTemplate()->getChildObject();
    }

    /**
     * @return \Ess\M2ePro\Model\Ebay\Template\Description
     */
    public function getEbayDescriptionTemplate()
    {
        return $this->getDescriptionTemplate()->getChildObject();
    }

    //########################################

    public function getProducts($asObjects = false, array $filters = [])
    {
        return $this->getParentObject()->getProducts($asObjects, $filters);
    }

    //########################################

    public function getAutoGlobalAddingTemplateCategoryId()
    {
        return $this->getData('auto_global_adding_template_category_id');
    }

    public function getAutoGlobalAddingTemplateCategorySecondaryId()
    {
        return $this->getData('auto_global_adding_template_category_secondary_id');
    }

    public function getAutoGlobalAddingTemplateStoreCategoryId()
    {
        return $this->getData('auto_global_adding_template_store_category_id');
    }

    public function getAutoGlobalAddingTemplateStoreCategorySecondaryId()
    {
        return $this->getData('auto_global_adding_template_store_category_secondary_id');
    }

    // ---------------------------------------

    public function isAutoGlobalAddingModeAddAndAssignCategory()
    {
        return $this->getParentObject()->getAutoGlobalAddingMode() == self::ADDING_MODE_ADD_AND_ASSIGN_CATEGORY;
    }

    //########################################

    public function getAutoWebsiteAddingTemplateCategoryId()
    {
        return $this->getData('auto_website_adding_template_category_id');
    }

    public function getAutoWebsiteAddingTemplateCategorySecondaryId()
    {
        return $this->getData('auto_website_adding_template_category_secondary_id');
    }

    public function getAutoWebsiteAddingTemplateStoreCategoryId()
    {
        return $this->getData('auto_website_adding_template_store_category_id');
    }

    public function getAutoWebsiteAddingTemplateStoreCategorySecondaryId()
    {
        return $this->getData('auto_website_adding_template_store_category_secondary_id');
    }

    // ---------------------------------------

    /**
     * @return bool
     */
    public function isAutoWebsiteAddingModeAddAndAssignCategory()
    {
        return $this->getParentObject()->getAutoWebsiteAddingMode() == self::ADDING_MODE_ADD_AND_ASSIGN_CATEGORY;
    }

    //########################################

    public function gePartsCompatibilityMode()
    {
        return $this->getData('parts_compatibility_mode');
    }

    public function isPartsCompatibilityModeKtypes()
    {
        if ($this->getEbayMarketplace()->isMultiMotorsEnabled()) {
            return $this->gePartsCompatibilityMode() == self::PARTS_COMPATIBILITY_MODE_KTYPES ||
                $this->gePartsCompatibilityMode() === null;
        }

        return $this->getEbayMarketplace()->isKtypeEnabled();
    }

    public function isPartsCompatibilityModeEpids()
    {
        if ($this->getEbayMarketplace()->isMultiMotorsEnabled()) {
            return $this->gePartsCompatibilityMode() == self::PARTS_COMPATIBILITY_MODE_EPIDS;
        }

        return $this->getEbayMarketplace()->isEpidEnabled();
    }

    //########################################

    /**
     * @param \Ess\M2ePro\Model\Listing\Other $listingOtherProduct
     * @param int $initiator
     * @return bool|\Ess\M2ePro\Model\Listing\Product
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function addProductFromOther(
        \Ess\M2ePro\Model\Listing\Other $listingOtherProduct,
        $initiator = \Ess\M2ePro\Helper\Data::INITIATOR_UNKNOWN
    ) {
        if (!$listingOtherProduct->getProductId()) {
            return false;
        }

        $productId = $listingOtherProduct->getProductId();
        $result = $this->getParentObject()->addProduct($productId, $initiator, false, true);

        if (!($result instanceof \Ess\M2ePro\Model\Listing\Product)) {
            return false;
        }

        $listingProduct = $result;

        $collection = $this->activeRecordFactory->getObject('Ebay\Item')->getCollection()
            ->addFieldToFilter('account_id', $listingOtherProduct->getAccount()->getId())
            ->addFieldToFilter('item_id', $listingOtherProduct->getChildObject()->getItemId());

        $ebayItem = $collection->getLastItem();
        if (!$ebayItem->getId()) {
            $ebayItem->setData(
                [
                    'account_id'     => $listingOtherProduct->getAccount()->getId(),
                    'marketplace_id' => $listingOtherProduct->getMarketplace()->getId(),
                    'item_id'        => $listingOtherProduct->getChildObject()->getItemId(),
                    'product_id'     => $listingOtherProduct->getProductId(),
                ]
            );
        }

        $ebayItem->setData('store_id', $this->getParentObject()->getStoreId())
            ->save();

        $ebayListingOther = $listingOtherProduct->getChildObject();

        $dataForUpdate = [
            'ebay_item_id'           => $ebayItem->getId(),
            'online_sku'             => $ebayListingOther->getSku(),
            'online_title'           => $ebayListingOther->getTitle(),
            'online_duration'        => $ebayListingOther->getOnlineDuration(),
            'online_current_price'   => $ebayListingOther->getOnlinePrice(),
            'online_qty'             => $ebayListingOther->getOnlineQty(),
            'online_qty_sold'        => $ebayListingOther->getOnlineQtySold(),
            'online_bids'            => $ebayListingOther->getOnlineBids(),
            'online_main_category'   => $ebayListingOther->getOnlineMainCategory(),
            'online_categories_data' => $this->getHelper('Data')->jsonEncode(
                $ebayListingOther->getOnlineCategoriesData()
            ),

            'start_date'     => $ebayListingOther->getStartDate(),
            'end_date'       => $ebayListingOther->getEndDate(),
            'status'         => $listingOtherProduct->getStatus(),
            'status_changer' => $listingOtherProduct->getStatusChanger()
        ];

        $listingProduct->addData($dataForUpdate)
            ->getChildObject()->addData($dataForUpdate);
        $listingProduct->setSetting(
            'additional_data',
            $listingProduct::MOVING_LISTING_OTHER_SOURCE_KEY,
            $listingOtherProduct->getId()
        );
        $listingProduct->save();

        $listingOtherProduct->setSetting(
            'additional_data',
            $listingOtherProduct::MOVING_LISTING_PRODUCT_DESTINATION_KEY,
            $listingProduct->getId()
        );
        $listingOtherProduct->save();

        $instruction = $this->activeRecordFactory->getObject('Listing_Product_Instruction');
        $instruction->setData(
            [
                'listing_product_id' => $listingProduct->getId(),
                'component'          => \Ess\M2ePro\Helper\Component\Ebay::NICK,
                'type'               => \Ess\M2ePro\Model\Listing::INSTRUCTION_TYPE_PRODUCT_MOVED_FROM_OTHER,
                'initiator'          => \Ess\M2ePro\Model\Listing::INSTRUCTION_INITIATOR_MOVING_PRODUCT_FROM_OTHER,
                'priority'           => 20,
            ]
        );
        $instruction->save();

        return $listingProduct;
    }

    public function addProductFromAnotherEbaySite(
        \Ess\M2ePro\Model\Listing\Product $sourceListingProduct,
        \Ess\M2ePro\Model\Listing $sourceListing
    ) {
        /** @var \Ess\M2ePro\Model\Listing\Product $listingProduct */
        $listingProduct = $this->getParentObject()->addProduct(
            $sourceListingProduct->getProductId(),
            \Ess\M2ePro\Helper\Data::INITIATOR_USER
        );

        /** @var \Ess\M2ePro\Model\Listing\Log $logModel */
        $logModel = $this->activeRecordFactory->getObject('Listing_Log');
        $logModel->setComponentMode($this->getComponentMode());

        $logMessage = $this->getHelper('Module\Translation')->__(
            'Product was copied from %previous_listing_name% (%previous_marketplace%)
            Listing to %current_listing_name% (%current_marketplace%) Listing.',
            $sourceListing->getTitle(),
            $sourceListing->getMarketplace()->getCode(),
            $this->getParentObject()->getTitle(),
            $this->getMarketplace()->getCode()
        );

        if ($listingProduct instanceof \Ess\M2ePro\Model\Listing\Product) {
            $logModel->addProductMessage(
                $sourceListing->getId(),
                $sourceListingProduct->getProductId(),
                $sourceListingProduct->getId(),
                \Ess\M2ePro\Helper\Data::INITIATOR_USER,
                $logModel->getResource()->getNextActionId(),
                \Ess\M2ePro\Model\Listing\Log::ACTION_SELL_ON_ANOTHER_SITE,
                $logMessage,
                \Ess\M2ePro\Model\Log\AbstractModel::TYPE_NOTICE
            );

            if ($sourceListing->getMarketplaceId() == $this->getParentObject()->getMarketplaceId()) {
                $listingProduct->getChildObject()->setData(
                    'template_category_id',
                    $sourceListingProduct->getChildObject()->getTemplateCategoryId()
                );
                $listingProduct->getChildObject()->setData(
                    'template_category_secondary_id',
                    $sourceListingProduct->getChildObject()->getTemplateCategorySecondaryId()
                );
                $listingProduct->getChildObject()->setData(
                    'template_store_category_id',
                    $sourceListingProduct->getChildObject()->getTemplateStoreCategoryId()
                );
                $listingProduct->getChildObject()->setData(
                    'template_store_category_secondary_id',
                    $sourceListingProduct->getChildObject()->getTemplateStoreCategorySecondaryId()
                );

                // @codingStandardsIgnoreLine
                $listingProduct->getChildObject()->save();
            }

            return $listingProduct;
        }

        $logMessage = $this->getHelper('Module\Translation')->__(
            'Product already exists in the %listing_name% Listing.',
            $this->getParentObject()->getTitle()
        );

        $logModel->addProductMessage(
            $sourceListing->getId(),
            $sourceListingProduct->getProductId(),
            $sourceListingProduct->getId(),
            \Ess\M2ePro\Helper\Data::INITIATOR_USER,
            $logModel->getResource()->getNextActionId(),
            \Ess\M2ePro\Model\Listing\Log::ACTION_SELL_ON_ANOTHER_SITE,
            $logMessage,
            \Ess\M2ePro\Model\Log\AbstractModel::TYPE_ERROR
        );

        return false;
    }

    public function addProductFromListing(
        \Ess\M2ePro\Model\Listing\Product $listingProduct,
        \Ess\M2ePro\Model\Listing $sourceListing
    ) {
        if (!$this->getParentObject()->addProductFromListing($listingProduct, $sourceListing, true)) {
            return false;
        }

        if ($this->getParentObject()->getStoreId() != $sourceListing->getStoreId()) {
            if (!$listingProduct->isNotListed()) {
                if ($item = $listingProduct->getChildObject()->getEbayItem()) {
                    $item->setData('store_id', $this->getParentObject()->getStoreId());
                    $item->save();
                }
            }
        }

        return true;
    }

    //########################################

    /**
     * @return array
     */
    public function getAddedListingProductsIds()
    {
        $ids = $this->getData('product_add_ids');
        $ids = array_filter((array)$this->getHelper('Data')->jsonDecode($ids));

        return array_values(array_unique($ids));
    }

    //########################################

    public function updateLastPrimaryCategory($path, $data)
    {
        $settings = $this->getParentObject()->getSettings('additional_data');
        $temp = &$settings;

        $pathCount = count($path);

        foreach ($path as $i => $part) {
            if (!array_key_exists($part, $temp)) {
                $temp[$part] = [];
            }

            if ($i == $pathCount - 1) {
                $temp[$part] = $data;
            }

            $temp = &$temp[$part];
        }

        $this->getParentObject()->setSettings('additional_data', $settings)->save();
    }

    public function getLastPrimaryCategory($key)
    {
        return (array)$this->getParentObject()->getSetting('additional_data', $key);
    }

    //########################################

    public function isCacheEnabled()
    {
        return true;
    }

    //########################################
}
