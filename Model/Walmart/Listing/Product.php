<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

/**
 * @method \Ess\M2ePro\Model\Listing\Product getParentObject()
 */

namespace Ess\M2ePro\Model\Walmart\Listing;

use Ess\M2ePro\Model\Listing\Product\PriceCalculator as PriceCalculator;
use Ess\M2ePro\Model\Walmart\Listing\Product\Variation\Manager\Type\Relation\ParentRelation;
use Ess\M2ePro\Model\Walmart\Template\SellingFormat\Promotion as Promotion;

/**
 * Class \Ess\M2ePro\Model\Walmart\Listing\Product
 */
class Product extends \Ess\M2ePro\Model\ActiveRecord\Component\Child\Walmart\AbstractModel
{
    const INSTRUCTION_TYPE_CHANNEL_STATUS_CHANGED = 'channel_status_changed';
    const INSTRUCTION_TYPE_CHANNEL_QTY_CHANGED = 'channel_qty_changed';
    const INSTRUCTION_TYPE_CHANNEL_PRICE_CHANGED = 'channel_price_changed';

    const PROMOTIONS_MAX_ALLOWED_COUNT = 10;

    //########################################

    /**
     * @var \Ess\M2ePro\Model\Walmart\Listing\Product\Variation\Manager
     */
    protected $variationManager = null;

    //########################################

    public function _construct()
    {
        parent::_construct();
        $this->_init('Ess\M2ePro\Model\ResourceModel\Walmart\Listing\Product');
    }

    //########################################

    /**
     * @return bool
     * @throws \Ess\M2ePro\Model\Exception
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function isLocked()
    {
        if (parent::isLocked()) {
            return true;
        }

        if ($this->getVariationManager()->isRelationParentType()) {
            foreach ($this->getVariationManager()->getTypeModel()->getChildListingsProducts() as $child) {
                /** @var $child \Ess\M2ePro\Model\Listing\Product */
                if ($child->getStatus() == \Ess\M2ePro\Model\Listing\Product::STATUS_LISTED) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @return bool
     * @throws \Ess\M2ePro\Model\Exception
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function delete()
    {
        if ($this->isLocked()) {
            return false;
        }

        if ($this->getVariationManager()->isRelationParentType()) {
            foreach ($this->getVariationManager()->getTypeModel()->getChildListingsProducts() as $child) {
                /** @var $child \Ess\M2ePro\Model\Listing\Product */
                $child->delete();
            }
        }

        $this->variationManager = null;

        return parent::delete();
    }

    //########################################

