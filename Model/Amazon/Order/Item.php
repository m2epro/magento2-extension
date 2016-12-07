<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

/**
 * @method Ess\M2ePro\Model\Order\Item getParentObject()
 */
namespace Ess\M2ePro\Model\Amazon\Order;

class Item extends \Ess\M2ePro\Model\ActiveRecord\Component\Child\Amazon\AbstractModel
{
    // M2ePro\TRANSLATIONS
    // Product Import is disabled in Amazon Account Settings.
    // Product for Amazon Item "%id%" was Created in Magento Catalog.
    // Product for Amazon Item "%title%" was Created in Magento Catalog.

    private $productBuilderFactory;

    private $productFactory;

    /** @var $channelItem \Ess\M2ePro\Model\Amazon\Item */
    private $channelItem = NULL;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\Magento\Product\BuilderFactory $productBuilderFactory,
        \Magento\Catalog\Model\ProductFactory $productFactory,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Factory $parentFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    )
    {
        $this->productBuilderFactory = $productBuilderFactory;
        $this->productFactory = $productFactory;
        parent::__construct(
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
        $this->_init('Ess\M2ePro\Model\ResourceModel\Amazon\Order\Item');

    }

    //########################################

    public function getProxy()
    {
        return $this->modelFactory->getObject('Amazon\Order\Item\Proxy', [
            'item' => $this
        ]);
    }

    //########################################

    /**
     * @return \Ess\M2ePro\Model\Amazon\Order
     */
    public function getAmazonOrder()
    {
        return $this->getParentObject()->getOrder()->getChildObject();
    }

    /**
     * @return \Ess\M2ePro\Model\Amazon\Account
     */
    public function getAmazonAccount()
    {
        return $this->getAmazonOrder()->getAmazonAccount();
    }

    //########################################

    /**
     * @return \Ess\M2ePro\Model\Amazon\Item|null
     */
    public function getChannelItem()
    {
        if (is_null($this->channelItem)) {
            $this->channelItem = $this->activeRecordFactory->getObject('Amazon\Item')->getCollection()
                ->addFieldToFilter('account_id', $this->getParentObject()->getOrder()->getAccountId())
                ->addFieldToFilter('marketplace_id', $this->getParentObject()->getOrder()->getMarketplaceId())
                ->addFieldToFilter('sku', $this->getSku())
                ->setOrder('create_date', \Magento\Framework\Data\Collection::SORT_ORDER_DESC)
                ->getFirstItem();
        }

        return !is_null($this->channelItem->getId()) ? $this->channelItem : NULL;
    }

    //########################################

    public function getAmazonOrderItemId()
    {
        return $this->getData('amazon_order_item_id');
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

    public function getGeneralId()
    {
        return $this->getData('general_id');
    }

    /**
     * @return int
     */
    public function getIsIsbnGeneralId()
    {
        return (int)$this->getData('is_isbn_general_id');
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
    public function getQtyPurchased()
    {
        return (int)$this->getData('qty_purchased');
    }

    // ---------------------------------------

    /**
     * @return float
     */
    public function getGiftPrice()
    {
        return (float)$this->getData('gift_price');
    }

    public function getGiftType()
    {
        return $this->getData('gift_type');
    }

    public function getGiftMessage()
    {
        return $this->getData('gift_message');
    }

    // ---------------------------------------

    public function getTaxDetails()
    {
        return $this->getSettings('tax_details');
    }

    public function getTaxAmount()
    {
        $taxDetails = $this->getTaxDetails();
        return isset($taxDetails['product']['value']) ? (float)$taxDetails['product']['value'] : 0.0;
    }

    // ---------------------------------------

    /**
     * @return array
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function getDiscountDetails()
    {
        return $this->getSettings('discount_details');
    }

    /**
     * @return float
     */
    public function getDiscountAmount()
    {
        $discountDetails = $this->getDiscountDetails();
        return !empty($discountDetails['promotion']['value'])
            ? ($discountDetails['promotion']['value'] / $this->getQtyPurchased()) : 0.0;
    }

    // ---------------------------------------

    /**
     * @return array
     */
    public function getVariationProductOptions()
    {
        $channelItem = $this->getChannelItem();

        if (is_null($channelItem)) {
            return array();
        }

        return $channelItem->getVariationProductOptions();
    }

    /**
     * @return array
     */
    public function getVariationChannelOptions()
    {
        $channelItem = $this->getChannelItem();

        if (is_null($channelItem)) {
            return array();
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
        if (!is_null($this->getChannelItem())) {
            return $this->getAmazonAccount()->isMagentoOrdersListingsStoreCustom()
                ? $this->getAmazonAccount()->getMagentoOrdersListingsStoreId()
                : $this->getChannelItem()->getStoreId();
        }
        // ---------------------------------------

        return $this->getAmazonAccount()->getMagentoOrdersListingsOtherStoreId();
    }

    //########################################

    /**
     * @return int|mixed
     * @throws \Ess\M2ePro\Model\Exception
     */
    public function getAssociatedProductId()
    {
        $this->validate();

        // Item was listed by M2E
        // ---------------------------------------
        if (!is_null($this->getChannelItem())) {
            return $this->getChannelItem()->getProductId();
        }
        // ---------------------------------------

        // 3rd party Item
        // ---------------------------------------
        $sku = $this->getSku();
        if ($sku != '' && strlen($sku) <=\Ess\M2ePro\Helper\Magento\Product::SKU_MAX_LENGTH) {
            $product = $this->productFactory->create()
                ->setStoreId($this->getAmazonOrder()->getAssociatedStoreId())
                ->getCollection()
                    ->addAttributeToSelect('sku')
                    ->addAttributeToFilter('sku', $sku)
                    ->getFirstItem();

            if ($product->getId()) {
                $this->_eventManager->dispatch('ess_associate_amazon_order_item_to_product', array(
                    'product'    => $product,
                    'order_item' => $this->getParentObject(),
                ));

                return $product->getId();
            }
        }
        // ---------------------------------------

        $product = $this->createProduct();

        $this->_eventManager->dispatch('ess_associate_amazon_order_item_to_product', array(
            'product'    => $product,
            'order_item' => $this->getParentObject(),
        ));

        return $product->getId();
    }

    /**
     * @throws \Ess\M2ePro\Model\Exception
     */
    private function validate()
    {
        $channelItem = $this->getChannelItem();

        if (!is_null($channelItem) && !$this->getAmazonAccount()->isMagentoOrdersListingsModeEnabled()) {
            throw new \Ess\M2ePro\Model\Exception(
                'Magento Order Creation for Items Listed by M2E Pro is disabled in Account Settings.'
            );
        }

        if (is_null($channelItem) && !$this->getAmazonAccount()->isMagentoOrdersListingsOtherModeEnabled()) {
            throw new \Ess\M2ePro\Model\Exception(
                'Magento Order Creation for Items Listed by 3rd party software is disabled in Account Settings.'
            );
        }
    }

    /**
     * @return \Magento\Catalog\Model\Product
     * @throws \Ess\M2ePro\Model\Exception
     */
    private function createProduct()
    {
        if (!$this->getAmazonAccount()->isMagentoOrdersListingsOtherProductImportEnabled()) {
            throw new \Ess\M2ePro\Model\Exception('Product Import is disabled in Amazon Account Settings.');
        }

        $storeId = $this->getAmazonAccount()->getMagentoOrdersListingsOtherStoreId();
        if ($storeId == 0) {
            $storeId = $this->getHelper('Magento\Store')->getDefaultStoreId();
        }

        $sku = $this->getSku();
        if (strlen($sku) > \Ess\M2ePro\Helper\Magento\Product::SKU_MAX_LENGTH) {
            $hashLength = 10;
            $savedSkuLength = \Ess\M2ePro\Helper\Magento\Product::SKU_MAX_LENGTH - $hashLength - 1;
            $hash = $this->getHelper('Data')->generateUniqueHash($sku, $hashLength);

            $isSaveStart = (bool)$this->getHelper('Module')->getConfig()->getGroupValue(
                '/order/magento/settings/', 'save_start_of_long_sku_for_new_product'
            );

            if ($isSaveStart) {
                $sku = substr($sku, 0, $savedSkuLength).'-'.$hash;
            } else {
                $sku = $hash.'-'.substr($sku, strlen($sku) - $savedSkuLength, $savedSkuLength);
            }
        }

        $productData = array(
            'title'             => $this->getTitle(),
            'sku'               => $sku,
            'description'       => '',
            'short_description' => '',
            'qty'               => $this->getQtyForNewProduct(),
            'price'             => $this->getPrice(),
            'store_id'          => $storeId,
            'tax_class_id'      => $this->getAmazonAccount()->getMagentoOrdersListingsOtherProductTaxClassId()
        );

        // Create product in magento
        // ---------------------------------------
        /** @var $productBuilder \Ess\M2ePro\Model\Magento\Product\Builder */
        $productBuilder = $this->productBuilderFactory->create()->setData($productData);
        $productBuilder->buildProduct();
        // ---------------------------------------

        $this->getParentObject()->getOrder()->addSuccessLog(
            'Product for Amazon Item "%title%" was Created in Magento Catalog.', array('!title' => $this->getTitle())
        );

        return $productBuilder->getProduct();
    }

    private function getQtyForNewProduct()
    {
        $otherListing = $this->parentFactory->getObject(\Ess\M2ePro\Helper\Component\Amazon::NICK, 'Listing\Other')
            ->getCollection()
            ->addFieldToFilter('account_id', $this->getParentObject()->getOrder()->getAccountId())
            ->addFieldToFilter('marketplace_id', $this->getParentObject()->getOrder()->getMarketplaceId())
            ->addFieldToFilter('sku', $this->getSku())
            ->getFirstItem();

        if ((int)$otherListing->getOnlineQty() > $this->getQtyPurchased()) {
            return $otherListing->getOnlineQty();
        }

        return $this->getQtyPurchased();
    }

    //########################################
}