<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

/**
 * @method \Ess\M2ePro\Model\Listing\Product\Variation getParentObject()
 */

namespace Ess\M2ePro\Model\Walmart\Listing\Product;

use Ess\M2ePro\Model\Walmart\Template\SellingFormat\Promotion as Promotion;

class Variation extends \Ess\M2ePro\Model\ActiveRecord\Component\Child\Walmart\AbstractModel
{
    /** @var \Ess\M2ePro\Model\Walmart\Listing\Product\PriceCalculatorFactory */
    private $walmartPriceCalculatorFactory;

    /**
     * @param \Ess\M2ePro\Model\Walmart\Listing\Product\PriceCalculatorFactory $walmartPriceCalculatorFactory
     * @param \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Walmart\Factory $walmartFactory
     * @param \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Factory $parentFactory
     * @param \Ess\M2ePro\Model\Factory $modelFactory
     * @param \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory
     * @param \Ess\M2ePro\Helper\Factory $helperFactory
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource|null $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        \Ess\M2ePro\Model\Walmart\Listing\Product\PriceCalculatorFactory $walmartPriceCalculatorFactory,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Walmart\Factory $walmartFactory,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Factory $parentFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct(
            $walmartFactory,
            $parentFactory,
            $modelFactory,
            $activeRecordFactory,
            $helperFactory,
            $context,
            $registry,
            $resource,
            $resourceCollection,
            $data
        );
        $this->walmartPriceCalculatorFactory = $walmartPriceCalculatorFactory;
    }

    public function _construct()
    {
        parent::_construct();
        $this->_init(\Ess\M2ePro\Model\ResourceModel\Walmart\Listing\Product\Variation::class);
    }

    //########################################

    public function afterSave()
    {
        $this->getHelper('Data_Cache_Permanent')->removeTagValues(
            "listing_product_{$this->getListingProduct()->getId()}_variations"
        );
        return parent::afterSave();
    }

    public function beforeDelete()
    {
        $this->getHelper('Data_Cache_Permanent')->removeTagValues(
            "listing_product_{$this->getListingProduct()->getId()}_variations"
        );
        return parent::beforeDelete();
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
     * @return \Ess\M2ePro\Model\Listing\Product
     */
    public function getListingProduct()
    {
        return $this->getParentObject()->getListingProduct();
    }

    /**
     * @return \Ess\M2ePro\Model\Walmart\Listing\Product
     */
    public function getWalmartListingProduct()
    {
        return $this->getListingProduct()->getChildObject();
    }

    // ---------------------------------------

    /**
     * @return \Ess\M2ePro\Model\Template\SellingFormat
     */
    public function getSellingFormatTemplate()
    {
        return $this->getWalmartListingProduct()->getSellingFormatTemplate();
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
        return $this->getWalmartListingProduct()->getSynchronizationTemplate();
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
        return $this->getWalmartListingProduct()->getDescriptionTemplate();
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
     * @param bool $asObjects
     * @param array $filters
     * @param bool $tryToGetFromStorage
     * @return array
     */
    public function getOptions($asObjects = false, array $filters = [], $tryToGetFromStorage = true)
    {
        return $this->getParentObject()->getOptions($asObjects, $filters, $tryToGetFromStorage);
    }

    //########################################

    /**
     * @return string
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function getSku()
    {
        $sku = '';

        // Options Models
        $options = $this->getOptions(true);

        // Configurable, Grouped product
        if (
            $this->getListingProduct()->getMagentoProduct()->isConfigurableType()
            || $this->getListingProduct()->getMagentoProduct()->isGroupedType()
        ) {
            foreach ($options as $option) {
                /** @var \Ess\M2ePro\Model\Listing\Product\Variation\Option $option */
                $sku = $option->getChildObject()->getSku();
                break;
            }

