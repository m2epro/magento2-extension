<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Helper\Component\Amazon;

class Configuration
{
    public const PRODUCT_ID_OVERRIDE_MODE_NONE              = 0;
    public const PRODUCT_ID_OVERRIDE_MODE_ALL               = 1;
    public const PRODUCT_ID_OVERRIDE_MODE_SPECIFIC_PRODUCTS = 2;

    public const WORLDWIDE_ID_MODE_NONE             = 0;
    public const WORLDWIDE_ID_MODE_CUSTOM_ATTRIBUTE = 1;

    public const GENERAL_ID_MODE_NONE             = 0;
    public const GENERAL_ID_MODE_CUSTOM_ATTRIBUTE = 1;

    private const CONFIG_GROUP = '/amazon/configuration/';

    /** @var \Ess\M2ePro\Model\Config\Manager */
    private $config;

    public function __construct(
        \Ess\M2ePro\Model\Config\Manager $config
    ) {
        $this->config = $config;
    }

    // ----------------------------------------

    /**
     * @return int
     */
    public function getBusinessMode(): int
    {
        return (int)$this->config->getGroupValue(
            self::CONFIG_GROUP,
            'business_mode'
        );
    }

    /**
     * @return bool
     */
    public function isEnabledBusinessMode(): bool
    {
        return $this->getBusinessMode() == 1;
    }

    // ----------------------------------------

    /**
     * @return int
     */
    public function getProductIdOverrideMode(): int
    {
        return (int)$this->config->getGroupValue(
            self::CONFIG_GROUP,
            'product_id_override_mode'
        );
    }

    /**
     * @return bool
     */
    public function isProductIdOverrideModeNone(): bool
    {
        return $this->getProductIdOverrideMode() == self::PRODUCT_ID_OVERRIDE_MODE_NONE;
    }

    /**
     * @return bool
     */
    public function isProductIdOverrideModeAll(): bool
    {
        return $this->getProductIdOverrideMode() == self::PRODUCT_ID_OVERRIDE_MODE_ALL;
    }

    /**
     * @return bool
     */
    public function isProductIdOverrideModeSpecificProducts(): bool
    {
        return $this->getProductIdOverrideMode() == self::PRODUCT_ID_OVERRIDE_MODE_SPECIFIC_PRODUCTS;
    }

    // ----------------------------------------

    /**
     * @return int
     */
    public function getWorldwideIdMode(): int
    {
        return (int)$this->config->getGroupValue(
            self::CONFIG_GROUP,
            'worldwide_id_mode'
        );
    }

    /**
     * @return bool
     */
    public function isWorldwideIdModeNone(): bool
    {
        return $this->getWorldwideIdMode() == self::WORLDWIDE_ID_MODE_NONE;
    }

    /**
     * @return bool
     */
    public function isWorldwideIdModeCustomAttribute(): bool
    {
        return $this->getWorldwideIdMode() == self::WORLDWIDE_ID_MODE_CUSTOM_ATTRIBUTE;
    }

    /**
     * @return mixed|null
     */
    public function getWorldwideCustomAttribute()
    {
        return $this->config->getGroupValue(
            self::CONFIG_GROUP,
            'worldwide_id_custom_attribute'
        );
    }

    // ----------------------------------------

    /**
     * @return int
     */
    public function getGeneralIdMode(): int
    {
        return (int)$this->config->getGroupValue(
            self::CONFIG_GROUP,
            'general_id_mode'
        );
    }

    /**
     * @return bool
     */
    public function isGeneralIdModeNone(): bool
    {
        return $this->getGeneralIdMode() == self::GENERAL_ID_MODE_NONE;
    }

    /**
     * @return bool
     */
    public function isGeneralIdModeCustomAttribute(): bool
    {
        return $this->getGeneralIdMode() == self::GENERAL_ID_MODE_CUSTOM_ATTRIBUTE;
    }

    /**
     * @return mixed|null
     */
    public function getGeneralIdCustomAttribute()
    {
        return $this->config->getGroupValue(
            self::CONFIG_GROUP,
            'general_id_custom_attribute'
        );
    }

    // ----------------------------------------

    /**
     * @param array $values
     *
     * @return void
     */
    public function setConfigValues(array $values): void
    {
        $allowedConfigKeys = [
            'business_mode',
            'product_id_override_mode',
            'worldwide_id_mode',
            'worldwide_id_custom_attribute',
            'general_id_mode',
            'general_id_custom_attribute',
        ];

        foreach ($allowedConfigKeys as $configKey) {
            if (!isset($values[$configKey])) {
                continue;
            }

            $this->config->setGroupValue(
                self::CONFIG_GROUP,
                $configKey,
                $values[$configKey]
            );
        }
    }
}
