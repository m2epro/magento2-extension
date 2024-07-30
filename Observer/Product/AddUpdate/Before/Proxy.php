<?php

namespace Ess\M2ePro\Observer\Product\AddUpdate\Before;

class Proxy
{
    /** @var null */
    private $productId = null;
    /** @var null */
    private $storeId = null;

    /** @var array */
    private $data = [];
    /** @var array */
    private $attributes = [];

    /** @var array */
    private $websiteIds = [];
    /** @var array */
    private $categoriesIds = [];
    private array $bundleOptionNames = [];

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

    // ----------------------------------------

    public function setBundleOptionNames(array $bundleOptionNames): void
    {
        $this->bundleOptionNames = $bundleOptionNames;
    }

    public function getBundleOptionNames(): array
    {
        return $this->bundleOptionNames;
    }
}
