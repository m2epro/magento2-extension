<?php

declare(strict_types=1);

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Wizard\Category\Add;

use Ess\M2ePro\Block\Adminhtml\Listing\Category\Tree as CategoryTree;

class Tree extends CategoryTree
{
    protected array $selectedIds = [];
    protected string $gridId;
    protected \Magento\Framework\Data\Tree\Node $currentNode;
    protected \Magento\Framework\App\ResourceConnection $resourceConnection;
    protected \Magento\Catalog\Model\ResourceModel\Category\TreeFactory $categoryTreeFactory;
    private \Ess\M2ePro\Helper\Module\Database\Structure $databaseHelper;
    private \Ess\M2ePro\Model\ResourceModel\Listing\Product $listingProductResource;
    private \Ess\M2ePro\Model\Listing\Ui\RuntimeStorage $uiListingRuntimeStorage;

    public function __construct(
        \Ess\M2ePro\Model\Listing\Ui\RuntimeStorage $uiListingRuntimeStorage,
        \Ess\M2ePro\Model\ResourceModel\Listing\Product $listingProductResource,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Magento\Catalog\Model\ResourceModel\Category\TreeFactory $categoryTreeFactory,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $blockContext,
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Catalog\Model\ResourceModel\Category\Tree $categoryTree,
        \Magento\Framework\Registry $registry,
        \Magento\Catalog\Model\CategoryFactory $categoryFactory,
        \Ess\M2ePro\Helper\Module\Database\Structure $databaseHelper,
        array $data = []
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->categoryTreeFactory = $categoryTreeFactory;
        $this->databaseHelper = $databaseHelper;
        $this->listingProductResource = $listingProductResource;
        $this->uiListingRuntimeStorage = $uiListingRuntimeStorage;

        parent::__construct(
            $blockContext,
            $context,
            $categoryTree,
            $registry,
            $categoryFactory,
            $data,
        );
    }

    // Fix. _categoryTree is same object for every use, Tree always has data from first usage.
    public function getRoot($parentNodeCategory = null, $recursionLevel = 3)
    {
        $this->_categoryTree = $this->categoryTreeFactory->create();

        return parent::getRoot($parentNodeCategory, $recursionLevel);
    }

    //########################################

    public function setSelectedIds(array $ids): self
    {
        $this->selectedIds = $ids;

        return $this;
    }

    public function getSelectedIds(): array
    {
        return $this->selectedIds;
    }

    public function setCurrentNodeById($categoryId): self
    {
        $category = $this->_categoryFactory->create()->load($categoryId);
        $node = $this->getRoot($category, 1)->getTree()->getNodeById($categoryId);

        return $this->setCurrentNode($node);
    }

    public function setCurrentNode(\Magento\Framework\Data\Tree\Node $currentNode)
    {
        $this->currentNode = $currentNode;

        return $this;
    }

    public function getCurrentNode()
    {
        return $this->currentNode;
    }

    public function getCurrentNodeId()
    {
        return $this->currentNode ? $this->currentNode->getId() : null;
    }

    //########################################

    public function setGridId($gridId)
    {
        $this->gridId = $gridId;

        return $this;
    }

    public function getGridId()
    {
        return $this->gridId;
    }

    //########################################

    public function getLoadTreeUrl()
    {
        return $this->getUrl('*/*/getCategoriesJson', ['_current' => true]);
    }

    //########################################

    public function getCategoryCollection()
    {
        $collection = $this->getData('category_collection');

        if (!$collection) {
            $collection = $this->_categoryFactory->create()
                                                 ->getCollection()
                                                 ->addAttributeToSelect('name')
                                                 ->addAttributeToSelect('is_active');

            $this->loadProductsCount($collection);

            $this->setData('category_collection', $collection);
        }

        return $collection;
    }

    //########################################

    public function _construct()
    {
        parent::_construct();

        // Initialization block
        // ---------------------------------------
        $this->setId('listingCategoryTree');
        // ---------------------------------------

        $this->setTemplate('listing/wizard/category/tree.phtml');

        $this->_isAjax = $this->getRequest()->isXmlHttpRequest();
    }

    public function getTreeJson($parentNodeCategory = null): string
    {
        $rootArray = $this->_getNodeJson($this->getRoot($parentNodeCategory));

        return json_encode($rootArray['children'] ?? []);
    }

    public function _getNodeJson($node, $level = 0)
    {
        // create a node from data array
        if (is_array($node)) {
            $node = new \Magento\Framework\Data\Tree\Node($node, 'entity_id', new \Magento\Framework\Data\Tree());
        }

        $node->loadChildren();

        $item = [];
        $item['text'] = $this->buildNodeName($node);
        $item['id'] = $node->getId();
        $item['cls'] = 'folder ' . ($node->getIsActive() ? 'active-category' : 'no-active-category');
        $item['path'] = $node->getData('path');
        $item['allowDrop'] = false;
        $item['allowDrag'] = false;

        $isParent = $this->_isParentSelectedCategory($node);

        if ((int)$node->getChildrenCount() > 0) {
            $item['children'] = [];
        }

        if ($node->hasChildren()) {
            $item['children'] = [];

            if (!($this->getUseAjax() && $node->getLevel() > 1 && !$isParent)) {
                foreach ($node->getChildren() as $child) {
                    $item['children'][] = $this->_getNodeJson($child, $level + 1);
                }
            }
        }

        if ($isParent || $node->getLevel() < 2) {
            $item['expanded'] = true;
        }

        return $item;
    }

