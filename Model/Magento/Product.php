<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Magento;

use Ess\M2ePro\Model\Magento\Product\Image;
use \Magento\Catalog\Model\Product\Attribute\Source\Status;

class Product extends \Ess\M2ePro\Model\AbstractModel
{
    const TYPE_SIMPLE_ORIGIN       = 'simple';
    const TYPE_CONFIGURABLE_ORIGIN = 'configurable';
    const TYPE_BUNDLE_ORIGIN       = 'bundle';
    const TYPE_GROUPED_ORIGIN      = 'grouped';
    const TYPE_DOWNLOADABLE_ORIGIN = 'downloadable';
    const TYPE_VIRTUAL_ORIGIN      = 'virtual';

    const BUNDLE_PRICE_TYPE_DYNAMIC = 0;
    const BUNDLE_PRICE_TYPE_FIXED   = 1;

    const THUMBNAIL_IMAGE_CACHE_TIME = 604800;

    const TAX_CLASS_ID_NONE = 0;

    const FORCING_QTY_TYPE_MANAGE_STOCK_NO = 1;
    const FORCING_QTY_TYPE_BACKORDERS = 2;

    /**
     *  $statistics = array(
     *      'id' => array(
     *         'store_id' => array(
     *              'product_id' => array(
     *                  'qty' => array(
     *                      '1' => $qty,
     *                      '2' => $qty,
     *                  ),
     *              ),
     *              ...
     *          ),
     *          ...
     *      ),
     *      ...
     *  )
     */

    public static $statistics = [];

    protected $driverPool;
    protected $resourceModel;
    protected $productFactory;
    protected $websiteFactory;
    protected $productType;
    protected $configurableFactory;
    protected $stockRegistry;
    protected $productStatus;
    protected $catalogInventoryConfiguration;
    protected $storeFactory;
    protected $filesystem;
    protected $objectManager;
    protected $activeRecordFactory;
    protected $magentoProductCollectionFactory;

    protected $statisticId;

    protected $_productId = 0;

    protected $_storeId = \Magento\Store\Model\Store::DEFAULT_STORE_ID;

    /**
     * @var \Magento\Catalog\Model\Product
     */
    protected $_productModel = NULL;

    /** @var \Ess\M2ePro\Model\Magento\Product\Variation */
    protected $_variationInstance = NULL;

    // applied only for standard variations type
    protected $variationVirtualAttributes = [];

    protected $isIgnoreVariationVirtualAttributes = false;

    // applied only for standard variations type
    protected $variationFilterAttributes = [];

    protected $isIgnoreVariationFilterAttributes = false;

    public $notFoundAttributes = [];

    //########################################

    public function __construct(
        \Magento\Framework\Filesystem\DriverPool $driverPool,
        \Magento\Framework\App\ResourceConnection $resourceModel,
        \Magento\Catalog\Model\ProductFactory $productFactory,
        \Magento\Store\Model\WebsiteFactory $websiteFactory,
        \Magento\Catalog\Model\Product\Type $productType,
        \Ess\M2ePro\Model\Magento\Product\Type\ConfigurableFactory $configurableFactory,
        \Magento\CatalogInventory\Api\StockRegistryInterface $stockRegistry,
        \Ess\M2ePro\Model\Magento\Product\Status $productStatus,
        \Magento\CatalogInventory\Model\Configuration $catalogInventoryConfiguration,
        \Magento\Store\Model\StoreFactory $storeFactory,
        \Magento\Framework\Filesystem $filesystem,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Model\ResourceModel\Magento\Product\CollectionFactory $magentoProductCollectionFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory
    )
    {
        $this->driverPool = $driverPool;
        $this->resourceModel = $resourceModel;
        $this->productFactory = $productFactory;
        $this->websiteFactory = $websiteFactory;
        $this->productType = $productType;
        $this->configurableFactory = $configurableFactory;
        $this->stockRegistry = $stockRegistry;
        $this->productStatus = $productStatus;
        $this->catalogInventoryConfiguration = $catalogInventoryConfiguration;
        $this->storeFactory = $storeFactory;
        $this->filesystem = $filesystem;
        $this->objectManager = $objectManager;
        $this->activeRecordFactory = $activeRecordFactory;
        $this->magentoProductCollectionFactory = $magentoProductCollectionFactory;
        parent::__construct($helperFactory, $modelFactory);
    }

    //########################################

    /**
     * @return bool
     */
    public function exists()
    {
        if (is_null($this->_productId)) {
            return false;
        }

        $table = $this->resourceModel->getTableName('catalog_product_entity');
        $dbSelect = $this->resourceModel->getConnection()
             ->select()
             ->from($table, new \Zend_Db_Expr('COUNT(*)'))
             ->where('`entity_id` = ?', (int)$this->_productId);

        $count = $this->resourceModel->getConnection()->fetchOne($dbSelect);
        return $count == 1;
    }

    /**
     * @param int|null $productId
     * @param int|null $storeId
     * @throws \Ess\M2ePro\Model\Exception
     * @return \Ess\M2ePro\Model\Magento\Product | \Ess\M2ePro\Model\Magento\Product\Cache
     */
    public function loadProduct($productId = NULL, $storeId = NULL)
    {
        $productId = (is_null($productId)) ? $this->_productId : $productId;
        $storeId = (is_null($storeId)) ? $this->_storeId : $storeId;

        if ($productId <= 0) {
            throw new \Ess\M2ePro\Model\Exception('The Product ID is not set.');
        }

        $this->_productModel = $this->productFactory->create()->setStoreId($storeId);
        $this->_productModel->load($productId, 'entity_id');

        $this->setProductId($productId);
        $this->setStoreId($storeId);

        return $this;
    }

