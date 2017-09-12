<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Order;

/**
 * @method \Ess\M2ePro\Model\Amazon\Order\Item|\Ess\M2ePro\Model\Ebay\Order\Item getChildObject()
 */
class Item extends \Ess\M2ePro\Model\ActiveRecord\Component\Parent\AbstractModel
{
    // M2ePro\TRANSLATIONS
    // Product does not exist.
    // Product is disabled.
    // Order Import does not support product type: %type%.

    /** @var \Ess\M2ePro\Model\Order */
    private $order = NULL;

    /** @var \Ess\M2ePro\Model\Magento\Product */
    private $magentoProduct = NULL;

    private $proxy = NULL;

    //########################################

    public function _construct()
    {
        parent::_construct();
        $this->_init('Ess\M2ePro\Model\ResourceModel\Order\Item');
    }

    //########################################

    /**
     * @return bool
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function isLocked()
    {
        if (parent::isLocked()) {
            return true;
        }

        return $this->getChildObject()->isLocked();
    }

    public function delete()
    {
        if ($this->isLocked()) {
            return false;
        }

        $this->order = NULL;

        $this->deleteChildInstance();

        return parent::delete();
    }

    //########################################

    public function getOrderId()
    {
        return $this->getData('order_id');
    }

    public function getProductId()
    {
        return $this->getData('product_id');
    }

    /**
     * @return int
     */
    public function getQtyReserved()
    {
        return (int)$this->getData('qty_reserved');
    }

    public function setAssociatedOptions(array $options)
    {
        $this->setSetting('product_details', 'associated_options', $options);
        return $this;
    }

    public function getAssociatedOptions()
    {
        return $this->getSetting('product_details', 'associated_options', array());
    }

    public function setAssociatedProducts(array $products)
    {
        $this->setSetting('product_details', 'associated_products', $products);
        return $this;
    }

    public function getAssociatedProducts()
    {
        return $this->getSetting('product_details', 'associated_products', array());
    }

    public function setReservedProducts(array $products)
    {
        $this->setSetting('product_details', 'reserved_products', $products);
        return $this;
    }

    public function getReservedProducts()
    {
        return $this->getSetting('product_details', 'reserved_products', array());
    }

    //########################################

    /**
     * @param \Ess\M2ePro\Model\Order $order
     * @return $this
     */
    public function setOrder(\Ess\M2ePro\Model\Order $order)
    {
        $this->order = $order;
        return $this;
    }

