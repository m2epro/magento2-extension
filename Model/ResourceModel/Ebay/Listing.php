<?php

namespace Ess\M2ePro\Model\ResourceModel\Ebay;

use Magento\Framework\DB\Select;

class Listing extends \Ess\M2ePro\Model\ResourceModel\ActiveRecord\Component\Child\AbstractModel
{
    public const COLUMN_LISTING_ID = 'listing_id';
    public const COLUMN_TEMPLATE_SYNCHRONIZATION_ID = 'template_synchronization_id';
    public const COLUMN_ADD_PRODUCT_MODE = 'add_product_mode';
    public const COLUMN_ADDITIONAL_DATA = 'additional_data';

    /** @var \Magento\Catalog\Model\Product\Action  */
    protected $catalogProductAction;
    /** @var \Magento\Catalog\Model\ProductFactory  */
    protected $productFactory;
    /** @var bool  */
    protected $_isPkAutoIncrement = false;

    public function _construct()
    {
        $this->_init(
            \Ess\M2ePro\Helper\Module\Database\Tables::TABLE_EBAY_LISTING,
            self::COLUMN_LISTING_ID
        );
    }

    public function __construct(
        \Magento\Catalog\Model\Product\Action $catalogProductAction,
        \Magento\Catalog\Model\ProductFactory $productFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Factory $parentFactory,
        \Magento\Framework\Model\ResourceModel\Db\Context $context,
        $connectionName = null
    ) {
        $this->catalogProductAction = $catalogProductAction;
        $this->productFactory = $productFactory;
        parent::__construct($helperFactory, $activeRecordFactory, $parentFactory, $context, $connectionName);
    }

    public function getProductCollection($listingId)
    {
        $collection = $this->productFactory->create()->getCollection();

        $lpTable = $this->activeRecordFactory->getObject('Listing\Product')->getResource()->getMainTable();
        $elpTable = $this->activeRecordFactory->getObject('Ebay_Listing_Product')->getResource()->getMainTable();

        $collection->joinTable(
            ['lp' => $lpTable],
            'product_id=entity_id',
            ['id' => 'id'],
            '{{table}}.listing_id=' . (int)$listingId
        );

        $collection->joinTable(
            ['elp' => $elpTable],
            'listing_product_id=id',
            ['listing_product_id' => 'listing_product_id']
        );

        return $collection;
    }

    public function updateMotorsAttributesData(
        $listingId,
        array $listingProductIds,
        $attribute,
        $data,
        $overwrite = false
    ) {
        if (count($listingProductIds) == 0) {
            return;
        }

        $listing = $this->parentFactory->getCachedObjectLoaded(
            \Ess\M2ePro\Helper\Component\Ebay::NICK,
            'Listing',
            $listingId
        );
        $storeId = (int)$listing->getStoreId();

        $listingProductsCollection = $this->activeRecordFactory->getObject('Listing\Product')->getCollection();
        $listingProductsCollection->addFieldToFilter('id', ['in' => $listingProductIds]);
        $listingProductsCollection->getSelect()->reset(\Magento\Framework\DB\Select::COLUMNS);
        $listingProductsCollection->getSelect()->columns(['product_id']);

        $productIds = $listingProductsCollection->getColumnValues('product_id');

        if ($overwrite) {
            $this->catalogProductAction->updateAttributes(
                $productIds,
                [$attribute => $data],
                $storeId
            );

            return;
        }

        $productCollection = $this->productFactory->create()->getCollection();
        $productCollection->setStoreId($storeId);
        $productCollection->addFieldToFilter('entity_id', ['in' => $productIds]);
        $productCollection->addAttributeToSelect($attribute);

        foreach ($productCollection->getItems() as $itemId => $item) {
            $currentAttributeValue = $item->getData($attribute);
            $newAttributeValue = $data;

            if (!empty($currentAttributeValue)) {
                $newAttributeValue = $currentAttributeValue . ',' . $data;
            }

            $this->catalogProductAction->updateAttributes(
                [$itemId],
                [$attribute => $newAttributeValue],
                $storeId
            );
        }
    }

    public function getUsedProductsIds($listingId)
    {
        $collection = $this->activeRecordFactory->getObject('Listing\Product')->getCollection();
        $collection->addFieldToFilter('listing_id', $listingId);

        $collection->getSelect()->reset(Select::COLUMNS);
        $collection->getSelect()->columns(['product_id']);

        $collection->getSelect()->joinLeft(
            [
                'lpv' => $this->activeRecordFactory->getObject('Listing_Product_Variation')
                                                   ->getResource()->getMainTable(),
            ],
            'lpv.listing_product_id = main_table.id',
            []
        );
        $collection->getSelect()->joinLeft(
            [
                'lpvo' => $this->activeRecordFactory->getObject('Listing_Product_Variation_Option')
                                                    ->getResource()->getMainTable(),
            ],
            'lpvo.listing_product_variation_id = lpv.id',
            ['variation_product_id' => 'product_id']
        );

        $products = [];

        foreach ($collection->getItems() as $item) {
            $productId = $item->getData('product_id');
            $variationProductId = $item->getData('variation_product_id');

            $products[$productId] = $productId;
            !empty($variationProductId) && $products[$variationProductId] = $variationProductId;
        }

        return array_values($products);
    }
}
