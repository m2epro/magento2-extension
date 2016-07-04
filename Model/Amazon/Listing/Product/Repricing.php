<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Listing\Product;

use Ess\M2ePro\Model\Amazon\Listing\Product\Variation\Manager;
use Ess\M2ePro\Model\Exception;
use Ess\M2ePro\Model\Exception\Logic;
use Ess\M2ePro\Model\Listing\Product;
use Ess\M2ePro\Model\Magento\Product\Cache;
use Ess\M2ePro\Model\Amazon\Account\Repricing as AccountRepricing;
use Ess\M2ePro\Model\Amazon\Listing\Product as AmazonListingProduct;

class Repricing extends \Ess\M2ePro\Model\ActiveRecord\AbstractModel
{
    /** @var Product $listingProductModel */
    private $listingProductModel = NULL;

    private $regularPriceCache = NULL;

    protected $amazonFactory;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\Factory $modelFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection,
        array $data,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory
    )
    {
        parent::__construct(
            $modelFactory,
            $activeRecordFactory,
            $helperFactory,
            $context,
            $registry,
            $resource,
            $resourceCollection,
            $data
        );

        $this->amazonFactory = $amazonFactory;
    }

    //########################################

    public function _construct()
    {
        parent::_construct();
        $this->_init('Ess\M2ePro\Model\ResourceModel\Amazon\Listing\Product\Repricing');
    }

    //########################################

    public function setListingProduct(Product $listingProduct)
    {
        $this->listingProductModel = $listingProduct;
        return $this;
    }

    /**
     * @return Product
     */
    public function getListingProduct()
    {
        if (!is_null($this->listingProductModel)) {
            return $this->listingProductModel;
        }

        return $this->listingProductModel = $this->amazonFactory->getObjectLoaded(
            'Listing\Product', $this->getListingProductId()
        );
    }

    /**
     * @return AmazonListingProduct
     * @throws Logic
     */
    public function getAmazonListingProduct()
    {
        return $this->getListingProduct()->getChildObject();
    }

    /**
     * @return Cache
     */
    public function getMagentoProduct()
    {
        return $this->getAmazonListingProduct()->getMagentoProduct();
    }

    /**
     * @return Cache
     * @throws Exception
     */
    public function getActualMagentoProduct()
    {
        return $this->getAmazonListingProduct()->getActualMagentoProduct();
    }

    /**
     * @return Manager
     */
    public function getVariationManager()
    {
        return $this->getAmazonListingProduct()->getVariationManager();
    }

    /**
     * @return AccountRepricing
     */
    public function getAccountRepricing()
    {
        return $this->getAmazonListingProduct()->getAmazonAccount()->getRepricing();
    }

    //########################################

    /**
     * @return int
     */
    public function getListingProductId()
    {
        return (int)$this->getData('listing_product_id');
    }

    /**
     * @return bool
     */
    public function isOnlineDisabled()
    {
        return (bool)$this->getData('is_online_disabled');
    }

    /**
     * @return float|int
     */
    public function getOnlineRegularPrice()
    {
        return $this->getData('online_regular_price');
    }

    /**
     * @return float|int
     */
    public function getOnlineMinPrice()
    {
        return $this->getData('online_min_price');
    }

    /**
     * @return float|int
     */
    public function getOnlineMaxPrice()
    {
        return $this->getData('online_max_price');
    }

    /**
     * @return bool
     */
    public function isProcessRequired()
    {
        return (bool)$this->getData('is_process_required');
    }

    //########################################

    public function getRegularPrice()
    {
        if (!is_null($this->regularPriceCache)) {
            return $this->regularPriceCache;
        }

        $source        = $this->getAccountRepricing()->getRegularPriceSource();
        $coefficient   = $this->getAccountRepricing()->getRegularPriceCoefficient();
        $variationMode = $this->getAccountRepricing()->getRegularPriceVariationMode();

        if ($source['mode'] == AccountRepricing::REGULAR_PRICE_MODE_PRODUCT_POLICY) {
            $amazonSellingFormatTemplate = $this->getAmazonListingProduct()->getAmazonSellingFormatTemplate();

            $source        = $amazonSellingFormatTemplate->getPriceSource();
            $coefficient   = $amazonSellingFormatTemplate->getPriceCoefficient();
            $variationMode = $amazonSellingFormatTemplate->getPriceVariationMode();
        }

        $calculator = $this->getPriceCalculator($source, $coefficient, $variationMode);

        if ($this->getVariationManager()->isPhysicalUnit() &&
            $this->getVariationManager()->getTypeModel()->isVariationProductMatched()) {

            $variations = $this->getListingProduct()->getVariations(true);
            if (count($variations) <= 0) {
                throw new Exception(
                    'There are no variations for a variation product.',
                    array(
                        'listing_product_id' => $this->getId()
                    )
                );
            }

            $variation = reset($variations);

            return $this->regularPriceCache = $calculator->getVariationValue($variation);
        }

        return $this->regularPriceCache = $calculator->getProductValue();
    }

    public function getMinPrice()
    {
        $source = $this->getAccountRepricing()->getMinPriceSource();

        if ($this->getAccountRepricing()->isMinPriceModeRegularPercent() ||
            $this->getAccountRepricing()->isMinPriceModeRegularValue()
        ) {
            if ($this->getAccountRepricing()->isRegularPriceModeManual()) {
                return NULL;
            }

            $value = $this->getRegularPrice() - $this->calculateModificationValueBasedOnRegular($source);
            return $value <= 0 ? 0 : (float)$value;
        }

        $calculator = $this->getPriceCalculator(
            $source,
            $this->getAccountRepricing()->getMinPriceCoefficient(),
            $this->getAccountRepricing()->getMinPriceVariationMode()
        );

        if ($this->getVariationManager()->isPhysicalUnit() &&
            $this->getVariationManager()->getTypeModel()->isVariationProductMatched()) {

            $variations = $this->getListingProduct()->getVariations(true);
            if (count($variations) <= 0) {
                throw new Exception(
                    'There are no variations for a variation product.',
                    array(
                        'listing_product_id' => $this->getId()
                    )
                );
            }

            $variation = reset($variations);

            return $calculator->getVariationValue($variation);
        }

        return $calculator->getProductValue();
    }

    public function getMaxPrice()
    {
        $source = $this->getAccountRepricing()->getMaxPriceSource();

        if ($this->getAccountRepricing()->isMaxPriceModeRegularPercent() ||
            $this->getAccountRepricing()->isMaxPriceModeRegularValue()
        ) {
            if ($this->getAccountRepricing()->isRegularPriceModeManual()) {
                return NULL;
            }

            $value = $this->getRegularPrice() + $this->calculateModificationValueBasedOnRegular($source);
            return $value <= 0 ? 0 : (float)$value;
        }

        $calculator = $this->getPriceCalculator(
            $source,
            $this->getAccountRepricing()->getMaxPriceCoefficient(),
            $this->getAccountRepricing()->getMaxPriceVariationMode()
        );

        if ($this->getVariationManager()->isPhysicalUnit() &&
            $this->getVariationManager()->getTypeModel()->isVariationProductMatched()) {

            $variations = $this->getListingProduct()->getVariations(true);
            if (count($variations) <= 0) {
                throw new Exception(
                    'There are no variations for a variation product.',
                    array(
                        'listing_product_id' => $this->getId()
                    )
                );
            }

            $variation = reset($variations);

            return $calculator->getVariationValue($variation);
        }

        return $calculator->getProductValue();
    }

    //########################################

    public function isDisabled()
    {
        $source = $this->getAccountRepricing()->getDisableSource();

        if ($source['mode'] == AccountRepricing::DISABLE_MODE_MANUAL) {
            return NULL;
        }

        if ($source['mode'] == AccountRepricing::DISABLE_MODE_ATTRIBUTE) {
            return filter_var(
                $this->getActualMagentoProduct()->getAttributeValue($source['attribute']), FILTER_VALIDATE_BOOLEAN
            );
        }

        $isDisabled = !$this->getAmazonListingProduct()->getMagentoProduct()->isStatusEnabled();
        if ($isDisabled) {
            return $isDisabled;
        }

        if ($this->getMagentoProduct()->isSimpleType() || $this->getMagentoProduct()->isGroupedType()) {
            return $isDisabled;
        }

        return !$this->getActualMagentoProduct()->isStatusEnabled();
    }

    //########################################

    /**
     * @param array $src
     * @param string $coefficient
     * @param int $priceVariationMode
     * @return PriceCalculator
     */
    private function getPriceCalculator(array $src, $coefficient = NULL, $priceVariationMode = NULL)
    {
        $calculator = $this->modelFactory->getObject('Amazon\Listing\Product\Repricing\PriceCalculator');
        $calculator->setSource($src)->setProduct($this->getListingProduct());
        $calculator->setCoefficient($coefficient);
        $calculator->setPriceVariationMode($priceVariationMode);

        return $calculator;
    }

    private function calculateModificationValueBasedOnRegular(array $source)
    {
        $regularPrice = $this->getRegularPrice();
        if (empty($regularPrice)) {
            return NULL;
        }

        $value = 0;

        if ($source['mode'] == AccountRepricing::MAX_PRICE_MODE_REGULAR_VALUE &&
            $source['mode'] == AccountRepricing::MIN_PRICE_MODE_REGULAR_VALUE
        ) {
            $value = $source['regular_value'];
        }

        if ($source['mode'] == AccountRepricing::MAX_PRICE_MODE_REGULAR_PERCENT &&
            $source['mode'] == AccountRepricing::MIN_PRICE_MODE_REGULAR_PERCENT
        ) {
            $value = round($regularPrice * ((int)$source['regular_percent'] / 100), 2);
        }

        return $value;
    }

    //########################################
}