    //########################################

    /**
     * @param int $productId
     * @return \Ess\M2ePro\Model\Magento\Product
     */
    public function setProductId($productId)
    {
        $this->_productId = $productId;
        return $this;
    }

    /**
     * @return int
     */
    public function getProductId()
    {
        return $this->_productId;
    }

    // ---------------------------------------

    /**
     * @param int $storeId
     * @return \Ess\M2ePro\Model\Magento\Product
     */
    public function setStoreId($storeId)
    {
        $this->_storeId = $storeId;
        return $this;
    }

    /**
     * @return int
     */
    public function getStoreId()
    {
        return $this->_storeId;
    }

    //########################################

    /**
     * @return array
     */
    public function getStoreIds()
    {
        $storeIds = array();
        foreach ($this->getWebsiteIds() as $websiteId) {
            try {
                $websiteStores = $this->websiteFactory->create()->load($websiteId)->getStoreIds();
                $storeIds = array_merge($storeIds, $websiteStores);
            } catch (\Exception $e) {
                continue;
            }
        }
        return $storeIds;
    }

    /**
     * @return array
     */
    public function getWebsiteIds()
    {
        $select = $this->resourceModel->getConnection()
            ->select()
            ->from($this->resourceModel->getTableName('catalog_product_website'), 'website_id')
            ->where('product_id = ?', (int)$this->getProductId());

        $websiteIds = $this->resourceModel->getConnection()->fetchCol($select);
        return $websiteIds ? $websiteIds : [];
    }

    //########################################

    /**
     * @throws \Ess\M2ePro\Model\Exception
     * @return \Magento\Catalog\Model\Product
     */
    public function getProduct()
    {
        if ($this->_productModel) {
            return $this->_productModel;
        }

        if ($this->_productId > 0) {
            $this->loadProduct();
            return $this->_productModel;
        }

        throw new \Ess\M2ePro\Model\Exception('Load instance first');
    }

    /**
     * @param \Magento\Catalog\Model\Product $productModel
     * @return \Ess\M2ePro\Model\Magento\Product
     */
    public function setProduct(\Magento\Catalog\Model\Product $productModel)
    {
        $this->_productModel = $productModel;

        $this->setProductId($this->_productModel->getId());
        $this->setStoreId($this->_productModel->getStoreId());

        return $this;
    }

    // ---------------------------------------

    /**
     * @return \Magento\Catalog\Model\Product\Type\AbstractType
     * @throws \Ess\M2ePro\Model\Exception
     */
    public function getTypeInstance()
    {
        if (is_null($this->_productModel) && $this->_productId < 0) {
            throw new \Ess\M2ePro\Model\Exception('Load instance first');
        }

        /** @var \Magento\Catalog\Model\Product\Type\AbstractType $typeInstance */
        if ($this->isConfigurableType() && !$this->getProduct()->getData('overridden_type_instance_injected')) {

            $config = $this->productType->getTypes();

            $typeInstance = $this->configurableFactory->create();
            $typeInstance->setConfig($config['configurable']);

            $this->getProduct()->setTypeInstance($typeInstance);
            $this->getProduct()->setData('overridden_type_instance_injected', true);

        } else {
            $typeInstance = $this->getProduct()->getTypeInstance();
        }

        $typeInstance->setStoreFilter($this->getStoreId(), $this->getProduct());

        return $typeInstance;
    }

    /**
     * @return \Magento\CatalogInventory\Model\Stock\Item
     * @throws \Ess\M2ePro\Model\Exception
     */
    public function getStockItem()
    {
        if (is_null($this->_productModel) && $this->_productId < 0) {
            throw new \Ess\M2ePro\Model\Exception('Load instance first');
        }

        return $this->stockRegistry->getStockItem(
            $this->getProduct()->getId(),
            $this->getProduct()->getStore()->getWebsiteId()
        );
    }

    //########################################

    /**
     * @return array
     */
    public function getVariationVirtualAttributes()
    {
        return $this->variationVirtualAttributes;
    }

    /**
     * @param array $attributes
     * @return $this
     */
    public function setVariationVirtualAttributes(array $attributes)
    {
        $this->variationVirtualAttributes = $attributes;
        return $this;
    }

    /**
     * @return bool
     */
    public function isIgnoreVariationVirtualAttributes()
    {
        return $this->isIgnoreVariationVirtualAttributes;
    }

    /**
     * @param bool $isIgnore
     * @return $this
     */
    public function setIgnoreVariationVirtualAttributes($isIgnore = true)
    {
        $this->isIgnoreVariationVirtualAttributes = $isIgnore;
        return $this;
    }

    // ---------------------------------------

    /**
     * @return array
     */
    public function getVariationFilterAttributes()
    {
        return $this->variationFilterAttributes;
    }

    /**
     * @param array $attributes
     * @return $this
     */
    public function setVariationFilterAttributes(array $attributes)
    {
        $this->variationFilterAttributes = $attributes;
        return $this;
    }

    /**
     * @return bool
     */
    public function isIgnoreVariationFilterAttributes()
    {
        return $this->isIgnoreVariationFilterAttributes;
    }

    /**
     * @param bool $isIgnore
     * @return $this
     */
    public function setIgnoreVariationFilterAttributes($isIgnore = true)
    {
        $this->isIgnoreVariationFilterAttributes = $isIgnore;
        return $this;
    }

    //########################################

