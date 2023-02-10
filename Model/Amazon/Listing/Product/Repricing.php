<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Listing\Product;

use Ess\M2ePro\Model\Exception;
use Ess\M2ePro\Model\Listing\Product;
use Ess\M2ePro\Model\Magento\Product\Cache;
use Ess\M2ePro\Model\Amazon\Account\Repricing as AccountRepricing;
use Ess\M2ePro\Model\Amazon\Listing\Product as AmazonListingProduct;

class Repricing extends \Ess\M2ePro\Model\ActiveRecord\AbstractModel
{
    /** @var Product $listingProductModel */
    private $listingProductModel = null;
    /** @var null  */
    private $regularPriceCache = null;
    /** @var \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory  */
    protected $amazonFactory;

    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->amazonFactory = $amazonFactory;

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
    }

    /**
     * @return void
     */
    public function _construct()
    {
        parent::_construct();
        $this->_init(\Ess\M2ePro\Model\ResourceModel\Amazon\Listing\Product\Repricing::class);
    }

    /**
     * @param \Ess\M2ePro\Model\Listing\Product $listingProduct
     *
     * @return $this
     */
    public function setListingProduct(Product $listingProduct): self
    {
        $this->listingProductModel = $listingProduct;

        return $this;
    }

    /**
     * @return \Ess\M2ePro\Model\ActiveRecord\Component\Parent\AbstractModel|\Ess\M2ePro\Model\Listing\Product|null
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function getListingProduct()
    {
        if ($this->listingProductModel !== null) {
            return $this->listingProductModel;
        }

        return $this->listingProductModel = $this->amazonFactory->getObjectLoaded(
            'Listing\Product',
            $this->getListingProductId()
        );
    }

    /**
     * @return \Ess\M2ePro\Model\Amazon\Listing\Product
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function getAmazonListingProduct(): AmazonListingProduct
    {
        return $this->getListingProduct()->getChildObject();
    }

    /**
     * @return \Ess\M2ePro\Model\Magento\Product\Cache
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function getMagentoProduct(): Cache
    {
        return $this->getAmazonListingProduct()->getMagentoProduct();
    }

    /**
     * @return \Ess\M2ePro\Model\Magento\Product\Cache
     * @throws \Ess\M2ePro\Model\Exception
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function getActualMagentoProduct(): Cache
    {
        return $this->getAmazonListingProduct()->getActualMagentoProduct();
    }

    /**
     * @return \Ess\M2ePro\Model\AbstractModel|\Ess\M2ePro\Model\Amazon\Listing\Product\Variation\Manager|null
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function getVariationManager()
    {
        return $this->getAmazonListingProduct()->getVariationManager();
    }

    /**
     * @return \Ess\M2ePro\Model\Amazon\Account\Repricing
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function getAccountRepricing(): AccountRepricing
    {
        return $this->getAmazonListingProduct()->getAmazonAccount()->getRepricing();
    }

    /**
     * @return int
     */
    public function getListingProductId(): int
    {
        return (int)$this->getData('listing_product_id');
    }

    /**
     * @return bool
     */
    public function isOnlineDisabled(): bool
    {
        return (bool)$this->getData('is_online_disabled');
    }

    /**
     * @return bool
     */
    public function isOnlineInactive(): bool
    {
        return (bool)$this->getData('is_online_inactive');
    }

    /**
     * @return bool
     */
    public function isOnlineManaged(): bool
    {
        return !$this->isOnlineDisabled() && !$this->isOnlineInactive();
    }

    /**
     * @return bool
     */
    public function isProcessRequired(): bool
    {
        return (bool)$this->getData('is_process_required');
    }

    /**
     * @return float|int
     */
    public function getLastUpdatedRegularPrice()
    {
        return $this->getData('last_updated_regular_price');
    }

    /**
     * @return float|int
     */
    public function getLastUpdatedMinPrice()
    {
        return $this->getData('last_updated_min_price');
    }

    /**
     * @return float|int
     */
    public function getLastUpdatedMaxPrice()
    {
        return $this->getData('last_updated_max_price');
    }

    /**
     * @return bool
     */
    public function getLastUpdatedIsDisabled(): bool
    {
        return (bool)$this->getData('last_updated_is_disabled');
    }

    /**
     * @return float|int|mixed
     * @throws \Ess\M2ePro\Model\Exception
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function getRegularPrice()
    {
        if ($this->regularPriceCache !== null) {
            return $this->regularPriceCache;
        }

        $source = $this->getAccountRepricing()->getRegularPriceSource();
        $sourceModeMapping = [
            Product\PriceCalculator::MODE_NONE => AccountRepricing::PRICE_MODE_MANUAL,
            Product\PriceCalculator::MODE_PRODUCT => AccountRepricing::PRICE_MODE_PRODUCT,
            Product\PriceCalculator::MODE_ATTRIBUTE => AccountRepricing::PRICE_MODE_ATTRIBUTE,
            Product\PriceCalculator::MODE_SPECIAL => AccountRepricing::PRICE_MODE_SPECIAL,
        ];
        $coefficient = $this->getAccountRepricing()->getRegularPriceCoefficient();
        $variationMode = $this->getAccountRepricing()->getRegularPriceVariationMode();
        $modifier = [];

        if ($source['mode'] == AccountRepricing::REGULAR_PRICE_MODE_PRODUCT_POLICY) {
            $amazonSellingFormatTemplate = $this->getAmazonListingProduct()->getAmazonSellingFormatTemplate();

            $source = $amazonSellingFormatTemplate->getRegularPriceSource();
            $sourceModeMapping = null;
            $coefficient = null;
            $variationMode = $amazonSellingFormatTemplate->getRegularPriceVariationMode();
            $modifier = $amazonSellingFormatTemplate->getRegularPriceModifier();
        }

        $calculator = $this->getPriceCalculator($source, $sourceModeMapping, $coefficient, $variationMode, $modifier);

        if (
            $this->getVariationManager()->isPhysicalUnit() &&
            $this->getVariationManager()->getTypeModel()->isVariationProductMatched()
        ) {
            $variations = $this->getListingProduct()->getVariations(true);
            if (empty($variations)) {
                throw new Exception\Logic(
                    'There are no variations for a variation product.',
                    [
                        'listing_product_id' => $this->getId(),
                    ]
                );
            }

            $variation = reset($variations);

            return $this->regularPriceCache = $calculator->getVariationValue($variation);
        }

        return $this->regularPriceCache = $calculator->getProductValue();
    }

    /**
     * @return float|int|mixed
     * @throws \Ess\M2ePro\Model\Exception
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function getMinPrice()
    {
        $source = $this->getAccountRepricing()->getMinPriceSource();

        if (
            $this->getAccountRepricing()->isMinPriceModeRegularPercent() ||
            $this->getAccountRepricing()->isMinPriceModeRegularValue()
        ) {
            if ($this->getAccountRepricing()->isRegularPriceModeManual()) {
                return null;
            }

            $value = $this->getRegularPrice() - $this->calculateModificationValueBasedOnRegular($source);

            return $value <= 0 ? 0 : (float)$value;
        }

        $sourceModeMapping = [
            Product\PriceCalculator::MODE_NONE => AccountRepricing::PRICE_MODE_MANUAL,
            Product\PriceCalculator::MODE_PRODUCT => AccountRepricing::PRICE_MODE_PRODUCT,
            Product\PriceCalculator::MODE_ATTRIBUTE => AccountRepricing::PRICE_MODE_ATTRIBUTE,
            Product\PriceCalculator::MODE_SPECIAL => AccountRepricing::PRICE_MODE_SPECIAL,
        ];

        $calculator = $this->getPriceCalculator(
            $source,
            $sourceModeMapping,
            $this->getAccountRepricing()->getMinPriceCoefficient(),
            $this->getAccountRepricing()->getMinPriceVariationMode()
        );

        if (
            $this->getVariationManager()->isPhysicalUnit() &&
            $this->getVariationManager()->getTypeModel()->isVariationProductMatched()
        ) {
            $variations = $this->getListingProduct()->getVariations(true);
            if (empty($variations)) {
                throw new Exception\Logic(
                    'There are no variations for a variation product.',
                    [
                        'listing_product_id' => $this->getId(),
                    ]
                );
            }

            $variation = reset($variations);

            return $calculator->getVariationValue($variation);
        }

        return $calculator->getProductValue();
    }

    /**
     * @return float|int|mixed
     * @throws \Ess\M2ePro\Model\Exception
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function getMaxPrice()
    {
        $source = $this->getAccountRepricing()->getMaxPriceSource();

        if (
            $this->getAccountRepricing()->isMaxPriceModeRegularPercent() ||
            $this->getAccountRepricing()->isMaxPriceModeRegularValue()
        ) {
            if ($this->getAccountRepricing()->isRegularPriceModeManual()) {
                return null;
            }

            $value = $this->getRegularPrice() + $this->calculateModificationValueBasedOnRegular($source);

            return $value <= 0 ? 0 : (float)$value;
        }

        $sourceModeMapping = [
            Product\PriceCalculator::MODE_NONE => AccountRepricing::PRICE_MODE_MANUAL,
            Product\PriceCalculator::MODE_PRODUCT => AccountRepricing::PRICE_MODE_PRODUCT,
            Product\PriceCalculator::MODE_ATTRIBUTE => AccountRepricing::PRICE_MODE_ATTRIBUTE,
            Product\PriceCalculator::MODE_SPECIAL => AccountRepricing::PRICE_MODE_SPECIAL,
        ];

        $calculator = $this->getPriceCalculator(
            $source,
            $sourceModeMapping,
            $this->getAccountRepricing()->getMaxPriceCoefficient(),
            $this->getAccountRepricing()->getMaxPriceVariationMode()
        );

        if (
            $this->getVariationManager()->isPhysicalUnit() &&
            $this->getVariationManager()->getTypeModel()->isVariationProductMatched()
        ) {
            $variations = $this->getListingProduct()->getVariations(true);
            if (empty($variations)) {
                throw new Exception\Logic(
                    'There are no variations for a variation product.',
                    [
                        'listing_product_id' => $this->getId(),
                    ]
                );
            }

            $variation = reset($variations);

            return $calculator->getVariationValue($variation);
        }

        return $calculator->getProductValue();
    }

    /**
     * @return bool|mixed|null
     * @throws \Ess\M2ePro\Model\Exception
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function isDisabled()
    {
        $source = $this->getAccountRepricing()->getDisableSource();

        if ($source['mode'] == AccountRepricing::DISABLE_MODE_MANUAL) {
            return null;
        }

        if ($source['mode'] == AccountRepricing::DISABLE_MODE_ATTRIBUTE) {
            return filter_var(
                $this->getActualMagentoProduct()->getAttributeValue($source['attribute'], false),
                FILTER_VALIDATE_BOOLEAN
            );
        }

        $isDisabled = !$this->getAmazonListingProduct()->getMagentoProduct()->isStatusEnabled();
        if ($isDisabled) {
            return true;
        }

        if (
            $this->getMagentoProduct()->isSimpleType() ||
            $this->getMagentoProduct()->isBundleType() ||
            $this->getMagentoProduct()->isDownloadableType()
        ) {
            return false;
        }

        return !$this->getActualMagentoProduct()->isStatusEnabled();
    }

    /**
     * @param array $source
     * @param array|null $sourceModeMapping
     * @param string|null $coefficient
     * @param int|null $priceVariationMode
     * @param array $modifier
     *
     * @return \Ess\M2ePro\Model\Amazon\Listing\Product\Repricing\PriceCalculator
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    private function getPriceCalculator(
        array $source,
        array $sourceModeMapping = null,
        string $coefficient = null,
        int $priceVariationMode = null,
        array $modifier = []
    ): Repricing\PriceCalculator {
        /** @var \Ess\M2ePro\Model\Amazon\Listing\Product\Repricing\PriceCalculator $calculator */
        $calculator = $this->modelFactory->getObject('Amazon_Listing_Product_Repricing_PriceCalculator');
        $sourceModeMapping !== null && $calculator->setSourceModeMapping($sourceModeMapping);
        $calculator->setSource($source)->setProduct($this->getListingProduct());
        $calculator->setCoefficient($coefficient);
        $calculator->setPriceVariationMode($priceVariationMode);
        $calculator->setModifier($modifier);

        return $calculator;
    }

    /**
     * @param array $source
     *
     * @return float|int|mixed|null
     * @throws \Ess\M2ePro\Model\Exception\Logic|\Ess\M2ePro\Model\Exception
     */
    private function calculateModificationValueBasedOnRegular(array $source)
    {
        $regularPrice = $this->getRegularPrice();
        if (empty($regularPrice)) {
            return null;
        }

        $value = 0;

        if (
            $source['mode'] == AccountRepricing::MAX_PRICE_MODE_REGULAR_VALUE &&
            $source['mode'] == AccountRepricing::MIN_PRICE_MODE_REGULAR_VALUE
        ) {
            $value = $source['regular_value'];
        }

        if (
            $source['mode'] == AccountRepricing::MAX_PRICE_MODE_REGULAR_PERCENT &&
            $source['mode'] == AccountRepricing::MIN_PRICE_MODE_REGULAR_PERCENT
        ) {
            $value = round($regularPrice * ((int)$source['regular_percent'] / 100), 2);
        }

        return $value;
    }
}
