<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Magento\Product;

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

        return $this->getHelper('Data\Cache\Runtime')->getValue($key);
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

        $tags = array(
            'magento_product',
            'magento_product_'.$this->getProductId().'_'.$this->getStoreId()
        );

        return $this->getHelper('Data\Cache\Runtime')->setValue($key, $value, $tags);
    }

    public function clearCache()
    {
        return $this->getHelper('Data\Cache\Runtime')->removeTagValues(
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
        if (!is_null($this->_variationInstance)) {
            return $this->_variationInstance;
        }

        $this->_variationInstance = $this->modelFactory
            ->getObject('Magento\Product\Variation\Cache')->setMagentoProduct($this);
        return $this->_variationInstance;
    }

    //########################################

    protected function getMethodData($methodName, $params = null)
    {
        $cacheKey = array(
            __CLASS__,
            $methodName,
        );

        if (!is_null($params)) {
            $cacheKey[] = $params;
        }

        $cacheResult = $this->getCacheValue($cacheKey);

        if ($this->isCacheEnabled() && !is_null($cacheResult)) {
            return $cacheResult;
        }

        if (!is_null($params)) {
            $data = call_user_func_array(array('parent', $methodName), $params);
        } else {
            $data = call_user_func(array('parent', $methodName));
        }

        if (!$this->isCacheEnabled()) {
            return $data;
        }

        return $this->setCacheValue($cacheKey, $data);
    }

    //########################################
}