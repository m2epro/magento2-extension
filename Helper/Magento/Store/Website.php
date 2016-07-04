<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Helper\Magento\Store;

class Website extends \Ess\M2ePro\Helper\AbstractHelper
{
    private $defaultWebsite = NULL;

    protected $websiteFactory;
    protected $storeManager;

    //########################################

    public function __construct(
        \Magento\Store\Model\WebsiteFactory $websiteFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Magento\Framework\App\Helper\Context $context
    )
    {
        $this->websiteFactory = $websiteFactory;
        $this->storeManager = $storeManager;
        parent::__construct($helperFactory, $context);
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

    public function getDefault()
    {
        if (!is_null($this->defaultWebsite)) {
            return $this->defaultWebsite;
        }

        $this->defaultWebsite = $this->storeManager->getWebsite(true);;

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
            return NULL;
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
            $error = $this->getHelper('Module\Translation')->__('Website with code %value% already exists', $code);
            throw new \Ess\M2ePro\Model\Exception($error);
        }

        $website = $this->websiteFactory->create();

        $website->setCode($code);
        $website->setName($name);
        $website->setId(null)->save();

        return $website;
    }

    //########################################
}