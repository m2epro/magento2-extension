<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Observer\Product\AddUpdate\Before;

/**
 * Class \Ess\M2ePro\Observer\Product\AddUpdate\Before\Proxy
 */
class Proxy
{
    private $productId = null;
    private $storeId = null;

    private $data = [];
    private $attributes = [];

    private $websiteIds = [];
    private $categoriesIds = [];

    //########################################

    /**
     * @param int $value
     */
    public function setProductId($value)
    {
        $this->productId = (int)$value;
    }

    /**
     * @return null|int
     */
    public function getProductId()
    {
        return $this->productId;
    }

    // ---------------------------------------

    /**
     * @param int $value
     */
    public function setStoreId($value)
    {
        $this->storeId = (int)$value;
    }

    /**
     * @return null|int
     */
    public function getStoreId()
    {
        return $this->storeId;
    }

    //########################################

    public function setData($key, $value)
    {
        $this->data[$key] = $value;
    }

    public function getData($key)
    {
        return isset($this->data[$key]) ? $this->data[$key] : null;
    }

    // ---------------------------------------

    /**
     * @param array $attributes
     */
    public function setAttributes(array $attributes = [])
    {
        $this->attributes = $attributes;
    }

    /**
     * @return array
     */
    public function getAttributes()
    {
        return $this->attributes;
    }

    //########################################

    /**
     * @param array $ids
     */
    public function setWebsiteIds(array $ids = [])
    {
        $this->websiteIds = $ids;
    }

    /**
     * @return array
     */
    public function getWebsiteIds()
    {
        return $this->websiteIds;
    }

    // ---------------------------------------

    /**
     * @param array $ids
     */
    public function setCategoriesIds(array $ids = [])
    {
        $this->categoriesIds = $ids;
    }

    /**
     * @return array
     */
    public function getCategoriesIds()
    {
        return $this->categoriesIds;
    }

    //########################################
}