    private function getTypeIdByProductId($productId)
    {
        $tempKey = 'product_id_' . (int)$productId . '_type';

        if (!is_null($typeId = $this->helperFactory->getObject('Data\GlobalData')->getValue($tempKey))) {
            return $typeId;
        }

        $resource = $this->resourceModel;

        $typeId = $resource->getConnection()
             ->select()
             ->from($resource->getTableName('catalog_product_entity'), ['type_id'])
             ->where('`entity_id` = ?',(int)$productId)
             ->query()
             ->fetchColumn();

        $this->helperFactory->getObject('Data\GlobalData')->setValue($tempKey, $typeId);
        return $typeId;
    }

    public function getNameByProductId($productId, $storeId = \Magento\Store\Model\Store::DEFAULT_STORE_ID)
    {
        $nameCacheKey = 'product_id_' . (int)$productId . '_' . (int)$storeId . '_name';

        if (!is_null($name = $this->helperFactory->getObject('Data\GlobalData')->getValue($nameCacheKey))) {
            return $name;
        }

        $resource = $this->resourceModel;

        $cacheHelper = $this->helperFactory->getObject('Data\Cache\Permanent');

        if (($attributeId = $cacheHelper->getValue(__METHOD__)) === NULL) {

            $attributeId = $resource->getConnection('core_read')
                ->select()
                ->from($resource->getTableName('eav_attribute'), array('attribute_id'))
                ->where('attribute_code = ?', 'name')
                ->where('entity_type_id = ?', $this->productFactory
                                                   ->create()->getResource()->getTypeId())
                ->query()
                ->fetchColumn();

            $cacheHelper->setValue(__METHOD__, $attributeId);
        }

        $storeIds = [(int)$storeId, \Magento\Store\Model\Store::DEFAULT_STORE_ID];
        $storeIds = array_unique($storeIds);

        /* @var $collection \Ess\M2ePro\Model\ResourceModel\Magento\Product\Collection */
        $collection = $this->magentoProductCollectionFactory->create();
        $collection->addFieldToFilter('entity_id', (int)$productId);
        $collection->joinTable(
            ['cpev' => $resource->getTableName('catalog_product_entity_varchar')],
            'entity_id = entity_id',
            ['value' => 'value']
        );
        $queryStmt = $collection->getSelect()
            ->reset(\Zend_Db_Select::COLUMNS)
            ->columns(['value' => 'cpev.value'])
            ->where('cpev.store_id IN (?)', $storeIds)
            ->where('cpev.attribute_id = ?', (int)$attributeId)
            ->order('cpev.store_id DESC')
            ->query();

        $nameValue = '';
        while ($tempValue = $queryStmt->fetchColumn()) {

            if (!empty($tempValue)) {
                $nameValue = $tempValue;
                break;
            }
        }

        $this->helperFactory->getObject('Data\GlobalData')->setValue($nameCacheKey, (string)$nameValue);
        return (string)$nameValue;
    }

    private function getSkuByProductId($productId)
    {
        $tempKey = 'product_id_' . (int)$productId . '_name';

        if (!is_null($sku = $this->helperFactory->getObject('Data\GlobalData')->getValue($tempKey))) {
            return $sku;
        }

        $resource = $this->resourceModel;

        $sku = $resource->getConnection('core_read')
             ->select()
             ->from($resource->getTableName('catalog_product_entity'), ['sku'])
             ->where('`entity_id` = ?',(int)$productId)
             ->query()
             ->fetchColumn();

        $this->helperFactory->getObject('Data\GlobalData')->setValue($tempKey, $sku);
        return $sku;
    }

    //########################################

    public function getTypeId()
    {
        $typeId = NULL;
        if (!$this->_productModel && $this->_productId > 0) {
            $typeId = $this->getTypeIdByProductId($this->_productId);
        } else {
            $typeId = $this->getProduct()->getTypeId();
        }

        return $typeId;
    }

    // ---------------------------------------

    /**
     * @return bool
     */
    public function isSimpleType()
    {
        return $this->getHelper('Magento\Product')->isSimpleType($this->getTypeId());
    }

