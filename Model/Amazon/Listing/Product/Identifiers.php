<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Listing\Product;

use Ess\M2ePro\Model\Amazon\Listing\Product\Identifiers\GeneralId;
use Ess\M2ePro\Model\Amazon\Listing\Product\Identifiers\WorldwideId;

class Identifiers
{
    private const PRODUCT_OVERRIDE_ID_CUSTOM_CODE = 'CUSTOM';
    private const REGISTERED_PARAMETER_PRIVATE_LABEL = 'PrivateLabel';

    private const PRODUCT_OVERRIDE_ID_SPECIALIZED_CODE = 'CUSTOM_SPECIALIZED';
    private const REGISTERED_PARAMETER_SPECIALIZED = 'Specialized';

    private const PRODUCT_OVERRIDE_ID_NON_CONSUMER_CODE = 'CUSTOM_NONCONSUMER';
    private const REGISTERED_PARAMETER_NON_CONSUMER = 'NonConsumer';

    private const PRODUCT_OVERRIDE_ID_PRE_CONFIGURED_CODE = 'CUSTOM_PRECONFIGURED';
    private const REGISTERED_PARAMETER_PRE_CONFIGURED = 'PreConfigured';

    /** @var string[] */
    private $registeredParameterMap = [
        self::PRODUCT_OVERRIDE_ID_CUSTOM_CODE => self::REGISTERED_PARAMETER_PRIVATE_LABEL,
        self::PRODUCT_OVERRIDE_ID_SPECIALIZED_CODE => self::REGISTERED_PARAMETER_SPECIALIZED,
        self::PRODUCT_OVERRIDE_ID_NON_CONSUMER_CODE => self::REGISTERED_PARAMETER_NON_CONSUMER,
        self::PRODUCT_OVERRIDE_ID_PRE_CONFIGURED_CODE => self::REGISTERED_PARAMETER_PRE_CONFIGURED,
    ];

    /** @var \Ess\M2ePro\Model\Magento\Product */
    private $magentoProduct;
    /** @var \Ess\M2ePro\Helper\Component\Amazon\Configuration */
    private $config;

    public function __construct(
        \Ess\M2ePro\Model\Magento\Product $magentoProduct,
        \Ess\M2ePro\Helper\Component\Amazon\Configuration $config
    ) {
        $this->magentoProduct = $magentoProduct;
        $this->config = $config;
    }

    /**
     * @return string|null
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function getRegisteredParameter(): ?string
    {
        if ($this->config->isProductIdOverrideModeNone()) {
            return null;
        }

        if ($this->config->isProductIdOverrideModeAll()) {
            return self::REGISTERED_PARAMETER_PRIVATE_LABEL;
        }

        if ($this->config->isProductIdOverrideModeSpecificProducts()) {
            return $this->getRegisteredParameterForSpecificProduct();
        }

        throw new \Ess\M2ePro\Model\Exception\Logic('Invalid product override mode');
    }

    /**
     * @return \Ess\M2ePro\Model\Amazon\Listing\Product\Identifiers\GeneralId|null
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function getGeneralId(): ?GeneralId
    {
        if ($this->config->isGeneralIdModeNone()) {
            return null;
        }

        if ($this->config->isGeneralIdModeCustomAttribute()) {
            $attributeCode = $this->config->getGeneralIdCustomAttribute();
            if ($attributeValue = $this->getAttributeValue($attributeCode)) {
                return new GeneralId($attributeValue);
            }

            return null;
        }

        throw new \Ess\M2ePro\Model\Exception\Logic('Invalid general id mode');
    }

    /**
     * @return WorldwideId|null
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function getWorldwideId(): ?WorldwideId
    {
        $worldwideId = $this->getOriginalWorldwideId();

        if ($worldwideId && !$this->worldwideIdIsOverriddenByRegisteredParam($worldwideId)) {
            return new WorldwideId($worldwideId);
        }

        return null;
    }

    /**
     * @return string|null
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    private function getRegisteredParameterForSpecificProduct(): ?string
    {
        if ($worldwideId = $this->getOriginalWorldwideId()) {
            return $this->getRegisteredParameterByCode($worldwideId);
        }

        return null;
    }

    /**
     * @return string|null
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    private function getOriginalWorldwideId(): ?string
    {
        if ($this->config->isWorldwideIdModeNone()) {
            return null;
        }

        if ($this->config->isWorldwideIdModeCustomAttribute()) {
            $attributeCode = $this->config->getWorldwideCustomAttribute();

            return $this->getAttributeValue($attributeCode);
        }

        throw new \Ess\M2ePro\Model\Exception\Logic('Invalid worldwide id mode');
    }

    /**
     * @param string $worldwideId
     *
     * @return bool
     */
    private function worldwideIdIsOverriddenByRegisteredParam(string $worldwideId): bool
    {
        return $this->config->isProductIdOverrideModeSpecificProducts()
            && $this->getRegisteredParameterByCode($worldwideId);
    }

    /**
     * @param string $code
     *
     * @return string|null
     */
    private function getRegisteredParameterByCode(string $code): ?string
    {
        $code = strtoupper($code);

        return $this->registeredParameterMap[$code] ?? null;
    }

    /**
     * @param string $attributeCode
     *
     * @return string
     */
    private function getAttributeValue(string $attributeCode): ?string
    {
        $value = $this->magentoProduct->getAttributeValue($attributeCode);
        $value = trim(str_replace('-', '', $value));

        return $value === '' ? null : $value;
    }
}
