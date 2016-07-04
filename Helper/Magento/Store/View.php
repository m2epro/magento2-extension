<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Helper\Magento\Store;

class View extends \Ess\M2ePro\Helper\AbstractHelper
{
    private $defaultStore = NULL;

    protected $storeFactory;
    protected $storeManager;

    //########################################

    public function __construct(
        \Magento\Store\Model\StoreFactory $storeFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Magento\Framework\App\Helper\Context $context
    )
    {
        $this->storeFactory = $storeFactory;
        $this->storeManager = $storeManager;
        parent::__construct($helperFactory, $context);
    }

    //########################################

    public function isExits($entity)
    {
        if ($entity instanceof \Magento\Store\Model\Store) {
            return (bool)$entity->getCode();
        }

        try {
            $this->storeManager->getStore($entity);
        } catch (\Exception $ex) {
            return false;
        }

        return true;
    }

    public function isChildOfGroup($storeId, $groupId)
    {
        $store = $this->storeManager->getStore($storeId);
        return ($store->getStoreGroupId() == $groupId);
    }

    // ---------------------------------------

    public function isSingleMode()
    {
        return $this->storeFactory->create()->getCollection()->getSize() <= 2;
    }

    public function isMultiMode()
    {
        return !$this->isSingleMode();
    }

    //########################################

    public function getDefault()
    {
        if (is_null($this->defaultStore)) {
            $defaultStoreGroup = $this->getHelper('Magento\Store\Group')->getDefault();
            $defaultStoreId = $defaultStoreGroup->getDefaultStoreId();

            $this->defaultStore = $this->storeManager->getStore($defaultStoreId);
        }

        return $this->defaultStore;
    }

    public function getDefaultStoreId()
    {
        return (int)$this->getDefault()->getId();
    }

    //########################################

    public function getPath($storeId)
    {
        if ($storeId == \Magento\Store\Model\Store::DEFAULT_STORE_ID) {
            return $this->getHelper('Module\Translation')->__('Admin (Default Values)');
        }

        try {
            $store = $this->storeManager->getStore($storeId);
        } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
            $error = $this->getHelper('Module\Translation')->__("Store with %store_id% doesn't exist.", $storeId);
            throw new \Ess\M2ePro\Model\Exception($error);
        }

        $path = $this->storeManager->getWebsite($store->getWebsiteId())->getName();
        $path .= ' > ' . $this->storeManager->getGroup($store->getStoreGroupId())->getName();
        $path .= ' > ' . $store->getName();

        return $path;
    }

    //########################################

    public function addStore($name, $code, $websiteId, $groupId = null)
    {
        if (!$this->getHelper('Magento\Store\Website')->isExists($websiteId)) {
            $error = $this->getHelper('Module\Translation')->__('Website with id %value% does not exists.', $websiteId);
            throw new \Ess\M2ePro\Model\Exception($error);
        }

        try {
            $store = $this->storeFactory->create()->load($code, 'code');
            $error = $this->getHelper('Module\Translation')->__('Store with %code% already exists.', $code);
            throw new \Ess\M2ePro\Model\Exception($error);

        } catch (\Exception $e) {
            // M2ePro_TRANSLATIONS
            // Group with id %group_id% doesn't belongs to website with %site_id%.
            if ($groupId) {

                if (!$this->getHelper('Magento\Store\Group')->isChildOfWebsite($groupId, $websiteId)) {
                    $error = $this->getHelper('Module\Translation')->__('Group with id %group_id% doesn\'t belong to'.
                        'website with %site_id%.',$groupId, $websiteId);
                    throw new \Ess\M2ePro\Model\Exception($error);
                }
            } else {
                $groupId = $this->storeManager->getWebsite($websiteId)->getDefaultGroupId();
            }

            $store = $this->storeFactory->create();
            $store->setId(null);

            $store->setWebsite($this->storeManager->getWebsite($websiteId));
            $store->setWebsiteId($websiteId);

            $store->setGroup($this->storeManager->getGroup($groupId));
            $store->setGroupId($groupId);

            $store->setCode($code);
            $store->setName($name);

            $store->save();
            $this->storeManager->reinitStores();

            return $store;
        }
    }

    //########################################
}