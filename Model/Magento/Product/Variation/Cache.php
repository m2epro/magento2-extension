<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Magento\Product\Variation;

/**
 * Class \Ess\M2ePro\Model\Magento\Product\Variation\Cache
 */
class Cache extends \Ess\M2ePro\Model\Magento\Product\Variation
{
    //########################################

    public function getVariationsTypeStandard()
    {
        $cacheKeyParams = [
            'virtual_attributes' => $this->getMagentoProduct()->getVariationVirtualAttributes(),
            'filter_attributes' => $this->getMagentoProduct()->getVariationFilterAttributes(),
            'is_ignore_virtual_attributes' => $this->getMagentoProduct()->isIgnoreVariationVirtualAttributes(),
            'is_ignore_filter_attributes' => $this->getMagentoProduct()->isIgnoreVariationFilterAttributes(),
        ];

        return $this->getMethodData(__FUNCTION__, $cacheKeyParams);
    }

    public function getVariationsTypeRaw()
    {
        return $this->getMethodData(__FUNCTION__);
    }

    public function getTitlesVariationSet()
    {
        return $this->getMethodData(__FUNCTION__);
    }

    //########################################

    protected function getMethodData($methodName, $cacheKeyParams = [])
    {
        if ($this->getMagentoProduct() === null) {
            throw new \Ess\M2ePro\Model\Exception('Magento Product was not set.');
        }

        $cacheKey = [
            __CLASS__,
            $methodName,
        ];

        if ($cacheKeyParams !== []) {
            $cacheKey[] = $cacheKeyParams;
        }

        $cacheResult = $this->getMagentoProduct()->getCacheValue($cacheKey);

        if ($this->getMagentoProduct()->isCacheEnabled() && $cacheResult !== null) {
            return $cacheResult;
        }

        $data = parent::$methodName();

        if (!$this->getMagentoProduct()->isCacheEnabled()) {
            return $data;
        }

        return $this->getMagentoProduct()->setCacheValue($cacheKey, $data);
    }

    //########################################
}
