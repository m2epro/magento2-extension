<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Account;

use Ess\M2ePro\Model\Account;

/**
 * Class \Ess\M2ePro\Model\Amazon\Account\Repricing
 */
class Repricing extends \Ess\M2ePro\Model\ActiveRecord\AbstractModel
{
    public const PRICE_MODE_MANUAL = 0;
    public const PRICE_MODE_PRODUCT = 1;
    public const PRICE_MODE_SPECIAL = 2;
    public const PRICE_MODE_ATTRIBUTE = 3;

    public const REGULAR_PRICE_MODE_PRODUCT_POLICY = 4;

    public const MIN_PRICE_MODE_REGULAR_VALUE = 4;
    public const MIN_PRICE_MODE_REGULAR_PERCENT = 5;
    public const MIN_PRICE_MODE_REGULAR_VALUE_ATTRIBUTE = 6;
    public const MIN_PRICE_MODE_REGULAR_PERCENT_ATTRIBUTE = 7;

    public const MAX_PRICE_MODE_REGULAR_VALUE = 4;
    public const MAX_PRICE_MODE_REGULAR_PERCENT = 5;
    public const MAX_PRICE_MODE_REGULAR_VALUE_ATTRIBUTE = 6;
    public const MAX_PRICE_MODE_REGULAR_PERCENT_ATTRIBUTE = 7;

    public const PRICE_VARIATION_MODE_PARENT = 1;
    public const PRICE_VARIATION_MODE_CHILDREN = 2;

    public const DISABLE_MODE_MANUAL = 0;
    public const DISABLE_MODE_PRODUCT_STATUS = 1;
    public const DISABLE_MODE_ATTRIBUTE = 2;

    /**
     * @var Account
     */
    private $accountModel = null;
    /** @var \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory  */
    protected $amazonFactory;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        ?\Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        ?\Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
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

    //########################################

    public function _construct()
    {
        parent::_construct();
        $this->_init(\Ess\M2ePro\Model\ResourceModel\Amazon\Account\Repricing::class);
    }

    //########################################

    public function save()
    {
        $this->getHelper('Data_Cache_Permanent')->removeTagValues('account');

        return parent::save();
    }

    //########################################

    public function delete()
    {
        $this->getHelper('Data_Cache_Permanent')->removeTagValues('account');

        $temp = parent::delete();
        $temp && $this->accountModel = null;

        return $temp;
    }

    //########################################

    /**
     * @return Account
     */
    public function getAccount()
    {
        if ($this->accountModel === null) {
            $this->accountModel = $this->amazonFactory->getCachedObjectLoaded('Account', $this->getAccountId());
        }

        return $this->accountModel;
    }

    /**
     * @param Account $instance
     */
    public function setAccount(Account $instance)
    {
        $this->accountModel = $instance;
    }

    //########################################

    /**
     * @return int
     */
    public function getAccountId()
    {
        return (int)$this->getData('account_id');
    }

    /**
     * @return string
     */
    public function getEmail()
    {
        return $this->getData('email');
    }

    /**
     * @return string
     */
    public function getToken()
    {
        return $this->getData('token');
    }

    /**
     * @return bool
     */
    public function isInvalid(): bool
    {
        return (bool)$this->getData('invalid');
    }

    /**
     * @return $this
     */
    public function markAsInvalid(): Repricing
    {
        $this->setData('invalid', 1);

        return $this;
    }

    /**
     * @return int
     */
    public function getTotalProducts()
    {
        return $this->getData('total_products');
    }

    //########################################

    /**
     * @return int
     */
    public function getRegularPriceMode()
    {
        return (int)$this->getData('regular_price_mode');
    }

    /**
     * @return bool
     */
    public function isRegularPriceModeManual()
    {
        return $this->getRegularPriceMode() == self::PRICE_MODE_MANUAL;
    }

    /**
     * @return bool
     */
    public function isRegularPriceModeProductPolicy()
    {
        return $this->getRegularPriceMode() == self::REGULAR_PRICE_MODE_PRODUCT_POLICY;
    }

    /**
     * @return bool
     */
    public function isRegularPriceModeProduct()
    {
        return $this->getRegularPriceMode() == self::PRICE_MODE_PRODUCT;
    }

    /**
     * @return bool
     */
    public function isRegularPriceModeSpecial()
    {
        return $this->getRegularPriceMode() == self::PRICE_MODE_SPECIAL;
    }

