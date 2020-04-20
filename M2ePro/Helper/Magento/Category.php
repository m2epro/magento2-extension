<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Helper\Magento;

/**
 * Class \Ess\M2ePro\Helper\Magento\Category
 */
class Category extends \Ess\M2ePro\Helper\Magento\AbstractHelper
{
    protected $categoryFactory;
    protected $resourceConnection;
    protected $storeFactory;
    protected $storeManager;

    //########################################

    public function __construct(
        \Magento\Catalog\Model\CategoryFactory $categoryFactory,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Magento\Store\Model\StoreFactory $storeFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Magento\Framework\App\Helper\Context $context
    ) {
        $this->categoryFactory = $categoryFactory;
        $this->resourceConnection = $resourceConnection;
        $this->storeFactory = $storeFactory;
        $this->storeManager = $storeManager;
        parent::__construct($objectManager, $helperFactory, $context);
    }

    //########################################

    public function getCategoriesByProduct($product, $storeId = 0, $returnType = self::RETURN_TYPE_IDS)
    {
        $productId = $this->_getIdFromInput($product);
        if ($productId === false) {
            return [];
        }

        return $this->getAllCategoriesByProducts([$productId], $storeId, $returnType);
    }

    public function getAllCategoriesByProducts(array $products, $storeId = 0, $returnType = self::RETURN_TYPE_IDS)
    {
        $productIds = $this->_getIdsFromInput($products, 'product_id');
        if (empty($productIds)) {
            return [];
        }

        $connection = $this->resourceConnection->getConnection();
        $categoryProductTableName = $this->getHelper('Module_Database_Structure')
            ->getTableNameWithPrefix('catalog_category_product');

        $dbSelect = $connection->select()
            ->from(['ccp' => $categoryProductTableName], 'category_id')
            ->where('ccp.product_id IN ('.implode(',', $productIds).')')
            ->group('ccp.category_id');

        if ($storeId > 0) {
            $storeModel = $this->storeFactory->create()->load($storeId);
            if ($storeModel !== null) {
                $websiteId = $storeModel->getWebsiteId();
                $productWebsiteTableName = $this->getHelper('Module_Database_Structure')
                    ->getTableNameWithPrefix('catalog_product_website');
                $dbSelect->joinLeft(['cpw' => $productWebsiteTableName], 'ccp.product_id = cpw.product_id')
                         ->where('cpw.website_id = ?', (int)$websiteId);
            }
        }

        $result = $connection->query($dbSelect);
        $result->setFetchMode(\Zend_Db::FETCH_NUM);
        $fetchArray = $result->fetchAll();

        return $this->_convertFetchNumArrayToReturnType(
            $fetchArray,
            $returnType,
            \Magento\Catalog\Model\Category::class
        );
    }

    // ---------------------------------------

    public function getGeneralProductsFromCategories(
        array $categories,
        $storeId = 0,
        $returnType = self::RETURN_TYPE_IDS
    ) {
        $categoryIds = $this->_getIdsFromInput($categories, 'category_id');
        if (empty($categoryIds)) {
            return [];
        }

        return $this->_getProductsFromCategoryIds($categoryIds, $storeId, $returnType, true);
    }

    public function getProductsFromCategories(array $categories, $storeId = 0, $returnType = self::RETURN_TYPE_IDS)
    {
        $categoryIds = $this->_getIdsFromInput($categories, 'category_id');
        if (empty($categoryIds)) {
            return [];
        }

        return $this->_getProductsFromCategoryIds($categoryIds, $storeId, $returnType);
    }

    // ---------------------------------------

    public function getUncategorizedProducts($storeId = 0, $returnType = self::RETURN_TYPE_IDS)
    {
        $connection = $this->resourceConnection->getConnection();
        $productTableName = $this->getHelper('Module_Database_Structure')
            ->getTableNameWithPrefix('catalog_product_entity');
        $categoryProductTableName = $this->getHelper('Module_Database_Structure')
            ->getTableNameWithPrefix('catalog_category_product');

        $dbSelect = $connection->select()
            ->from(['cp' => $productTableName], 'entity_id')
            ->joinLeft(['ccp' => $categoryProductTableName], 'cp.entity_id = ccp.product_id')
            ->where('ccp.category_id IS NULL');

        if ($storeId > 0) {
            $storeModel = $this->storeFactory->create()->load($storeId);
            if ($storeModel !== null) {
                $websiteId = $storeModel->getWebsiteId();
                $productWebsiteTableName = $this->getHelper('Module_Database_Structure')
                    ->getTableNameWithPrefix('catalog_product_website');
                $dbSelect->joinLeft(['cpw' => $productWebsiteTableName], 'cp.entity_id = cpw.product_id')
                         ->where('cpw.website_id = ?', (int)$websiteId);
            }
        }

        $result = $connection->query($dbSelect);
        $result->setFetchMode(\Zend_Db::FETCH_NUM);
        $fetchArray = $result->fetchAll();

        return $this->_convertFetchNumArrayToReturnType(
            $fetchArray,
            $returnType,
            \Magento\Catalog\Model\Product::class
        );
    }

