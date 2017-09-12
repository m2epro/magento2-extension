<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Observer\Product\AddUpdate;

class Before extends AbstractAddUpdate
{
    public static $proxyStorage = array();

    private $proxyFactory = NULL;
    /**
     * @var null|\Ess\M2ePro\Observer\Product\AddUpdate\Before\Proxy
     */
    private $proxy = NULL;

    //########################################

    public function __construct(
        \Ess\M2ePro\Observer\Product\AddUpdate\Before\ProxyFactory $proxyFactory,
        \Magento\Catalog\Model\ProductFactory $productFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Model\Factory $modelFactory
    )
    {
        $this->proxyFactory = $proxyFactory;
        parent::__construct($productFactory, $helperFactory, $activeRecordFactory, $modelFactory);
    }

    //########################################

    public function beforeProcess()
    {
        parent::beforeProcess();
        $this->clearStoredProxy();
    }

    public function afterProcess()
    {
        parent::afterProcess();
        $this->storeProxy();
    }

    // ---------------------------------------

    public function process()
    {
        if ($this->isAddingProductProcess()) {
            return;
        }

        $this->reloadProduct();

        $this->getProxy()->setData('name',$this->getProduct()->getName());

        $this->getProxy()->setWebsiteIds($this->getProduct()->getWebsiteIds());
        $this->getProxy()->setCategoriesIds($this->getProduct()->getCategoryIds());

        if (!$this->areThereAffectedItems()) {
            return;
        }

        $this->getProxy()->setData('status',(int)$this->getProduct()->getStatus());
        $this->getProxy()->setData('price',(float)$this->getProduct()->getPrice());
        $this->getProxy()->setData('special_price',(float)$this->getProduct()->getSpecialPrice());
        $this->getProxy()->setData('special_price_from_date',$this->getProduct()->getSpecialFromDate());
        $this->getProxy()->setData('special_price_to_date',$this->getProduct()->getSpecialToDate());
        $this->getProxy()->setData('tier_price',$this->getProduct()->getTierPrice());

        $this->getProxy()->setAttributes($this->getTrackingAttributesWithValues());
    }

    //########################################

    protected function isAddingProductProcess()
    {
        return $this->getProductId() <= 0;
    }

    //########################################

    /**
     * @return \Ess\M2ePro\Observer\Product\AddUpdate\Before\Proxy
     */
    private function getProxy()
    {
        if (!is_null($this->proxy)) {
            return $this->proxy;
        }

        /** @var \Ess\M2ePro\Observer\Product\AddUpdate\Before\Proxy $object */
        $object = $this->proxyFactory->create();

        $object->setProductId($this->getProductId());
        $object->setStoreId($this->getStoreId());

        return $this->proxy = $object;
    }

    // ---------------------------------------

    private function clearStoredProxy()
    {
        $key = $this->getProductId().'_'.$this->getStoreId();
        if ($this->isAddingProductProcess()) {
            $key = $this->getProduct()->getSku();
        }

        unset(self::$proxyStorage[$key]);
    }

    private function storeProxy()
    {
        $key = $this->getProductId().'_'.$this->getStoreId();
        if ($this->isAddingProductProcess()) {
            $key = $this->getHelper('Data')->generateUniqueHash();
            $this->getEvent()->getProduct()->setData('before_event_key', $key);
        }

        self::$proxyStorage[$key] = $this->getProxy();
    }

    //########################################

    private function getTrackingAttributes()
    {
        $attributes = array();

        foreach ($this->getAffectedListingsProducts() as $listingProduct) {
            /** @var \Ess\M2ePro\Model\Listing\Product $listingProduct */
            $tempAttributes = $listingProduct->getTrackingAttributes();
            $attributes = array_merge($attributes, $tempAttributes);
        }

        return array_values(array_unique($attributes));
    }

    private function getTrackingAttributesWithValues()
    {
        $attributes = array();

        foreach ($this->getTrackingAttributes() as $attributeCode) {
            $attributes[$attributeCode] = $this->getMagentoProduct()->getAttributeValue($attributeCode);
        }

        return $attributes;
    }

    //########################################
}