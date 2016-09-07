<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Observer\Product\AddUpdate\Before;

class Proxy
{
    private $productId = NULL;
    private $storeId = NULL;

    private $data = array();
    private $attributes = array();

    private $websiteIds = array();
    private $categoriesIds = array();

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
        return isset($this->data[$key]) ? $this->data[$key] : NULL;
    }

    // ---------------------------------------

    /**
     * @param array $attributes
     */
    public function setAttributes(array $attributes = array())
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
    public function setWebsiteIds(array $ids = array())
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
    public function setCategoriesIds(array $ids = array())
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