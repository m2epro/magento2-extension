<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
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

    /**
     * @var \Ess\M2ePro\Model\Ebay\Template\Category
     */
    private $autoGlobalAddingCategoryTemplateModel = NULL;

    /**
     * @var \Ess\M2ePro\Model\Ebay\Template\OtherCategory
     */
    private $autoGlobalAddingOtherCategoryTemplateModel = NULL;

    /**
     * @var \Ess\M2ePro\Model\Ebay\Template\Category
     */
    private $autoWebsiteAddingCategoryTemplateModel = NULL;

    /**
     * @var \Ess\M2ePro\Model\Ebay\Template\OtherCategory
     */
    private $autoWebsiteAddingOtherCategoryTemplateModel = NULL;

    /**
     * @var \Ess\M2ePro\Model\Ebay\Template\Manager[]
     */
    private $templateManagers = array();

    // ---------------------------------------

    /**
     * @var \Ess\M2ePro\Model\Template\SellingFormat
     */
    private $sellingFormatTemplateModel = NULL;

    /**
     * @var \Ess\M2ePro\Model\Template\Synchronization
     */
    private $synchronizationTemplateModel = NULL;

    /**
     * @var \Ess\M2ePro\Model\Template\Description
     */
    private $descriptionTemplateModel = NULL;

    /**
     * @var \Ess\M2ePro\Model\Ebay\Template\Payment
     */
    private $paymentTemplateModel = NULL;

    /**
     * @var \Ess\M2ePro\Model\Ebay\Template\ReturnPolicy
     */
    private $returnTemplateModel = NULL;

    /**
     * @var \Ess\M2ePro\Model\Ebay\Template\Shipping
     */
    private $shippingTemplateModel = NULL;

    //########################################

    public function _construct()
    {
        parent::_construct();
        $this->_init('Ess\M2ePro\Model\ResourceModel\Ebay\Listing');
    }

    //########################################

    public function save()
    {
        $this->getHelper('Data\Cache\Permanent')->removeTagValues('listing');
        return parent::save();
    }

    //########################################

    public function delete()
    {
        if ($this->isLocked()) {
            return false;
        }

        $this->templateManagers = array();
        $this->autoGlobalAddingCategoryTemplateModel = NULL;
        $this->autoGlobalAddingOtherCategoryTemplateModel = NULL;
        $this->autoWebsiteAddingCategoryTemplateModel = NULL;
        $this->autoWebsiteAddingOtherCategoryTemplateModel = NULL;
        $this->sellingFormatTemplateModel = NULL;
        $this->synchronizationTemplateModel = NULL;
        $this->descriptionTemplateModel = NULL;
        $this->paymentTemplateModel = NULL;
        $this->returnTemplateModel = NULL;
        $this->shippingTemplateModel = NULL;

        $this->getHelper('Data\Cache\Permanent')->removeTagValues('listing');

        return parent::delete();
    }

    //########################################

    /**
     * @return \Ess\M2ePro\Model\Ebay\Template\Category
     */
    public function getAutoGlobalAddingCategoryTemplate()
    {
        if (is_null($this->autoGlobalAddingCategoryTemplateModel)) {

            try {
                $this->autoGlobalAddingCategoryTemplateModel = $this->activeRecordFactory->getCachedObjectLoaded(
                    'Ebay\Template\Category', (int)$this->getAutoGlobalAddingTemplateCategoryId()
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

    // ---------------------------------------

    /**
     * @return \Ess\M2ePro\Model\Ebay\Template\OtherCategory
     */
    public function getAutoGlobalAddingOtherCategoryTemplate()
    {
        if (is_null($this->autoGlobalAddingOtherCategoryTemplateModel)) {

            try {
                $this->autoGlobalAddingOtherCategoryTemplateModel = $this->activeRecordFactory->getCachedObjectLoaded(
                    'Ebay\Template\OtherCategory', (int)$this->getAutoGlobalAddingTemplateOtherCategoryId()
                );
            } catch (\Exception $exception) {
                return $this->autoGlobalAddingOtherCategoryTemplateModel;
            }
        }

        return $this->autoGlobalAddingOtherCategoryTemplateModel;
    }

    /**
     * @param \Ess\M2ePro\Model\Ebay\Template\OtherCategory $instance
     */
    public function setAutoGlobalAddingOtherCategoryTemplate(\Ess\M2ePro\Model\Ebay\Template\OtherCategory $instance)
    {
         $this->autoGlobalAddingOtherCategoryTemplateModel = $instance;
    }

    // ---------------------------------------

    /**
     * @return \Ess\M2ePro\Model\Ebay\Template\Category
     */
    public function getAutoWebsiteAddingCategoryTemplate()
    {
        if (is_null($this->autoWebsiteAddingCategoryTemplateModel)) {

            try {
                $this->autoWebsiteAddingCategoryTemplateModel = $this->activeRecordFactory->getCachedObjectLoaded(
                    'Ebay\Template\Category', (int)$this->getAutoWebsiteAddingTemplateCategoryId()
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
     * @return \Ess\M2ePro\Model\Ebay\Template\OtherCategory
     */
    public function getAutoWebsiteAddingOtherCategoryTemplate()
    {
        if (is_null($this->autoWebsiteAddingOtherCategoryTemplateModel)) {

            try {
                $this->autoWebsiteAddingOtherCategoryTemplateModel = $this->activeRecordFactory->getCachedObjectLoaded(
                    'Ebay\Template\OtherCategory', (int)$this->getAutoWebsiteAddingTemplateOtherCategoryId()
                );
            } catch (\Exception $exception) {
                return $this->autoWebsiteAddingOtherCategoryTemplateModel;
            }
        }

        return $this->autoWebsiteAddingOtherCategoryTemplateModel;
    }

    /**
     * @param \Ess\M2ePro\Model\Ebay\Template\OtherCategory $instance
     */
    public function setAutoWebsiteAddingOtherCategoryTemplate(\Ess\M2ePro\Model\Ebay\Template\OtherCategory $instance)
    {
         $this->autoWebsiteAddingOtherCategoryTemplateModel = $instance;
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
            $manager = $this->modelFactory->getObject('Ebay\Template\Manager')->setOwnerObject($this);
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
        if (is_null($this->sellingFormatTemplateModel)) {
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
        if (is_null($this->synchronizationTemplateModel)) {
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
        if (is_null($this->descriptionTemplateModel)) {
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
        if (is_null($this->paymentTemplateModel)) {
            $template =\Ess\M2ePro\Model\Ebay\Template\Manager::TEMPLATE_PAYMENT;
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
        if (is_null($this->returnTemplateModel)) {
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
        if (is_null($this->shippingTemplateModel)) {
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

    public function getProducts($asObjects = false, array $filters = array())
    {
        return $this->getParentObject()->getProducts($asObjects,$filters);
    }

    //########################################

    public function getAutoGlobalAddingTemplateCategoryId()
    {
        return $this->getData('auto_global_adding_template_category_id');
    }

    public function getAutoGlobalAddingTemplateOtherCategoryId()
    {
        return $this->getData('auto_global_adding_template_other_category_id');
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

    public function getAutoWebsiteAddingTemplateOtherCategoryId()
    {
        return $this->getData('auto_website_adding_template_other_category_id');
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
                is_null($this->gePartsCompatibilityMode());
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
     * @param bool $checkingMode
     * @param bool $checkHasProduct
     * @return bool|\Ess\M2ePro\Model\Listing\Product
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function addProductFromOther(\Ess\M2ePro\Model\Listing\Other $listingOtherProduct,
                                        $initiator = \Ess\M2ePro\Helper\Data::INITIATOR_UNKNOWN,
                                        $checkingMode = false,
                                        $checkHasProduct = true)
    {
        if (!$listingOtherProduct->getProductId()) {
            return false;
        }

        $productId = $listingOtherProduct->getProductId();
        $result = $this->getParentObject()->addProduct($productId, $initiator, $checkingMode, true);

        if ($checkingMode) {
            return $result;
        }

        if (!($result instanceof \Ess\M2ePro\Model\Listing\Product)) {
            return false;
        }

        $listingProduct = $result;

        $collection = $this->activeRecordFactory->getObject('Ebay\Item')->getCollection()
            ->addFieldToFilter('account_id', $listingOtherProduct->getAccount()->getId())
            ->addFieldToFilter('item_id', $listingOtherProduct->getChildObject()->getItemId());

        $ebayItem = $collection->getLastItem();

        if (!$ebayItem->getId()) {

            $ebayItem->setData(array(
                'account_id'     => $listingOtherProduct->getAccount()->getId(),
                'marketplace_id' => $listingOtherProduct->getMarketplace()->getId(),
                'item_id'        => $listingOtherProduct->getChildObject()->getItemId(),
                'product_id'     => $listingOtherProduct->getProductId(),
            ));
        }

        $ebayItem->setData('store_id',$this->getParentObject()->getStoreId())
                 ->save();

        $ebayListingProduct = $listingOtherProduct->getChildObject();

        $dataForUpdate = array(
            'ebay_item_id'         => $ebayItem->getId(),

            'online_sku'           => $ebayListingProduct->getSku(),
            'online_title'         => $ebayListingProduct->getTitle(),
            'online_duration'      => $ebayListingProduct->getOnlineDuration(),
            'online_current_price' => $ebayListingProduct->getOnlinePrice(),
            'online_qty'           => $ebayListingProduct->getOnlineQty(),
            'online_qty_sold'      => $ebayListingProduct->getOnlineQtySold(),
            'online_bids'          => $ebayListingProduct->getOnlineBids(),
            'start_date'           => $ebayListingProduct->getStartDate(),
            'end_date'             => $ebayListingProduct->getEndDate(),

            'status'               => $listingOtherProduct->getStatus(),
            'status_changer'       => $listingOtherProduct->getStatusChanger()
        );

        $listingOtherAdditionalData = $listingOtherProduct->getAdditionalData();

        if (!empty($listingOtherAdditionalData['out_of_stock_control'])) {
            $listingProductAdditionalData = $listingProduct->getAdditionalData();
            $additionalDataForUpdate = array_merge(
                $listingProductAdditionalData, array('out_of_stock_control' => true)
            );
            $dataForUpdate['additional_data'] = $this->getHelper('Data')->jsonEncode($additionalDataForUpdate);
        }

        $listingProduct->addData($dataForUpdate)
                       ->getChildObject()->addData($dataForUpdate);
        $listingProduct->save();

        return $listingProduct;
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

    /**
     * @return array
     */
    public function getTrackingAttributes()
    {
        return array();
    }

    //########################################

    /**
     * @param string $template
     * @param bool $asArrays
     * @param string|array $columns
     * @return array
     */
    public function getAffectedListingsProductsByTemplate($template, $asArrays = true, $columns = '*')
    {
        $templateManager = $this->modelFactory->getObject('Ebay\Template\Manager');
        $templateManager->setTemplate($template);

        /** @var \Ess\M2ePro\Model\ResourceModel\Listing\Product\Collection $collection */
        $collection = $this->parentFactory->getObject(\Ess\M2ePro\Helper\Component\Ebay::NICK, 'Listing\Product')
            ->getCollection();
        $collection->addFieldToFilter('listing_id', $this->getId());
        $collection->addFieldToFilter(
            $templateManager->getModeColumnName(),\Ess\M2ePro\Model\Ebay\Template\Manager::MODE_PARENT
        );

        if (is_array($columns) && !empty($columns)) {
            $collection->getSelect()->reset(\Zend_Db_Select::COLUMNS);
            $collection->getSelect()->columns($columns);
        }

        return $asArrays ? (array)$collection->getData() : (array)$collection->getItems();
    }

    public function setSynchStatusNeed($newData, $oldData)
    {
        $templateManager = $this->modelFactory->getObject('Ebay\Template\Manager');

        $newTemplates = $templateManager->getTemplatesFromData($newData);
        $oldTemplates = $templateManager->getTemplatesFromData($oldData);

        foreach ($templateManager->getAllTemplates() as $template) {

            $templateManager->setTemplate($template);

            $templateManager->getTemplateModel(true)->getResource()->setSynchStatusNeed(
                $newTemplates[$template]->getDataSnapshot(),
                $oldTemplates[$template]->getDataSnapshot(),
                $this->getAffectedListingsProductsByTemplate(
                    $template, true,
                    $template == \Ess\M2ePro\Model\Ebay\Template\Manager::TEMPLATE_SYNCHRONIZATION ?
                        array('id', 'synch_status', 'synch_reasons') : array('id')
                )
            );
        }
    }

    //########################################

    public function updateLastPrimaryCategory($path,$data)
    {
        $settings = $this->getParentObject()->getSettings('additional_data');
        $temp = &$settings;

        foreach ($path as $i => $part) {

            if (!array_key_exists($part,$temp)) {
                $temp[$part] = array();
            }

            if ($i == count($path) - 1) {
                $temp[$part] = $data;
            }

            $temp = &$temp[$part];
        }

        $this->getParentObject()->setSettings('additional_data',$settings)->save();
    }

    public function getLastPrimaryCategory($key)
    {
        return (array)$this->getSetting('additional_data',$key);
    }

    //########################################

    public function isCacheEnabled()
    {
        return true;
    }

    //########################################
}