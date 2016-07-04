<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Listing\Other;

class Mapping extends \Ess\M2ePro\Model\AbstractModel
{
    /**
     * @var \Ess\M2ePro\Model\Account|null
     */
    protected $account = NULL;

    protected $mappingSettings = NULL;

    protected $ebayFactory;
    protected $productFactory;

    //########################################

    function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $ebayFactory,
        \Magento\Catalog\Model\ProductFactory $productFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory
    )
    {
        $this->ebayFactory = $ebayFactory;
        $this->productFactory = $productFactory;
        parent::__construct($helperFactory, $modelFactory);
    }

    //########################################

    public function initialize(\Ess\M2ePro\Model\Account $account = NULL)
    {
        $this->account = $account;
        $this->mappingSettings = NULL;
    }

    //########################################

    /**
     * @param array $otherListings
     * @return bool
     */
    public function autoMapOtherListingsProducts(array $otherListings)
    {
        $otherListingsFiltered = array();

        foreach ($otherListings as $otherListing) {

            if (!($otherListing instanceof \Ess\M2ePro\Model\Listing\Other)) {
                continue;
            }

            /** @var $otherListing \Ess\M2ePro\Model\Listing\Other */

            if ($otherListing->getProductId()) {
                continue;
            }

            $otherListingsFiltered[] = $otherListing;
        }

        if (count($otherListingsFiltered) <= 0) {
            return false;
        }

        $accounts = array();

        foreach ($otherListingsFiltered as $otherListing) {

            /** @var $otherListing \Ess\M2ePro\Model\Listing\Other */

            $identifier = $otherListing->getAccountId();

            if (!isset($accounts[$identifier])) {
                $accounts[$identifier] = array();
            }

            $accounts[$identifier][] = $otherListing;
        }

        $result = true;

        foreach ($accounts as $otherListings) {
            foreach ($otherListings as $otherListing) {
                /** @var $otherListing \Ess\M2ePro\Model\Listing\Other */
                $temp = $this->autoMapOtherListingProduct($otherListing);
                $temp === false && $result = false;
            }
        }

        return $result;
    }

    /**
     * @param \Ess\M2ePro\Model\Listing\Other $otherListing
     * @return bool
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function autoMapOtherListingProduct(\Ess\M2ePro\Model\Listing\Other $otherListing)
    {
        if ($otherListing->getProductId()) {
            return false;
        }

        $this->setAccountByOtherListingProduct($otherListing);

        if (!$this->getAccount()->getChildObject()->isOtherListingsMappingEnabled()) {
            return false;
        }

        $mappingSettings = $this->getMappingRulesByPriority();

        foreach ($mappingSettings as $type) {

            $magentoProductId = NULL;

            if ($type == 'sku') {
                $magentoProductId = $this->getSkuMappedMagentoProductId($otherListing);
            }

            if ($type == 'title') {
                $magentoProductId = $this->getTitleMappedMagentoProductId($otherListing);
            }

            if (is_null($magentoProductId)) {
                continue;
            }

            $otherListing->mapProduct($magentoProductId, \Ess\M2ePro\Helper\Data::INITIATOR_EXTENSION);

            return true;
        }

        return false;
    }

    //########################################

    /**
     * @return array|null
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    protected function getMappingRulesByPriority()
    {
        if (!is_null($this->mappingSettings)) {
            return $this->mappingSettings;
        }

        $this->mappingSettings = array();

        foreach ($this->getAccount()->getChildObject()->getOtherListingsMappingSettings() as $key=>$value) {
            if ((int)$value['mode'] == 0) {
                continue;
            }
            for ($i=0;$i<10;$i++) {
                if (!isset($this->mappingSettings[(int)$value['priority']+$i])) {
                    $this->mappingSettings[(int)$value['priority']+$i] = (string)$key;
                    break;
                }
            }
        }

        ksort($this->mappingSettings);

        return $this->mappingSettings;
    }

    // ---------------------------------------

    /**
     * @param \Ess\M2ePro\Model\Listing\Other $otherListing
     * @return int|null
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    protected function getSkuMappedMagentoProductId(\Ess\M2ePro\Model\Listing\Other $otherListing)
    {
        $temp = $otherListing->getChildObject()->getSku();

        if (empty($temp)) {
            return NULL;
        }

        if ($this->getAccount()->getChildObject()->isOtherListingsMappingSkuModeProductId()) {

            $productId = trim($otherListing->getChildObject()->getSku());

            if (!ctype_digit($productId) || (int)$productId <= 0) {
                return NULL;
            }

            $product = $this->productFactory->create()->load($productId);

            if ($product->getId()) {
                return $product->getId();
            }

            return NULL;
        }

        $attributeCode = NULL;

        if ($this->getAccount()->getChildObject()->isOtherListingsMappingSkuModeDefault()) {
            $attributeCode = 'sku';
        }

        if ($this->getAccount()->getChildObject()->isOtherListingsMappingSkuModeCustomAttribute()) {
            $attributeCode = $this->getAccount()->getChildObject()->getOtherListingsMappingSkuAttribute();
        }

        if (is_null($attributeCode)) {
            return NULL;
        }

        $storeId = $otherListing->getChildObject()->getRelatedStoreId();
        $attributeValue = trim($otherListing->getChildObject()->getSku());

        $productObj = $this->productFactory->create()->setStoreId($storeId);
        $productObj = $productObj->loadByAttribute($attributeCode, $attributeValue);

        if ($productObj && $productObj->getId()) {
            return $productObj->getId();
        }

        return NULL;
    }

    /**
     * @param \Ess\M2ePro\Model\Listing\Other $otherListing
     * @return int|null
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    protected function getTitleMappedMagentoProductId(\Ess\M2ePro\Model\Listing\Other $otherListing)
    {
        $temp = $otherListing->getChildObject()->getTitle();

        if (empty($temp)) {
            return NULL;
        }

        $attributeCode = NULL;

        if ($this->getAccount()->getChildObject()->isOtherListingsMappingTitleModeDefault()) {
            $attributeCode = 'name';
        }

        if ($this->getAccount()->getChildObject()->isOtherListingsMappingTitleModeCustomAttribute()) {
            $attributeCode = $this->getAccount()->getChildObject()->getOtherListingsMappingTitleAttribute();
        }

        if (is_null($attributeCode)) {
            return NULL;
        }

        $storeId = $otherListing->getChildObject()->getRelatedStoreId();
        $attributeValue = trim($otherListing->getChildObject()->getTitle());

        $productObj = $this->productFactory->create()->setStoreId($storeId);
        $productObj = $productObj->loadByAttribute($attributeCode, $attributeValue);

        if ($productObj && $productObj->getId()) {
            return $productObj->getId();
        }

        return NULL;
    }

    //########################################

    /**
     * @return \Ess\M2ePro\Model\Account
     */
    protected function getAccount()
    {
        return $this->account;
    }

    // ---------------------------------------

    protected function setAccountByOtherListingProduct(\Ess\M2ePro\Model\Listing\Other $otherListing)
    {
        if (!is_null($this->account) && $this->account->getId() == $otherListing->getAccountId()) {
            return;
        }

        $this->account = $this->ebayFactory->getCachedObjectLoaded(
            'Account', $otherListing->getAccountId()
        );

        $this->mappingSettings = NULL;
    }

    //########################################
}