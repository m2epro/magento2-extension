<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\StoreSwitcher
 */
class StoreSwitcher extends Magento\AbstractBlock
{
    const DISPLAY_DEFAULT_STORE_MODE_UP   = 'up';
    const DISPLAY_DEFAULT_STORE_MODE_DOWN = 'down';

    protected $_template = 'store_switcher.phtml';

    protected $_storeIds;
    protected $_hasDefaultOption = true;

    protected $_websiteFactory;
    protected $_storeGroupFactory;
    protected $_storeFactory;

    public function __construct(
        \Magento\Store\Model\WebsiteFactory $websiteFactory,
        \Magento\Store\Model\GroupFactory $storeGroupFactory,
        \Magento\Store\Model\StoreFactory $storeFactory,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        array $data = []
    ) {
        $this->_websiteFactory = $websiteFactory;
        $this->_storeGroupFactory = $storeGroupFactory;
        $this->_storeFactory = $storeFactory;
        parent::__construct($context, $data);
    }

    protected function _construct()
    {
        parent::_construct();

        $this->setUseConfirm(true);
        $this->setUseAjax(true);
        $this->setDefaultStoreName($this->__('Default Config'));
    }

    public function isDisplayDefaultStoreModeUp()
    {
        if (!$this->getData('display_default_store_mode')) {
            return true;
        }

        return $this->getData('display_default_store_mode') == self::DISPLAY_DEFAULT_STORE_MODE_UP;
    }

    public function isDisplayDefaultStoreModeDown()
    {
        return $this->getData('display_default_store_mode') == self::DISPLAY_DEFAULT_STORE_MODE_DOWN;
    }

    //########################################

    public function isRequiredOption()
    {
        return $this->getData('required_option') === true;
    }

    public function hasEmptyOption()
    {
        return $this->getData('has_empty_option') === true;
    }

    //########################################

    public function getDefaultStoreId()
    {
        return \Magento\Store\Model\Store::DEFAULT_STORE_ID;
    }

    public function getDefaultStoreName()
    {
        if ($this->getData('default_store_title')) {
            return $this->getData('default_store_title');
        }

        return parent::getDefaultStoreName();
    }

    //########################################

    public function getWebsiteCollection()
    {
        $collection = $this->_websiteFactory->create()->getResourceCollection();

        $websiteIds = $this->getWebsiteIds();
        if ($websiteIds !== null) {
            $collection->addIdFilter($this->getWebsiteIds());
        }

        return $collection->load();
    }

    public function getWebsites()
    {
        $websites = $this->_storeManager->getWebsites();
        if ($websiteIds = $this->getWebsiteIds()) {
            foreach (array_keys($websites) as $websiteId) {
                if (!in_array($websiteId, $websiteIds)) {
                    unset($websites[$websiteId]);
                }
            }
        }
        return $websites;
    }

    //########################################

    public function getGroupCollection($website)
    {
        if (!$website instanceof \Magento\Store\Model\Website) {
            $website = $this->_websiteFactory->create()->load($website);
        }
        return $website->getGroupCollection();
    }

    public function getStoreGroups($website)
    {
        if (!$website instanceof \Magento\Store\Model\Website) {
            $website = $this->_storeManager->getWebsite($website);
        }
        return $website->getGroups();
    }

    public function getStoreCollection($group)
    {
        if (!$group instanceof \Magento\Store\Model\Group) {
            $group = $this->_storeGroupFactory->create()->load($group);
        }
        $stores = $group->getStoreCollection();
        $_storeIds = $this->getStoreIds();
        if (!empty($_storeIds)) {
            $stores->addIdFilter($_storeIds);
        }
        return $stores;
    }

    public function getStores($group)
    {
        if (!$group instanceof \Magento\Store\Model\Group) {
            $group = $this->_storeManager->getGroup($group);
        }
        $stores = $group->getStores();
        if ($storeIds = $this->getStoreIds()) {
            foreach (array_keys($stores) as $storeId) {
                if (!in_array($storeId, $storeIds)) {
                    unset($stores[$storeId]);
                }
            }
        }
        return $stores;
    }

    public function getSwitchUrl()
    {
        if ($url = $this->getData('switch_url')) {
            return $url;
        }
        return $this->getUrl('*/*/new', ['_current' => true, 'store' => null]);
    }

    public function getStoreId()
    {
        $selected = $this->getData('selected');
        return $selected ? $selected : 0;
    }

    public function setStoreIds($storeIds)
    {
        $this->_storeIds = $storeIds;
        return $this;
    }

    public function getStoreIds()
    {
        return $this->_storeIds;
    }

    public function getStoreSelectId()
    {
        $id = $this->getData('id');
        return $id ? $id : 'store_switcher';
    }

    public function getStoreSelectName()
    {
        $name = $this->getData('name');
        return $name ? $name : $this->getStoreSelectId();
    }

    public function hasDefaultOption()
    {
        if ($this->getData('has_default_option') !== null) {
            $this->_hasDefaultOption = $this->getData('has_default_option');
        }
        return $this->_hasDefaultOption;
    }
}