    public function afterSaveNewEntity()
    {
        /** @var \Ess\M2ePro\Model\Walmart\Listing\Product\Variation\Manager $variationManager */
        $variationManager = $this->getVariationManager();
        $magentoProduct = $this->getMagentoProduct();

        if ($magentoProduct->isProductWithVariations() && !$variationManager->isVariationProduct()) {
            $this->setData('is_variation_product', 1);
            $variationManager->setRelationParentType();
            $variationManager->getTypeModel()->resetProductAttributes(false);
            $variationManager->getTypeModel()->getProcessor()->process();
        }
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
     * @return \Ess\M2ePro\Model\Walmart\Account
     */
    public function getWalmartAccount()
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
     * @return \Ess\M2ePro\Model\Walmart\Marketplace
     */
    public function getWalmartMarketplace()
    {
        return $this->getMarketplace()->getChildObject();
    }

    //########################################

    /**
     * @return \Ess\M2ePro\Model\Listing
     */
    public function getListing()
    {
        return $this->getParentObject()->getListing();
    }

    /**
     * @return \Ess\M2ePro\Model\Walmart\Listing
     */
    public function getWalmartListing()
    {
        return $this->getListing()->getChildObject();
    }

    // ---------------------------------------

    /**
     * @return \Ess\M2ePro\Model\Template\SellingFormat
     */
    public function getSellingFormatTemplate()
    {
        return $this->getWalmartListing()->getSellingFormatTemplate();
    }

    /**
     * @return \Ess\M2ePro\Model\Walmart\Template\SellingFormat
     */
    public function getWalmartSellingFormatTemplate()
    {
        return $this->getSellingFormatTemplate()->getChildObject();
    }

    // ---------------------------------------

    /**
     * @return \Ess\M2ePro\Model\Template\Synchronization
     */
    public function getSynchronizationTemplate()
    {
        return $this->getWalmartListing()->getSynchronizationTemplate();
    }

    /**
     * @return \Ess\M2ePro\Model\Walmart\Template\Synchronization
     */
    public function getWalmartSynchronizationTemplate()
    {
        return $this->getSynchronizationTemplate()->getChildObject();
    }

    // ---------------------------------------

    /**
     * @return \Ess\M2ePro\Model\Template\Description
     */
    public function getDescriptionTemplate()
    {
        return $this->getWalmartListing()->getDescriptionTemplate();
    }

    /**
     * @return \Ess\M2ePro\Model\Walmart\Template\Description
     */
    public function getWalmartDescriptionTemplate()
    {
        return $this->getDescriptionTemplate()->getChildObject();
    }

    //########################################

    /**
     * @return \Ess\M2ePro\Model\Walmart\Template\SellingFormat\Source
     */
    public function getSellingFormatTemplateSource()
    {
        return $this->getWalmartSellingFormatTemplate()->getSource($this->getActualMagentoProduct());
    }

    /**
     * @return \Ess\M2ePro\Model\Walmart\Template\Description\Source
     */
    public function getDescriptionTemplateSource()
    {
        return $this->getWalmartDescriptionTemplate()->getSource($this->getActualMagentoProduct());
    }

    //########################################

    /**
     * @return \Ess\M2ePro\Model\Magento\Product\Cache
     */
    public function getMagentoProduct()
    {
        return $this->getParentObject()->getMagentoProduct();
    }

    /**
     * @return \Ess\M2ePro\Model\Magento\Product\Cache
     */
    public function getActualMagentoProduct()
    {
        if (!$this->getVariationManager()->isPhysicalUnit() ||
            !$this->getVariationManager()->getTypeModel()->isVariationProductMatched()
        ) {
            return $this->getMagentoProduct();
        }

        if ($this->getMagentoProduct()->isConfigurableType() ||
            $this->getMagentoProduct()->isGroupedType()) {
            $variations = $this->getVariations(true);
            if (count($variations) <= 0) {
                throw new \Ess\M2ePro\Model\Exception\Logic(
                    'There are no variations for a variation product.',
                    [
                        'listing_product_id' => $this->getId()
                    ]
                );
            }
            $variation = reset($variations);
            $options = $variation->getOptions(true);
            $option = reset($options);

            return $option->getMagentoProduct();
        }

        return $this->getMagentoProduct();
    }

    /**
     * @param \Ess\M2ePro\Model\Magento\Product\Cache $instance
     * @return \Ess\M2ePro\Model\Magento\Product\Cache
     * @throws \Ess\M2ePro\Model\Exception
     */
    public function prepareMagentoProduct(\Ess\M2ePro\Model\Magento\Product\Cache $instance)
    {
        if (!$this->getVariationManager()->isRelationMode()) {
            return $instance;
        }

        /** @var ParentRelation $parentTypeModel */

        if ($this->getVariationManager()->isRelationParentType()) {
            $parentTypeModel = $this->getVariationManager()->getTypeModel();
        } else {
            $parentWalmartListingProduct = $this->getVariationManager()->getTypeModel()
                ->getWalmartParentListingProduct();
            $parentTypeModel = $parentWalmartListingProduct->getVariationManager()->getTypeModel();
        }

        $instance->setVariationVirtualAttributes($parentTypeModel->getVirtualProductAttributes());
        $instance->setVariationFilterAttributes($parentTypeModel->getVirtualChannelAttributes());

        return $instance;
    }

    //########################################

    /**
     * @return \Ess\M2ePro\Model\Walmart\Item
     */
    public function getWalmartItem()
    {
        return $this->activeRecordFactory->getObject('Walmart\Item')->getCollection()
            ->addFieldToFilter('account_id', $this->getListing()->getAccountId())
            ->addFieldToFilter('marketplace_id', $this->getListing()->getMarketplaceId())
            ->addFieldToFilter('sku', $this->getSku())
            ->setOrder('create_date', \Magento\Framework\Data\Collection\AbstractDb::SORT_ORDER_DESC)
            ->getFirstItem();
    }

    public function getVariationManager()
    {
        if ($this->variationManager === null) {
            $this->variationManager = $this->modelFactory->getObject('Walmart_Listing_Product_Variation_Manager');
            $this->variationManager->setListingProduct($this->getParentObject());
        }

        return $this->variationManager;
    }

    /**
     * @param bool $asObjects
     * @param array $filters
     * @param bool $tryToGetFromStorage
     * @return array
     */
    public function getVariations($asObjects = false, array $filters = [], $tryToGetFromStorage = true)
    {
        return $this->getParentObject()->getVariations($asObjects, $filters, $tryToGetFromStorage);
    }

    //########################################

    /**
     * @return int
     */
    public function getTemplateCategoryId()
    {
        return (int)($this->getData('template_category_id'));
    }

    /**
     * @return bool
     */
    public function isExistCategoryTemplate()
    {
        return $this->getTemplateCategoryId() > 0;
    }

    /**
     * @return \Ess\M2ePro\Model\Walmart\Template\Category | null
     */
    public function getCategoryTemplate()
    {
        if (!$this->isExistCategoryTemplate()) {
            return null;
        }

        return $this->activeRecordFactory->getCachedObjectLoaded(
            'Walmart_Template_Category',
            $this->getTemplateCategoryId()
        );
    }

    // ---------------------------------------

    /**
     * @return string
     */
    public function getSku()
    {
        return $this->getData('sku');
    }

    /**
     * @return string
     */
    public function getGtin()
    {
        return $this->getData('gtin');
    }

    /**
     * @return string
     */
    public function getUpc()
    {
        return $this->getData('upc');
    }

    /**
     * @return string
     */
    public function getEan()
    {
        return $this->getData('ean');
    }

    /**
     * @return string
     */
    public function getIsbn()
    {
        return $this->getData('isbn');
    }

    /**
     * @return string
     */
    public function getWpid()
    {
        return $this->getData('wpid');
    }

    /**
     * @return string
     */
    public function getItemId()
    {
        return $this->getData('item_id');
    }

    // ---------------------------------------

    /**
     * @return string
     */
    public function getChannelUrl()
    {
        return $this->getData('channel_url');
    }

    // ---------------------------------------

    /**
     * @return string
     */
    public function getPublishStatus()
    {
        return $this->getData('publish_status');
    }

    /**
     * @return string
     */
    public function getLifecycleStatus()
    {
        return $this->getData('lifecycle_status');
    }

    /**
     * @return array
     */
    public function getStatusChangeReasons()
    {
        return $this->getSettings('status_change_reasons');
    }

    // ---------------------------------------

    /**
     * @return bool
     */
    public function isOnlinePriceInvalid()
    {
        return (bool)$this->getData('is_online_price_invalid');
    }

    // ---------------------------------------

    /**
     * @return float|null
     */
    public function getOnlinePrice()
    {
        return $this->getData('online_price');
    }

    /**
     * @return array
     */
    public function getOnlinePromotions()
    {
        return $this->getSettings('online_promotions');
    }

    // ---------------------------------------

    /**
     * @return int
     */
    public function getOnlineQty()
    {
        return (int)$this->getData('online_qty');
    }

    /**
     * @return int
     */
    public function getOnlineLagTime()
    {
        return (int)$this->getData('online_lag_time');
    }

    // ---------------------------------------

    /**
     * @return array
     */
    public function getOnlineDetailsData()
    {
        return $this->getSettings('online_details_data');
    }

    /**
     * @return bool
     */
    public function isDetailsDataChanged()
    {
        return (bool)$this->getData('is_details_data_changed');
    }

    // ---------------------------------------

    /**
     * @return string
     */
    public function getOnlineStartDate()
    {
        return $this->getData('online_start_date');
    }

    /**
     * @return string
     */
    public function getOnlineEndDate()
    {
        return $this->getData('online_end_date');
    }

    // ---------------------------------------

    /**
     * @return bool
     */
    public function isMissedOnChannel()
    {
        return (bool)$this->getData('is_missed_on_channel');
    }

    // ---------------------------------------

    /**
     * @return string
     */
    public function getListDate()
    {
        return $this->getData('list_date');
    }

    //########################################

    /**
     * @param bool $magentoMode
     * @return int
     * @throws \Ess\M2ePro\Model\Exception
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function getQty($magentoMode = false)
    {
        if ($this->getVariationManager()->isPhysicalUnit() &&
            $this->getVariationManager()->getTypeModel()->isVariationProductMatched()) {
            $variations = $this->getVariations(true);
            if (count($variations) <= 0) {
                throw new \Ess\M2ePro\Model\Exception\Logic(
                    'There are no variations for a variation product.',
                    [
                        'listing_product_id' => $this->getId()
                    ]
                );
            }
            /** @var $variation \Ess\M2ePro\Model\Listing\Product\Variation */
            $variation = reset($variations);

            return $variation->getChildObject()->getQty($magentoMode);
        }