    /**
     * @return bool
     * @throws \Ess\M2ePro\Model\Exception
     */
    public function isSimpleTypeWithCustomOptions()
    {
        if (!$this->isSimpleType()) {
            return false;
        }

        foreach ($this->getProduct()->getOptions() as $option) {
            if ((int)$option->getData('is_require') &&
                in_array($option->getData('type'), array('drop_down', 'radio', 'multiple', 'checkbox'))) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return bool
     */
    public function isSimpleTypeWithoutCustomOptions()
    {
        if (!$this->isSimpleType()) {
            return false;
        }

        return !$this->isSimpleTypeWithCustomOptions();
    }

    // ---------------------------------------

    /**
     * @return bool
     */
    public function isDownloadableType()
    {
        return $this->getHelper('Magento\Product')->isDownloadableType($this->getTypeId());
    }

    /**
     * @return bool
     * @throws \Ess\M2ePro\Model\Exception
     */
    public function isDownloadableTypeWithSeparatedLinks()
    {
        if (!$this->isDownloadableType()) {
            return false;
        }

        return (bool)$this->getProduct()->getData('links_purchased_separately');
    }

    /**
     * @return bool
     * @throws \Ess\M2ePro\Model\Exception
     */
    public function isDownloadableTypeWithoutSeparatedLinks()
    {
        if (!$this->isDownloadableType()) {
            return false;
        }

        return !$this->isDownloadableTypeWithSeparatedLinks();
    }

    // ---------------------------------------

    /**
     * @return bool
     */
    public function isConfigurableType()
    {
        return $this->getHelper('Magento\Product')->isConfigurableType($this->getTypeId());
    }

    /**
     * @return bool
     */
    public function isBundleType()
    {
        return $this->getHelper('Magento\Product')->isBundleType($this->getTypeId());
    }

    /**
     * @return bool
     */
    public function isGroupedType()
    {
        return $this->getHelper('Magento\Product')->isGroupedType($this->getTypeId());
    }

    // ---------------------------------------

    /**
     * @return bool
     */
    public function isSimpleTypeOrigin()
    {
        return $this->getTypeId() == self::TYPE_SIMPLE_ORIGIN;
    }

    /**
     * @return bool
     */
    public function isConfigurableTypeOrigin()
    {
        return $this->getTypeId() == self::TYPE_CONFIGURABLE_ORIGIN;
    }

    /**
     * @return bool
     */
    public function isBundleTypeOrigin()
    {
        return $this->getTypeId() == self::TYPE_BUNDLE_ORIGIN;
    }

    /**
     * @return bool
     */
    public function isGroupedTypeOrigin()
    {
        return $this->getTypeId() == self::TYPE_GROUPED_ORIGIN;
    }

    /**
     * @return bool
     */
    public function isDownloadableTypeOrigin()
    {
        return $this->getTypeId() == self::TYPE_DOWNLOADABLE_ORIGIN;
    }

    /**
     * @return bool
     */
    public function isVirtualTypeOrigin()
    {
        return $this->getTypeId() == self::TYPE_VIRTUAL_ORIGIN;
    }

    //########################################

    /**
     * @return int
     * @throws \Ess\M2ePro\Model\Exception
     */
    public function getBundlePriceType()
    {
        return (int)$this->getProduct()->getPriceType();
    }

    // ---------------------------------------

    /**
     * @return bool
     */
    public function isBundlePriceTypeDynamic()
    {
        return $this->getBundlePriceType() == self::BUNDLE_PRICE_TYPE_DYNAMIC;
    }

    /**
     * @return bool
     */
    public function isBundlePriceTypeFixed()
    {
        return $this->getBundlePriceType() == self::BUNDLE_PRICE_TYPE_FIXED;
    }

    //########################################

    /**
     * @return bool
     */
    public function isProductWithVariations()
    {
        return !$this->isProductWithoutVariations();
    }

    /**
     * @return bool
     */
    public function isProductWithoutVariations()
    {
        return $this->isSimpleTypeWithoutCustomOptions() || $this->isDownloadableTypeWithoutSeparatedLinks();
    }

    /**
     * @return bool
     */
    public function isStrictVariationProduct()
    {
        return $this->isConfigurableType() || $this->isBundleType() || $this->isGroupedType();
    }

    //########################################

    public function getSku()
    {
        if (!$this->_productModel && $this->_productId > 0) {
            $temp = $this->getSkuByProductId($this->_productId);
            if (!is_null($temp) && $temp != '') {
                return $temp;
            }
        }
        return $this->getProduct()->getSku();
    }

    public function getName()
    {
        if (!$this->_productModel && $this->_productId > 0) {
            return $this->getNameByProductId($this->_productId, $this->_storeId);
        }
        return $this->getProduct()->getName();
    }

    // ---------------------------------------

    /**
     * @return bool
     * @throws \Ess\M2ePro\Model\Exception
     */
    public function isStatusEnabled()
    {
        if (!$this->_productModel && $this->_productId > 0) {

            $status = $this->productStatus->getProductStatus($this->_productId, $this->_storeId);

            if (is_array($status) && isset($status[$this->_productId])) {

                $status = (int)$status[$this->_productId];
                if ($status == Status::STATUS_DISABLED ||
                    $status == Status::STATUS_ENABLED) {
                    return $status == Status::STATUS_ENABLED;
                }
            }
        }

        return (int)$this->getProduct()->getStatus() == Status::STATUS_ENABLED;
    }

    /**
     * @return bool
     * @throws \Ess\M2ePro\Model\Exception
     */
    public function isStockAvailability()
    {
        return $this->calculateStockAvailability(
            $this->getStockItem()->getData('is_in_stock'),
            $this->getStockItem()->getData('manage_stock'),
            $this->getStockItem()->getData('use_config_manage_stock')
        );
    }

    private function calculateStockAvailability($isInStock, $manageStock, $useConfigManageStock)
    {
        return $this->getHelper('Magento\Product')->calculateStockAvailability(
            $isInStock,
            $manageStock,
            $useConfigManageStock
        );
    }

    //########################################

    public function getPrice()
    {
        // for bundle with dynamic price and grouped always returns 0
        // for configurable product always returns 0
        return (float)$this->getProduct()->getPrice();
    }

    public function setPrice($value)
    {
        // there is no any sense to set price for bundle
        // with dynamic price or grouped
        return $this->getProduct()->setPrice($value);
    }

    // ---------------------------------------

    public function getSpecialPrice()
    {
        if (!$this->isSpecialPriceActual()) {
            return NULL;
        }

        // for grouped always returns 0
        $specialPriceValue = (float)$this->getProduct()->getSpecialPrice();

        if ($this->isBundleType()) {

            if ($this->isBundlePriceTypeDynamic()) {
                // there is no reason to calculate it
                // because product price is not defined at all
                $specialPriceValue = 0;
            } else {
                $specialPriceValue = round((($this->getPrice() * $specialPriceValue) / 100), 2);
            }
        }

        return (float)$specialPriceValue;
    }

    public function setSpecialPrice($value)
    {
        // there is no any sense to set price for grouped
        // it sets percent instead of price value for bundle
        return $this->getProduct()->setSpecialPrice($value);
    }

    // ---------------------------------------

    /**
     * @return bool
     * @throws \Ess\M2ePro\Model\Exception
     */
    public function isSpecialPriceActual()
    {
        $fromDate = strtotime($this->getSpecialPriceFromDate());
        $toDate = strtotime($this->getSpecialPriceToDate());
        $currentTimeStamp = $this->helperFactory->getObject('Data')->getCurrentGmtDate(true);

        return $currentTimeStamp >= $fromDate && $currentTimeStamp < $toDate &&
               (float)$this->getProduct()->getSpecialPrice() > 0;
    }

    // ---------------------------------------

    public function getSpecialPriceFromDate()
    {
        $fromDate = $this->getProduct()->getSpecialFromDate();

        if (is_null($fromDate) || $fromDate === false || $fromDate == '') {
            $currentDateTime = $this->helperFactory->getObject('Data')->getCurrentGmtDate();
            $fromDate = $this->helperFactory->getObject('Data')->getDate($currentDateTime,false,'Y-01-01 00:00:00');
        } else {
            $fromDate = $this->helperFactory->getObject('Data')->getDate($fromDate,false,'Y-m-d 00:00:00');
        }

        return $fromDate;
    }

    public function getSpecialPriceToDate()
    {
        $toDate = $this->getProduct()->getSpecialToDate();

        if (is_null($toDate) || $toDate === false || $toDate == '') {

            $currentDateTime = $this->helperFactory->getObject('Data')->getCurrentGmtDate();

            $toDate = new \DateTime($currentDateTime, new \DateTimeZone('UTC'));
            $toDate->modify('+1 year');
            $toDate = $this->helperFactory->getObject('Data')->getDate($toDate->format('U'),false,'Y-01-01 00:00:00');

        } else {

            $toDate = $this->helperFactory->getObject('Data')->getDate($toDate,false,'Y-m-d 00:00:00');

            $toDate = new \DateTime($toDate, new \DateTimeZone('UTC'));
            $toDate->modify('+1 day');
            $toDate = $this->helperFactory->getObject('Data')->getDate($toDate->format('U'),false,'Y-m-d 00:00:00');
        }

        return $toDate;
    }

    // ---------------------------------------

    /**
     * @param null $websiteId
     * @param null $customerGroupId
     * @return array
     */
    public function getTierPrice($websiteId = NULL, $customerGroupId = NULL)
    {
        $attribute = $this->getProduct()->getResource()->getAttribute('tier_price');
        $attribute->getBackend()->afterLoad($this->getProduct());

        $prices = $this->getProduct()->getData('tier_price');
        if (empty($prices)) {
            return array();
        }

        $resultPrices = array();

        foreach ($prices as $priceValue) {
            if (!is_null($websiteId) && !empty($priceValue['website_id']) && $websiteId != $priceValue['website_id']) {
                continue;
            }

            if (!is_null($customerGroupId) &&
                $priceValue['cust_group'] != \Magento\Customer\Model\Group::CUST_GROUP_ALL &&
                $customerGroupId != $priceValue['cust_group']
            ) {
                continue;
            }

            $resultPrices[(int)$priceValue['price_qty']] = $priceValue['website_price'];
        }

        return $resultPrices;
    }

    //########################################

    public function getQty($lifeMode = false)
    {
        if ($lifeMode && (!$this->isStatusEnabled() || !$this->isStockAvailability())) {
            return 0;
        }

        if ($this->isStrictVariationProduct()) {

            if ($this->isBundleType()) {
                return $this->getBundleQty($lifeMode);
            }
            if ($this->isGroupedType()) {
                return $this->getGroupedQty($lifeMode);
            }
            if ($this->isConfigurableType()) {
                return $this->getConfigurableQty($lifeMode);
            }
        }

        return $this->calculateQty(
            $this->getStockItem()->getQty(),
            $this->getStockItem()->getData('manage_stock'),
            $this->getStockItem()->getUseConfigManageStock(),
            $this->getStockItem()->getData('backorders'),
            $this->getStockItem()->getUseConfigBackorders()
        );
    }

    public function setQty($value)
    {
        $this->getStockItem()->setQty($value)->save();
    }

    // ---------------------------------------

    protected function calculateQty($qty,
                                    $manageStock, $useConfigManageStock,
                                    $backorders, $useConfigBackorders)
    {
        $forceQtyMode = (int)$this->activeRecordFactory->getObject('Config\Module')->getGroupValue(
            '/product/force_qty/','mode'
        );

        if ($forceQtyMode == 0) {
            return $qty;
        }

        $forceQtyValue = (int)$this->activeRecordFactory->getObject('Config\Module')->getGroupValue(
            '/product/force_qty/','value'
        );

        $manageStockGlobal = $this->catalogInventoryConfiguration->getManageStock();
        if (($useConfigManageStock && !$manageStockGlobal) || (!$useConfigManageStock && !$manageStock)) {
            self::$statistics[$this->getStatisticId()]
                             [$this->getProductId()]
                             [$this->getStoreId()]
                             ['qty']
                             [self::FORCING_QTY_TYPE_MANAGE_STOCK_NO] = $forceQtyValue;
            return $forceQtyValue;
        }

        $backOrdersGlobal = $this->catalogInventoryConfiguration->getBackorders();
        if (($useConfigBackorders && $backOrdersGlobal != \Magento\CatalogInventory\Model\Stock::BACKORDERS_NO) ||
           (!$useConfigBackorders && $backorders != \Magento\CatalogInventory\Model\Stock::BACKORDERS_NO)) {
            if ($forceQtyValue > $qty) {
                self::$statistics[$this->getStatisticId()]
                                 [$this->getProductId()]
                                 [$this->getStoreId()]
                                 ['qty']
                                 [self::FORCING_QTY_TYPE_BACKORDERS] = $forceQtyValue;
                return $forceQtyValue;
            }
        }

        return $qty;
    }

    // ---------------------------------------

    protected function getConfigurableQty($lifeMode = false)
    {
        $totalQty = 0;

        foreach ($this->getTypeInstance()->getUsedProducts($this->getProduct()) as $childProduct) {

            $stockItem = $this->stockRegistry->getStockItem(
                $childProduct->getId(),
                $childProduct->getStore()->getWebsiteId()
            );

            $isInStock = $this->calculateStockAvailability(
                $stockItem->getData('is_in_stock'),
                $stockItem->getData('manage_stock'),
                $stockItem->getData('use_config_manage_stock')
            );

            $qty = $this->calculateQty(
                $stockItem->getQty(),
                $stockItem->getData('manage_stock'),
                $stockItem->getUseConfigManageStock(),
                $stockItem->getData('backorders'),
                $stockItem->getUseConfigBackorders()
            );

            if ($lifeMode &&
                (!$isInStock || $childProduct->getStatus() != Status::STATUS_ENABLED)) {
                continue;
            }

            $totalQty += $qty;
        }

        return $totalQty;
    }

    protected function getGroupedQty($lifeMode = false)
    {
        $totalQty = 0;

        foreach ($this->getTypeInstance()->getAssociatedProducts($this->getProduct()) as $childProduct) {

            $stockItem = $this->stockRegistry->getStockItem(
                $childProduct->getId(),
                $childProduct->getStore()->getWebsiteId()
            );

            $isInStock = $this->calculateStockAvailability(
                $stockItem->getData('is_in_stock'),
                $stockItem->getData('manage_stock'),
                $stockItem->getData('use_config_manage_stock')
            );

            $qty = $this->calculateQty(
                $stockItem->getQty(),
                $stockItem->getData('manage_stock'),
                $stockItem->getUseConfigManageStock(),
                $stockItem->getData('backorders'),
                $stockItem->getUseConfigBackorders()
            );

            if ($lifeMode &&
                (!$isInStock || $childProduct->getStatus() != Status::STATUS_ENABLED)) {
                continue;
            }

            $totalQty += $qty;
        }

        return $totalQty;
    }

    protected function getBundleQty($lifeMode = false)
    {
        $product = $this->getProduct();

        // Prepare bundle options format usable for search
        $productInstance = $this->getTypeInstance();

        $optionCollection = $productInstance->getOptionsCollection($product);
        $optionsData = $optionCollection->getData();

        foreach ($optionsData as $singleOption) {
            // Save QTY, before calculate = 0
            $bundleOptionsArray[$singleOption['option_id']] = 0;
        }

        $selectionsCollection = $productInstance->getSelectionsCollection($optionCollection->getAllIds(), $product);
        $_items = $selectionsCollection->getItems();

        $bundleOptionsQtyArray = array();
        foreach ($_items as $_item) {

            if (!isset($bundleOptionsArray[$_item->getOptionId()])) {
                continue;
            }

            $stockItem = $this->stockRegistry->getStockItem($_item->getId());

            $isInStock = $this->calculateStockAvailability(
                $stockItem->getData('is_in_stock'),
                $stockItem->getData('manage_stock'),
                $stockItem->getData('use_config_manage_stock')
            );

            $qty = $this->calculateQty(
                $stockItem->getData('qty'),
                $stockItem->getData('manage_stock'),
                $stockItem->getData('use_config_manage_stock'),
                $stockItem->getData('backorders'),
                $stockItem->getData('use_config_backorders')
            );

            if ($lifeMode &&
                (!$isInStock || $_item->getStatus() != Status::STATUS_ENABLED)) {
                continue;
            }

            // Only positive
            // grouping qty by product id
            $bundleOptionsQtyArray[$_item->getProductId()][$_item->getOptionId()] = $qty;
        }

        foreach ($bundleOptionsQtyArray as $optionQty) {
            foreach ($optionQty as $optionId => $val) {
                $bundleOptionsArray[$optionId] += floor($val/count($optionQty));
            }
        }

        // Get min of qty product for all options
        $minQty = -1;
        foreach ($bundleOptionsArray as $singleBundle) {
            if ($singleBundle < $minQty || $minQty == -1) {
                $minQty = $singleBundle;
            }
        }

        return $minQty;
    }

    // ---------------------------------------

    public function setStatisticId($id)
    {
        $this->statisticId = $id;
        return $this;
    }

    public function getStatisticId()
    {
        return $this->statisticId;
    }

    //########################################

    public function getAttributeFrontendInput($attributeCode)
    {
        $productObject = $this->getProduct();

        /** @var $attribute \Magento\Eav\Model\Entity\Attribute\AbstractAttribute */
        $attribute = $productObject->getResource()->getAttribute($attributeCode);

        if (!$attribute) {
            $this->addNotFoundAttributes($attributeCode);
            return '';
        }

        if (!$productObject->hasData($attributeCode)) {
            $this->addNotFoundAttributes($attributeCode);
            return '';
        }

        return $attribute->getFrontendInput();
    }

    public function getAttributeValue($attributeCode)
    {
        $productObject = $this->getProduct();

        /** @var $attribute \Magento\Eav\Model\Entity\Attribute\AbstractAttribute */
        $attribute = $productObject->getResource()->getAttribute($attributeCode);

        if (!$attribute) {
            $this->addNotFoundAttributes($attributeCode);
            return '';
        }

        if (!$productObject->hasData($attributeCode)) {
            $this->addNotFoundAttributes($attributeCode);
            return '';
        }

        $value = $productObject->getData($attributeCode);

        if ($attributeCode == 'media_gallery') {
            $links = array();
            foreach ($this->getGalleryImages(100) as $image) {

                if (!$image->getUrl()) {
                    continue;
                }
                $links[] = $image->getUrl();
            }
            return implode(',', $links);
        }

        if (is_null($value) || is_bool($value) || is_array($value) || $value === '') {
            return '';
        }

        // SELECT and MULTISELECT
        if ($attribute->getFrontendInput() === 'select' || $attribute->getFrontendInput() === 'multiselect') {

            if ($attribute->getSource() instanceof \Magento\Eav\Model\Entity\Attribute\Source\SourceInterface &&
                $attribute->getSource()->getAllOptions()) {

                $attribute->setStoreId($this->getStoreId());

                $value = $attribute->getSource()->getOptionText($value);
                $value = is_array($value) ? implode(',', $value) : (string)$value;
            }

        // DATE
        } else if ($attribute->getFrontendInput() == 'date') {
            $temp = explode(' ',$value);
            isset($temp[0]) && $value = (string)$temp[0];

        // YES NO
        }  else if ($attribute->getFrontendInput() == 'boolean') {
            (bool)$value ? $value = $this->helperFactory->getObject('Module\Translation')->__('Yes') :
                           $value = $this->helperFactory->getObject('Module\Translation')->__('No');

        // PRICE
        }  else if ($attribute->getFrontendInput() == 'price') {
            $value = (string)number_format($value, 2, '.', '');

        // MEDIA IMAGE
        }  else if ($attribute->getFrontendInput() == 'media_image') {
            if ($value == 'no_selection') {
                $value = '';
            } else {
                if (!preg_match('((mailto\:|(news|(ht|f)tp(s?))\://){1}\S+)',$value)) {
                    $value = $this->storeFactory->create()
                                  ->load($this->getStoreId())
                                  ->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA, false)
                                  . 'catalog/product/'.ltrim($value,'/');
                }
            }
        }

        if ($value instanceof \Magento\Framework\Phrase) {
            $value = $value->render();
        }

        return is_string($value) ? $value : '';
    }

    public function setAttributeValue($attributeCode, $value)
    {
        // supports only string values
        if (is_string($value)) {
            $productObject = $this->getProduct();

            $productObject->setData($attributeCode, $value)
                ->getResource()
                ->saveAttribute($productObject, $attributeCode);
        }

        return $this;
    }

    //########################################

    public function getThumbnailImage()
    {
        $resource = $this->resourceModel;

        $cacheHelper = $this->helperFactory->getObject('Data\Cache\Permanent');

        if (($attributeId = $cacheHelper->getValue(__METHOD__)) === NULL) {

            $attributeId = $resource->getConnection()
                   ->select()
                   ->from($resource->getTableName('eav_attribute'), ['attribute_id'])
                   ->where('attribute_code = ?', 'thumbnail')
                   ->where('entity_type_id = ?', $this->productFactory
                                                      ->create()->getResource()->getTypeId())
                   ->query()
                   ->fetchColumn();

            $cacheHelper->setValue(__METHOD__, $attributeId);
        }

        $storeIds = [(int)$this->getStoreId(), \Magento\Store\Model\Store::DEFAULT_STORE_ID];
        $storeIds = array_unique($storeIds);

        /* @var $collection \Ess\M2ePro\Model\ResourceModel\Magento\Product\Collection */
        $collection = $this->magentoProductCollectionFactory->create();
        $collection->addFieldToFilter('entity_id', (int)$this->getProductId());
        $collection->joinTable(
            ['cpev' => $resource->getTableName('catalog_product_entity_varchar')],
            'entity_id = entity_id',
            ['value' => 'value']
        );
        $queryStmt = $collection->getSelect()
            ->reset(\Zend_Db_Select::COLUMNS)
            ->columns(['value' => 'cpev.value'])
            ->where('cpev.store_id IN (?)', $storeIds)
            ->where('cpev.attribute_id = ?', (int)$attributeId)
            ->order('cpev.store_id DESC')
            ->query();

        $thumbnailTempPath = null;
        while ($tempPath = $queryStmt->fetchColumn()) {

            if ($tempPath != '' && $tempPath != 'no_selection' && $tempPath != '/') {
                $thumbnailTempPath = $tempPath;
                break;
            }
        }

        if (is_null($thumbnailTempPath)) {
            return NULL;
        }

        $thumbnailTempPath = $this->filesystem->getDirectoryRead(\Magento\Framework\App\Filesystem\DirectoryList::MEDIA)
                ->getAbsolutePath().DIRECTORY_SEPARATOR.'catalog/product'.$thumbnailTempPath;

        $thumbnailTempPath = $this->prepareImageUrl($thumbnailTempPath);

        /** @var Image $image */
        $image = $this->modelFactory->getObject('Magento\Product\Image');
        $image->setUrl($thumbnailTempPath);
        $image->setStoreId($this->getStoreId());

        if (!$image->isSelfHosted()) {
            return null;
        }

        $width  = 100;
        $height = 100;

        $fileDriver = $this->driverPool->getDriver(\Magento\Framework\Filesystem\DriverPool::FILE);
        $prefixResizedImage = "resized-{$width}px-{$height}px-";
        $imagePathResized = dirname($image->getPath())
            .DIRECTORY_SEPARATOR
            .$prefixResizedImage
            .basename($image->getPath());

        if ($fileDriver->isFile($imagePathResized)) {

            $currentTime = $this->helperFactory->getObject('Data')->getCurrentGmtDate(true);

            if (filemtime($imagePathResized) + self::THUMBNAIL_IMAGE_CACHE_TIME > $currentTime) {

                $image->setPath($imagePathResized)
                    ->setUrl($image->getUrlByPath())
                    ->resetHash();

                return $image;
            }

            $fileDriver->deleteFile($imagePathResized);
        }

        try {

            $imageObj = $this->objectManager->create('\Magento\Framework\Image', [
                'fileName' => $image->getPath()
            ]);
            $imageObj->constrainOnly(TRUE);
            $imageObj->keepAspectRatio(TRUE);
            $imageObj->keepFrame(FALSE);
            $imageObj->resize($width, $height);
            $imageObj->save($imagePathResized);

        } catch (\Exception $exception) {
            return null;
        }

        if (!$fileDriver->isFile($imagePathResized)) {
            return null;
        }

        $image->setPath($imagePathResized)
            ->setUrl($image->getUrlByPath())
            ->resetHash();

        return $image;
    }

    /**
     * @param string $attribute
     * @return Image|null
     */
    public function getImage($attribute = 'image')
    {
        if (empty($attribute)) {
            return null;
        }

        $imageUrl = $this->getAttributeValue($attribute);
        $imageUrl = $this->prepareImageUrl($imageUrl);

        if (empty($imageUrl)) {
            return null;
        }

        /** @var Image $image */
        $image = $this->modelFactory->getObject('Magento\Product\Image');
        $image->setUrl($imageUrl);
        $image->setStoreId($this->getStoreId());

        return $image;
    }

    /**
     * @param int $limitImages
     * @return Image[]
     */
    public function getGalleryImages($limitImages = 0)
    {
        $limitImages = (int)$limitImages;

        if ($limitImages <= 0) {
            return array();
        }

        $galleryImages = $this->getProduct()->getData('media_gallery');

        if (!isset($galleryImages['images']) || !is_array($galleryImages['images'])) {
            return array();
        }

        $i = 0;
        $images = array();

        foreach ($galleryImages['images'] as $galleryImage) {

            if ($i >= $limitImages) {
                break;
            }

            if (isset($galleryImage['disabled']) && (bool)$galleryImage['disabled']) {
                continue;
            }

            if (!isset($galleryImage['file'])) {
                continue;
            }

            $imageUrl = $this->storeFactory->create()
                             ->load($this->getStoreId())
                             ->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA, false);
            $imageUrl .= 'catalog/product/'.ltrim($galleryImage['file'],'/');
            $imageUrl = $this->prepareImageUrl($imageUrl);

            if (empty($imageUrl)) {
                continue;
            }

            /** @var Image $image */
            $image = $this->modelFactory->getObject('Magento\Product\Image');
            $image->setUrl($imageUrl);
            $image->setStoreId($this->getStoreId());

            $images[] = $image;
            $i++;
        }

        return $images;
    }

