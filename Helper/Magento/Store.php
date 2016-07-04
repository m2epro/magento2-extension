<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Helper\Magento;

class Store extends \Ess\M2ePro\Helper\AbstractHelper
{
    private $defaultWebsite = NULL;
    private $defaultStoreGroup = NULL;
    private $defaultStore = NULL;

    protected $storeManager;

    //########################################

    public function __construct(
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Magento\Framework\App\Helper\Context $context
    )
    {
        $this->storeManager = $storeManager;
        parent::__construct($helperFactory, $context);
    }

    //########################################

    public function isSingleStoreMode()
    {
        return count($this->storeManager->getStores(true)) <= 2;
    }

    public function isMultiStoreMode()
    {
        return !$this->isSingleStoreMode();
    }

    //########################################

    public function getDefaultWebsite()
    {
        if (is_null($this->defaultWebsite)) {
            $this->defaultWebsite = $this->storeManager->getWebsite(true);
        }
        return $this->defaultWebsite;
    }

    public function getDefaultStoreGroup()
    {
        if (is_null($this->defaultStoreGroup)) {

            $defaultWebsite = $this->getDefaultWebsite();
            $defaultStoreGroupId = $defaultWebsite->getDefaultGroupId();

            $this->defaultStoreGroup = $this->storeManager->getGroup($defaultStoreGroupId);
        }
        return $this->defaultStoreGroup;
    }

    public function getDefaultStore()
    {
        if (is_null($this->defaultStore)) {

            $defaultStoreGroup = $this->getDefaultStoreGroup();
            $defaultStoreId = $defaultStoreGroup->getDefaultStoreId();

            $this->defaultStore = $this->storeManager->getStore($defaultStoreId);
        }
        return $this->defaultStore;
    }

    // ---------------------------------------

    public function getDefaultWebsiteId()
    {
        return (int)$this->getDefaultWebsite()->getId();
    }

    public function getDefaultStoreGroupId()
    {
        return (int)$this->getDefaultStoreGroup()->getId();
    }

    public function getDefaultStoreId()
    {
        return (int)$this->getDefaultStore()->getId();
    }

    //########################################

    public function getStorePath($storeId)
    {
        if ($storeId == \Magento\Store\Model\Store::DEFAULT_STORE_ID) {
            return $this->getHelper('Module\Translation')->__('Admin (Default Values)');
        }

        try {
            $store = $this->storeManager->getStore($storeId);
        } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
            return $this->getHelper('Module\Translation')->__('Requested store is not found');
        }

        $path = $this->storeManager->getWebsite($store->getWebsiteId())->getName();
        $path .= ' > ' . $this->storeManager->getGroup($store->getStoreGroupId())->getName();
        $path .= ' > ' . $store->getName();

        return $path;
    }

    public function getWebsite($storeId)
    {
        try {
            $store = $this->storeManager->getStore($storeId);
        } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
            return NULL;
        }

        return $this->storeManager->getWebsite($store->getWebsiteId());
    }

    public function getWebsiteName($storeId)
    {
        $website = $this->getWebsite($storeId);

        return $website ? $website->getName() : '';
    }

    //########################################
}