        /** @var $calculator \Ess\M2ePro\Model\Walmart\Listing\Product\QtyCalculator */
        $calculator = $this->modelFactory->getObject('Walmart_Listing_Product_QtyCalculator');
        $calculator->setProduct($this->getParentObject());
        $calculator->setIsMagentoMode($magentoMode);

        return $calculator->getProductValue();
    }

    //########################################

    /**
     * @return float|int
     * @throws \Ess\M2ePro\Model\Exception
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function getPrice()
    {
        if ($this->getVariationManager()->isPhysicalUnit() &&
            $this->getVariationManager()->getTypeModel()->isVariationProductMatched()) {
            $variations = $this->getVariations(true);
            if (count($variations) <= 0) {
                throw new \Ess\M2ePro\Model\Exception\Logic(
                    'There are no variations for a variation product.',
                    [
                        'listing_product_id' => $this->getId()
                    ]
                );
            }
            /** @var $variation \Ess\M2ePro\Model\Listing\Product\Variation */
            $variation = reset($variations);

            return $variation->getChildObject()->getPrice();
        }

        $src = $this->getWalmartSellingFormatTemplate()->getPriceSource();

        /** @var $calculator \Ess\M2ePro\Model\Walmart\Listing\Product\PriceCalculator */
        $calculator = $this->modelFactory->getObject('Walmart_Listing_Product_PriceCalculator');
        $calculator->setSource($src)->setProduct($this->getParentObject());
        $calculator->setCoefficient($this->getWalmartSellingFormatTemplate()->getPriceCoefficient());
        $calculator->setVatPercent($this->getWalmartSellingFormatTemplate()->getPriceVatPercent());

        return $calculator->getProductValue();
    }

    /**
     * @return float|int
     * @throws \Ess\M2ePro\Model\Exception
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function getMapPrice()
    {
        if ($this->getVariationManager()->isPhysicalUnit() &&
            $this->getVariationManager()->getTypeModel()->isVariationProductMatched()) {
            $variations = $this->getVariations(true);
            if (count($variations) <= 0) {
                throw new \Ess\M2ePro\Model\Exception\Logic(
                    'There are no variations for a variation product.',
                    [
                        'listing_product_id' => $this->getId()
                    ]
                );
            }
            /** @var $variation \Ess\M2ePro\Model\Listing\Product\Variation */
            $variation = reset($variations);

            return $variation->getChildObject()->getMapPrice();
        }

        $src = $this->getWalmartSellingFormatTemplate()->getMapPriceSource();

        /** @var $calculator \Ess\M2ePro\Model\Walmart\Listing\Product\PriceCalculator */
        $calculator = $this->modelFactory->getObject('Walmart_Listing_Product_PriceCalculator');
        $calculator->setSource($src)->setProduct($this->getParentObject());

        return $calculator->getProductValue();
    }

    /**
     * @return array
     * @throws \Ess\M2ePro\Model\Exception
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function getPromotions()
    {
        if ($this->getWalmartSellingFormatTemplate()->isPromotionsModeNo()) {
            return [];
        }

        if ($this->getVariationManager()->isPhysicalUnit() &&
            $this->getVariationManager()->getTypeModel()->isVariationProductMatched()) {
            $variations = $this->getVariations(true);
            if (count($variations) <= 0) {
                throw new \Ess\M2ePro\Model\Exception\Logic(
                    'There are no variations for a variation product.',
                    [
                        'listing_product_id' => $this->getId()
                    ]
                );
            }
            /** @var $variation \Ess\M2ePro\Model\Listing\Product\Variation */
            $variation = reset($variations);

            return $variation->getChildObject()->getPromotions();
        }

        /** @var \Ess\M2ePro\Model\Walmart\Template\SellingFormat\Promotion[] $promotions */
        $promotions = $this->getWalmartSellingFormatTemplate()->getPromotions(true);
        if (empty($promotions)) {
            return [];
        }

        $resultPromotions = [];

        foreach ($promotions as $promotion) {

            /** @var $priceCalculator \Ess\M2ePro\Model\Walmart\Listing\Product\PriceCalculator */
            $priceCalculator = $this->modelFactory->getObject('Walmart_Listing_Product_PriceCalculator');
            $priceCalculator->setSource($promotion->getPriceSource())->setProduct($this->getParentObject());
            $priceCalculator->setSourceModeMapping([
                PriceCalculator::MODE_PRODUCT   => Promotion::PRICE_MODE_PRODUCT,
                PriceCalculator::MODE_SPECIAL   => Promotion::PRICE_MODE_SPECIAL,
                PriceCalculator::MODE_ATTRIBUTE => Promotion::PRICE_MODE_ATTRIBUTE,
            ]);
            $priceCalculator->setCoefficient($promotion->getPriceCoefficient());
            $priceCalculator->setVatPercent($this->getWalmartSellingFormatTemplate()->getPriceVatPercent());

            /** @var $comparisonPriceCalculator \Ess\M2ePro\Model\Walmart\Listing\Product\PriceCalculator */
            $comparisonPriceCalculator = $this->modelFactory->getObject('Walmart_Listing_Product_PriceCalculator');
            $comparisonPriceCalculator->setSource(
                $promotion->getComparisonPriceSource()
            )->setProduct($this->getParentObject());
            $comparisonPriceCalculator->setSourceModeMapping([
                PriceCalculator::MODE_PRODUCT   => Promotion::COMPARISON_PRICE_MODE_PRODUCT,
                PriceCalculator::MODE_SPECIAL   => Promotion::COMPARISON_PRICE_MODE_SPECIAL,
                PriceCalculator::MODE_ATTRIBUTE => Promotion::COMPARISON_PRICE_MODE_ATTRIBUTE,
            ]);
            $comparisonPriceCalculator->setCoefficient($promotion->getComparisonPriceCoefficient());
            $comparisonPriceCalculator->setVatPercent($this->getWalmartSellingFormatTemplate()->getPriceVatPercent());

            $promotionSource = $promotion->getSource($this->getMagentoProduct());

            $resultPromotions[] = [
                'start_date'       => $promotionSource->getStartDate(),
                'end_date'         => $promotionSource->getEndDate(),
                'price'            => $priceCalculator->getProductValue(),
                'comparison_price' => $comparisonPriceCalculator->getProductValue(),
                'type'             => strtoupper($promotion->getType())
            ];

            if (count($resultPromotions) >= self::PROMOTIONS_MAX_ALLOWED_COUNT) {
                break;
            }
        }

        return $resultPromotions;
    }

    //########################################

    public function listAction(array $params = [])
    {
        return $this->processDispatcher(\Ess\M2ePro\Model\Listing\Product::ACTION_LIST, $params);
    }

    public function relistAction(array $params = [])
    {
        return $this->processDispatcher(\Ess\M2ePro\Model\Listing\Product::ACTION_RELIST, $params);
    }

    public function reviseAction(array $params = [])
    {
        return $this->processDispatcher(\Ess\M2ePro\Model\Listing\Product::ACTION_REVISE, $params);
    }

    public function stopAction(array $params = [])
    {
        return $this->processDispatcher(\Ess\M2ePro\Model\Listing\Product::ACTION_STOP, $params);
    }

    // ---------------------------------------

    protected function processDispatcher($action, array $params = [])
    {
        $dispatcherObject = $this->modelFactory->getObject('Walmart_Connector_Product_Dispatcher');
        return $dispatcherObject->process($action, $this->getId(), $params);
    }

    //########################################

    public function getTrackingAttributes()
    {
        $attributes = $this->getListing()->getTrackingAttributes();

        $categoryTemplate = $this->getCategoryTemplate();
        if ($categoryTemplate !== null) {
            $attributes = array_merge($attributes, $categoryTemplate->getTrackingAttributes());
        }

        $attributes = array_merge($attributes, $this->getDescriptionTemplate()->getTrackingAttributes());
        $attributes = array_merge($attributes, $this->getSellingFormatTemplate()->getTrackingAttributes());

        return array_unique($attributes);
    }

    //########################################
}
