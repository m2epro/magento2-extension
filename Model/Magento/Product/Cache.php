<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Magento\Product;

/**
 * Class \Ess\M2ePro\Model\Magento\Product\Cache
 */
class Cache extends \Ess\M2ePro\Model\Magento\Product
{
    private $isCacheEnabled = false;

    //########################################

    public function getCacheValue($key)
    {
        $key = sha1(
            'magento_product_'
            . $this->getProductId()
            . '_'
            . $this->getStoreId()
            . '_'
            . $this->getHelper('Data')->jsonEncode($key)
        );

        return $this->getHelper('Data_Cache_Runtime')->getValue($key);
    }

    public function setCacheValue($key, $value)
    {
        $key = sha1(
            'magento_product_'
            . $this->getProductId()
            . '_'
            . $this->getStoreId()
            . '_'
            . $this->getHelper('Data')->jsonEncode($key)
        );

        $tags = [
            'magento_product',
            'magento_product_'.$this->getProductId().'_'.$this->getStoreId()
        ];

        return $this->getHelper('Data_Cache_Runtime')->setValue($key, $value, $tags);
    }

    public function clearCache()
    {
        return $this->getHelper('Data_Cache_Runtime')->removeTagValues(
            'magento_product_'.$this->getProductId().'_'.$this->getStoreId()
        );
    }

    //########################################

    /**
     * @return bool
     */
    public function isCacheEnabled()
    {
        return $this->isCacheEnabled;
    }

    /**
     * @return $this
     */
    public function enableCache()
    {
        $this->isCacheEnabled = true;
        return $this;
    }

    /**
     * @return $this
     */
    public function disableCache()
    {
        $this->isCacheEnabled = false;
        return $this;
    }

    //########################################

    public function exists()
    {
        return $this->getMethodData(__FUNCTION__);
    }

    //########################################

    /**
     * {@inheritdoc}
     */
    public function getTypeInstance()
    {
        return $this->getMethodData(__FUNCTION__);
    }

    /**
     * {@inheritdoc}
     */
    public function getStockItem()
    {
        return $this->getMethodData(__FUNCTION__);
    }

    //########################################

    public function getTypeId()
    {
        return $this->getMethodData(__FUNCTION__);
    }

    //########################################

    public function isSimpleTypeWithCustomOptions()
    {
        return $this->getMethodData(__FUNCTION__);
    }

    //########################################

    public function getSku()
    {
        return $this->getMethodData(__FUNCTION__);
    }

    //########################################

    public function getName()
    {
        return $this->getMethodData(__FUNCTION__);
    }

    //########################################

    public function isStatusEnabled()
    {
        return $this->getMethodData(__FUNCTION__);
    }

    //########################################

    public function isStockAvailability()
    {
        return $this->getMethodData(__FUNCTION__);
    }

    //########################################

    public function getPrice()
    {
        return $this->getMethodData(__FUNCTION__);
    }

    //########################################

    public function getSpecialPrice()
    {
        return $this->getMethodData(__FUNCTION__);
    }

    //########################################

    public function getQty($lifeMode = false)
    {
        $args = func_get_args();
        return $this->getMethodData(__FUNCTION__, $args);
    }

    //########################################

    public function getAttributeValue($attributeCode)
    {
        $args = func_get_args();
        return $this->getMethodData(__FUNCTION__, $args);
    }

    //########################################

    public function getThumbnailImage()
    {
        return $this->getMethodData(__FUNCTION__);
    }

    public function getImage($attribute = 'image')
    {
        $args = func_get_args();
        return $this->getMethodData(__FUNCTION__, $args);
    }

    public function getGalleryImages($limitImages = 0)
    {
        $args = func_get_args();
        return $this->getMethodData(__FUNCTION__, $args);
    }

    //########################################

    public function getVariationInstance()
    {
        if ($this->_variationInstance !== null) {
            return $this->_variationInstance;
        }

        $this->_variationInstance = $this->modelFactory
            ->getObject('Magento_Product_Variation_Cache')->setMagentoProduct($this);
        return $this->_variationInstance;
    }

    //########################################

    protected function getMethodData($methodName, $params = null)
    {
        $cacheKey = [
            __CLASS__,
            $methodName,
        ];

        if ($params !== null) {
            $cacheKey[] = $params;
        }

        $cacheResult = $this->getCacheValue($cacheKey);

        if ($this->isCacheEnabled() && $cacheResult !== null) {
            return $cacheResult;
        }

        if ($params !== null) {
            $data = call_user_func_array(['parent', $methodName], $params);
        } else {
            $data = call_user_func(['parent', $methodName]);
        }

        if (!$this->isCacheEnabled()) {
            return $data;
        }

        return $this->setCacheValue($cacheKey, $data);
    }

    //########################################
}