            // Bundle product
        } elseif ($this->getListingProduct()->getMagentoProduct()->isBundleType()) {
            foreach ($options as $option) {
                /** @var \Ess\M2ePro\Model\Listing\Product\Variation\Option $option */

                if (!$option->getProductId()) {
                    continue;
                }

                $sku != '' && $sku .= '-';
                $sku .= $option->getChildObject()->getSku();
            }

            // Simple with options product
        } elseif ($this->getListingProduct()->getMagentoProduct()->isSimpleTypeWithCustomOptions()) {
            foreach ($options as $option) {
                /** @var \Ess\M2ePro\Model\Listing\Product\Variation\Option $option */
                $sku != '' && $sku .= '-';
                $tempSku = $option->getChildObject()->getSku();
                if ($tempSku == '') {
                    $sku .= $this->getHelper('Data')->convertStringToSku($option->getOption());
                } else {
                    $sku .= $tempSku;
                }
            }

            // Downloadable with separated links product
        } elseif ($this->getListingProduct()->getMagentoProduct()->isDownloadableTypeWithSeparatedLinks()) {

            /** @var \Ess\M2ePro\Model\Listing\Product\Variation\Option $option */

            $option = reset($options);
            $sku = $option->getMagentoProduct()->getSku() . '-'
                . $this->getHelper('Data')->convertStringToSku($option->getOption());
        }

        return $sku;
    }

    //########################################

    public function getQty($magentoMode = false)
    {
        /** @var \Ess\M2ePro\Model\Walmart\Listing\Product\QtyCalculator $calculator */
        $calculator = $this->modelFactory->getObject('Walmart_Listing_Product_QtyCalculator');
        $calculator->setProduct($this->getListingProduct());
        $calculator->setIsMagentoMode($magentoMode);

        return $calculator->getVariationValue($this->getParentObject());
    }

    // ---------------------------------------

    /**
     * @return float|int|mixed
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function getPrice()
    {
        $src = $this->getWalmartSellingFormatTemplate()->getPriceSource();

        /** @var \Ess\M2ePro\Model\Walmart\Listing\Product\PriceCalculator $calculator */
        $calculator = $this->walmartPriceCalculatorFactory->create();
        $calculator->setSource($src)->setProduct($this->getListingProduct());
        $calculator->setModifier($this->getWalmartSellingFormatTemplate()->getPriceModifier());
        $calculator->setVatPercent($this->getWalmartSellingFormatTemplate()->getPriceVatPercent());
        $calculator->setPriceVariationMode($this->getWalmartSellingFormatTemplate()->getPriceVariationMode());

        return $calculator->getVariationValue($this->getParentObject());
    }

    // ---------------------------------------

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

        /** @var \Ess\M2ePro\Model\Walmart\Template\SellingFormat\Promotion[] $promotions */
        $promotions = $this->getWalmartSellingFormatTemplate()->getPromotions(true);
        if (empty($promotions)) {
            return [];
        }

        $resultPromotions = [];

        foreach ($promotions as $promotion) {

            /** @var \Ess\M2ePro\Model\Walmart\Listing\Product\PriceCalculator $priceCalculator */
            $priceCalculator = $this->walmartPriceCalculatorFactory->create();
            $priceCalculator->setSource($promotion->getPriceSource())->setProduct($this->getListingProduct());
            $priceCalculator->setSourceModeMapping([
                PriceCalculator::MODE_PRODUCT   => Promotion::PRICE_MODE_PRODUCT,
                PriceCalculator::MODE_SPECIAL   => Promotion::PRICE_MODE_SPECIAL,
                PriceCalculator::MODE_ATTRIBUTE => Promotion::PRICE_MODE_ATTRIBUTE,
            ]);
            $priceCalculator->setCoefficient($promotion->getPriceCoefficient());
            $priceCalculator->setVatPercent($this->getWalmartSellingFormatTemplate()->getPriceVatPercent());
            $priceCalculator->setPriceVariationMode(
                $this->getWalmartSellingFormatTemplate()->getPriceVariationMode()
            );

            /** @var \Ess\M2ePro\Model\Walmart\Listing\Product\PriceCalculator $comparisonPriceCalculator */
            $comparisonPriceCalculator = $this->walmartPriceCalculatorFactory->create();
            $comparisonPriceCalculator->setSource(
                $promotion->getComparisonPriceSource()
            )->setProduct($this->getListingProduct());
            $comparisonPriceCalculator->setSourceModeMapping([
                PriceCalculator::MODE_PRODUCT   => Promotion::COMPARISON_PRICE_MODE_PRODUCT,
                PriceCalculator::MODE_SPECIAL   => Promotion::COMPARISON_PRICE_MODE_SPECIAL,
                PriceCalculator::MODE_ATTRIBUTE => Promotion::COMPARISON_PRICE_MODE_ATTRIBUTE,
            ]);
            $comparisonPriceCalculator->setCoefficient($promotion->getComparisonPriceCoefficient());
            $comparisonPriceCalculator->setVatPercent($this->getWalmartSellingFormatTemplate()->getPriceVatPercent());
            $comparisonPriceCalculator->setPriceVariationMode(
                $this->getWalmartSellingFormatTemplate()->getPriceVariationMode()
            );

            $promotionSource = $promotion->getSource($this->getWalmartListingProduct()->getActualMagentoProduct());

            $resultPromotions[] = [
                'start_date'       => $promotionSource->getStartDate(),
                'end_date'         => $promotionSource->getEndDate(),
                'price'            => $priceCalculator->getVariationValue($this->getParentObject()),
                'comparison_price' => $comparisonPriceCalculator->getVariationValue($this->getParentObject()),
                'type'             => strtoupper($promotion->getType())
            ];

            if (count($resultPromotions) >= \Ess\M2ePro\Model\Walmart\Listing\Product::PROMOTIONS_MAX_ALLOWED_COUNT) {
                break;
            }
        }

        return $resultPromotions;
    }

    //########################################
}
