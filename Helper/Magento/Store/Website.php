<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Helper\Magento\Store;

class Website
{
    /** @var \Magento\Store\Api\Data\WebsiteInterface */
    private $defaultWebsite;
    /** @var \Magento\Store\Model\WebsiteFactory */
    private $websiteFactory;
    /** @var \Magento\Store\Model\StoreManagerInterface */
    private $storeManager;
    /** @var \Ess\M2ePro\Helper\Module\Translation */
    private $translationHelper;

    /**
     * @param \Magento\Store\Model\WebsiteFactory $websiteFactory
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Ess\M2ePro\Helper\Module\Translation $translationHelper
     */
    public function __construct(
        \Magento\Store\Model\WebsiteFactory $websiteFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Ess\M2ePro\Helper\Module\Translation $translationHelper
    ) {
        $this->websiteFactory = $websiteFactory;
        $this->storeManager = $storeManager;
        $this->translationHelper = $translationHelper;
    }

    //########################################

    public function isExists($entity)
    {
        if ($entity instanceof \Magento\Store\Model\Website) {
            return (bool)$entity->getCode();
        }

        try {
            $this->storeManager->getWebsite($entity);
        } catch (\Exception $e) {
            return false;
        }

        return true;
    }

    public function getName($storeId)
    {
        $website = $this->getWebsite($storeId);

        return $website ? $website->getName() : '';
    }

    //########################################

    /**
     * @return \Magento\Store\Api\Data\WebsiteInterface|null
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getDefault()
    {
        if ($this->defaultWebsite !== null) {
            return $this->defaultWebsite;
        }

        $this->defaultWebsite = $this->storeManager->getWebsite(true);

        return $this->defaultWebsite;
    }

    public function getDefaultId()
    {
        return (int)$this->getDefault()->getId();
    }

    //########################################

    public function getWebsite($storeId)
    {
        try {
            $store = $this->storeManager->getStore($storeId);
        } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
            return null;
        }

        return $this->storeManager->getWebsite($store->getWebsiteId());
    }

    public function getWebsites($withDefault = false)
    {
        return $this->storeManager->getWebsites($withDefault);
    }

    //########################################

    public function addWebsite($name, $code)
    {
        $website = $this->websiteFactory->create()->load($code, 'code');

        if ($website->getId()) {
            $error = $this->translationHelper->__('Website with code %value% already exists', $code);
            throw new \Ess\M2ePro\Model\Exception($error);
        }

        $website = $this->websiteFactory->create();

        $website->setCode($code);
        $website->setName($name);
        $website->setId(null)->save();

        return $website;
    }
}
