<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Order;

use \Ess\M2ePro\Model\Ebay\Order\Item as EbayItem;
use \Ess\M2ePro\Model\Amazon\Order\Item as AmazonItem;
use \Ess\M2ePro\Model\Walmart\Order\Item as WalmartItem;

/**
 * @method EbayItem|AmazonItem|WalmartItem getChildObject()
 */
class Item extends \Ess\M2ePro\Model\ActiveRecord\Component\Parent\AbstractModel
{
    // M2ePro\TRANSLATIONS
    // Product does not exist.
    // Product is disabled.
    // Order Import does not support product type: %type%.

    /** @var \Ess\M2ePro\Model\Order */
    private $order;

    /** @var \Ess\M2ePro\Model\Magento\Product */
    private $magentoProduct;

    private $proxy;

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

        $this->order = null;

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
        return $this->getSetting('product_details', 'associated_options', []);
    }

    public function setAssociatedProducts(array $products)
    {
        $this->setSetting('product_details', 'associated_products', $products);
        return $this;
    }

    public function getAssociatedProducts()
    {
        return $this->getSetting('product_details', 'associated_products', []);
    }

    public function setReservedProducts(array $products)
    {
        $this->setSetting('product_details', 'reserved_products', $products);
        return $this;
    }

    public function getReservedProducts()
    {
        return $this->getSetting('product_details', 'reserved_products', []);
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
        if ($this->order === null) {
            $this->order = $this->parentFactory->getObjectLoaded(
                $this->getComponentMode(),
                'Order',
                $this->getOrderId()
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

        if ($this->magentoProduct === null) {
            $this->magentoProduct = $this->modelFactory->getObject('Magento\Product');
        }
        $this->magentoProduct->setProduct($product);

        return $this;
    }

    public function getProduct()
    {
        if ($this->getProductId() === null) {
            return null;
        }

        return $this->getMagentoProduct()->getProduct();
    }

    public function getMagentoProduct()
    {
        if ($this->getProductId() === null) {
            return null;
        }

        if ($this->magentoProduct === null) {
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
        if ($this->proxy === null) {
            $this->proxy = $this->getChildObject()->getProxy();
        }

        return $this->proxy;
    }

    //########################################

    public function getStoreId()
    {
        $channelItem = $this->getChildObject()->getChannelItem();

        if ($channelItem === null) {
            return $this->getOrder()->getStoreId();
        }

        $storeId = $channelItem->getStoreId();

        if ($storeId != \Magento\Store\Model\Store::DEFAULT_STORE_ID) {
            return $storeId;
        }

        if ($this->getProductId() === null) {
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
        if ($this->getProductId() === null || !$this->getMagentoProduct()->exists()) {
            $this->assignProduct($this->getChildObject()->getAssociatedProductId());
        }

        $supportedProductTypes = $this->getHelper('Magento\Product')->getOriginKnownTypes();

        if (!in_array($this->getMagentoProduct()->getTypeId(), $supportedProductTypes)) {
            $message = $this->getHelper('Module\Log')->encodeDescription(
                'Order Import does not support Product type: %type%.',
                [
                    'type' => $this->getMagentoProduct()->getTypeId()
                ]
            );

            throw new \Ess\M2ePro\Model\Exception($message);
        }

        $this->associateVariationWithOptions();

        if (!$this->getMagentoProduct()->isStatusEnabled()) {
            throw new \Ess\M2ePro\Model\Exception('Product is disabled.');
        }
    }

    /**
     * @return bool
     */
    public function canCreateMagentoOrder()
    {
        return $this->getChildObject()->canCreateMagentoOrder();
    }

    /**
     * @return bool
     */
    public function isReservable()
    {
        return $this->getChildObject()->isReservable();
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

        $productDetails = $this->getAssociatedProductDetails($magentoProduct);

        if (!isset($productDetails['associated_options'])) {
            return;
        }

        $existOptionsIds = array_keys($existOptions);
        $foundOptionsIds = array_keys($productDetails['associated_options']);

        if (empty($existOptions) && empty($existProducts)) {
            // options mapping invoked for the first time, use found options
            $this->setAssociatedOptions($productDetails['associated_options']);

            if (isset($productDetails['associated_products'])) {
                $this->setAssociatedProducts($productDetails['associated_products']);
            }

            $this->save();

            return;
        }

        if (!empty(array_diff($foundOptionsIds, $existOptionsIds))) {
            // options were already mapped, but not all of them
            throw new \Ess\M2ePro\Model\Exception\Logic('Selected Options do not match the Product Options.');
        }
    }

    /**
     * @param \Ess\M2ePro\Model\Magento\Product $magentoProduct
     * @return array
     * @throws \Ess\M2ePro\Model\Exception
     */
    private function getAssociatedProductDetails(\Ess\M2ePro\Model\Magento\Product $magentoProduct)
    {
        if (!$magentoProduct->getTypeId()) {
            return [];
        }

        $magentoOptions = $this->prepareMagentoOptions($magentoProduct->getVariationInstance()->getVariationsTypeRaw());

        $storedItemOptions = (array)$this->getChildObject()->getVariationProductOptions();
        $orderItemOptions  = (array)$this->getChildObject()->getVariationOptions();

        /** @var $optionsFinder \Ess\M2ePro\Model\Order\Item\OptionsFinder */
        $optionsFinder = $this->modelFactory->getObject('Order_Item_OptionsFinder');
        $optionsFinder->setProduct($magentoProduct)
                      ->setMagentoOptions($magentoOptions)
                      ->addChannelOptions($storedItemOptions);

        if ($orderItemOptions !== $storedItemOptions) {
            $optionsFinder->addChannelOptions($orderItemOptions);
        }

        $optionsFinder->find();

        if (!$optionsFinder->hasFailedOptions()) {
            return $optionsFinder->getOptionsData();
        }

        throw new \Ess\M2ePro\Model\Exception($optionsFinder->getOptionsNotFoundMessage());
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
            $this->setAssociatedProducts([]);
            $this->setAssociatedOptions([]);
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

        if (empty($associatedProducts)
            || (!$magentoProduct->isGroupedType() && empty($associatedOptions))
        ) {
            throw new \InvalidArgumentException('Required Options were not selected.');
        }

        if ($magentoProduct->isGroupedType()) {
            $associatedOptions = [];
            $associatedProducts = reset($associatedProducts);
        }

        $associatedProducts = $this->getHelper('Magento\Product')->prepareAssociatedProducts(
            $associatedProducts,
            $magentoProduct
        );

        $this->setAssociatedProducts($associatedProducts);
        $this->setAssociatedOptions($associatedOptions);
        $this->save();
    }

    //########################################

    public function unassignProduct()
    {
        $this->setData('product_id', null);
        $this->setAssociatedProducts([]);
        $this->setAssociatedOptions([]);

        if ($this->getOrder()->getReserve()->isPlaced()) {
            $this->getOrder()->getReserve()->cancel();
        }

        $this->save();
    }

    //########################################
}