    protected function _isParentSelectedCategory($node)
    {
        if ($node && $this->getCurrentNode()) {
            $pathIds = explode('/', $this->getCurrentNode()->getData('path'));
            if (in_array($node->getId(), $pathIds)) {
                return true;
            }
        }

        return false;
    }

    public function buildNodeName($node): string
    {
        return sprintf(
            '%s (<span category_id="%s">0</span> %s %s)',
            $this->_escaper->escapeHtml($node->getName()),
            $node->getId(),
            __('of'),
            $node->getProductCount()
        );
    }

    public function getAffectedCategoriesCount()
    {
        if ($this->getData('affected_categories_count') !== null) {
            return $this->getData('affected_categories_count');
        }

        $collection = $this->_categoryFactory->create()->getCollection();

        $dbSelect = $collection->getConnection()->select()
                               ->from(
                                   $this->databaseHelper
                                       ->getTableNameWithPrefix('catalog_category_product'),
                                   'category_id',
                               )
                               ->where('`product_id` IN(?)', $this->getSelectedIds());

        $affectedCategoriesCount = $collection->getSelectCountSql()
                                              ->where('entity_id IN (' . $dbSelect->__toString() . ')')
                                              ->query()
                                              ->fetchColumn();

        $this->setData('affected_categories_count', (int)$affectedCategoriesCount);

        return $this->getData('affected_categories_count');
    }

    public function getProductsForEachCategory()
    {
        if ($this->getData('products_for_each_category') !== null) {
            return $this->getData('products_for_each_category');
        }

        $ids = array_map('intval', $this->selectedIds);
        $ids = implode(',', $ids);
        !$ids && $ids = 0;

        $collection = $this->_categoryFactory->create()->getCollection();
        $select = $collection->getSelect();
        $select->joinLeft(
            [
                'ccp' => $this->databaseHelper
                    ->getTableNameWithPrefix('catalog_category_product'),
            ],
            "e.entity_id = ccp.category_id AND ccp.product_id IN ({$ids})",
            ['product_id'],
        );

        $productsForEachCategory = [];
        foreach ($select->query() as $row) {
            if (!isset($productsForEachCategory[$row['entity_id']])) {
                $productsForEachCategory[$row['entity_id']] = [];
            }
            $row['product_id'] && $productsForEachCategory[$row['entity_id']][] = $row['product_id'];
        }

        $this->setData('products_for_each_category', $productsForEachCategory);

        return $this->getData('products_for_each_category');
    }

    public function getProductsCountForEachCategory()
    {
        if ($this->getData('products_count_for_each_category') !== null) {
            return $this->getData('products_count_for_each_category');
        }

        $productsCountForEachCategory = $this->getProductsForEachCategory();
        $productsCountForEachCategory = array_map('count', $productsCountForEachCategory);

        $this->setData('products_count_for_each_category', $productsCountForEachCategory);

        return $this->getData('products_count_for_each_category');
    }

    public function getInfoJson()
    {
        return json_encode([
            'category_products' => $this->getProductsCountForEachCategory(),
            'total_products_count' => count($this->getSelectedIds()),
            'total_categories_count' => $this->getAffectedCategoriesCount(),
        ]);
    }

    protected function loadProductsCount($collection)
    {
        $items = $collection->getItems();

        if (!$items) {
            return;
        }

        $listing = $this->uiListingRuntimeStorage->getListing();

        // ---------------------------------------
        $excludeProductsSelect = $collection->getConnection()->select()->from(
            $this->listingProductResource->getMainTable(),
            new \Zend_Db_Expr('DISTINCT `product_id`'),
        );

        $excludeProductsSelect->where('`listing_id` = ?', (int)$listing['id']);

        $select = $collection->getConnection()->select();
        $select->from(
            [
                'main_table' => $this->databaseHelper
                    ->getTableNameWithPrefix('catalog_category_product'),
            ],
            ['category_id', new \Zend_Db_Expr('COUNT(main_table.product_id)')],
        )
               ->where($collection->getConnection()->quoteInto('main_table.category_id IN(?)', array_keys($items)))
               ->where('main_table.product_id NOT IN (' . $excludeProductsSelect . ')')
               ->group('main_table.category_id');

        $counts = $collection->getConnection()->fetchPairs($select);

        foreach ($items as $item) {
            if (isset($counts[$item->getId()])) {
                $item->setProductCount($counts[$item->getId()]);
            } else {
                $item->setProductCount(0);
            }
        }
    }
}
