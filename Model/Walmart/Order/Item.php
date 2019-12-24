<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

/**
 * @method \Ess\M2ePro\Model\Order\Item getParentObject()
 */

namespace Ess\M2ePro\Model\Walmart\Order;

/**
 * Class \Ess\M2ePro\Model\Walmart\Order\Item
 */
class Item extends \Ess\M2ePro\Model\ActiveRecord\Component\Child\Walmart\AbstractModel
{
    const STATUS_CREATED = 'created';
    const STATUS_ACKNOWLEDGED = 'acknowledged';
    const STATUS_SHIPPED = 'shipped';
    const STATUS_SHIPPED_PARTIALLY = 'shippedPartially';
    const STATUS_CANCELLED = 'cancelled';

    // M2ePro\TRANSLATIONS
    // Product Import is disabled in Walmart Account Settings.
    // Product for Walmart Item "%id%" was Created in Magento Catalog.
    // Product for Walmart Item "%title%" was Created in Magento Catalog.

    /** @var $channelItem \Ess\M2ePro\Model\Walmart\Item */
    private $channelItem = null;

    protected $productBuilderFactory;
    protected $productFactory;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\Magento\Product\BuilderFactory $productBuilderFactory,
        \Magento\Catalog\Model\ProductFactory $productFactory,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Walmart\Factory $walmartFactory,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Factory $parentFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->productBuilderFactory = $productBuilderFactory;
        $this->productFactory = $productFactory;
        parent::__construct(
            $walmartFactory,
            $parentFactory,
            $modelFactory,
            $activeRecordFactory,
            $helperFactory,
            $context,
            $registry,
            $resource,
            $resourceCollection,
            $data
        );
    }

    //########################################

    public function _construct()
    {
        parent::_construct();
        $this->_init('Ess\M2ePro\Model\ResourceModel\Walmart\Order\Item');
    }

    //########################################

    public function getProxy()
    {
        return $this->modelFactory->getObject('Walmart_Order_Item_ProxyObject', [
            'item' => $this
        ]);
    }

    //########################################

    /**
     * @return \Ess\M2ePro\Model\Walmart\Order
     */
    public function getWalmartOrder()
    {
        return $this->getParentObject()->getOrder()->getChildObject();
    }

    /**
     * @return \Ess\M2ePro\Model\Walmart\Account
     */
    public function getWalmartAccount()
    {
        return $this->getWalmartOrder()->getWalmartAccount();
    }

    //########################################

    /**
     * @return \Ess\M2ePro\Model\Walmart\Item|null
     */
    public function getChannelItem()
    {
        if ($this->channelItem === null) {
            $this->channelItem = $this->activeRecordFactory->getObject('Walmart\Item')->getCollection()
                ->addFieldToFilter('account_id', $this->getParentObject()->getOrder()->getAccountId())
                ->addFieldToFilter('marketplace_id', $this->getParentObject()->getOrder()->getMarketplaceId())
                ->addFieldToFilter('sku', $this->getSku())
                ->setOrder('create_date', \Magento\Framework\Data\Collection\AbstractDb::SORT_ORDER_DESC)
                ->getFirstItem();
        }

        return $this->channelItem->getId() !== null ? $this->channelItem : null;
    }

    //########################################

    public function getWalmartOrderItemId()
    {
        return $this->getData('walmart_order_item_id');
    }

    public function getMergedWalmartOrderItemIds()
    {
        return $this->getSettings('merged_walmart_order_item_ids');
    }

    // ---------------------------------------

    public function getStatus()
    {
        return $this->getData('status');
    }

    // ---------------------------------------

    public function getTitle()
    {
        return $this->getData('title');
    }

    public function getSku()
    {
        return $this->getData('sku');
    }

    // ---------------------------------------

    /**
     * @return float
     */
    public function getPrice()
    {
        return (float)$this->getData('price');
    }

    /**
     * @return mixed
     */
    public function getCurrency()
    {
        return $this->getData('currency');
    }

    /**
     * @return int
     */
    public function getQty()
    {
        return (int)$this->getData('qty');
    }

    /*
     * Compatibility with Amazon | Ebay
     */
    public function getQtyPurchased()
    {
        return $this->getQty();
    }

    // ---------------------------------------

    /**
     * @return array
     */
    public function getVariationProductOptions()
    {
        $channelItem = $this->getChannelItem();

        if ($channelItem === null) {
            return [];
        }

        return $channelItem->getVariationProductOptions();
    }

    /**
     * @return array
     */
    public function getVariationChannelOptions()
    {
        $channelItem = $this->getChannelItem();

        if ($channelItem === null) {
            return [];
        }

        return $channelItem->getVariationChannelOptions();
    }

    //########################################

    /**
     * @return int
     */
    public function getAssociatedStoreId()
    {
        // Item was listed by M2E
        // ---------------------------------------
        if ($this->getChannelItem() !== null) {
            return $this->getWalmartAccount()->isMagentoOrdersListingsStoreCustom()
                ? $this->getWalmartAccount()->getMagentoOrdersListingsStoreId()
                : $this->getChannelItem()->getStoreId();
        }
        // ---------------------------------------

        return $this->getWalmartAccount()->getMagentoOrdersListingsOtherStoreId();
    }

    //########################################

    public function canCreateMagentoOrder()
    {
        return $this->isOrdersCreationEnabled();
    }

    public function isReservable()
    {
        return $this->isOrdersCreationEnabled();
    }

    // ---------------------------------------

    private function isOrdersCreationEnabled()
    {
        $channelItem = $this->getChannelItem();

        if ($channelItem !== null && !$this->getWalmartAccount()->isMagentoOrdersListingsModeEnabled()) {
            return false;
        }

        if ($channelItem === null && !$this->getWalmartAccount()->isMagentoOrdersListingsOtherModeEnabled()) {
            return false;
        }

        return true;
    }

    //########################################

    /**
     * @return int|mixed
     * @throws \Ess\M2ePro\Model\Exception
     */
    public function getAssociatedProductId()
    {
        // Item was listed by M2E
        // ---------------------------------------
        if ($this->getChannelItem() !== null) {
            return $this->getChannelItem()->getProductId();
        }
        // ---------------------------------------

        // 3rd party Item
        // ---------------------------------------
        $sku = $this->getSku();
        if ($sku != '' && strlen($sku) <= \Ess\M2ePro\Helper\Magento\Product::SKU_MAX_LENGTH) {
            $product = $this->productFactory->create()
                ->setStoreId($this->getWalmartOrder()->getAssociatedStoreId())
                ->getCollection()
                ->addAttributeToSelect('sku')
                ->addAttributeToFilter('sku', $sku)
                ->getFirstItem();

            if ($product->getId()) {
                $this->_eventManager->dispatch('ess_associate_walmart_order_item_to_product', [
                    'product'    => $product,
                    'order_item' => $this->getParentObject(),
                ]);

                return $product->getId();
            }
        }
        // ---------------------------------------

        $product = $this->createProduct();

        $this->_eventManager->dispatch('ess_associate_walmart_order_item_to_product', [
            'product'    => $product,
            'order_item' => $this->getParentObject(),
        ]);

        return $product->getId();
    }

    /**
     * @return \Magento\Catalog\Model\Product
     * @throws \Ess\M2ePro\Model\Exception
     */
    private function createProduct()
    {
        if (!$this->getWalmartAccount()->isMagentoOrdersListingsOtherProductImportEnabled()) {
            throw new \Ess\M2ePro\Model\Exception('Product Import is disabled in Walmart Account Settings.');
        }

        $storeId = $this->getWalmartAccount()->getMagentoOrdersListingsOtherStoreId();
        if ($storeId == 0) {
            $storeId = $this->getHelper('Magento\Store')->getDefaultStoreId();
        }

        $sku = $this->getSku();
        if (strlen($sku) > \Ess\M2ePro\Helper\Magento\Product::SKU_MAX_LENGTH) {
            $hashLength = 10;
            $savedSkuLength = \Ess\M2ePro\Helper\Magento\Product::SKU_MAX_LENGTH - $hashLength - 1;
            $hash = $this->getHelper('Data')->generateUniqueHash($sku, $hashLength);

            $isSaveStart = (bool)$this->getHelper('Module')->getConfig()->getGroupValue(
                '/order/magento/settings/',
                'save_start_of_long_sku_for_new_product'
            );

            if ($isSaveStart) {
                $sku = substr($sku, 0, $savedSkuLength) . '-' . $hash;
            } else {
                $sku = $hash . '-' . substr($sku, strlen($sku) - $savedSkuLength, $savedSkuLength);
            }
        }

        $productData = [
            'title'             => $this->getTitle(),
            'sku'               => $sku,
            'description'       => '',
            'short_description' => '',
            'qty'               => $this->getQtyForNewProduct(),
            'price'             => $this->getPrice(),
            'store_id'          => $storeId,
            'tax_class_id'     => $this->getWalmartAccount()->getMagentoOrdersListingsOtherProductTaxClassId()
        ];

        // Create product in magento
        // ---------------------------------------
        /** @var $productBuilder \Ess\M2ePro\Model\Magento\Product\Builder */
        $productBuilder = $this->productBuilderFactory->create()->setData($productData);
        $productBuilder->buildProduct();
        // ---------------------------------------

        $this->getParentObject()->getOrder()->addSuccessLog(
            'Product for Walmart Item "%title%" was Created in Magento Catalog.',
            ['!title' => $this->getTitle()]
        );

        return $productBuilder->getProduct();
    }

    private function getQtyForNewProduct()
    {
        $otherListing = $this->walmartFactory->getObject('Listing\Other')->getCollection()
            ->addFieldToFilter('account_id', $this->getParentObject()->getOrder()->getAccountId())
            ->addFieldToFilter('marketplace_id', $this->getParentObject()->getOrder()->getMarketplaceId())
            ->addFieldToFilter('sku', $this->getSku())
            ->getFirstItem();

        if ((int)$otherListing->getOnlineQty() > $this->getQty()) {
            return $otherListing->getOnlineQty();
        }

        return $this->getQty();
    }

    //########################################
}