    /**
     * @return \Ess\M2ePro\Model\Order
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function getOrder()
    {
        if (is_null($this->order)) {
            $this->order = $this->parentFactory->getObjectLoaded(
                $this->getComponentMode(), 'Order', $this->getOrderId()
            );
        }

        return $this->order;
    }

    //########################################

    public function setProduct($product)
    {
        if (!$product instanceof \Magento\Catalog\Model\Product) {
            $this->magentoProduct = null;
            return $this;
        }

        if (is_null($this->magentoProduct)) {
            $this->magentoProduct = $this->helperFactory->getObject('Magento\Product');
        }
        $this->magentoProduct->setProduct($product);

        return $this;
    }

    public function getProduct()
    {
        if (is_null($this->getProductId())) {
            return NULL;
        }

        return $this->getMagentoProduct()->getProduct();
    }

    public function getMagentoProduct()
    {
        if (is_null($this->getProductId())) {
            return NULL;
        }

        if (is_null($this->magentoProduct)) {
            $this->magentoProduct = $this->modelFactory->getObject('Magento\Product');
            $this->magentoProduct
                ->setStoreId($this->getOrder()->getStoreId())
                ->setProductId($this->getProductId());
        }

        return $this->magentoProduct;
    }

    //########################################

    public function getProxy()
    {
        if (is_null($this->proxy)) {
            $this->proxy = $this->getChildObject()->getProxy();
        }

        return $this->proxy;
    }

    //########################################

    public function getStoreId()
    {
        $channelItem = $this->getChildObject()->getChannelItem();

        if (is_null($channelItem)) {
            return $this->getOrder()->getStoreId();
        }

        $storeId = $channelItem->getStoreId();

        if ($storeId != \Magento\Store\Model\Store::DEFAULT_STORE_ID) {
            return $storeId;
        }

        if (is_null($this->getProductId())) {
            return $this->getHelper('Magento\Store')->getDefaultStoreId();
        }

        $storeIds = $this->modelFactory->getObject('Magento\Product')
            ->setProductId($this->getProductId())
            ->getStoreIds();

        if (empty($storeIds)) {
            return \Magento\Store\Model\Store::DEFAULT_STORE_ID;
        }

        return array_shift($storeIds);
    }

    //########################################

    /**
     * Associate order item with product in magento
     *
     * @throws \Ess\M2ePro\Model\Exception
     */
    public function associateWithProduct()
    {
        if (is_null($this->getProductId()) || !$this->getMagentoProduct()->exists()) {
            $this->assignProduct($this->getChildObject()->getAssociatedProductId());
        }

        $supportedProductTypes = $this->getHelper('Magento\Product')->getOriginKnownTypes();

        if (!in_array($this->getMagentoProduct()->getTypeId(), $supportedProductTypes)) {
            $message = $this->getHelper('Module\Log')->encodeDescription(
                'Order Import does not support Product type: %type%.', array(
                    'type' => $this->getMagentoProduct()->getTypeId()
                )
            );

            throw new \Ess\M2ePro\Model\Exception($message);
        }

        $this->associateVariationWithOptions();

        if (!$this->getMagentoProduct()->isStatusEnabled()) {
            throw new \Ess\M2ePro\Model\Exception('Product is disabled.');
        }
    }

    //########################################

    /**
     * Associate order item variation with options of magento product
     *
     * @throws \LogicException
     * @throws \Exception
     */
    private function associateVariationWithOptions()
    {
        $variationChannelOptions = $this->getChildObject()->getVariationChannelOptions();
        $magentoProduct   = $this->getMagentoProduct();

        $existOptions  = $this->getAssociatedOptions();
        $existProducts = $this->getAssociatedProducts();

        if (count($existProducts) == 1
            && ($magentoProduct->isDownloadableType() ||
                $magentoProduct->isGroupedType() ||
                $magentoProduct->isConfigurableType())
        ) {
            // grouped and configurable products can have only one associated product mapped with sold variation
            // so if count($existProducts) == 1 - there is no need for further actions
            return;
        }

        if (!empty($variationChannelOptions)) {
            $matchingHash = \Ess\M2ePro\Model\Order\Matching::generateHash($variationChannelOptions);

            $matchingCollection = $this->activeRecordFactory->getObject('Order\Matching')->getCollection();
            $matchingCollection->addFieldToFilter('product_id', $this->getProductId());
            $matchingCollection->addFieldToFilter('component', $this->getComponentMode());
            $matchingCollection->addFieldToFilter('hash', $matchingHash);

            /** @var $matching \Ess\M2ePro\Model\Order\Matching */
            $matching = $matchingCollection->getFirstItem();

            if ($matching->getId()) {
                $productDetails = $matching->getOutputVariationOptions();

                $this->setAssociatedProducts($productDetails['associated_products']);
                $this->setAssociatedOptions($productDetails['associated_options']);

                $this->save();
                return;
            }
        }

        $magentoOptions = $this->prepareMagentoOptions($magentoProduct->getVariationInstance()->getVariationsTypeRaw());

        $variationProductOptions = $this->getChildObject()->getVariationProductOptions();

        /** @var $optionsFinder \Ess\M2ePro\Model\Order\Item\OptionsFinder */
        $optionsFinder = $this->modelFactory->getObject('Order\Item\OptionsFinder');
        $optionsFinder->setProductId($magentoProduct->getProductId());
        $optionsFinder->setProductType($magentoProduct->getTypeId());
        $optionsFinder->setChannelOptions($variationProductOptions);
        $optionsFinder->setMagentoOptions($magentoOptions);

        $productDetails = $optionsFinder->getProductDetails();

        if (!isset($productDetails['associated_options'])) {
            return;
        }

        $existOptionsIds = array_keys($existOptions);
        $foundOptionsIds = array_keys($productDetails['associated_options']);

        if (count($existOptions) == 0 && count($existProducts) == 0) {
            // options mapping invoked for the first time, use found options
            $this->setAssociatedOptions($productDetails['associated_options']);

            if (isset($productDetails['associated_products'])) {
                $this->setAssociatedProducts($productDetails['associated_products']);
            }

            if ($optionsFinder->hasFailedOptions()) {
                throw new \Ess\M2ePro\Model\Exception\Logic(
                    sprintf('Product Option(s) "%s" not found.', implode(', ', $optionsFinder->getFailedOptions()))
                );
            }

            $this->save();

            return;
        }

        if (count(array_diff($foundOptionsIds, $existOptionsIds)) > 0) {
            // options were already mapped, but not all of them
            throw new \Ess\M2ePro\Model\Exception\Logic('Selected Options do not match the Product Options.');
        }
    }

