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

        return $worldwideId ? new WorldwideId($worldwideId) : null;
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