    /**
     * @param int $position
     * @return Image|null
     */
    public function getGalleryImageByPosition($position = 1)
    {
        $position = (int)$position;

        if ($position <= 0) {
            return null;
        }

        // need for correct sampling of the array
        $position--;

        $galleryImages = $this->getProduct()->getData('media_gallery');

        if (!isset($galleryImages['images']) || !is_array($galleryImages['images'])) {
            return null;
        }

        $galleryImages = array_values($galleryImages['images']);

        if (!isset($galleryImages[$position])) {
            return null;
        }

        $galleryImage = $galleryImages[$position];

        if (isset($galleryImage['disabled']) && (bool)$galleryImage['disabled']) {
            return null;
        }

        if (!isset($galleryImage['file'])) {
            return null;
        }

        $imagePath = 'catalog/product/' . ltrim($galleryImage['file'], '/');
        $imageUrl  = $this->storeFactory->create()
                ->load($this->getStoreId())
                ->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA, false) . $imagePath;

        $imageUrl = $this->prepareImageUrl($imageUrl);

        /** @var Image $image */
        $image = $this->modelFactory->getObject('Magento\Product\Image');
        $image->setUrl($imageUrl);
        $image->setStoreId($this->getStoreId());

        return $image;
    }

    private function prepareImageUrl($url)
    {
        if (!is_string($url) || $url == '') {
            return '';
        }

        return str_replace(' ', '%20', $url);
    }

    //########################################

    public function getVariationInstance()
    {
        if (is_null($this->_variationInstance)) {
            $this->_variationInstance = $this->modelFactory
                                             ->getObject('Magento\Product\Variation')
                                             ->setMagentoProduct($this);
        }

        return $this->_variationInstance;
    }

    //########################################

    private function addNotFoundAttributes($attributeCode)
    {
        $this->notFoundAttributes[] = $attributeCode;
        $this->notFoundAttributes = array_unique($this->notFoundAttributes);
    }

    // ---------------------------------------

    /**
     * @return array
     */
    public function getNotFoundAttributes()
    {
        return $this->notFoundAttributes;
    }

    public function clearNotFoundAttributes()
    {
        $this->notFoundAttributes = [];
    }

    //########################################
}