    /**
     * @return bool
     */
    public function isRegularPriceModeAttribute()
    {
        return $this->getRegularPriceMode() == self::PRICE_MODE_ATTRIBUTE;
    }

    public function getRegularPriceCoefficient()
    {
        return $this->getData('regular_price_coefficient');
    }

    /**
     * @return array
     */
    public function getRegularPriceSource()
    {
        return [
            'mode' => $this->getRegularPriceMode(),
            'coefficient' => $this->getRegularPriceCoefficient(),
            'attribute' => $this->getData('regular_price_attribute'),
        ];
    }

    /**
     * @return array
     */
    public function getRegularPriceAttributes()
    {
        $attributes = [];
        $src = $this->getRegularPriceSource();

        if ($src['mode'] == self::PRICE_MODE_ATTRIBUTE) {
            $attributes[] = $src['attribute'];
        }

        return $attributes;
    }

    //########################################

    /**
     * @return int
     */
    public function getRegularPriceVariationMode()
    {
        return (int)$this->getData('regular_price_variation_mode');
    }

    /**
     * @return bool
     */
    public function isRegularPriceVariationModeParent()
    {
        return $this->getRegularPriceVariationMode() == self::PRICE_VARIATION_MODE_PARENT;
    }

    /**
     * @return bool
     */
    public function isRegularPriceVariationModeChildren()
    {
        return $this->getRegularPriceVariationMode() == self::PRICE_VARIATION_MODE_CHILDREN;
    }

    //########################################

    public function getMinPriceMode(): int
    {
        return (int)$this->getData('min_price_mode');
    }

    public function isMinPriceModeManual(): bool
    {
        return $this->getMinPriceMode() == self::PRICE_MODE_MANUAL;
    }

    public function isMinPriceModeRegularValue(): bool
    {
        return $this->getMinPriceMode() == self::MIN_PRICE_MODE_REGULAR_VALUE;
    }

    public function isMinPriceModeRegularValueAttribute(): bool
    {
        return $this->getMinPriceMode() == self::MIN_PRICE_MODE_REGULAR_VALUE_ATTRIBUTE;
    }

    public function isMinPriceModeRegularPercent(): bool
    {
        return $this->getMinPriceMode() == self::MIN_PRICE_MODE_REGULAR_PERCENT;
    }

    public function isMinPriceModeRegularPercentAttribute(): bool
    {
        return $this->getMinPriceMode() == self::MIN_PRICE_MODE_REGULAR_PERCENT_ATTRIBUTE;
    }

    public function isMinPriceModeAttribute(): bool
    {
        return $this->getMinPriceMode() == self::PRICE_MODE_ATTRIBUTE;
    }

    public function getMinPriceCoefficient()
    {
        return $this->getData('min_price_coefficient');
    }

    public function getMinPriceSource(): array
    {
        return [
            'mode' => $this->getMinPriceMode(),
            'coefficient' => $this->getMinPriceCoefficient(),
            'attribute' => $this->getData('min_price_attribute'),
            'regular_value' => $this->getData('min_price_value'),
            'regular_percent' => $this->getData('min_price_percent'),
            'value_attribute' => $this->getData('min_price_value_attribute'),
            'percent_attribute' => $this->getData('min_price_percent_attribute'),
        ];
    }

    public function getMinPriceAttributes(): array
    {
        $attributes = [];
        $src = $this->getMinPriceSource();

        if ($src['mode'] == self::PRICE_MODE_ATTRIBUTE) {
            $attributes[] = $src['attribute'];
        }

        if ($src['mode'] == self::MIN_PRICE_MODE_REGULAR_VALUE_ATTRIBUTE) {
            $attributes[] = $src['value_attribute'];
        }

        if ($src['mode'] == self::MIN_PRICE_MODE_REGULAR_PERCENT_ATTRIBUTE) {
            $attributes[] = $src['percent_attribute'];
        }

        return $attributes;
    }

    //########################################

    public function getMinPriceVariationMode(): int
    {
        return (int)$this->getData('min_price_variation_mode');
    }

    public function isMinPriceVariationModeParent(): bool
    {
        return $this->getMinPriceVariationMode() == self::PRICE_VARIATION_MODE_PARENT;
    }

    public function isMinPriceVariationModeChildren(): bool
    {
        return $this->getMinPriceVariationMode() == self::PRICE_VARIATION_MODE_CHILDREN;
    }

    //########################################

    public function getMaxPriceMode(): int
    {
        return (int)$this->getData('max_price_mode');
    }