    public function isProductUncategorized($product, $storeId = 0)
    {
        $productId = $this->_getIdFromInput($product);
        if ($productId === false) {
            return [];
        }

        $connection = $this->resourceConnection->getConnection();
        $categoryProductTableName = $this->getHelper('Module_Database_Structure')
            ->getTableNameWithPrefix('catalog_category_product');

        $dbSelect = $connection->select()
            ->from(['ccp' => $categoryProductTableName], 'product_id')
            ->where('ccp.product_id = ?', $productId);

        if ($storeId > 0) {
            $storeModel = $this->storeFactory->create()->load($storeId);
            if ($storeModel !== null) {
                $websiteId = $storeModel->getWebsiteId();
                $productWebsiteTableName = $this->getHelper('Module_Database_Structure')
                    ->getTableNameWithPrefix('catalog_product_website');
                $dbSelect->joinLeft(['cpw' => $productWebsiteTableName], 'ccp.product_id = cpw.product_id')
                         ->where('cpw.website_id = ?', (int)$websiteId);
            }
        }

        if ($connection->fetchOne($dbSelect) === false) {
            return true;
        }

        return false;
    }

    public function getLimitedCategoriesByProducts($productIds, $storeId = 0)
    {
        $connection = $this->resourceConnection->getConnection();
        $tableName = $this->getHelper('Module_Database_Structure')->getTableNameWithPrefix('catalog_category_product');

        $dbSelect = $connection->select()
            ->from(['ccp' => $tableName])
            ->where('ccp.product_id IN ('.implode(',', $productIds).')');

        if ($storeId > 0) {
            $storeModel = $this->storeFactory->create()->load($storeId);
            if ($storeModel !== null) {
                $websiteId = $storeModel->getWebsiteId();
                $productWebsiteTableName = $this->getHelper('Module_Database_Structure')
                    ->getTableNameWithPrefix('catalog_product_website');
                $dbSelect->joinLeft(['cpw' => $productWebsiteTableName], 'ccp.product_id = cpw.product_id')
                    ->where('cpw.website_id = ?', (int)$websiteId);
            }
        }

        $fetchResult = $connection->fetchAll($dbSelect);

        $categories = [];
        $productsCount = [];
        foreach ($fetchResult as $row) {
            if (!isset($categories[$row['category_id']])) {
                $productsCount[$row['category_id']] = 1;
                $categories[$row['category_id']] = [$row['product_id'] => false];
                continue;
            }

            $productsCount[$row['category_id']]++;
            $categories[$row['category_id']][$row['product_id']] = false;
        }

        arsort($productsCount);

        $resultCategories = [];
        foreach ($productIds as $productId) {
            foreach ($productsCount as $categoryId => $count) {
                if (!isset($categories[$categoryId][$productId])) {
                    continue;
                }

                $resultCategories[] = $categoryId;
                break;
            }
        }

        return array_values(array_unique($resultCategories));
    }

    //########################################

    protected function _getProductsFromCategoryIds(array $categoryIds, $storeId, $returnType, $onlyGeneral = false)
    {
        if (empty($categoryIds)) {
            return [];
        }

        $connection = $this->resourceConnection->getConnection();
        $tableName = $this->getHelper('Module_Database_Structure')->getTableNameWithPrefix('catalog_category_product');

        $dbSelect = $connection->select()
            ->from(['ccp' => $tableName], 'product_id')
            ->where('ccp.category_id IN ('.implode(',', $categoryIds).')')
            ->group('ccp.product_id');

        if ($onlyGeneral) {
            $dbSelect->having('count(*) = ?', count($categoryIds));
        }

        if ($storeId > 0) {
            $storeModel = $this->storeFactory->create()->load($storeId);
            if ($storeModel !== null) {
                $websiteId = $storeModel->getWebsiteId();
                $productWebsiteTableName = $this->getHelper('Module_Database_Structure')
                    ->getTableNameWithPrefix('catalog_product_website');
                $dbSelect->joinLeft(['cpw' => $productWebsiteTableName], 'ccp.product_id = cpw.product_id')
                    ->where('cpw.website_id = ?', (int)$websiteId);
            }
        }

        $result = $connection->query($dbSelect);
        $result->setFetchMode(\Zend_Db::FETCH_NUM);
        $fetchArray = $result->fetchAll();

        return $this->_convertFetchNumArrayToReturnType(
            $fetchArray,
            $returnType,
            \Magento\Catalog\Model\Product::class
        );
    }

    //########################################

    public function getPath($categoryId)
    {
        $category = $this->categoryFactory->create();
        $category->load($categoryId);

        if (!$category->getId()) {
            return [];
        }

        $categoryPath = [];

        $pathIds = $category->getPathIds();
        array_shift($pathIds);
        $categories = $category->getCollection()
            ->setStore($this->storeManager->getStore())
            ->addAttributeToSelect('name')
            ->addAttributeToSelect('url_key')
            ->addFieldToFilter('entity_id', ['in' => $pathIds])
            ->load()
            ->getItems();

        foreach ($pathIds as $categoryId) {
            if (!isset($categories[$categoryId]) || !$categories[$categoryId]->getName()) {
                continue;
            }

            $categoryPath[] = $categories[$categoryId]->getName();
        }

        return $categoryPath;
    }

    //########################################
}