    public function prepareMagentoOptions($options)
    {
        if (method_exists($this->getChildObject(), 'prepareMagentoOptions')) {
            return $this->getChildObject()->prepareMagentoOptions($options);
        }

       return $options;
    }

    //########################################

    public function assignProduct($productId)
    {
        /** @var $magentoProduct \Ess\M2ePro\Model\Magento\Product */
        $magentoProduct = $this->modelFactory->getObject('Magento\Product');
        $magentoProduct->setProductId($productId);

        if (!$magentoProduct->exists()) {
            $this->setData('product_id', null);
            $this->setAssociatedProducts(array());
            $this->setAssociatedOptions(array());
            $this->save();

            throw new \InvalidArgumentException('Product does not exist.');
        }

        $this->setData('product_id', (int)$productId);

        $this->save();
    }

    //########################################

    public function assignProductDetails(array $associatedOptions, array $associatedProducts)
    {
        /** @var $magentoProduct \Ess\M2ePro\Model\Magento\Product */
        $magentoProduct = $this->modelFactory->getObject('Magento\Product');
        $magentoProduct->setProductId($this->getProductId());

        if (!$magentoProduct->exists()) {
            throw new \Ess\M2ePro\Model\Exception\Logic('Product does not exist.');
        }

        if (count($associatedProducts) == 0
            || (!$magentoProduct->isGroupedType() && count($associatedOptions) == 0)
        ) {
            throw new \InvalidArgumentException('Required Options were not selected.');
        }

        if ($magentoProduct->isGroupedType()) {
            $associatedOptions = array();
            $associatedProducts = reset($associatedProducts);
        }

        $magentoOptions = $this->prepareMagentoOptions($magentoProduct->getVariationInstance()->getVariationsTypeRaw());

        /** @var $optionsFinder \Ess\M2ePro\Model\Order\Item\OptionsFinder */
        $optionsFinder = $this->modelFactory->getObject('Order\Item\OptionsFinder');
        $optionsFinder->setProductId($magentoProduct->getProductId());
        $optionsFinder->setProductType($magentoProduct->getTypeId());
        $optionsFinder->setMagentoOptions($magentoOptions);

        $associatedProducts = $optionsFinder->prepareAssociatedProducts($associatedProducts);

        $this->setAssociatedProducts($associatedProducts);
        $this->setAssociatedOptions($associatedOptions);
        $this->save();
    }

    //########################################

    public function unassignProduct()
    {
        $this->setData('product_id', null);
        $this->setAssociatedProducts(array());
        $this->setAssociatedOptions(array());

        if ($this->getOrder()->getReserve()->isPlaced()) {
            $this->getOrder()->getReserve()->cancel();
        }

        $this->save();
    }

    //########################################
}