    public function isMaxPriceModeManual(): bool
    {
        return $this->getMaxPriceMode() == self::PRICE_MODE_MANUAL;
    }

    public function isMaxPriceModeRegularValue(): bool
    {
        return $this->getMaxPriceMode() == self::MAX_PRICE_MODE_REGULAR_VALUE;
    }

    public function isMaxPriceModeRegularPercent(): bool
    {
        return $this->getMaxPriceMode() == self::MAX_PRICE_MODE_REGULAR_PERCENT;
    }

    public function isMaxPriceModeRegularValueAttribute(): bool
    {
        return $this->getMaxPriceMode() == self::MAX_PRICE_MODE_REGULAR_VALUE_ATTRIBUTE;
    }

    public function isMaxPriceModeRegularPercentAttribute(): bool
    {
        return $this->getMaxPriceMode() == self::MAX_PRICE_MODE_REGULAR_PERCENT_ATTRIBUTE;
    }

    public function isMaxPriceModeAttribute(): bool
    {
        return $this->getMaxPriceMode() == self::PRICE_MODE_ATTRIBUTE;
    }

    public function getMaxPriceCoefficient()
    {
        return $this->getData('max_price_coefficient');
    }

    public function getMaxPriceSource(): array
    {
        return [
            'mode' => $this->getMaxPriceMode(),
            'coefficient' => $this->getMaxPriceCoefficient(),
            'attribute' => $this->getData('max_price_attribute'),
            'regular_value' => $this->getData('max_price_value'),
            'regular_percent' => $this->getData('max_price_percent'),
            'value_attribute' => $this->getData('max_price_value_attribute'),
            'percent_attribute' => $this->getData('max_price_percent_attribute'),
        ];
    }

    public function getMaxPriceAttributes(): array
    {
        $attributes = [];
        $src = $this->getMaxPriceSource();

        if ($src['mode'] == self::PRICE_MODE_ATTRIBUTE) {
            $attributes[] = $src['attribute'];
        }

        if ($src['mode'] == self::MAX_PRICE_MODE_REGULAR_VALUE_ATTRIBUTE) {
            $attributes[] = $src['value_attribute'];
        }

        if ($src['mode'] == self::MAX_PRICE_MODE_REGULAR_PERCENT_ATTRIBUTE) {
            $attributes[] = $src['percent_attribute'];
        }

        return $attributes;
    }

    //########################################

    public function getMaxPriceVariationMode(): int
    {
        return (int)$this->getData('max_price_variation_mode');
    }

    public function isMaxPriceVariationModeParent(): bool
    {
        return $this->getMaxPriceVariationMode() == self::PRICE_VARIATION_MODE_PARENT;
    }

    public function isMaxPriceVariationModeChildren(): bool
    {
        return $this->getMaxPriceVariationMode() == self::PRICE_VARIATION_MODE_CHILDREN;
    }

    //########################################

    /**
     * @return string|null
     */
    public function getLastCheckedListingProductDate()
    {
        return $this->getData('last_checked_listing_product_update_date');
    }

    //########################################

    /**
     * @return int
     */
    public function getDisableMode()
    {
        return (int)$this->getData('disable_mode');
    }

    /**
     * @return bool
     */
    public function isDisableModeManual()
    {
        return $this->getDisableMode() == self::DISABLE_MODE_MANUAL;
    }

    /**
     * @return bool
     */
    public function isDisableModeProductStatus()
    {
        return $this->getDisableMode() == self::DISABLE_MODE_PRODUCT_STATUS;
    }

    /**
     * @return bool
     */
    public function isDisableModeAttribute()
    {
        return $this->getDisableMode() == self::DISABLE_MODE_ATTRIBUTE;
    }

    /**
     * @return array
     */
    public function getDisableSource()
    {
        return [
            'mode' => $this->getDisableMode(),
            'attribute' => $this->getData('disable_mode_attribute'),
        ];
    }

    /**
     * @return array
     */
    public function getDisableAttributes()
    {
        $attributes = [];
        $src = $this->getDisableSource();

        if ($src['mode'] == self::DISABLE_MODE_ATTRIBUTE) {
            $attributes[] = $src['attribute'];
        }

        return $attributes;
    }

    //########################################

    public function isCacheEnabled()
    {
        return true;
    }

    public function getCacheGroupTags()
    {
        $tags = parent::getCacheGroupTags();
        $tags[] = 'account';

        return $tags;
    }

    //########################################
}
